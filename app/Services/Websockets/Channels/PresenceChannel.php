<?php

namespace App\Services\Websockets\Channels;

use BeyondCode\LaravelWebSockets\WebSockets\Channels\PresenceChannel as BasePresenceChannel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Modules\UserSystem\Jobs\UpdateUserPresence;
use stdClass;
use Ratchet\ConnectionInterface;

class PresenceChannel extends BasePresenceChannel
{

  public function subscribe(ConnectionInterface $connection, stdClass $payload)
  {
    parent::subscribe($connection, $payload);
    $channelData = json_decode($payload->channel_data);
    $user_id = $channelData->user_id;
    $channelName = $payload->channel;
    if($channelName == "presence-users") {
      dispatch(new UpdateUserPresence('member_added', $user_id));
    }
  }

  public function unsubscribe(ConnectionInterface $connection)
  {
    if (isset($this->subscribedConnections[$connection->socketId])) {
      $channelData = $this->users[$connection->socketId];
      $user_id = $channelData->user_id;
      $channelName = $this->channelName;
      if($channelName == "presence-users") {
        dispatch(new UpdateUserPresence('member_removed', $user_id));
      }
    }

    parent::unsubscribe($connection);
  }

}