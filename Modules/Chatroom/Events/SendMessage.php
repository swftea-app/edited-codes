<?php

namespace Modules\Chatroom\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SendMessage implements ShouldBroadcast, ShouldQueue {
  use Dispatchable, InteractsWithSockets, SerializesModels, Queueable;
  public $to;
  public $type;
  public $id;
  public $message;
  public $sender;
  public $additional_info;
  public function __construct($to = 'chatroom', $id, $sender, $message, $type, $additional_info = null) {
    $this->to = $to;
    $this->type = $type;
    $this->id = $id;
    $this->message = $message;
    $this->sender = $sender;
    $this->additional_info = $additional_info;
  }
  public function handle() {

  }
  public function broadcastAs() {
    return "newMessage";
  }
  public function broadcastWith() {
    return [
      'message' => [
        'formatted_text' => $this->message,
        'type' => $this->type,
        'sender' => $this->sender,
        'extra_info' => $this->additional_info
      ]
    ];
  }
  public function broadcastOn() {
    if($this->to == 'chatroom') {
      return new PrivateChannel("chatroom-".$this->id);
    } else {
      return [];
    }
  }
}
