<?php

namespace Modules\Games\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SendGameMessage implements ShouldBroadcast, ShouldQueue {
  use Dispatchable, InteractsWithSockets, SerializesModels, Queueable;
  public $type;
  public $id;
  public $message;
  public $bot;
  public $additional_info;
  public function __construct($type, $id,$bot, $message, $additional_info = null) {
      $this->type = $type;
      $this->id = $id;
      $this->message = $message;
      $this->bot = $bot;
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
        'type' => 'game',
        'bot' => $this->bot,
        'extra_info' => $this->additional_info
      ]
    ];
  }
  public function broadcastOn() {
    if($this->type == 'chatroom') {
      return new PrivateChannel("chatroom-".$this->id);
    } else {
      return [];
    }
  }
}
