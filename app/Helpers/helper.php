<?php

use Illuminate\Support\Facades\DB;

function validate_url($url ) {
  $url = trim( $url );

  return (
    ( strpos( $url, 'http://' ) === 0 || strpos( $url, 'https://' ) === 0 ) &&
    filter_var(
      $url,
      FILTER_VALIDATE_URL,
      FILTER_FLAG_SCHEME_REQUIRED || FILTER_FLAG_HOST_REQUIRED
    ) !== false
  );
}
function getImageUrl($image) {
  if(validate_url($image)) {
    return $image;
  } else {
    $disk = config('admin.upload.disk');
    if (config("filesystems.disks.{$disk}")) {
      return asset(\Illuminate\Support\Facades\Storage::disk($disk)->url($image));
    } else {
      return '';
    }
  }
}
function natural_language_join(array $list, $conjunction = 'and') {
  $last = array_pop($list);
  if ($list) {
    return implode(', ', $list) . ' ' . $conjunction . ' ' . $last;
  }
  return $last;
}
function lowcardScore($score) {
  return $score;
}
function lowcardRawScore($score) {
  $array = ['H','D','S','C'];
  $random = \Illuminate\Support\Arr::random($array);
  $key_map = [
    1 => '2',
    2 => '3',
    3 => '4',
    4 => '5',
    5 => '6',
    6 => '7',
    7 => '8',
    8 => '9',
    9 => '10',
    10 => 'J',
    11 => 'Q',
    12 => 'K',
    13 => 'A',
  ];
  $val = '';
  if(array_key_exists($score, $key_map)) {
    $val = $key_map[$score];
  }
  return $val.''.$random;
}
function lowcardCardImage($score) {
  return asset('storage/icons/games/lowcard/'.$score.'.png');
}
function cricketCardImage($score) {
  return asset('storage/icons/games/cricket/'.$score.'.png');
}
function randomCricketScore() {
  $arr = [
    '1',
    '1',
    '2',
    '2',
    '3',
    '3',
    '4',
    '4',
    '6',
    '6',
    'LBW',
    'BOWLED',
    'CATCH',
    'STUMPED',
    '3UMP_OUT',
    'RUN_OUT',
    'HIT_OUT',
    '3UMP_NOT_OUT',
    '1',
    '1',
    '2',
    '2',
    '3',
    '3',
    '4',
    '4',
    '6',
    '6',
    '1',
    '1',
    '2',
    '2',
    '3',
    '3',
    '4',
    '6',
    'LBW',
    'BOWLED',
    'CATCH',
    'STUMPED',
    '3UMP_OUT',
    'RUN_OUT',
    'HIT_OUT',
    '3UMP_NOT_OUT',
    '4',
    '1',
    '1',
    '6',
    '6',
    '2',
    '2',
    '3',
    '3',
    '4',
    '4',
    '6',
    '6',
    '1',
    '1',
    '2',
    '2',
    '3',
    '3',
    '4',
    '4',
    '6',
    '6',
    '1',
    '1',
    '2',
    '2',
    '3',
    '3',
    '4',
    '4',
    '6',
    '6',
    '1',
    '1',
    '2',
    '2',
    '3',
    '3',
    '4',
    '4',
    '4',
    '4',
    '6',
    '6',
    '6',
    'LBW',
    'BOWLED',
    'CATCH',
    'STUMPED',
    '3UMP_OUT',
    'RUN_OUT',
    'HIT_OUT',
    '3UMP_NOT_OUT',
  ];
  $rand = rand(0, count($arr) - 1);
  return $arr[$rand];
}
function getHitLabel($from, $score) {
  switch ($score) {
    case '1':
      return $from.' bats: '.cricketCardImage($score).' One';
    case '2':
      return $from.' bats: '.cricketCardImage($score).' Two';
    case '3':
      return $from.' bats: '.cricketCardImage($score).' Three';
    case '4':
      return $from.' hits: '.cricketCardImage($score).' Four!';
    case '6':
      return $from.' hits: '.cricketCardImage($score).' SIX!';
    case 'LBW':
      return $from.' bats: '.cricketCardImage($score).' LBW!';
    case 'BOWLED':
      return $from.' bats: '.cricketCardImage($score).' Bowled!';
    case 'CATCH':
      return $from.' bats: '.cricketCardImage($score).' Caught!';
    case 'STUMPED':
      return $from.' bats: '.cricketCardImage($score).' Stumped!';
    case 'RUN_OUT':
      return $from.' bats: '.cricketCardImage($score).' Run OUT!';
    case 'HIT_OUT':
      return $from.' bats: '.cricketCardImage($score).' Hit OUT!';
    case '3UMP_OUT':
      return $from.' bats: '.cricketCardImage($score).' 3rd Umpire: OUT!';
    case '3UMP_NOT_OUT':
      return $from.' bats: '.cricketCardImage($score).' 3rd Umpire: NOT OUT!';
  }
  return -1;
}
function getCricketRun($rand) {
  switch ($rand) {
    case '1':
      return 1;
    case '2':
      return 2;
    case '3':
      return 3;
    case '4':
      return 4;
    case '6':
      return 6;
    case 'LBW':
    case 'BOWLED':
    case 'CATCH':
    case 'STUMPED':
    case 'RUN_OUT':
    case 'HIT_OUT':
    case '3UMP_OUT':
      return -1;
    case '3UMP_NOT_OUT':
      return 0;
  }
  return -1;
}
function diceImage($key) {
  return asset('storage/icons/games/dice/'.$key.'.png');
}
function luckySevenImage($key) {
  return '';
//  return asset('storage/icons/games/lucky7/'.$key.'.png');
}
function diceGameId($number) {
  return '#'.$number;
}
function getDiceGroupsName() {
  return diceImage('Santa')." Santa ".diceImage('Banta')." Banta ".diceImage('Odin')." Odin ".diceImage('Modin')." Modin ".diceImage('Vamp')." Vamp ".diceImage('Pamp')." Pamp";
}
function getLuckySevenGroupsName() {
  return luckySevenImage('LOW')." Low (L) ".luckySevenImage('SEVEN')." Seven (7) ".luckySevenImage('HIGH')." High (H) ";
}
function getDiceWordFromShortKey($key) {
  switch ($key) {
    case 'S':
      return 'Santa';
    case 'B':
      return 'Banta';
    case 'M':
      return 'Modin';
    case 'O':
      return 'Odin';
    case 'V':
      return 'Vamp';
    case 'P':
      return 'Pamp';
    default:
      return false;
  }
}
function getDiceKeyFromShortId($id) {
  switch ($id) {
    case 1:
      return 'S';
    case 2:
      return 'B';
    case 3:
      return 'M';
    case 4:
      return 'O';
    case 5:
      return 'V';
    case 6:
      return 'P';
    default:
      return false;
  }
}

