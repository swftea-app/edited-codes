<?php

namespace App\Services\Websockets\Channels;

use BeyondCode\LaravelWebSockets\WebSockets\Channels\PrivateChannel as BasePrivateChannel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use stdClass;
use Ratchet\ConnectionInterface;

class PrivateChannel extends BasePrivateChannel
{

  public function subscribe(ConnectionInterface $connection, stdClass $payload)
  {
    $channelName = $payload->channel;
    $explodeChannel = explode("private-notification-chatroom-", $channelName);
    if(count($explodeChannel) == 2 && $explodeChannel[0] == '') {
      $chatroom_user = explode("-", $explodeChannel[1]);
      $chatroom_id = $chatroom_user[0];
      $user_id = $chatroom_user[1];
      DB::table('chatroom_users')->insert([
        'chatroom_id' => $chatroom_id,
        'user_id' => $user_id,
      ]);
    }
    parent::subscribe($connection, $payload);
  }

  public function unsubscribe(ConnectionInterface $connection)
  {
    if (isset($this->subscribedConnections[$connection->socketId])) {
      $channelName = $this->channelName;
      $explodeChannel = explode("private-notification-chatroom-", $channelName);
      if(count($explodeChannel) == 2 && $explodeChannel[0] == '') {
        $chatroom_user = explode("-", $explodeChannel[1]);
        $chatroom_id = $chatroom_user[0];
        $user_id = $chatroom_user[1];
        DB::table('chatroom_users')->where("user_id","=", $user_id)->where("chatroom_id","=", $chatroom_id)->delete();
      }
    }

    parent::unsubscribe($connection);
  }

}