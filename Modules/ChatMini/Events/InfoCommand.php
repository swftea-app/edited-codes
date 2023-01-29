<?php

namespace Modules\ChatMini\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InfoCommand implements ShouldBroadcast, ShouldQueue {
    use SerializesModels, Dispatchable,InteractsWithSockets, Queueable;
    public $exception;
    public $user;
    public $type;
    public $uid;
    public $noti_type;
    public function __construct($exception, $user, $type, $uid, $noti_type = "error") {
        $this->exception = $exception;
        $this->user = $user;
        $this->type = $type;
        $this->uid = $uid;
        $this->noti_type = $noti_type;
    }
    public function handle() {

    }
    public function broadcastAs() {
      return "message";
    }
    public function broadcastWith() {
      return [
        'message' => $this->exception,
        'type' => $this->noti_type
      ];
    }
    public function broadcastOn() {
        return new PrivateChannel("notification-".$this->type."-".$this->uid."-".$this->user);
    }
}
