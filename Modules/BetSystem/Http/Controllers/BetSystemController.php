<?php

namespace Modules\BetSystem\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\BetSystem\Entities\BettingGroup;
use Modules\BetSystem\Entities\BettingGroupTeam;
use Modules\SwfteaMission\Jobs\SeasonPoint;
use Modules\UserSystem\Entities\User;

class BetSystemController extends Controller {
  public function all() {
    $bets = BettingGroup::with(['teams.details'])->where('resolved','=', false)->orderBy('end_time','DESC')->get();
    return $bets;
  }
  public function group($group_id) {
    $group = BettingGroup::with(['teams.details','winner'])->where('id','=', $group_id)->first();
    $group->append('total_bets');
    $group->append('my_bets');
    $group->append('bets_participants');
    $group->append('end_time_left');
    return $group;
  }

  public function bidnow($group_id, Request $request) {
    $group = BettingGroup::where('id','=',$group_id)->first();
    if($group) {
      $validator = Validator::make($request->all(), [
        'team_id' => 'required',
        'amount' => 'required|numeric|min:10',
      ]);
      if($validator->fails()) {
        return [
          "error" => true,
          "message" => $validator->errors()->first()
        ];
      }
      $team = BettingGroupTeam::where('id', '=', $request->team_id)->first();
      $teams = [];
      foreach ($group->teams()->get() as $t) {
        $teams[] = $t->id;
      }
      if($team && in_array($team->id, $teams)) {
        $user = User::where('id','=', auth()->user()->id)->first();
        $amount = $request->amount;
        if($user->credit < $amount) {
          return [
            "error" => true,
            "message" => "Insufficient fund.",
          ];
        }
        if($amount < 10) {
          return [
            "error" => true,
            "message" => "Bid amount cannot be less than 10 credits.",
          ];
        }
        if($amount > $group->max_amount) {
          return [
            "error" => true,
            "message" => "The betting amount cannot be more than ".$group->max_amount." credits.",
          ];
        }
        $old_balance = $user->credit;
        $new_balance = $old_balance - $amount;
        if($new_balance < 500.00) {
          return [
            "error" => true,
            "message" => "You must leave 500.00 credits in your account.",
          ];
        }
        dispatch(
          new SeasonPoint(
            'add points',
            $user->id,
            'Mainbet',
            'do_bet',
            1)
        )->onQueue('low');
        #all good?
        $user->histories()->create([
          'type' => 'Place bid',
          'creditor' => 'transfer',
          'creditor_id' => 0,
          'message' => "Bet on ".$team->details->name." for game ".$group->title." using ".$amount." credits.",
          'old_value' => $user->credit,
          'new_value' => $user->credit - $amount,
          'user_id' => $user->id
        ]); // account history to receiver
        DB::table('users')
          ->where('id','=',$user->id)
          ->decrement('credit', $amount);

        $team->bets()->create([
          'user_id' => $user->id,
          'amount' => $amount,
        ]);
        return [
          "error" => false,
          "message" => "Your bet was successful. Thank you."
        ];


      }
    }
  }
}