function getLucky7WordFromKey($key) {
  switch ($key) {
    case 'L':
      return 'LOW';
    case '7':
      return 'SEVEN';
    case 'H':
      return 'HIGH';
    default:
      return false;
  }
}

function getDiceIdFromShortKey($key) {
  switch ($key) {
    case 'S':
      return 1;
    case 'B':
      return 2;
    case 'M':
      return 3;
    case 'O':
      return 4;
    case 'V':
      return 5;
    case 'P':
      return 6;
    default:
      return false;
  }
}

function getLuckySevenWordFromShortKey($key) {
  switch ($key) {
    case 'LOW':
      return 'LOW';
    case 'SEVEN':
      return 'SEVEN';
    case 'HIGH':
      return 'HIGH';
    default:
      return false;
  }
}

function getWinnerGroupLuckySeven($key) {
  if($key < 7) {
    return 'LOW';
  }
  if($key == 7) {
    return 'SEVEN';
  }
  if($key > 7) {
    return 'HIGH';
  }
}

function getLuckySevenBetOnFromKey($key) {
  if($key == 'LOW') {
    return 4;
  }
  if($key == 'SEVEN') {
    return 7;
  }
  if($key == 'HIGH') {
    return 10;
  }
}

function shuffleLuckySeven() {
  $one = rand(1,6);
  $two = rand(1,6);
  $total = $one + $two;
  return [
    'shuffle' => [$one, $two],
    'total' => $total
  ];
}

