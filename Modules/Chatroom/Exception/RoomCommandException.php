<?php

namespace Modules\Chatroom\Exception;


use Throwable;

class RoomCommandException extends \Exception {
  public $raw_message;
  public function __construct($message = "", $code = 0, Throwable $previous = null) {
    $this->raw_message = $message;
    parent::__construct($message, $code, $previous);
  }
  public function getRawMessage() {
    return $this->raw_message;
  }
}
