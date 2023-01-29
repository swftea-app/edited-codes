<?php

namespace Modules\Chat\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast, ShouldQueue {
    use Dispatchable, InteractsWithSockets,SerializesModels, Queueable;
    public $message;
    public $type;
    public $sender_data;
      public function __construct($message, $sender_data) {
        $this->message = $message;
        $this->sender_data = $sender_data;
        # Get message from
      $type = explode("\\", $message->messageable_type);
      switch (end($type)) {
        case "Chatroom":
          $this->type = 'chatroom';
          break;
        default:
          $this->type = 'undefined';
      } //set chatroom type
    }
    public function handle() {

    }
    public function broadcastAs() {
      return "newMessage";
    }
    public function broadcastWith() {
      return [
        'message' => getSelectedItemOnMessage($this->message),
        'sender' => $this->sender_data,
      ];
    }
    public function broadcastOn() {
      if($this->type == 'chatroom') {
        return new PrivateChannel("chatroom-".$this->message->messageable_id);
      } else {
        return [];
      }
    }
}
