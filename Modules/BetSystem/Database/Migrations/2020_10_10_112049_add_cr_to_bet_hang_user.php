<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCrToBetHangUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      $betters = \Modules\BetSystem\Entities\BettingUser::where('group_team_id','=',92)->get();
      $rate = 1.80;
      $winners = [];
      foreach ($betters as $better) {
        $user_id = $better->user_id;
        $amount = $rate * $better->amount;
        if(array_key_exists($user_id, $winners)) {
          $winners[$user_id] += $amount;
        } else {
          $winners[$user_id] = $amount;
        }
      }
      foreach ($winners as $user_id => $amount) {
        $user = \Illuminate\Support\Facades\DB::table('users')->select(['credit','username'])->where('id','=',$user_id)->first();
        $credit = $user->credit;
        $new_amount = $credit + $amount;
        \Modules\AccountHistory\Entities\AccountHistory::create([
          'type' => 'Transfer',
          'creditor' => 'transfer',
          'creditor_id' => 0,
          'message' => "Won " . $amount . " credits by betting on DC for game DC vs RR",
          'old_value' => $credit,
          'new_value' => $new_amount,
          'user_id' => $user_id,
        ]); // account history to receiver
        \Illuminate\Support\Facades\DB::table('users')
          ->where('id', '=', $user_id)
          ->increment('credit', $amount);
      }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('', function (Blueprint $table) {

        });
    }
}
