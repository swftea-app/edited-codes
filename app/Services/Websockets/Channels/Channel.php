<?php

namespace App\Services\Websockets\Channels;

use BeyondCode\LaravelWebSockets\WebSockets\Channels\Channel as BaseChannel;
use Illuminate\Support\Facades\Redis;
use stdClass;
use Ratchet\ConnectionInterface;

class Channel extends BaseChannel
{

  public function subscribe(ConnectionInterface $connection, stdClass $payload)
  {
    parent::subscribe($connection, $payload);

    Redis::publish('subscribe.' . $this->channelName, json_encode($payload));
  }

  public function unsubscribe(ConnectionInterface $connection)
  {
    if (isset($this->subscribedConnections[$connection->socketId])) {
      Redis::publish('unsubscribe.' . $this->channelName, json_encode([]));
    }

    parent::unsubscribe($connection);
  }

}