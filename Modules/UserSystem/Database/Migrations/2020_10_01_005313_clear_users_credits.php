<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ClearUsersCredits extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $users = \Modules\UserSystem\Entities\User::role('User')->get();
        foreach ($users as $user) {
          $user->histories()->create([
            'type' => 'Transfer',
            'creditor' => 'transfer',
            'creditor_id' => 0,
            'message' => "Cleared all credits for not settling account. Contact administrator.",
            'old_value' => $user->credit,
            'new_value' => 0,
            'user_id' => $user->id
          ]); // account history to receiver
          \Illuminate\Support\Facades\DB::table('users')
            ->where('id','=',$user->id)
            ->decrement('credit', $user->credit);
        }
      $users = \Modules\UserSystem\Entities\User::role('Legends')->get();
      foreach ($users as $user) {
        $user->histories()->create([
          'type' => 'Transfer',
          'creditor' => 'transfer',
          'creditor_id' => 0,
          'message' => "Cleared all credits for not settling account. Contact administrator.",
          'old_value' => $user->credit,
          'new_value' => 0,
          'user_id' => $user->id
        ]); // account history to receiver
        \Illuminate\Support\Facades\DB::table('users')
          ->where('id','=',$user->id)
          ->decrement('credit', $user->credit);
      }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
