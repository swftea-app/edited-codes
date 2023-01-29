<?php

namespace Modules\UserSystem\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Lexx\ChatMessenger\Models\Message;
use Lexx\ChatMessenger\Models\Thread;
use Modules\ChatMini\Events\InfoCommand;
use Modules\GroupChat\Events\NewMessageSent;
use Modules\Level\Jobs\LevelJob;
use Modules\UserSystem\Entities\OnlineUsers;
use Modules\UserSystem\Entities\User;

class UpdateUserPresence implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $event;
    public $user;
    public function __construct($event, $user_id){
      $this->event = $event;
      $this->user = $user_id;
    }
    public function handle() {
      switch ($this->event) {
        case 'member_added':
          Artisan::call("chatmini:user", [
            '--user' => $this->user,
            '--presence' => 'online',
            'op' => 'update'
          ]);
          $online_user = OnlineUsers::firstOrNew([
            'user_id' => $this->user,
          ]);
          $online_user->is_offline = 0;
          $online_user->save();
          break; // when added
        case 'member_removed':
          Artisan::call("chatmini:user", [
            '--user' => $this->user,
            '--presence' => 'offline',
            'op' => 'update'
          ]);
          # Get update presence
          $online_user = OnlineUsers::where('user_id','=', $this->user)->first();
          if($online_user) {
            $online_user->is_offline = 1;
            $online_user->save();
          }
          break; // when added
      }
    }
}
