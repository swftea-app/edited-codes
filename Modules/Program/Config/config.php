<?php

return [
    'name' => 'Program',
    'min_level_to_merchant' => 1,
    'min_level_to_mentor' => 1,
    'amount_to_be_merchant' => 250000,
    'amount_to_be_mentor' => 1200000,
    'amount_to_be_head_mentor' => 40000,
    'merchant_point' => 100,
    'mentor_point' => 200,
    'program_bar_rate' => 0.00002,
    'program_config' => [
      'inspection_first' => 28 * 24 * 60 * 60,
      'inspection_interval' => 24 * 60 * 60,
    ]
];
