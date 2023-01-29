<?php
namespace Modules\Chatroom\Console;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\ChatMini\Entities\FreeToken;
use Modules\ChatMini\Events\InfoCommand;
use Modules\Chatroom\Entities\Chatroom;
use Modules\Chatroom\Exception\RoomCommandException;
use Modules\Chatroom\Jobs\ChatroomJob;
use Modules\Games\Jobs\CricketGameJob;
use Modules\Games\Jobs\DiceGameJob;
use Modules\Games\Jobs\GameJob;
use Modules\Games\Jobs\LuckSeven;
use Modules\Gift\Entities\AllGifts;
use Modules\SwfteaMission\Jobs\SeasonPoint;
use Modules\TextParser\Parser;
use Modules\UserSystem\Entities\User;

class RoomCommand extends Command {
  protected $name = 'chatmini:chatroom';

  protected $signature = 'chatmini:chatroom {op} {--clear=} {--extra=} {--text=} {--id=} {--name=} {--user=1} {--p=} {--d=Hey Nepali Folks!!}';

  protected $description = 'Command description.';
  public function __construct() {
    parent::__construct();
  }
  public function handle()
  {
//    if($this->argument('op') != "join") {
//      if($this->argument('op') != "leave") {
//        if(\cache('next_message_'.$this->option('user'), 0) != 0) {
//          event(new InfoCommand("Only single message can be sent per second.", $this->option('user'), "chatroom", $this->option('id')));
//          return;
//        } else {
//          \cache([
//            'next_message_'.$this->option('user') => time()
//          ], now()->addSecond());
//        }
//      }
//    }
    if ($this->argument('op') == "create") {
      $datas = [
        'name' => $this->option('name'),
        'user_id' => $this->option('user'),
        'password' => $this->option('p'),
        'description' => $this->option('d'),
      ];
      if (empty($datas['name'])) {
        $this->error("The name of room cannot be null");
        return;
      }
      $chatroom = new Chatroom();
      foreach ($datas as $key => $data) {
        $chatroom->{$key} = $data;
      }
      if (!empty($chatroom->password)) {
        $chatroom->privacy = 'private';
      } else {
        $chatroom->privacy = 'public';
      }
      if ($chatroom->user_id == 1 && auth()->user()) {
        $chatroom->user_id = auth()->user()->id;
      }
      $chatroom->save();
      $this->info("Chatroom " . $chatroom->name . " created. :)");
    }
    if ($this->argument('op') == "update") {
      $datas = [
        'name' => $this->option('name'),
        'user_id' => $this->option('user'),
        'password' => $this->option('p'),
        'description' => $this->option('d'),
        'id' => $this->option('id'),
      ];
      if (empty($datas['id'])) {
        $this->error("The id of room cannot be null");
        return;
      }
      $chatroom = Chatroom::find($datas['id']);
      unset($datas['id']);
      foreach ($datas as $key => $data) {
        $chatroom->{$key} = empty($data) ? $chatroom->{$key} : $data;
      }
      if (!empty($this->option('clear'))) {
        $chatroom->password = '';
        $chatroom->privacy = 'public';
      } else {
        if (!empty($chatroom->password)) {
          $chatroom->privacy = 'private';
        } else {
          $chatroom->privacy = 'public';
        }
      }
      $chatroom->save();
      $this->info("Chatroom " . $chatroom->name . " updated. :)");
    }
    if ($this->argument('op') == "delete") {
      $datas = [
        'id' => $this->option('id'),
        'pass' => $this->option('p'),
      ];
      if (empty($datas['id'])) {
        $this->error("The id of room cannot be null");
        return;
      }
      $chatroom = Chatroom::find($datas['id']);
      $name = $chatroom->name;
      if ($chatroom->password == $datas['pass']) {
        $chatroom->delete();
        $this->info("Chatroom " . $name . " deleted. :)");
      } else {
        $this->error("Chatroom cannot be deleted.");
      }
    }
    if ($this->argument('op') == "message") {
      $datas = [
        'id' => $this->option('id'),
        'pass' => $this->option('p'),
        'text' => $this->option('text'),
        'extra' => json_decode($this->option('extra')),
        'user' => $this->option('user'),
      ];
      if (empty($datas['id'])) {
        throw new RoomCommandException("The id of room cannot be null");
      }
      $chatroom = Chatroom::where('id','=',$datas['id'])->first();
      if(!$chatroom) {
        throw new RoomCommandException("Chatroom not found.");
      }
      if ($chatroom->password == $datas['pass']) {
        if(strlen(trim($datas['text'])) > 500) {
          return;
        }
        if (empty($datas['text'])) {
          return;
        } else {
          $message_group = "";
          try {
            $user = User::find($datas['user']);
            // Add to room
            if(canJoinChatroom($datas['user'], $chatroom->id)) {
              $chatroom->members()->attach($datas['user']);
            }
            $access = false;
            if(!$access && $user->id == config('usersystem.super_admin_uid')) {
              $access = true;
            } // Super Admin
            if(!$access && $user->can('message in any chatroom')) {
              $access = true;
            } // has permission [global]
            if(!$access && $chatroom->user_id == $user->id) {
              $access = true;
            } // own room
            if(!$access && $chatroom->moderators->contains($user->id)) {
              $access = true;
            } // is moderator
            $error_message = '';
            if(!$access) {
              $normal_case = true;
              if($normal_case && canJoinChatroom($datas['user'], $chatroom->id)) {
                $chatroom->members()->attach($datas['user']);
              }
              if($normal_case && $chatroom->mutedMembers->contains($datas['user'])) {
                $error_message = 'You are muted in this chatroom.';
                $normal_case = false;
              }
              if($normal_case && $chatroom->is_silent) {
                $error_message = 'Chatroom is silenced. Please wait for sometime to send messages.';
                $normal_case = false;
              }
              if($normal_case) {
                $access = true;
              }
            }
            if(!$access) {
              event(new InfoCommand(!empty($error_message) ? $error_message : "Internal server error!", $datas['user'], "chatroom", $datas['id']));
              return;
            }
            $parser = new Parser($datas['text'], $user);
            if ($parser->command_found) {
              switch ($parser->command) {
                // Gift
                case 'gift':
                  $gift_name = $parser->name;
                  $receivers = collect([]);
                  $gift = AllGifts::where('name','=',$gift_name)->first();
                  if($gift) {
                    $parser->valid_name = true;
                  } else {
                    event(new InfoCommand("The selected gift '".$gift_name."' is not listed in our store. Please visit gift store for more information.", $datas['user'], "chatroom", $datas['id']));
                    return;
                  }
                  $parser->gift = $gift;
                  if($parser->gift->discount > 0) {
                    $parser->gift->price = $parser->gift->price - (($parser->gift->discount/100) * $parser->gift->price);
                  }
                  // Check if gift exists
                  if (!$parser->valid_name) {
                    event(new InfoCommand("The selected gift '".$gift_name."' is not listed in our store. Please visit gift store for more information.", $datas['user'], "chatroom", $datas['id']));
                    return;
                  }
                  // validate shower time
                  $last_gift_sent_at = \cache('last_gift_sent_'.$datas['user'], 0);
                  if($last_gift_sent_at != 0) {
                    event(new InfoCommand("You cannot send gift at the moment. Please try again later.", $datas['user'], "chatroom", $datas['id']));
                    return;
                  }
                  // Validate and grab receivers
                  if ($parser->whom == 'all') {
                    foreach ($chatroom->members as $member) {
                      # Exclude sender
                      if($member->id != $parser->sender->id) {
                        $receivers->push($member);
                      }
                    }
                    $parser->receivers = $receivers;
                    if(count($parser->receivers) < 2) {
                      event(new InfoCommand('Gift shower failed. Unable to shower as it required minimum of 3 users.', $datas['user'], "chatroom", $datas['id']));
                      return;
                    }
                    $message_group = $parser->gift->price > 2 ? "gift_all" : "gift_all_cheap";
                    $sender_history_message = $parser->gift->name . " sent to ".count($receivers)." users of " . $chatroom->name." with rate of ".$parser->gift->price." credits";
                  } else {
                    $receiver = User::where('username', '=', $parser->whom)->first();
                    if (!$receiver) {
                      event(new InfoCommand('Gift sending failed. Please recheck username and try again.', $datas['user'], "chatroom", $datas['id']));
                      return;
                    } # Single receiver not existed
                    if ($parser->gift->price < 2.20) {
                      event(new InfoCommand("Gift sending failed. Gift with price below 2.20 credits can't be sent in private.", $datas['user'], "chatroom", $datas['id']));
                      return;
                    } # Single receiver not existed
                    if ($parser->sender->id == $receiver->id) {
                      event(new InfoCommand("Gift sending failed. You cannot send private gift to yourself.", $datas['user'], "chatroom", $datas['id']));
                      return;
                    } # Single receiver not existed
                    $parser->receiver = $receiver;
                    $receivers->push($receiver);
                    $sender_history_message = $parser->gift->name . " sent to " . $receiver->username . " in " . $chatroom->name." with rate of ".$parser->gift->price." credits";
                  }
                  // SET COLOR
//                  if(is_array($datas['extra'])) {
//                    $datas['extra']['color'] = $parser->gift->color;
//                  } else {
//                    $datas['extra']['color'] = '#E6397F';
//                  }
                  // Grab total credit
                  $total_credit_used = count($receivers) * $parser->gift->price;
                  // Validate balance
                  if ($parser->sender->credit < $total_credit_used) {
                    event(new InfoCommand('Gift sending failed. Insufficient balance for sending gifts. Please contact your nearest merchant/mentor or visit our store for purchasing credits. Thank you.', $datas['user'], "chatroom", $datas['id']));
                    return;
                  }
                  // Validate receivers
                  if (count($receivers) == 0) {
                    event(new InfoCommand('There are no sufficient users in this chatroom.', $datas['user'], "chatroom", $datas['id']));
                    return;
                  }
                  // Validate can send gifts permissions
                  if (!$parser->sender->can("send gifts in chatroom")) {
                    event(new InfoCommand('You don\'t have permissions to send gift. Please contact administrator to unban sending gifts.', $datas['user'], "chatroom", $datas['id']));
                    return;
                  }
                  // All Good?
                  # Parse text
                  $parser->parseGift();
                  # Add account history to sender
                  $parser->sender->histories()->create([
                    'type' => 'gift',
                    'creditor' => 'chatroom',
                    'creditor_id' => $chatroom->id,
                    'message' => $sender_history_message,
                    'old_value' => $parser->sender->credit,
                    'new_value' => $parser->sender->credit - $total_credit_used,
                    'user_id' => $parser->sender->id
                  ]);
                  # mission
                  dispatch(
                    new SeasonPoint(
                      'add points',
                      $parser->sender->id,
                      $parser->gift->name,
                      'gift_send',
                      count($receivers))
                  )->onQueue('low');
                  # Deduct amount from sender
                  DB::table('users')
                    ->where('id','=',$parser->sender->id)
                    ->decrement('credit', $total_credit_used);

                  # Send info message
                  $parser->gift->gift_image = getImageUrl($parser->gift->gift_image);
                  # Add gift to user
                  if($parser->gift->isPremium) {
                    $premium_price = round(($total_credit_used * 0.1)/count($receivers), 2);
                  } else {
                    $premium_price = 0;
                  }
                  foreach ($receivers as $receiver) {
                    # mission
                    dispatch(
                      new SeasonPoint(
                        'add points',
                        $receiver->id,
                        $parser->gift->name,
                        'gift_receive',
                        1)
                    )->onQueue('low');

                    $receiver->gifts()->create([
                      'name' => $parser->gift->name,
                      'price' => $parser->gift->price,
                      'icon' => '-',
                      'key' => '-',
                      'discount' => $parser->gift->discount,
                      'user_id' => $parser->sender->id,
                      'receiver_id' => $receiver->id,
                      'type_id' => $chatroom->id,
                      'type' => 'chatroom',
                      'gift_url' => asset($parser->gift->gift_image)
                    ]);
                    if($premium_price > 0) {
                      $receiver->histories()->create([
                        'type' => 'Transfer',
                        'creditor' => 'system',
                        'creditor_id' => 1,
                        'message' => "Received ".$premium_price.' credits from premium gift ('.$parser->gift->name.')',
                        'old_value' => $receiver->credit,
                        'new_value' => $receiver->credit + $premium_price,
                        'user_id' => $receiver->id
                      ]);
                      DB::table('users')
                        ->where('id','=',$receiver->id)
                        ->increment('credit', $premium_price);
                    }
                  }
                  if(count($receivers) > 1) {
                    \cache([
                      'last_gift_sent_'.$datas['user'] => time()
                    ], now()->addSeconds(1));
                    if($premium_price > 0) {
                      event(new InfoCommand('Wow!! You have sent a premium gift ('.$gift_name.') to '.count($receivers).' users using '.$total_credit_used.' credits. Thank you.', $datas['user'], "chatroom", $datas['id'],"info"));
                    } else {
                      event(new InfoCommand('Wow!! You have sent a '.$gift_name.' to '.count($receivers).' users using '.$total_credit_used.' credits. Thank you.', $datas['user'], "chatroom", $datas['id'],"info"));
                    }
                  } else {
                    if($premium_price > 0) {
                      event(new InfoCommand('Wow!! You have sent a premium gift ('.$gift_name.') to '.$parser->receiver->username.' using '.$total_credit_used.' credits. Thank you.', $datas['user'], "chatroom", $datas['id'], "info"));
                    } else {
                      event(new InfoCommand('Wow!! You have sent a '.$gift_name.' to '.$parser->receiver->username.' using '.$total_credit_used.' credits. Thank you.', $datas['user'], "chatroom", $datas['id'], "info"));
                    }
                  }
                  break;
                case 'botm':
                  /** check permissions**/
                  $access = false;
                  if(!$access && $parser->sender->id == config('usersystem.super_admin_uid')) {
                    $access = true;
                  } // Super Admin
                  if(!$access && $parser->sender->can('set max min amount in any bot room')) {
                    $access = true;
                  } // has permission [global]
                  if(!$access && $chatroom->user_id == $parser->sender->id) {
                    $access = true;
                  } // own room
                  if(!$access && $chatroom->moderators->contains($parser->sender->id)) {
                    $access = true;
                  } // is moderator
                  #validate access
                  if(!$access) {
                    event(new InfoCommand('You don\'t have permission to set min max amount.', $datas['user'], "chatroom", $datas['id']));
                    return;
                  } // Throw exception
                  if($parser->whom == 'min') {
                    \cache(['min_bot_amount_'.$chatroom->id => $parser->name], now()->addDays(1));
                    event(new InfoCommand('min bot amount is set to '.$parser->name.' credits.', $datas['user'], "chatroom", $datas['id'], "info"));
                  } else if($parser->whom == 'max') {
                    \cache(['max_bot_amount_'.$chatroom->id => $parser->name], now()->addDays(1));
                    event(new InfoCommand('Max bot amount is set to '.$parser->name.' credits.', $datas['user'], "chatroom", $datas['id'], "info"));
                  } else {
                    event(new InfoCommand('Invalid chatroom command.', $datas['user'], "chatroom", $datas['id']));
                  }
                  return;
                case 'earn':
                  if (empty($parser->whom)) {
                    event(new InfoCommand('Earning code cannot be empty.', $datas['user'], "chatroom", $datas['id']));
                    return;
                  }
                  $picker = FreeToken::where('token', '=', $parser->whom)->with(['pickers'])->withCount('pickers')->latest()->first();
                  if(!$picker) {
                    event(new InfoCommand('Invalid earning code.', $datas['user'], "chatroom", $datas['id']));
                    return;
                  }
                  if($picker->expires_on < now()) {
                    event(new InfoCommand('This earning code is expired.', $datas['user'], "chatroom", $datas['id']));
                    return;
                  }
                  if($picker->pickers_count > $picker->max_user) {
                    event(new InfoCommand('This earning code is already used by many users.', $datas['user'], "chatroom", $datas['id']));
                    return;
                  }
                  if($picker->pickers->contains($datas['user'])) {
                    event(new InfoCommand('You have already used this earning code.', $datas['user'], "chatroom", $datas['id']));
                    return;
                  }
                  #all good?
                  $picker->pickers()->attach($datas['user']);
                  $old_credit = $parser->sender->credit;
                  $new_credit = $old_credit + $picker->amount;
                  DB::table('users')
                    ->where('id','=',$parser->sender->id)
                    ->increment('credit', $picker->amount);
                  # Add account history to sender
                  $parser->sender->histories()->create([
                    'type' => 'gift',
                    'creditor' => 'chatroom',
                    'creditor_id' => $chatroom->id,
                    'message' => 'Picked code #'.$picker->token.' and earned '.$picker->amount.' credits.',
                    'old_value' => $old_credit,
                    'new_value' => $new_credit,
                    'user_id' => $parser->sender->id
                  ]);
                  event(new InfoCommand('Congratulations!! you have earned '.$picker->amount.' credits. Thank you!', $datas['user'], "chatroom", $datas['id'], 'info'));
                  return;
                  break;
                // Roll
                case 'roll':
                  $message_group = "normal_quote";
                  $parser->parseRoll();
                  break;
                // Roll
                case 'broadcast':
                  $access = false;
                  #validate kicking user
                  /** check permissions**/
                  $access = false;
                  if(!$access && $parser->sender->id == config('usersystem.super_admin_uid')) {
                    $access = true;
                  } // Super Admin
                  if(!$access && $parser->sender->can('broadcast in any room')) {
                    $access = true;
                  } // has permission [global]
                  if(!$access && $chatroom->user_id == $parser->sender->id) {
                    $access = true;
                  } // own room
                  if(!$access && $chatroom->moderators->contains($parser->sender->id)) {
                    $access = true;
                  } // is moderator
                  #validate access
                  if(!$access) {
                    event(new InfoCommand('You don\'t have permission to broadcast.', $datas['user'], "chatroom", $datas['id']));
                    return;
                  } // Throw exception
                  $message_group = "normal_quote";
                  $parser->parseBroadcast();
                  break;
                // check tag
                case 'checktag':
                  if (empty($parser->whom)) {
                    event(new InfoCommand('Username cannot be empty.', $datas['user'], "chatroom", $datas['id']));
                    return;
                  }
                  $sender_roles = $parser->sender->roles->pluck('name')->toArray();
                  $access = false;
                  if(in_array("Merchant", $sender_roles) || in_array("Mentor", $sender_roles) || in_array("Mentor Head", $sender_roles) || in_array("Admin", $sender_roles)) {
                    $access = true;
                  }
                  if(!$access) {
                    event(new InfoCommand("You don't have access to this command.", $datas['user'], "chatroom", $datas['id']));
                    return;
                  }
                  $receiver = DB::table('users')->where('username','=',$parser->whom)->first();
                  if(!$receiver) {
                    event(new InfoCommand('Invalid user.', $datas['user'], "chatroom", $datas['id']));
                    return;
                  }
                  if($receiver->tag_id == 1) {
                    event(new InfoCommand('This user is not tagged by any merchant/mentor. Send credits to tag this user.', $datas['user'], "chatroom", $datas['id'], 'info'));
                    return;
                  } elseif ($receiver->tag_id == $parser->sender->id) {
                    $valid_till = $parser->sender->program_expiry == null ? Carbon::now()->addSeconds(rand(0, 3000))->diffForHumans() : Carbon::parse($parser->sender->program_expiry)->diffForHumans();
                    event(new InfoCommand('This user is tagged by you till '.$valid_till, $datas['user'], "chatroom", $datas['id'], 'info'));
                    return;
                  } else {
                    event(new InfoCommand('This user is already tagged by another merchant/mentor', $datas['user'], "chatroom", $datas['id'], 'info'));
                    return;
                  }
                  break;
                // Kick
                case 'kick':
                  # validate all user case
//                  if ($parser->whom == 'all') {
//                    event(new InfoCommand('Cannot kick all user.', $datas['user'], "chatroom", $datas['id']));
//                    return;
//                  }
                  # validate self kicking
                  if ($parser->whom == $parser->sender->username) {
                    event(new InfoCommand('You cannot kick yourself.', $datas['user'], "chatroom", $datas['id']));
                    return;
                  }
                  #validate kicking user
                  /** check permissions**/
                  $access = false;
                  if(!$access && $parser->sender->id == config('usersystem.super_admin_uid')) {
                    $access = true;
                  } // Super Admin
                  if(!$access && $parser->sender->can('kick any user from all room')) {
                    $access = true;
                  } // has permission [global]
                  if(!$access && $chatroom->user_id == $parser->sender->id) {
                    $access = true;
                  } // own room
                  if(!$access && $chatroom->moderators->contains($parser->sender->id)) {
                    $access = true;
                  } // is moderator
                  #validate access
                  if(!$access) {
                    event(new InfoCommand('You don\'t have permission to kick member.', $datas['user'], "chatroom", $datas['id']));
                    return;
                  } // Throw exception
                  #validate user if existed
                  $receiver = User::where('username','=', $parser->whom)->first();
                  if(!$receiver) {
                    event(new InfoCommand('User not found.', $datas['user'], "chatroom", $datas['id']));
                    return;
                  } // Invalid user
                  $parser->receiver = $receiver;
                  # owner validation
                  if($chatroom->user_id == $receiver->id) {
                    event(new InfoCommand("Chatroom owner cannot be kicked.", $datas['user'],"chatroom",$datas['id']));
                    return;
                  }
                  # validate if user is moderator
                  if($chatroom->moderators->contains($receiver->id)) {
                    event(new InfoCommand("Moderators cannot be kicked.", $datas['user'],"chatroom",$datas['id']));
                    return;
                  }
                  # validate if receiver cannot be kicked
                  if($receiver->can('can never be kicked in any chatroom')) {
                    event(new InfoCommand("This user cannot be kicked.", $datas['user'],"chatroom",$datas['id']));
                    return;
                  }
                  #validate user is in chatroom
                  if(!$chatroom->members->contains($receiver->id)) {
                    event(new InfoCommand('User is not in chatroom', $datas['user'], "chatroom", $datas['id']));
                    return;
                  }
                  #validate user is first time kicked
                  if($chatroom->kickedMembers->contains($receiver->id)) {
                    event(new InfoCommand($parser->receiver->username." is recently kicked.", $datas['user'], "chatroom", $datas['id']));
                    return;
                  }
                  $block_user = false;
                  #if kicked by administrator
                  if($parser->sender->can('block on kick in any chatroom')) {
                    $block_user = true;
                  }
                  /** Everything Good */
                  $parser->parseLeaveRoom('%%receiver_username%%[%%receiver_level%%] has left');
                  $kick_message = $parser->formatted_text;
                  $parser->parseKick(); // Parse kick message for message history
                  $chatroom->kickedMembers()->attach($parser->receiver->id); // Save to kicked list
                  $chatroom->leave($parser->receiver->id, $kick_message); // Leave that chatroom
                  event(new InfoCommand("You are kicked.", $parser->receiver->id, "chatroom", $chatroom->id, "kicked")); // kick from room app
                  if($block_user) {
                    if(!$chatroom->blockedMembers->contains($parser->receiver->id)) {
                      $chatroom->blockedMembers()->attach($parser->receiver->id);
                    } // Block user
                  } // Block if kicked by admin
                  $message_group = "infomessage";
                  break;
                case 'clearkick':
                  #validate kicking user
                  /** check permissions**/
                  $access = false;
                  if(!$access && $parser->sender->id == config('usersystem.super_admin_uid')) {
                    $access = true;
                  } // Super Admin
                  if(!$access && $parser->sender->can('clear kicked user from all room')) {
                    $access = true;
                  } // has permission [global]
                  if(!$access && $chatroom->user_id == $parser->sender->id) {
                    $access = true;
                  } // own room
                  #validate access
                  if(!$access) {
                    event(new InfoCommand('You don\'t have permission to clear kicked member.', $datas['user'], "chatroom", $datas['id']));
                    return;
                  } // Throw exception
                  if($parser->whom != 'all') {
                    $receiver_exists = DB::table('users')
                      ->select(['username','id'])
                      ->where('username','=',$parser->whom)
                      ->first();
                    if($receiver_exists) {
                      $is_kicked = DB::table('chatroom_kicked_users')
                        ->where('chatroom_id','=',$datas['id'])
                        ->where('user_id','=',$receiver_exists->id)
                        ->exists();
                      if($is_kicked) {
                        DB::table('chatroom_kicked_users')
                          ->where('chatroom_id','=',$datas['id'])
                          ->where('user_id','=',$receiver_exists->id)
                          ->delete();
                        $parser->parseLeaveRoom('Kick cleared for '.$receiver_exists->username.'.');
                      } else {
                        event(new InfoCommand('This user is not kicked from this chatroom.', $datas['user'], "chatroom", $datas['id']));
                        return;
                      }
                    } else {
                      event(new InfoCommand('Invalid user.', $datas['user'], "chatroom", $datas['id']));
                      return;
                    }
                  } else {
                    /** Everything Good */
                    DB::table('chatroom_kicked_users')->where('chatroom_id','=',$datas['id'])->delete();
                    $parser->parseLeaveRoom('Kicklist has been cleared.');
                  }
                  $message_group = "infomessage";
                  break;
                // Block
                case 'ban':
                  #validate all user case
//                  if ($parser->whom == 'all') {
//                    event(new InfoCommand('Cannot block all user.', $datas['user'], "chatroom", $datas['id']));
//                    return;
//                  }
                  /** check permissions**/
                  $access = false;
                  if(!$access && $parser->sender->id == config('usersystem.super_admin_uid')) {
                    $access = true;
                  } // Super Admin
                  if(!$access && $parser->sender->can('block any user from any room')) {
                    $access = true;
                  } // has permission [global]
                  if(!$access && $chatroom->user_id == $parser->sender->id) {
                    $access = true;
                  } // own room
                  if(!$access && $chatroom->moderators->contains($parser->sender->id)) {
                    $access = true;
                  } // is moderator
                  #validate access
                  if(!$access) {
                    event(new InfoCommand('You don\'t have permission to ban member.', $datas['user'], "chatroom", $datas['id']));
                    return;
                  } // Throw exception
                  #validate user if existed
                  $receiver = User::where('username','=', $parser->whom)->first();
                  if(!$receiver) {
                    event(new InfoCommand('User not found.', $datas['user'], "chatroom", $datas['id']));
                    return;
                  } // Invalid user
                  $parser->receiver = $receiver;
                  #validate user is first time blocked
                  if($chatroom->blockedMembers->contains($receiver->id)) {
                    event(new InfoCommand($parser->receiver->username." is already banned.", $datas['user'], "chatroom", $datas['id']));
                    return;
                  }
                  # owner validation
                  if($chatroom->user_id == $receiver->id) {
                    event(new InfoCommand("Chatroom owner cannot be banned.", $datas['user'],"chatroom",$datas['id']));
                    return;
                  }
                  # validate if user is moderator
                  if($chatroom->moderators->contains($receiver->id)) {
                    event(new InfoCommand("Moderators cannot be banned.", $datas['user'],"chatroom",$datas['id']));
                    return;
                  }
                  # validate if receiver cannot be kicked
                  if($receiver->can('can never be blocked in any chatroom')) {
                    event(new InfoCommand("This user cannot be banned.", $datas['user'],"chatroom",$datas['id']));
                    return;
                  }
                  /** Everything Good */
                  $parser->parseLeaveRoom('%%receiver_username%%[%%receiver_level%%] has left');
                  $leave_message = $parser->formatted_text;
                  $parser->parseBlock();
                  $chatroom->blockedMembers()->attach($parser->receiver->id); // Save to blocked list
                  event(new InfoCommand("You are banned from this chatroom.", $parser->receiver->id, "chatroom", $chatroom->id, "kicked")); // kick from room app
                  $chatroom->leave($parser->receiver->id, $leave_message); // Leave that chatroom
                  $message_group = "infomessage";
                  break;
                case 'banlist':
                  /** check permissions**/
                  $access = false;
                  if(!$access && $parser->sender->id == config('usersystem.super_admin_uid')) {
                    $access = true;
                  } // Super Admin
                  if(!$access && $parser->sender->can('view block list of room')) {
                    $access = true;
                  } // has permission [global]
                  if(!$access && $chatroom->user_id == $parser->sender->id) {
                    $access = true;
                  } // own room
                  if(!$access && $chatroom->moderators->contains($parser->sender->id)) {
                    $access = true;
                  } // is moderator
                  #validate access
                  if(!$access) {
                    event(new InfoCommand('You don\'t have permission to see banned members.', $datas['user'], "chatroom", $datas['id']));
                    return;
                  } // Throw exception
                  /** Everything Good */
                  $blocked_members = $chatroom->blockedMembers()->get();
                  $members = [];
                  foreach ($blocked_members as $blocked_member) {
                    $members[] = $blocked_member->username;
                  }
                  event(new InfoCommand('Banned members: '.implode(",", $members), $datas['user'], "chatroom", $datas['id'],"info"));
                  return;
                  break;
                // Unblock
                case 'unban':
                  #validate all user case
//                  if ($parser->whom == 'all') {
//                    event(new InfoCommand('Cannot unblock all user.', $datas['user'], "chatroom", $datas['id']));
//                    return;
//                  }
                  /** check permissions**/
                  $access = false;
                  if(!$access && $parser->sender->id == config('usersystem.super_admin_uid')) {
                    $access = true;
                  } // Super Admin
                  if(!$access && $parser->sender->can('unblock any user from any room')) {
                    $access = true;
                  } // has permission [global]
                  if(!$access && $chatroom->user_id == $parser->sender->id) {
                    $access = true;
                  } // own room
                  if(!$access && $chatroom->moderators->contains($parser->sender->id)) {
                    $access = true;
                  } // is moderator
                  #validate access
                  if(!$access) {
                    event(new InfoCommand("You don't have permission to unban member.", $datas['user'], "chatroom", $datas['id']));
                    return;
                  } // Throw exception
                  #validate user if existed
                  $receiver = User::where('username','=', $parser->whom)->first();
                  if(!$receiver) {
                    event(new InfoCommand("User not found.", $datas['user'], "chatroom", $datas['id']));
                    return;
                  } // Invalid user
                  $parser->receiver = $receiver;
                  #validate user is blocked previously
                  if(!$chatroom->blockedMembers->contains($receiver->id)) {
                    event(new InfoCommand($parser->receiver->username." is not banned yet.", $datas['user'], "chatroom", $datas['id']));
                    return;
                  } // is blocked?
                  /** Everything Good */
                  $parser->parseUnBlock();
                  $chatroom->blockedMembers()->detach($parser->receiver->id); // Remove to blocked member
                  $message_group = "infomessage";
                  break;
                // Add moderator
                case 'mod':
                  // Check for permission
                  $access = false;
                  if($parser->sender->can('add moderator in any chatroom')) {
                    $access = true;
                  } // Global permission
                  if(!$access && $parser->sender->id == $chatroom->user_id) {
                    $access = true;
                  } // Own chatrooom
                  if(!$access && $parser->sender->id == config('usersystem.super_admin_uid')) {
                    $access = true;
                  } // Super admin
                  if(!$access) {
                    event(new InfoCommand("Cannot add moderator. You don't have permission", $datas['user'], "chatroom", $datas['id']));
                    return;
                  } // Not access, throw exception
                  // Validate user
                  $receiver = User::where('username','=', $parser->whom)->first();
                  if(!$receiver) {
                    event(new InfoCommand('User not found.', $datas['user'], "chatroom", $datas['id']));
                    return;
                  } // Invalid user, throw Exception
                  /** Everything Good? */
                  $parser->receiver = $receiver;
                  $parser->parseAddModerator();
                  # finally add him as moderator
                  if(!$chatroom->moderators->contains($parser->receiver->id)) {
                    $message_group = "infomessage";
                    $chatroom->moderators()->attach($parser->receiver);
                  } // First time moderator
                  else {
                    event(new InfoCommand($parser->receiver->username." is already moderator", $datas['user'], "chatroom", $datas['id']));
                    return;
                  }
                  break;
                // Remove from Moderator
                case 'demod':
                  // Check for permission
                  $access = false;
                  if($parser->sender->can('remove moderator in any chatroom')) {
                    $access = true;
                  } // Global permission
                  if(!$access && $parser->sender->id == $chatroom->user_id) {
                    $access = true;
                  } // Own chatrooom
                  if(!$access && $parser->sender->id == config('usersystem.super_admin_uid')) {
                    $access = true;
                  } // Super admin
                  if(!$access) {
                    event(new InfoCommand("Cannot remove moderator. You don't have permission", $datas['user'], "chatroom", $datas['id']));
                    return;
                  } // Not access, throw exception
                  // Validate user
                  $receiver = User::where('username','=', $parser->whom)->first();
                  if(!$receiver) {
                    event(new InfoCommand('User not found.', $datas['user'], "chatroom", $datas['id']));
                    return;
                  } // Invalid user, throw Exception
                  /** Everything Good? */
                  $parser->receiver = $receiver;
                  $parser->parseRemoveModerator();
                  # finally remove him from moderator if previously existed
                  if($chatroom->moderators->contains($parser->receiver->id)) {
                    $message_group = "infomessage";
                    $chatroom->moderators()->detach($parser->receiver);
                  }
                  else {
                    event(new InfoCommand($parser->receiver->username." is not moderator", $datas['user'], "chatroom", $datas['id']));
                    return;
                  }
                  break;
                // Add / Off announcement
                case 'announce':
                  $access = false;
                  if($parser->sender->can('add or remove announcement in any chatroom')) {
                    $access = true;
                  } // Global permission
                  if(!$access && $parser->sender->id == $chatroom->user_id) {
                    $access = true;
                  } // Own chatrooom
                  if(!$access && $parser->sender->id == config('usersystem.super_admin_uid')) {
                    $access = true;
                  } // Super admin
                  if(!$access && $chatroom->moderators->contains($parser->sender->id)) {
                    $access = true;
                  } // is moderator
                  if(!$access) {
                    event(new InfoCommand("Cannot send announcement. Only owner can add announcement.", $datas['user'], "chatroom", $datas['id']));
                    return;
                  } // Not access, throw exception
                  # Everything good?
                  # find command if to off it
                  if($parser->without_command_text == "off") {
                    # check if announcement exits
                    if($chatroom->announcement == null) {
                      event(new InfoCommand("There is no running announcement to turn off.", $datas['user'], "chatroom", $datas['id']));
                      return;
                    }
                    // Everything good?
                    $chatroom->announcement = null;
                    $chatroom->save();
                    event(new InfoCommand("Announcement cleared.", $parser->sender->id, "chatroom",$chatroom->id, "info"));
                    return;
                  } else {
                    if($chatroom->announcement != null) {
                      event(new InfoCommand("There is running announcement. Turn it off to add new announcement.", $parser->sender->id, "chatroom",$chatroom->id));
                      return;
                    } // validate announcement
                    if(strlen($parser->without_command_text) > 350) {
                      event(new InfoCommand("You cannot add text more than 350 character.", $parser->sender->id, "chatroom",$chatroom->id));
                      return;
                    } // validate announcement character
                    $chatroom->announcement = $parser->without_command_text;
                    $chatroom->save();
                    $message_group = "announcement";
                    $parser->parseAnnouncement();
                  }
                  break;
                // Mute
                case 'mute':
                  # validate all user case
//                  if ($parser->whom == 'all') {
//                    event(new InfoCommand('Cannot mute all user.', $datas['user'], "chatroom", $datas['id']));
//                    return;
//                  }
                  # validate self muting
                  if ($parser->whom == $parser->sender->username) {
                    event(new InfoCommand('You cannot mute yourself.', $datas['user'], "chatroom", $datas['id']));
                    return;
                  }
                  #validate muting user
                  /** check permissions**/
                  $access = false;
                  if(!$access && $parser->sender->id == config('usersystem.super_admin_uid')) {
                    $access = true;
                  } // Super Admin
                  if(!$access && $parser->sender->can('mute any user from any room')) {
                    $access = true;
                  } // has permission [global]
                  if(!$access && $chatroom->user_id == $parser->sender->id) {
                    $access = true;
                  } // own room
                  if(!$access && $chatroom->moderators->contains($parser->sender->id)) {
                    $access = true;
                  } // is moderator
                  #validate access
                  if(!$access) {
                    event(new InfoCommand('You don\'t have permission to mute member.', $datas['user'], "chatroom", $datas['id']));
                    return;
                  } // Throw exception
                  #validate user if existed
                  $receiver = User::where('username','=', $parser->whom)->first();
                  if(!$receiver) {
                    event(new InfoCommand('User not found.', $datas['user'], "chatroom", $datas['id']));
                    return;
                  } // Invalid user
                  $parser->receiver = $receiver;
                  # owner validation
                  if($chatroom->user_id == $receiver->id) {
                    event(new InfoCommand("Chatroom owner cannot be muted.", $datas['user'],"chatroom",$datas['id']));
                    return;
                  }
                  # validate if user is moderator
                  if($chatroom->moderators->contains($receiver->id)) {
                    event(new InfoCommand("Moderators cannot be muted.", $datas['user'],"chatroom",$datas['id']));
                    return;
                  }
                  # validate if receiver cannot be kicked
                  if($receiver->can('never be muted in any chatroom')) {
                    event(new InfoCommand("This user cannot be muted.", $datas['user'],"chatroom",$datas['id']));
                    return;
                  }
                  #validate user is in chatroom
                  if(!$chatroom->members->contains($receiver->id)) {
                    event(new InfoCommand('User is not in chatroom', $datas['user'], "chatroom", $datas['id']));
                    return;
                  }
                  #validate user is first time kicked
                  if($chatroom->mutedMembers->contains($receiver->id)) {
                    event(new InfoCommand($parser->receiver->username." is recently muted.", $datas['user'], "chatroom", $datas['id']));
                    return;
                  }
                  /** Everything Good */
                  $parser->parseMute(); // Parse kick message for message history
                  $chatroom->mutedMembers()->attach($parser->receiver->id); // Save to kicked list
                  $message_group = "infomessage";
                  break;
                // Mute
                case 'unmute':
                  # validate all user case
//                  if ($parser->whom == 'all') {
//                    event(new InfoCommand('Cannot unmute all user.', $datas['user'], "chatroom", $datas['id']));
//                    return;
//                  }
                  # validate self unmuting
                  if ($parser->whom == $parser->sender->username) {
                    event(new InfoCommand('You cannot unmute yourself.', $datas['user'], "chatroom", $datas['id']));
                    return;
                  }
                  #validate kicking user
                  /** check permissions**/
                  $access = false;
                  if(!$access && $parser->sender->id == config('usersystem.super_admin_uid')) {
                    $access = true;
                  } // Super Admin
                  if(!$access && $parser->sender->can('unmute any user from any room')) {
                    $access = true;
                  } // has permission [global]
                  if(!$access && $chatroom->user_id == $parser->sender->id) {
                    $access = true;
                  } // own room
                  if(!$access && $chatroom->moderators->contains($parser->sender->id)) {
                    $access = true;
                  } // is moderator
                  #validate access
                  if(!$access) {
                    event(new InfoCommand('You don\'t have permission to unmute member.', $datas['user'], "chatroom", $datas['id']));
                    return;
                  } // Throw exception
                  #validate user if existed
                  $receiver = User::where('username','=', $parser->whom)->first();
                  if(!$receiver) {
                    event(new InfoCommand('User not found.', $datas['user'], "chatroom", $datas['id']));
                    return;
                  } // Invalid user
                  $parser->receiver = $receiver;
                  #validate user is first time kicked
                  if(!$chatroom->mutedMembers->contains($receiver->id)) {
                    event(new InfoCommand($parser->receiver->username." is not muted yet.", $datas['user'], "chatroom", $datas['id']));
                    return;
                  }
                  /** Everything Good */
                  $parser->parseUnmute(); // Parse kick message for message history
                  $chatroom->mutedMembers()->detach($parser->receiver->id); // Save to kicked list
                  $message_group = "infomessage";
                  break;
                // silence
                case 'silence':
                  $silence_time = 30;
                  /** check permissions**/
                  $access = false;
                  if(!$access && $parser->sender->id == config('usersystem.super_admin_uid')) {
                    $access = true;
                  } // Super Admin
                  if(!$access && $parser->sender->can('silence any room')) {
                    $access = true;
                  } // has permission [global]
                  if(!$access && $chatroom->user_id == $parser->sender->id) {
                    $access = true;
                  } // own room
                  if(!$access && $chatroom->moderators->contains($parser->sender->id)) {
                    $access = true;
                  } // is moderator
                  #validate access
                  if(!$access) {
                    event(new InfoCommand('You don\'t have permission to silence this room.', $datas['user'], "chatroom", $datas['id']));
                    return;
                  } // Throw exception
                  # validate if room is previously silent
                  if($chatroom->is_silent) {
                    event(new InfoCommand('This room is previously silent.', $datas['user'], "chatroom", $datas['id']));
                    return;
                  }
                  # validate whom
                  if(!empty($parser->whom) && is_numeric($parser->whom)) {
                    $silence_time = (int) $parser->whom;
                  }
                  if($silence_time > 120) {
                    event(new InfoCommand("Cannot silence room more than 2 minutes.", $datas['user'], "chatroom", $datas['id']));
                    return;
                  }
                  /** Everything Good */
                  $parser->whom = $silence_time;
                  $parser->parseSilence(); // Parse kick message for message history
                  $chatroom->is_silent = true;
                  $chatroom->save();
                  dispatch(new ChatroomJob('clear_silence', $chatroom->id, $parser->sender->id))->delay(now()->addSeconds($silence_time));
                  $message_group = "infomessage";
                  break;
                // lock
                case 'lock':
                  /** check permissions**/
                  $access = false;
                  if(!$access && $parser->sender->id == config('usersystem.super_admin_uid')) {
                    $access = true;
                  } // Super Admin
                  if(!$access && $parser->sender->can('lock any chatroom')) {
                    $access = true;
                  } // has permission [global]
                  if(!$access && $chatroom->user_id == $parser->sender->id) {
                    $access = true;
                  } // own room
                  if(!$access && $chatroom->moderators->contains($parser->sender->id)) {
                    $access = true;
                  } // is moderator
                  #validate access
                  if(!$access) {
                    event(new InfoCommand('You don\'t have permission to lock this chatroom.', $datas['user'], "chatroom", $datas['id']));
                    return;
                  } // Throw exception
                  if($chatroom->locked) {
                    event(new InfoCommand("Cannot lock this chatroom. Chatroom is already locked.", $parser->sender->id, $parser->type, $chatroom->id));
                    return;
                  }
                  $chatroom->locked = true;
                  $chatroom->save();
                  $parser->parseLocked();
                  $message_group = "infomessage";
                  break;
                case 'unlock':
                  /** check permissions**/
                  $access = false;
                  if(!$access && $parser->sender->id == config('usersystem.super_admin_uid')) {
                    $access = true;
                  } // Super Admin
                  if(!$access && $parser->sender->can('unlock any chatroom')) {
                    $access = true;
                  } // has permission [global]
                  if(!$access && $chatroom->user_id == $parser->sender->id) {
                    $access = true;
                  } // own room
                  if(!$access && $chatroom->moderators->contains($parser->sender->id)) {
                    $access = true;
                  } // is moderator
                  #validate access
                  if(!$access) {
                    event(new InfoCommand('You don\'t have permission to unlock chatroom.', $datas['user'], "chatroom", $datas['id']));
                    return;
                  } // Throw exception
                  if(!$chatroom->locked) {
                    event(new InfoCommand("Cannot unlock this chatroom. Chatroom is not locked.", $parser->sender->id, $parser->type, $chatroom->id));
                    return;
                  }
                  $chatroom->locked = false;
                  $chatroom->save();
                  $parser->parseUnLocked();
                  $message_group = "infomessage";
                  break;
                // Chatroom edit
                case 'description':
                  $desc = trim($parser->without_command_text);
                  /** check permissions**/
                  $access = false;
                  if(!$access && $parser->sender->id == config('usersystem.super_admin_uid')) {
                    $access = true;
                  } // Super Admin
                  if(!$access && $parser->sender->can('change any chatroom description')) {
                    $access = true;
                  } // has permission [global]
                  if(!$access && $chatroom->user_id == $parser->sender->id) {
                    $access = true;
                  } // own room
                  if(!$access && $chatroom->moderators->contains($parser->sender->id)) {
                    $access = true;
                  } // is moderator
                  #validate access
                  if(!$access) {
                    event(new InfoCommand('You don\'t have permission to change description of this chatroom.', $parser->sender->id, "chatroom", $datas['id']));
                    return;
                  } // Throw exception
                  if(empty($desc)) {
                    event(new InfoCommand('Description cannot be empty.', $datas['user_id'], "chatroom", $datas['id']));
                    return;
                  }
                  if(strlen($desc) > 400) {
                    event(new InfoCommand('Description text cannot be more than 400 characters.', $datas['user_id'], "chatroom", $datas['id']));
                    return;
                  }
                  # All Good
                  $chatroom->description = $desc;
                  $chatroom->save();
                  event(new InfoCommand('Description has been changed. Thank you.', $parser->sender->id, "chatroom", $datas['id'],"info"));
                  return;
                  break;
                  default:
                  if($parser->isGameCommand()) {
                    $parser->parseGameCommand($chatroom);
                    return;
                  }
                  if(!$parser->parseHelperCommand($chatroom)) {
                    $parser->parseCommonCommand($chatroom);
                  }
                  return;
              }
            }
            elseif ($parser->game_command_found) {
              if($chatroom->game != null && $chatroom->game->game == 'lowcard') {
                switch ($parser->command) {
                  case 'start':
                    $amount = $parser->whom;
                    if(empty($amount)) {
                      $amount = 10.00;
                    }
                    dispatch(new GameJob('start game','chatroom',$chatroom, $parser->sender, (object)[
                      'amount' => $amount
                    ]))->onQueue('high');
                    break;
                  case 'j':
                    dispatch(new GameJob('join game','chatroom', $chatroom, $parser->sender))->onQueue('high');
                    break;
                  case 'd':
                    dispatch(new GameJob('draw round','chatroom', $chatroom, $parser->sender))->onQueue('high');
                    break;
                  default:
                    event(new InfoCommand("Invalid command.", $parser->sender->id, 'chatroom',$chatroom->id));
                }
              }
              if($chatroom->game != null && $chatroom->game->game == 'cricket') {
                switch ($parser->command) {
                  case 'start':
                    $amount = $parser->whom;
                    if(empty($amount)) {
                      $amount = 10.00;
                    }
                    dispatch(new CricketGameJob('start game','chatroom',$chatroom, $parser->sender, (object)[
                      'amount' => $amount
                    ]))->onQueue('game');
                    break;
                  case 'j':
                    dispatch(new CricketGameJob('join game','chatroom', $chatroom, $parser->sender))->onQueue('high');
                    break;
                  case 'd':
                    dispatch(new CricketGameJob('draw round','chatroom', $chatroom, $parser->sender))->onQueue('high');
                    break;
                  default:
                    event(new InfoCommand("Invalid command.", $parser->sender->id, 'chatroom',$chatroom->id));
                }
              }
              if($chatroom->game != null && $chatroom->game->game == 'dice-1') {
                switch ($parser->command) {
                  case 'start':
                    dispatch(new DiceGameJob('start game','chatroom', $chatroom, $parser->sender))->onQueue('high');
                    break;
                  case 'b':
                    $additional_data = (object) [
                      'bet_on' => strtoupper($parser->whom),
                      'amount' => $parser->name,
                    ];
                     dispatch(new DiceGameJob('do bet','chatroom', $chatroom, $parser->sender, $additional_data))->onQueue('high');
                    break;
                  default:
                    event(new InfoCommand("Invalid command.", $parser->sender->id, 'chatroom',$chatroom->id));
                }
              }
              if($chatroom->game != null && $chatroom->game->game == 'lucky7') {
                switch ($parser->command) {
                  case 'start':
                    dispatch(new LuckSeven('start game','chatroom', $chatroom, $parser->sender))->onQueue('high');
                    break;
                  case 'b':
                    $additional_data = (object) [
                      'bet_on' => strtoupper($parser->whom),
                      'amount' => (float) $parser->name,
                    ];
                     dispatch(new LuckSeven('do bet','chatroom', $chatroom, $parser->sender, $additional_data))->onQueue('high');
                    break;
                  default:
                    event(new InfoCommand("Invalid command.", $parser->sender->id, 'chatroom',$chatroom->id));
                }
              }
              if($chatroom->game == null) {
                event(new InfoCommand("Invalid game command.", $parser->sender->id, $parser->type, $chatroom->id));
              }
              return;
            }
            else {
              $parser->parseEmoticons();
            }
          }
          catch (\Exception $e) {
            throw new RoomCommandException($e);
          }
          # Finally add message when parser says to insert
          if($parser->chatroom_update) {
            $message_type = "";
            if(empty($message_type) && !empty($message_group)) {
              $message_type = $message_group;
            }
            if(empty($message_type) && $parser->command_found) {
              $message_type = $parser->command;
            }
            if(empty($message_type)) {
              $message_type = "message";
            }
            if(empty($parser->formatted_text)) {
              event(new InfoCommand("Invalid command.", $datas['user'], "chatroom", $datas['id']));
            }
            $extra_info = array_merge(
              ["emojies" => $parser->emojies],
              is_object($datas['extra']) ? (array) $datas['extra'] : []);
            $chatroom->messages()->create([
              'type' => $message_type,
              'raw_text' => $parser->raw_text,
              'full_text' => $parser->full_text,
              'formatted_text' => $parser->formatted_text,
              'user_id' => $datas['user'],
              'extra_info' => $extra_info
            ]);
          }
          $this->info("Message sent. :)");
        }
      } else {
        $this->error("Message cannot be sent. :(");
      }
    }
    if ($this->argument('op') == "join") {
      $datas = [
        'id' => $this->option('id'),
        'pass' => $this->option('p'),
        'user' => $this->option('user'),
      ];
      if (empty($datas['id'])) {
        $this->error("The id of room cannot be null");
        return;
      }
      $chatroom = Chatroom::where('id', '=', $datas['id'])->first();
      if($chatroom->password == $datas['pass']) {
        $user = User::find($datas['user']);
        /** All Good */
        $parser = new Parser("join", $user);
        $parser->parseJoinRoom();
        if(!$chatroom->members->contains($datas['user'])) {
          $chatroom->join($datas['user']); // Join user
        }
        if(!$parser->sender->can('enter left secretly in room')) {
          $chatroom->messages()->create([
            'type' => 'roomjoin',
            'raw_text' => $parser->raw_text,
            'full_text' => $parser->full_text,
            'formatted_text' => $parser->formatted_text,
            'user_id' => $parser->sender->id
          ]);
        }
        // room message added
        $this->info("Successfully joined. :)");
      } // Password matches
      else {
        throw new RoomCommandException("Cannot join chatroom. Incorrect password.");
      } // Throw Invalid password error
    }
    if ($this->argument('op') == "leave") {
      $datas = [
        'id' => $this->option('id'),
        'pass' => $this->option('p'),
        'user' => $this->option('user'),
      ];
      if (empty($datas['id'])) {
        $this->error("The id of room cannot be null");
        return;
      }
      $chatroom = Chatroom::withCount('members')->where('id', '=', $datas['id'])->first();
      if($chatroom->password == $datas['pass']) {
        /** All Good */
        if($chatroom->members->contains($datas['user'])) {
          $user = User::find($datas['user']);
          $parser = new Parser("leave", $user);
          $parser->parseLeaveRoom();
          $chatroom->leave($user, $parser->formatted_text); // Leave user
        }
        $this->info("Successfully left. :)");
      } // Password matches
      else {
        throw new RoomCommandException("Cannot leave chatroom. Incorrect password.");
      } // Throw Invalid password error
    }
  }

  /**
   * Get the console command arguments.
   *
   * @return array
   */
  protected function getArguments()
  {
    return [
    ];
  }

  /**
   * Get the console command options.
   *
   * @return array
   */
  protected function getOptions()
  {
    return [

    ];
  }
}
