<?php

namespace Modules\Chatroom\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Artisan;
use Modules\Chatroom\Entities\Chatroom;

class ChatroomJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $action;
    public $chatroom_id;
    public $user_id;
    public function __construct($action, $chatroom_id, $user_id){
        $this->action = $action;
        $this->chatroom_id = $chatroom_id;
        $this->user_id = $user_id;
    }
    public function handle() {
        switch ($this->action) {
//          case 'member_added':
//            Artisan::call("chatmini:chatroom", [
//              'op' => 'join',
//              '--user' => $this->user_id,
//              '--id' => $this->chatroom_id,
//            ]);
//            break; // when added
          case 'member_removed':
            Artisan::call("chatmini:chatroom", [
              'op' => 'leave',
              '--user' => $this->user_id,
              '--id' => $this->chatroom_id,
            ]);
            break; // when left
        case 'clear_silence':
          $chatroom = Chatroom::find($this->chatroom_id);
          $chatroom->is_silent = false;
          $chatroom->save();
          $chatroom->messages()->create([
            'type' => "infomessage",
            'raw_text' => "Room silence is over. Now you can send messages.",
            'full_text' => "Room silence is over. Now you can send messages.",
            'formatted_text' => "Room silence is over. Now you can send messages.",
            'user_id' => $this->user_id
          ]);
          break;
        }
    }
}