function shuffleDice() {
  $one = rand(1,6);
  $two = rand(1,6);
  $three = rand(1,6);
  $four = rand(1,6);
  $five = rand(1,6);
  $six = rand(1,6);

  $total = $one + $two + $three + $four + $five + $six;
  if($total > 33) {
    $selector = rand(1, 6);
    $one = $selector;
    $two = $selector;
    $three = $selector;
    $four = $selector;
    $five = $selector;
    $six = $selector;
  }
  if($total == 21) {
    $selector = rand(1, 6);
    $one = $selector;
    $two = $selector;
    $three = $selector;
    $four = $selector;
    $five = $selector;
    $six = $selector;
  }
  if($total > 11 && $total < 15) {
    $selector = rand(1, 6);
    $one = $selector;
    $two = $selector;
    $three = $selector;
    $four = $selector;
    $five = $selector;
    $six = $selector;
  }
  if($total > 19 && $total < 22) {
    $selector1 = rand(1, 6);
    $selector2 = rand(1, 6);
    $one = $selector1;
    $two = $selector2;
    $three = $selector1;
    $four = $selector2;
    $five = $selector1;
    $six = $selector2;
  }
  if($total > 25 && $total < 29) {
    $selector1 = rand(1, 6);
    $selector2 = rand(1, 6);
    $selector3 = rand(1, 6);
    $one = $selector1;
    $two = $selector2;
    $three = $selector3;
    $four = $selector1;
    $five = $selector2;
    $six = $selector3;
  }


  $bot = [
    getDiceKeyFromShortId($one),
    getDiceKeyFromShortId($two),
    getDiceKeyFromShortId($three),
    getDiceKeyFromShortId($four),
    getDiceKeyFromShortId($five),
    getDiceKeyFromShortId($six)
  ];

  if(count(array_unique($bot)) == 6) {
    shuffleDice();
  }

  return [
    'shuffle' => $bot,
    'winning' => array_count_values($bot)
  ];
}

function commandSong() {
  $songs = [
    'Nevermind, I\'ll find someone like you, I wish nothing but the best for you, too!!',
    'I am not a perfect person, There\'s many things I wish I didn\'t do',
    'I have died everyday, waiting for you Darling, don\'t be afraid, I have loved you for a thousand years!',
    'We will we will rock you!!',
    'जुम्मा चुम्मा दे दे,  जुम्मा चुम्मा दे दे चुम्मा',
    'केही मिठो वात गर रात त्यसै ढल्किदै छ',
    'पहली नज़र में कैसे जादू कर दिया, तेरा बन बैठा है मेरा जिया',
    'फर्की फर्की नहेर मलाई, फर्की फर्की नहेर मलाई तिमी भने..',
  ];
  $rand = rand(0, count($songs) - 1);
  return $songs[$rand];
}
function eightBallSong() {
  $balls = [
    '8ball says: Ok',
    '8ball says: May be',
    '8ball says: No',
    '8ball says: Yep',
  ];
  $rand = rand(0, count($balls) - 1);
  return $balls[$rand];
}

function flamesOptions() {
  $balls = [
    'Friends',
    'Sis - Bro',
    'Enemy',
    'Lovers',
  ];
  $rand = rand(0, count($balls) - 1);
  return $balls[$rand];
}
function getMyLockOptions() {
  $balls = [
    'You have a chance of getting positive response. Go for it!',
    'Do not rush anywhere tonight, You might be in danger!!',
    'Oh dear! - Anything you try today will not get succeed.',
  ];
  $rand = rand(0, count($balls) - 1);
  return $balls[$rand];
}
function getDanceOptions() {
  $balls = [
    'English song - "Blood on the Dance Floor"',
    'Hindi song - "Chal chaiya chaiya chaiya chaiya"',
    'Nepali song - "Sunday morning love you, monday morning love you, I wanna love you everyday!',
  ];
  $rand = rand(0, count($balls) - 1);
  return $balls[$rand];
}

function getLevelInfo($level) {
  $lev = [];
  foreach (config('level.groups') as $group) {
    if($level >= $group['min'] && $level <= $group['max']) {
      $lev = $group;
      break;
    }
  }
  return $lev;
}

function getMaxLevelBarForLevel($level) {
  $info = getLevelInfo($level);
  return $info['min bar'] + ($level * $info['bar rate']);
}

function getMaxProgressBar($level) {
  $level_up_second = getMaxLevelUpdateTime($level) * 60;
  $time_bar = getLevelBarTimeRate($level) * $level_up_second;
  $max_bar = getMaxLevelBarForLevel($level);
  return $time_bar + $max_bar;
}

function getMainProgressBar(\Modules\Level\Entities\Level $level, $bar) {
  $last_level_updated = \Carbon\Carbon::parse($level->created_at);
  $diff_from_now = \Carbon\Carbon::now()->diffInSeconds($last_level_updated);
  $time_bar = getLevelBarTimeRate($level->value) * $diff_from_now;
  $max_bar = getMaxLevelBarForLevel($level->value);
  if($bar > $max_bar) {
    $bar = $max_bar;
  }
  return $time_bar + $bar;
}

function getMaxCreditExpenseForLevel($level) {
  $info = getLevelInfo($level);
  return $info['min spend'] + ($level * $info['min spend rate']);
}

