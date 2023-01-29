<?php

return [
  'name' => 'Level',
  'all_levels' => [
    1 => 'Easter',
    2 => 'Easter',
    3 => 'Easter',
    4 => 'Easter',
    5 => 'Easter',
    6 => 'Rookie',
    7 => 'Rookie',
    8 => 'Rookie',
    9 => 'Rookie',
    10 => 'Rookie',
    11 => 'Newbie',
    12 => 'Newbie',
    13 => 'Newbie',
    14 => 'Newbie',
    15 => 'Newbie',
    16 => 'Novice',
    17 => 'Novice',
    18 => 'Novice',
    19 => 'Novice',
    20 => 'Novice',
    21 => 'Learner',
    22 => 'Learner',
    23 => 'Learner',
    24 => 'Learner',
    25 => 'Learner',
    26 => 'Amature',
    27 => 'Amature',
    28 => 'Amature',
    29 => 'Amature',
    30 => 'Amature',
    31 => 'Mentee',
    32 => 'Mentee',
    33 => 'Mentee',
    34 => 'Mentee',
    35 => 'Mentee',
    36 => 'Expounder',
    37 => 'Expounder',
    38 => 'Expounder',
    39 => 'Expounder',
    40 => 'Expounder',
    41 => 'Explorer',
    42 => 'Explorer',
    43 => 'Explorer',
    44 => 'Explorer',
    45 => 'Explorer',
    46 => 'Activist',
    47 => 'Activist',
    48 => 'Activist',
    49 => 'Activist',
    50 => 'Activist',
    51 => 'Performer',
    52 => 'Performer',
    53 => 'Performer',
    54 => 'Performer',
    55 => 'Performer',
    56 => 'Mentor',
    57 => 'Mentor',
    58 => 'Mentor',
    59 => 'Mentor',
    60 => 'Mentor',
    61 => 'Experienced',
    62 => 'Experienced',
    63 => 'Experienced',
    64 => 'Experienced',
    65 => 'Experienced',
    66 => 'Expert',
    67 => 'Expert',
    68 => 'Expert',
    69 => 'Expert',
    70 => 'Expert',
    71 => 'Pro',
    72 => 'Pro',
    73 => 'Pro',
    74 => 'Pro',
    75 => 'Pro',
    76 => 'Advanced',
    77 => 'Advanced',
    78 => 'Advanced',
    79 => 'Advanced',
    80 => 'Advanced',
    81 => 'Ultra Pro',
    82 => 'Ultra Pro',
    83 => 'Ultra Pro',
    84 => 'Ultra Pro',
    85 => 'Ultra Pro',
    86 => 'Superior',
    87 => 'Superior',
    88 => 'Superior',
    89 => 'Superior',
    90 => 'Superior',
    91 => 'Master',
    92 => 'Master',
    93 => 'Master',
    94 => 'Master',
    95 => 'Master',
    96 => 'Chief',
    97 => 'Chief',
    98 => 'Chief',
    99 => 'Chief',
    100 => 'Chief',
    101 => 'Legend',
    102 => 'Legend',
    103 => 'Legend',
    104 => 'Legend',
    105 => 'Legend',
    106 => 'Achiever',
    107 => 'Achiever',
    108 => 'Achiever',
    109 => 'Achiever',
    110 => 'Achiever',
  ],
  'groups' => [
    [
      'name' => 'Easter',

      'min' => 0,
      'max' => 5,

      'min reward' => 175,
      'reward increase rate' => 35,

      'min level up time' => 60,
      'level up time rate' => 0,

      'min spend' => 1000,
      'min spend rate' => 100,

      'spend bar rate' => 5,

      'min bar' => 500,
      'bar rate' => 10,

      'time level bar rate' => 0.069,
      'primary credit level bar rate' => 0.5,
      'secondary credit level bar rate' => 0.1
    ],
    [
      'name' => 'Rookie',

      'min' => 6,
      'max' => 10,

      'min reward' => 350,
      'reward increase rate' => 70,

      'min level up time' => 60,
      'level up time rate' => 0,

      'min spend' => 1000,
      'min spend rate' => 100,

      'spend bar rate' => 5,

      'min bar' => 500,
      'bar rate' => 10,

      'time level bar rate' => 0.069,
      'primary credit level bar rate' => 0.5,
      'secondary credit level bar rate' => 0.1
    ],
    [
      'name' => 'Newbie',

      'min' => 11,
      'max' => 15,

      'min reward' => 105,
      'reward increase rate' => 30,

      'min level up time' => 12 * 60,
      'level up time rate' => 4,

      'min spend' => 10000,
      'min spend rate' => 1000,

      'min bar' => 2000,
      'bar rate' => 10,

      'time level bar rate' => 0.023,
      'primary credit level bar rate' => 0.2,
      'secondary credit level bar rate' => 0.01
    ],
    [
      'name' => 'Novice',

      'min' => 16,
      'max' => 20,

      'min reward' => 525,
      'reward increase rate' => 20,

      'min level up time' => 1260,
      'level up time rate' => 3,

      'min spend' => 10000,
      'min spend rate' => 2000,

      'min bar' => 2000,
      'bar rate' => 10,

      'time level bar rate' => 0.023,
      'primary credit level bar rate' => 0.2,
      'secondary credit level bar rate' => 0.01

    ],
    [
      'name' => 'Learner',

      'min' => 21,
      'max' => 25,

      'min reward' => 700,
      'reward increase rate' => 15,

      'min level up time' => 1.5 * 24 * 60,
      'level up time rate' => 5,

      'min spend' => 1000000,
      'min spend rate' => 3000,

      'min bar' => 5000,
      'bar rate' => 12,

      'time level bar rate' => 0.0381,
      'primary credit level bar rate' => 0.05,
      'secondary credit level bar rate' => 0.01

    ],
    [
      'name' => 'Amature',

      'min' => 26,
      'max' => 30,

      'min reward' => 875,
      'reward increase rate' => 15,

      'min level up time' => 1.5 * 24 * 60,
      'level up time rate' => 3,

      'min spend' => 1000000,
      'min spend rate' => 3000,

      'min bar' => 5000,
      'bar rate' => 12,

      'time level bar rate' => 0.0381,
      'primary credit level bar rate' => 0.05,
      'secondary credit level bar rate' => 0.01

    ],
    [
      'name' => 'Amature',

      'min' => 31,
      'max' => 35,

      'min reward' => 1225,
      'reward increase rate' => 15,

      'min level up time' => 2 * 24 * 60,
      'level up time rate' => 3,

      'min spend' => 1000000,
      'min spend rate' => 3000,

      'min bar' => 10000,
      'bar rate' => 12,

      'time level bar rate' => 0.05,
      'primary credit level bar rate' => 0.01,
      'secondary credit level bar rate' => 0.001

    ],
    [
      'name' => 'Amature',

      'min' => 36,
      'max' => 40,

      'min reward' => 1225,
      'reward increase rate' => 15,

      'min level up time' => 2 * 24 * 60,
      'level up time rate' => 3,

      'min spend' => 1000000,
      'min spend rate' => 3000,

      'min bar' => 10000,
      'bar rate' => 12,

      'time level bar rate' => 0.05,
      'primary credit level bar rate' => 0.01,
      'secondary credit level bar rate' => 0.001

    ],
    [
      'name' => 'Amature',

      'min' => 41,
      'max' => 50,

      'min reward' => 2000,
      'reward increase rate' => 15,

      'min level up time' => 2 * 24 * 60,
      'level up time rate' => 3,

      'min spend' => 3000000,
      'min spend rate' => 3000,

      'min bar' => 10000,
      'bar rate' => 12,

      'time level bar rate' => 0.05,
      'primary credit level bar rate' => 0.01,
      'secondary credit level bar rate' => 0.001

    ],
    [
      'name' => 'Amature',

      'min' => 50,
      'max' => 60,

      'min reward' => 5000,
      'reward increase rate' => 15,

      'min level up time' => 2 * 24 * 60,
      'level up time rate' => 3,

      'min spend' => 3000000,
      'min spend rate' => 30000,

      'min bar' => 12000,
      'bar rate' => 12,

      'time level bar rate' => 0.05,
      'primary credit level bar rate' => 0.01,
      'secondary credit level bar rate' => 0.001

    ],
    [
      'name' => 'Experienced',

      'min' => 61,
      'max' => 80,

      'min reward' => 5000,
      'reward increase rate' => 15,

      'min level up time' => 2 * 24 * 60,
      'level up time rate' => 3,

      'min spend' => 3000000,
      'min spend rate' => 30000,

      'min bar' => 12000,
      'bar rate' => 12,

      'time level bar rate' => 0.05,
      'primary credit level bar rate' => 0.01,
      'secondary credit level bar rate' => 0.001

    ],
  ]
];
