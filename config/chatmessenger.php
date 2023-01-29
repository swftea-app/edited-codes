<?php

return [

    'user_model' => \Modules\UserSystem\Entities\User::class,

    'message_model' => Lexx\ChatMessenger\Models\Message::class,

    'participant_model' => Lexx\ChatMessenger\Models\Participant::class,

    'thread_model' => Lexx\ChatMessenger\Models\Thread::class,

    /**
     * Define custom database table names - without prefixes.
     */
    'messages_table' => "private_public_messages",

    'participants_table' => "private_public_messages_participants",

    'threads_table' => "private_public_messages_groups",

    /**
     * Define custom database table names - without prefixes.
    */

    'use_pusher' => env('CHATMESSENGER_USE_PUSHER', true),

    /**
     * 
     */
    'defaults' => [

        /**
         * specify the default column to use in getting participant names 
         * $thread->participantsString($userId, $columns = [])
         */
        'participant_aka' => env('CHATMESSENGER_PARTICIPANT_AKA', 'name'),
        
    ]
];
