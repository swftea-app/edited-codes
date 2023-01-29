<?php

return [
  'name' => 'UserSystem',
  'super_admin_uid' => 1,
  'minimum_transfer' => 10,
  'maximum_transfer' => 1000000,
  'minimum_credit_required_to_transfer' => 45,
  'minimum_level_required_to_transfer' => 1,
  'min_tag_credit' => 8400,
  'permissions' => [
    'administer user' => 'Administer User',
    'add user' => 'Add User',
    'delete user' => 'Delete User',
    'view user' => 'View User',
    'edit user' => 'Edit User',

    'send gifts in chatroom' => 'Send gift in chatroom',
    'add moderator in any chatroom' => 'Add moderator in any chatroom',
    'remove moderator in any chatroom' => 'Remove moderator in any chatroom',
    'block on kick in any chatroom' => 'Block on kick in any chatroom',
    'block any user from any room' => 'Block user in any chatroom',
    'unblock any user from any room' => 'Unblock any user from any room',
    'join any chatroom' => 'join any chatroom',
    'can never be kicked in any chatroom' => 'can never be kicked in any chatroom',
    'can never be blocked in any chatroom' => 'can never be kicked in any chatroom',
    'add or remove announcement in any chatroom' => 'add or remove announcement in any chatroom',
    'mute any user from any room' => 'mute any user from any room',
    'unmute any user from any room' => 'unmute any user from any room',
    'never be muted in any chatroom' => 'never be muted in any chatroom',
    'silence any room' => 'silence any room',
    'message in any chatroom' => 'message in any chatroom',
    'lock any chatroom' => 'lock any chatroom',
    'unlock any chatroom' => 'unlock any chatroom',
    'join any locked chatroom' => 'join any locked chatroom',
    'start bot in any chatroom' => 'start bot in any chatroom',
    'change any chatroom description' => 'change any chatroom description',
  ],
  'defaults' => [
    'credit' => 108.00,
    'level' => [
      'name' => 'Starter Level',
      'value' => 1
    ],
    'profile' => [
      'name' => 'Basic Profile'
    ]
  ],
];
