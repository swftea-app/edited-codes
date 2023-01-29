<?php

namespace Modules\GroupChat\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UpdateGroup implements ShouldBroadcast {
  use Dispatchable, InteractsWithSockets, SerializesModels;
  public $message;
  public $thread_id;
  public function __construct($data, $thread_id) {
    $this->message = $data;
    $this->thread_id = $thread_id;
  }
  public function broadcastOn() {
    return [new PrivateChannel('thread-'.$this->thread_id)];
  }
  public function broadcastWith() {
    return $this->message;
  }
  public function broadcastAs() {
    return 'groupUpdated';
  }
}
