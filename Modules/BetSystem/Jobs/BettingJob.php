<?php

namespace Modules\BetSystem\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Modules\AccountHistory\Entities\AccountHistory;
use Modules\BetSystem\Entities\BettingGroup;
use Modules\Games\Entities\Leaderboard;
use Modules\Notifications\Jobs\NotificationJob;
use Modules\SwfteaMission\Jobs\SeasonPoint;

class BettingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $action;
    public $params;
    public function __construct($action, $params) {
        $this->action = $action;
        $this->params = $params;
    }
    public function handle() {
      switch ($this->action) {
        case 'select_winner':
          $bet_id = $this->params->bet_id;
          $model = BettingGroup::where('id', '=', $bet_id)->first();
          $winner = $this->params->winner;
          $winner_note = $this->params->note;
          $is_no_result = $this->params->is_no_result;
          if ($model->start_time_left >= 0) {

          } else if ($model->winner_id != 0) {

          } else if ($model->is_no_result) {

          } else {
            if ($is_no_result == 1) { // NO RESULT
              $model->is_no_result = true;
              $model->winner_id = 0;
              foreach ($model->teams()->get() as $win) {
                foreach ($win->bets()->get() as $beter) {
                  $credit = $beter->user->credit;
                  $amount = $beter->amount;
                  $new_amount = $credit + $amount;
                  $beter->user->histories()->create([
                    'type' => 'Transfer',
                    'creditor' => 'transfer',
                    'creditor_id' => 0,
                    'message' => "Won " . $amount . " credits by betting on " . $win->details->name . " for game " . $model->title,
                    'old_value' => $credit,
                    'new_value' => $new_amount,
                    'user_id' => $beter->user->id,
                  ]); // account history to receiver
                  DB::table('users')
                    ->where('id', '=', $beter->user->id)
                    ->increment('credit', $amount);
                }
              }
            } else {
              $model->is_no_result = false;
              $model->winner_id = $winner;
              $winners = [];
              $players = [];

              $bet_winners = [];
              foreach ($model->teams()->get() as $win) {
                $bets = $win->bets()->get();
                $winning_rate = $win->winning_rate;
                if ($win->id != $winner) {
                  foreach ($bets as $beter) {
                    $players[] = $beter->user->id;
                  }
                  continue;
                }
                foreach ($bets as $beter) {
                  $amount = $beter->amount * $winning_rate;


                  $user_id = $beter->user->id;
                  if(array_key_exists($user_id, $bet_winners)) {
                    $bet_winners[$user_id] += $amount;
                  } else {
                    $bet_winners[$user_id] = $amount;
                  }


                  $winners[] = $beter->user->id;
                }
              }

              foreach ($bet_winners as $user_id => $amount) {
                $user = \Illuminate\Support\Facades\DB::table('users')->select(['credit','username'])->where('id','=',$user_id)->first();
                $credit = $user->credit;
                $new_amount = $credit + $amount;
                \Modules\AccountHistory\Entities\AccountHistory::create([
                  'type' => 'Transfer',
                  'creditor' => 'transfer',
                  'creditor_id' => 0,
                  'message' => "Won " . $amount . " credits by betting on winning team for game ".$model->title,
                  'old_value' => $credit,
                  'new_value' => $new_amount,
                  'user_id' => $user_id,
                ]); // account history to receiver
                \Illuminate\Support\Facades\DB::table('users')
                  ->where('id', '=', $user_id)
                  ->increment('credit', $amount);
              }

              $winners = array_unique($winners);
              $players = array_unique($players);
              foreach ($winners as $winner) {
                dispatch(
                  new SeasonPoint(
                    'add points',
                    $winner,
                    'Mainbet',
                    'win_bet',
                    1)
                )->onQueue('low');
                if (!in_array($winner, $players) && $model->betting_category_id == 4) {
                  $user_name = DB::table('users')->select(['username'])->where('id', '=', $winner)->first();
                  Leaderboard::create([
                    'username' => $user_name->username,
                    'type' => 'ipl_contest',
                  ]);
                  Leaderboard::create([
                    'username' => $user_name->username,
                    'type' => 'ipl_contest',
                  ]);
                  Leaderboard::create([
                    'username' => $user_name->username,
                    'type' => 'ipl_contest',
                  ]);
                }
              }
            }
            $model->winner_note = $winner_note;
            $model->save();
            dispatch(new NotificationJob('admin_info_notification', (object) [
              'title' => 'Swftea Mania',
              'message' => 'The winner for '.$model->title.' is just selected.'
            ]))->delay(now()->addSeconds(rand(0, 500)))->onQueue('low');
            break;
          }
      }
    }
}
