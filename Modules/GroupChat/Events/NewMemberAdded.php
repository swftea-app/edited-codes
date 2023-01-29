<?php

namespace Modules\GroupChat\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewMemberAdded implements ShouldBroadcast {
  use Dispatchable, InteractsWithSockets, SerializesModels;
  public $user;
  public $thread_id;
  public $title;
  public $message;
  public $label;
  public $extra_info;
  public function __construct($user, $thread_id, $title, $messageText, $label = "Private Chat", $extra_info = null) {
    $this->user = $user;
    $this->thread_id = $thread_id;
    $this->title = $title;
    $this->message = $messageText;
    $this->label = $label;
    $this->extra_info = $extra_info;
  }
  public function broadcastOn() {
    return [new PrivateChannel('group-chat-'.$this->user)];
  }
  public function broadcastWith() {
    return [
      'id' => $this->thread_id,
      'slug' => $this->thread_id,
      'type' => "thread",
      'message' => $this->message,
      'title' => $this->title,
      'label' => $this->label,
      'extra_info' => $this->extra_info
    ];
  }
  public function broadcastAs() {
    return 'newGroup';
  }
}
