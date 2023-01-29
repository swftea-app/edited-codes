<?php

namespace Modules\Badge\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Modules\Notifications\Entities\Notification;
use Modules\UserSystem\Entities\User;

class BadgeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $action;
    public $user;
    public $key;
    public function __construct($action, $user, $key)
    {
        $this->action = $action;
        $this->user = $user;
        $this->key = $key;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
      $user = User::where('id','=',$this->user)->first();
      switch ($this->key) {
        case 'bronze_badge':
          $user->badges()->attach(4);
          $user->histories()->create([
            'type' => 'Transfer',
            'creditor' => 'system',
            'creditor_id' => 1,
            'message' => 'Bronze badge earning 10,000 credits',
            'old_value' => $user->credit,
            'new_value' => $user->credit + 10000,
            'user_id' => $user->id
          ]);
          DB::table('users')
            ->where('id','=',$user->id)
            ->increment('credit', 10000);
          $notification = new Notification();
          $notification->title = "Congratulations !!";
          $notification->description = "You have earned Bronze Badge. Verify your reward on profile. Thank you.";
          $notification->user_id = $user->id;
          $notification->avatar = getIcon('info');
          $notification->save();
          break;
        case 'silver_badge':
          $user->badges()->attach(5);
          $user->histories()->create([
            'type' => 'Transfer',
            'creditor' => 'system',
            'creditor_id' => 1,
            'message' => 'Silver badge earning 20,000 credits',
            'old_value' => $user->credit,
            'new_value' => $user->credit + 20000,
            'user_id' => $user->id
          ]);
          DB::table('users')
            ->where('id','=',$user->id)
            ->increment('credit', 20000);
          $notification = new Notification();
          $notification->title = "Congratulations !!";
          $notification->description = "You have earned Silver Badge. Verify your reward on profile. Thank you.";
          $notification->user_id = $user->id;
          $notification->avatar = getIcon('info');
          $notification->save();
          break;
        case 'golden_badge':
          $user->badges()->attach(6);
          $user->histories()->create([
            'type' => 'Transfer',
            'creditor' => 'system',
            'creditor_id' => 1,
            'message' => 'Bronze badge earning 30,000 credits',
            'old_value' => $user->credit,
            'new_value' => $user->credit + 30000,
            'user_id' => $user->id
          ]);
          DB::table('users')
            ->where('id','=',$user->id)
            ->increment('credit', 30000);
          $notification = new Notification();
          $notification->title = "Congratulations !!";
          $notification->description = "You have earned Golden Badge. Verify your reward on profile. Thank you.";
          $notification->user_id = $user->id;
          $notification->avatar = getIcon('info');
          $notification->save();
          break;
        case 'diamond_badge':
          $user->badges()->attach(7);
          $user->histories()->create([
            'type' => 'Transfer',
            'creditor' => 'system',
            'creditor_id' => 1,
            'message' => 'Bronze badge earning 50,000 credits',
            'old_value' => $user->credit,
            'new_value' => $user->credit + 50000,
            'user_id' => $user->id
          ]);
          DB::table('users')
            ->where('id','=',$user->id)
            ->increment('credit', 50000);
          $notification = new Notification();
          $notification->title = "Congratulations !!";
          $notification->description = "You have earned Diamond Badge. Verify your reward on profile. Thank you.";
          $notification->user_id = $user->id;
          $notification->avatar = getIcon('info');
          $notification->save();
          break;
      }
    }
}
