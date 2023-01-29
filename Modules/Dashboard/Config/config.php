<?php

return [
  'name' => 'Dashboard',
  'permissions' => [
    'view registration trend widget' => 'View registration trend widget',
    'view user registration line graph' => 'View user registration trend line graph',
    'view chatroom registration line graph' => 'View chatroom registration trend line graph',
    'view level increment line graph' => 'View level increment line graph',
    'view message creation line graph' => 'view message creation line graph'
  ],
  'charts' => [
    'dashboard' => [
      'registration_trend' => [
        'chart_class' => '\\Modules\\Charts\\SimpleChart',
        'count' => 10,
        'size' => 12,
        'label' => 'Registration Trends',
        'can' => 'view registration trend widget',
        'labels' => [
          '10 days ago',
          '9 days ago',
          '8 days ago',
          '7 days ago',
          '6 days ago',
          '5 days ago',
          '4 days ago',
          '3 days ago',
          'Yesterday',
          'Today',
        ],
        'dataset' => [
          'new_user_registrations' => [
            'model' => '\\Modules\\UserSystem\\Entities\\User',
            'label' => 'User',
            'graph' => 'line',
            'method' => 'registrations',
            'params' => 'decreasing_count',
            'can' => 'view user registration line graph',
            'options' => [
              "fill" => false,
              "pointBackgroundColor" => "green",
              "pointBorderColor" => "green",
              "pointHoverBackgroundColor" => "green",
              "pointHoverBorderColor" => "green",
              "borderColor" => "green",
            ]
          ],
          'new_room_registrations' => [
            'model' => '\\Modules\\Chatroom\\Entities\\Chatroom',
            'label' => 'Chatroom',
            'graph' => 'line',
            'method' => 'registrations',
            'params' => 'decreasing_count',
            'can' => 'view chatroom registration line graph',
            'options' => [
              "fill" => false,
              "pointBackgroundColor" => "#007BFF",
              "pointBorderColor" => "#007BFF",
              "pointHoverBackgroundColor" => "#007BFF",
              "pointHoverBorderColor" => "#007BFF",
              "borderColor" => "#007BFF",
            ]
          ],
          'new_level_registrations' => [
            'model' => '\\Modules\\Level\\Entities\\Level',
            'label' => 'Level',
            'graph' => 'line',
            'method' => 'registrations',
            'params' => 'decreasing_count',
            'can' => 'view level increment line graph',
            'options' => [
              "fill" => false,
              "pointBackgroundColor" => "#FF0000",
              "pointBorderColor" => "#FF0000",
              "pointHoverBackgroundColor" => "#FF0000",
              "pointHoverBorderColor" => "#FF0000",
              "borderColor" => "#FF0000",
            ]
          ],
          'new_message_registrations' => [
            'model' => '\\Modules\\Chat\\Entities\\Message',
            'label' => 'Message',
            'graph' => 'line',
            'method' => 'registrations',
            'params' => 'decreasing_count',
            'can' => 'view message creation line graph',
            'options' => [
              "fill" => false,
              "pointBackgroundColor" => "red",
              "pointBorderColor" => "red",
              "pointHoverBackgroundColor" => "red",
              "pointHoverBorderColor" => "red",
              "borderColor" => "red",
            ]
          ],
        ]
      ]
    ]
  ]
];
