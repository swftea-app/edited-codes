<?php

namespace Modules\ChatMini\Jobs;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class SystemNotification implements ShouldBroadcast {
  use SerializesModels, \Illuminate\Foundation\Events\Dispatchable,InteractsWithSockets, Queueable;
  public $notification;
  public $to;
  public function __construct($to, $notification) {
    $this->notification = $notification;
    $this->to = $to;
  }

  public function broadcastAs() {
    return "updates";
  }
  public function broadcastWith() {
    return $this->notification;
  }
  public function broadcastOn() {
    return new PrivateChannel("system-notifications-".$this->to);
  }
}
