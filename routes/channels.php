<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('chatroom-*', function ($user) {
    return $user;
});
Broadcast::channel('userupdated-*', function ($user) {
    return $user;
});
Broadcast::channel('notification-*', function ($user) {
    return $user;
});
Broadcast::channel('notifications-*', function ($user) {
    return $user;
});
Broadcast::channel('histories-*', function ($user) {
  return $user;
});
Broadcast::channel('group-chat-*', function ($user) {
  return $user;
});
Broadcast::channel('group-chat-*', function ($user) {
  return $user;
});
Broadcast::channel('thread-*', function ($user) {
  return $user;
});
Broadcast::channel('email-*', function ($user) {
  return $user;
});
Broadcast::channel('users', function ($user) {
    return $user;
});