function getMaxLevelUpdateTime($level) {
  $info = getLevelInfo($level);
  return $info['min level up time'] + ($level * $info['level up time rate']);
}

function getBarUpdateRate($level) {
  $info = getLevelInfo($level);
  return $info['spend bar rate'];
}

function getBonusForLevelUpdate($level) {
  $info = getLevelInfo($level);
  return $info['min reward'] + ($level * $info['reward increase rate']);
}

function getLevelBarOfTime($seconds, $level) {
  $rate = getLevelBarTimeRate($level);
  return $seconds * $rate;
}

function getLevelBarTimeRate($level) {
  $info = getLevelInfo($level);
  return $info['time level bar rate'];
}

function getPrimaryCreditRate($level) {
  $info = getLevelInfo($level);
  return $info['primary credit level bar rate'];
}

function getSecondaryCreditRate($level) {
  $info = getLevelInfo($level);
  return $info['secondary credit level bar rate'];
}

function canUpdateLevel(\Modules\Level\Entities\Level $level, $bar) {
  $last_level_updated = \Carbon\Carbon::parse($level->created_at);
  $diff_from_now = \Carbon\Carbon::now()->diffInSeconds($last_level_updated);
  $max_bar = getMaxLevelBarForLevel($level->value);
  $level_up_second = getMaxLevelUpdateTime($level->value) * 60;
  if($bar >= $max_bar && $diff_from_now > $level_up_second) {
    return true;
  } else {
    return false;
  }
}

function getBarForCredit($user_id, $amount) {
  if(is_string($user_id)) {
    $user = DB::table('users')->select(['id'])->where('username','=',$user_id)->first();
    $user_id = $user->id;
  }
  $profile = DB::table('profiles')->select(['spent_for_next_level', 'level_bar', 'today_spent_amount'])->where('user_id', '=', $user_id)->first();
  $old_spend = $profile->spent_for_next_level;
  $level = DB::table('levels')->select(['value'])->where('user_id', '=', $user_id)->latest()->first();
  $max = getMaxCreditExpenseForLevel($level->value);
  if ($old_spend > $max) {
    $ca = 0;
    $na = $amount;
  } else {
    $mc = $max - $old_spend;
    if ($amount > $mc) {
      $ca = $mc;
      $na = $amount - $mc;
    } else {
      $ca = $amount;
      $na = 0;
    }
  }
  $increasing_bar = (getPrimaryCreditRate($level->value) * $ca) + (getSecondaryCreditRate($level->value) * $na);
  return $increasing_bar;
}

function getProgramPointLimit($roles) {
  $point = 0;
  if(in_array("Merchant", $roles)) {
    $point = 1400;
  }

  if(in_array("Mentor", $roles)) {
    $point = 3000;
  }
  return $point;
}

function getMerchantMentorBarPercent($role, $bar) {
  $point = 0;
  if($role == "Mentor") {
    $point = config('program.mentor_point');
  } elseif($role == "Merchant") {
    $point = config('program.merchant_point');
  }
  $bar = $bar > $point ? $point : $bar;
  return round($bar / $point) * 100;
}

function getIcon($name) {
  switch ($name) {
    case 'heart':
      return asset('storage/icons/notifications/ic_heart.png');
      break;
    case 'warn':
      return asset('storage/icons/notifications/ic_warining.png');
    case 'friend':
      return asset('storage/icons/notifications/ic_contact_add.png');
    case 'like':
      return asset('storage/icons/notifications/ic_thumb_up.png');
    default:
      return asset('storage/icons/notifications/ic_light_on.png');
  }
}
function getSelectedItemOnMessage($message) {
  $m = (object) [
    'formatted_text' => $message->formatted_text,
    'type' => $message->type,
    'extra_info' => $message->extra_info
  ];
  return $m;
}

function getPickerCode() {
  return rand(1000000,9999999);
}
function getPickerAmount() {
  return round(rand(2000,4500) / 100, 2);
}
function getMaxNumberPicker() {
  return rand(40,80);
}

function getThreadId($one, $two) {
  if($one > $two) {
    return "private-".$one."-".$two;
  } else {
    return "private-".$two."-".$one;
  }
}

function canJoinChatroom($user_id, $chatroom_id) {
  $canJoin = DB::table('chatroom_users')
    ->where('user_id','=', $user_id)
    ->where('chatroom_id','=', $chatroom_id)->count();
  if($canJoin) {
    return false;
  } else {
    return true;
  }
}