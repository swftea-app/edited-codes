<?php

namespace Modules\Notifications\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SendNotification implements ShouldBroadcast {
  use SerializesModels, Dispatchable,InteractsWithSockets, Queueable;
    public $notification;
    public function __construct($notification)
    {
        $this->notification = $notification;
    }

    public function broadcastAs() {
      return "notifications";
    }
    public function broadcastWith() {
      return [
        'notification' => $this->notification
      ];
    }
    public function broadcastOn() {
        return new PrivateChannel("notifications-".$this->notification->user_id);
    }
}
