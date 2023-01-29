<?php

namespace Modules\UserSystem\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserUpdated implements ShouldBroadcast, ShouldQueue
{
    use SerializesModels, Dispatchable, InteractsWithSockets, Queueable;
    public $user;
    public $updates;

  /**
   * Create a new event instance.
   *
   * @param array $updates
   * @param $user
   */
    public function __construct($updates, $user) {
        $this->user = $user;
        $this->updates = $updates;
    }
    public function broadcastAs() {
      return "updates";
    }
    public function broadcastWith() {
      return $this->updates;
    }
    public function broadcastOn() {
      if(is_object($this->user)) {
        return new PrivateChannel("notification-profile-".$this->user->id);
      } else {
        return new PrivateChannel("notification-profile-".$this->user);
      }
    }
}
