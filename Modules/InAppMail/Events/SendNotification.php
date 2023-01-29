<?php

namespace Modules\InAppMail\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SendNotification  implements ShouldBroadcast, ShouldQueue {
  use Dispatchable, InteractsWithSockets,SerializesModels, Queueable;
  public $receiver;
  public $message;
  public function __construct($receiver, $message) {
      $this->receiver = $receiver;
      $this->message = $message;
  }
  public function handle() {

  }
  public function broadcastAs() {
    return "newEmail";
  }
  public function broadcastWith() {
    return [
      'details' => $this->message
    ];
  }
  public function broadcastOn() {
    return new PrivateChannel("email-".$this->receiver);
  }
}
