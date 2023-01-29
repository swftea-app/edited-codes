<?php

namespace Modules\AccountHistory\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SendNotification implements ShouldBroadcast {
  use SerializesModels, Dispatchable,InteractsWithSockets, Queueable;
  public $history;
  public function __construct($history)
  {
    $this->history = $history;
  }

  public function broadcastAs() {
    return "histories";
  }
  public function broadcastWith() {
    return [
      'history' => $this->history
    ];
  }
  public function broadcastOn() {
    return new PrivateChannel("histories-".$this->history->user_id);
  }
}
