<?php

namespace Modules\Chatroom\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Artisan;
use Modules\ChatMini\Events\InfoCommand;
use Modules\Chatroom\Entities\Chatroom;

class ChatroomMessage implements ShouldQueue {
  use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
  public $action;
  public $chatroom_id;
  public $user_id;
  public $event_data;
  public $additional_info;
  public function __construct($action, $chatroom_id, $user_id, $payload_data, $additional_info = []){
    $this->action = $action;
    $this->chatroom_id = $chatroom_id;
    $this->user_id = $user_id;
    $this->event_data = $payload_data;
    $this->additional_info = $additional_info;
  }
  public function handle() {
      switch ($this->action) {
        case 'send message':
          Artisan::call("chatmini:chatroom", [
            '--user' => $this->user_id,
            '--id' => $this->chatroom_id,
            '--text' => $this->event_data,
            '--extra' => json_encode($this->additional_info),
            'op' => 'message'
          ]);
          break; // when message received
        case 'send recording':
          $chatroom = Chatroom::find($this->chatroom_id);
          $chatroom->messages()->create([
            'type' => 'recordMessage',
            'raw_text' => $this->event_data,
            'full_text' => $this->event_data,
            'formatted_text' => $this->event_data,
            'user_id' => $this->user_id,
            'extra_info' => $this->additional_info
          ]);
          break; // when record message received
      }
  }
  public function failed($exception) {
    if($exception instanceof \Modules\Chatroom\Exception\RoomCommandException) {
      $error = (array) $exception->getRawMessage();
      if(array_key_exists('raw_message', $error)) {
        event(new InfoCommand($error['raw_message'], $this->user_id, "chatroom", $this->chatroom_id));
      }
    }
  }
}
