<?php

namespace Modules\TextParser;

use Illuminate\Support\Facades\Cache;
use Modules\ChatMini\Events\InfoCommand;
use Modules\Chatroom\Events\SendMessage;
use Modules\Games\Jobs\CricketGameJob;
use Modules\Games\Jobs\DiceGameJob;
use Modules\Games\Jobs\GameJob;
use Modules\Games\Jobs\LuckSeven;
use Modules\Notifications\Jobs\NotificationJob;
use Modules\UserSystem\Entities\User;
use Spatie\Regex\MatchResult;
use Spatie\Regex\Regex;

class Parser {
  private $text;
  public $raw_text;
  public $formatted_text;
  public $full_text;
  public $without_command_text;
  public $without_emoticons_text;
  public $gift;

  public $command;
  public $whom;

  public $name;
  public $command_found = false;
  public $game_command_found = false;

  public $sender = null;
  public $receiver = null;

  public $valid_name = false;

  public $type = 'chatroom';
  public $chatroom_update = true;

  public $receivers = [];

  public $emojies = [];

  public function __construct($text, $sender = false) {
    $text = trim($text);
    $this->text = $text;
    $this->raw_text = $text;
    $this->formatted_text = $text;
    $this->full_text = $text;
    $this->sender = $sender;
    $base_command = config('chatmini.primary_command');
    $game_command = config('chatmini.game_command');
    $this->command = $base_command;
    if(\Str::startsWith($this->text, $base_command)) {
      $this->command_found = true;
      $this->text = \Str::replaceFirst($base_command,'', $this->text);
      $exploded_text_without_command = explode(" ", $this->text, 2);
      $this->command = $exploded_text_without_command[0];
      $this->without_command_text = array_key_exists(1, $exploded_text_without_command) ? $exploded_text_without_command[1] : '';
      $other_command_explode = explode(" ",$this->without_command_text, 2);
      $whom = array_key_exists(0, $other_command_explode) ? $other_command_explode[0] : '';
      $gift_name = array_key_exists(1, $other_command_explode) ? $other_command_explode[1] : '';
      $this->whom = $whom;
      $this->name = $gift_name;
    }
    if(\Str::startsWith($this->text, $game_command)) {
      $this->game_command_found = true;
      $this->text = \Str::replaceFirst($game_command,'', $this->text);
      $exploded_text_without_command = explode(" ", $this->text, 2);
      $this->command = $exploded_text_without_command[0];
      $this->without_command_text = array_key_exists(1, $exploded_text_without_command) ? $exploded_text_without_command[1] : '';
      $other_command_explode = explode(" ",$this->without_command_text, 2);
      $whom = array_key_exists(0, $other_command_explode) ? $other_command_explode[0] : '';
      $gift_name = array_key_exists(1, $other_command_explode) ? $other_command_explode[1] : '';
      $this->whom = $whom;
      $this->name = $gift_name;
    }
  }
  public function formatGift() {
    if($this->whom == 'all') {
      $this->formatted_text = '{"sender":"%%sender%%","color":"%%color%%","sender_level":"%%sender_level%%","gift_name":"'.$this->gift->name.'","gift_url": "%%gift-'.$this->name.'-url%%","receivers":"%%receivers%%"}';
    } else {
      $this->formatted_text = '{"sender":"%%sender%%","color":"%%color%%","sender_level":"%%sender_level%%","gift_name":"'.$this->gift->name.'","gift_url":"%%gift-'.$this->name.'-url%%","receiver":"%%receiver%%","receiver_level":"%%receiver_level%%"}';
    }
    return $this->formatted_text;
  }
  public function natural_language_join(array $list, $conjunction = 'and') {
    $last = array_pop($list);
    if ($list) {
      return implode(', ', $list) . ' ' . $conjunction . ' ' . $last;
    }
    return $last;
  }
  public function parseGift() {
    $this->formatGift();
    $this->parseBasic();
    if($this->valid_name) {
      #s Super Admin
      if($this->sender->id == config('usersystem.super_admin_uid')) {
        $this->gift->price = 0;
      }
      #Parsing
      $patterns = [
        "/%%gift-".$this->gift->name."%%/" => '-',
        "/%%gift-".$this->gift->name."-url%%/i" => getImageUrl($this->gift->gift_image),
      ];
      if($this->receiver != null) {
        $patterns['/%%receiver_level%%/'] = $this->receiver->level->value;
        $patterns['/%%receiver%%/'] = $this->receiver->username;
      }
      if(count($this->receivers) > 0) {
        $receivers = [];
        foreach ($this->receivers as $receiver) {
          if(count($receivers) == 8) {
            $receivers[] = count($this->receivers) - 8 ." others";
            break;
          }
          $receivers[] = $receiver->username;
        }
        $patterns['/%%receivers%%/'] = $this->natural_language_join($receivers, "and");
      }
      if($this->gift->price > 10) {
        $patterns['/%%color%%/'] = $this->gift->color;
      } else {
        if($this->gift->price > 2) {
          $patterns['/%%color%%/'] = "#E6397F";
        } else {
          $patterns['/%%color%%/'] = "#000000";
        }
      }
      $this->formatted_text = Regex::replace(array_keys($patterns), array_values($patterns), $this->formatted_text)->result();
    }
  }
  public function formatKick() {
    $this->formatted_text = "%%sender%%[%%sender_level%%] kicked %%receiver_username%%[%%receiver_level%%] from this %%type%%";
  }
  public function formatMute() {
    $this->formatted_text = "%%sender%%[%%sender_level%%] muted %%receiver_username%%[%%receiver_level%%] from this %%type%%";
  }
  public function formatSilence() {
    $this->formatted_text = "This %%type%% is silenced for %%whom%%s";
  }
  public function formatLock() {
    $this->formatted_text = "%%sender%%[%%sender_level%%] locked this %%type%%";
  }
  public function formatUnLock() {
    $this->formatted_text = "%%sender%%[%%sender_level%%] unlocked this %%type%%";
  }
  public function formatUnmute() {
    $this->formatted_text = "%%sender%%[%%sender_level%%] unmuted %%receiver_username%%[%%receiver_level%%] from this %%type%%";
  }
  public function formatBlock() {
    $this->formatted_text = "%%sender%%[%%sender_level%%] banned %%receiver_username%%[%%receiver_level%%] from this %%type%%. Reason: Spamming in the %%type%%";
  }
  public function formatGroupJoin() {
    $this->formatted_text = "%%whom%% is added by %%sender%% in this %%type%%";
  }
  public function formatGroupLeave() {
    $this->formatted_text = "%%sender%%[%%sender_level%%] left this %%type%%";
  }
  public function formatUnBlock() {
    $this->formatted_text = "%%sender%%[%%sender_level%%] unbanned %%receiver_username%%[%%receiver_level%%] from this %%type%%. Reason: Giving user last chance.";
  }
  public function formatAnnouncement() {
    $this->formatted_text = "%%announcement%%";
  }
  public function formatJoinRoom() {
    $this->formatted_text = "%%sender%%[%%sender_level%%] has entered";
  }
  public function formatLeaveRoom($text = null) {
    if($text == null) {
      $this->formatted_text = "%%sender%%[%%sender_level%%] has left";
    } else {
      $this->formatted_text = $text;
    }
  }
  public  function parseAnnouncement() {
    $this->formatAnnouncement();
    $this->parseBasic();
    $patterns = [
      '/%%announcement%%/' => $this->without_command_text
    ];
    $this->formatted_text = Regex::replace(array_keys($patterns), array_values($patterns), $this->formatted_text)->result();
  }
  public function parseKick() {
    $this->formatKick();
    $this->parseBasic();
    $patterns = [];
    $this->formatted_text = Regex::replace(array_keys($patterns), array_values($patterns), $this->formatted_text)->result();
  }
  public function parseMute() {
    $this->formatMute();
    $this->parseBasic();
    $patterns = [];
    $this->formatted_text = Regex::replace(array_keys($patterns), array_values($patterns), $this->formatted_text)->result();
  }
  public function parseSilence() {
    $this->formatSilence();
    $this->parseBasic();
    $patterns = [];
    $this->formatted_text = Regex::replace(array_keys($patterns), array_values($patterns), $this->formatted_text)->result();
  }
  public function parseLocked() {
    $this->formatLock();
    $this->parseBasic();
    $patterns = [];
    $this->formatted_text = Regex::replace(array_keys($patterns), array_values($patterns), $this->formatted_text)->result();
  }
  public function parseUnLocked() {
    $this->formatUnLock();
    $this->parseBasic();
    $patterns = [];
    $this->formatted_text = Regex::replace(array_keys($patterns), array_values($patterns), $this->formatted_text)->result();
  }
  public function parseUnmute() {
    $this->formatUnmute();
    $this->parseBasic();
    $patterns = [];
    $this->formatted_text = Regex::replace(array_keys($patterns), array_values($patterns), $this->formatted_text)->result();
  }
  public function parseBlock() {
    $this->formatBlock();
    $this->parseBasic();
    $patterns = [];
    $this->formatted_text = Regex::replace(array_keys($patterns), array_values($patterns), $this->formatted_text)->result();
  }
  public function parseGroupJoin() {
    $this->formatGroupJoin();
    $this->parseBasic();
    $patterns = [];
    $this->formatted_text = Regex::replace(array_keys($patterns), array_values($patterns), $this->formatted_text)->result();
  }
  public function parseGroupLeave() {
    $this->formatGroupLeave();
    $this->parseBasic();
    $patterns = [];
    $this->formatted_text = Regex::replace(array_keys($patterns), array_values($patterns), $this->formatted_text)->result();
  }
  public function parseJoinRoom() {
    $this->formatJoinRoom();
    $this->parseBasic();
    $patterns = [];
    $this->formatted_text = Regex::replace(array_keys($patterns), array_values($patterns), $this->formatted_text)->result();
  }
  public function parseLeaveRoom($text = null) {
    $this->formatLeaveRoom($text);
    $this->parseBasic();
    $patterns = [];
    $this->formatted_text = Regex::replace(array_keys($patterns), array_values($patterns), $this->formatted_text)->result();
  }
  public function parseUnBlock() {
    $this->formatUnBlock();
    $this->parseBasic();
    $patterns = [];
    $this->formatted_text = Regex::replace(array_keys($patterns), array_values($patterns), $this->formatted_text)->result();
  }
  public function formatAddModerator() {
    $this->formatted_text = "%%receiver_username%%[%%receiver_level%%] is now moderator of this %%type%%.";
  }
  public function parseAddModerator() {
    $this->formatAddModerator();
    $this->parseBasic();
    $patterns = [];
    $this->formatted_text = Regex::replace(array_keys($patterns), array_values($patterns), $this->formatted_text)->result();
  }
  public function formatRemoveModerator() {
    $this->formatted_text = "%%receiver_username%%[%%receiver_level%%] is no longer moderator of this %%type%%";
  }
  public function parseRemoveModerator() {
    $this->formatRemoveModerator();
    $this->parseBasic();
    $patterns = [];
    $this->formatted_text = Regex::replace(array_keys($patterns), array_values($patterns), $this->formatted_text)->result();
  }
  public function formatEmoticons() {

  }
  public function parseEmoticons() {
    $this->formatEmoticons();
    # Get all my emoticon
//    Grab Cached Emoticons
    $matches = [];
    $all_emoticons = Cache::remember("emoji_".$this->sender->id, 300, function () {
      $user = User::with(['emoticons.emoticons'])->where('id','=', $this->sender->id)->first();
      $all_purchased_emojis = [];
      foreach ($user->emoticons as $emoticon_category) {
        foreach ($emoticon_category->emoticons as $emoticon) {
          $all_purchased_emojis[$emoticon->name] = getImageUrl($emoticon->img);
        }
      }
      return $all_purchased_emojis;
    });
    $emojies_matches = Regex::matchAll('/\(([a-z_\-.]+?)\)/', $this->formatted_text)->results();
    foreach ($emojies_matches as $matchResult) {
      if(array_key_exists($matchResult->result(), $all_emoticons)) {
        $matches[substr($matchResult->result(), 1, -1)] = [
          'img' => $all_emoticons[$matchResult->result()],
          'name' => $matchResult->result(),
        ];
      }
    }
    $this->emojies = $matches;
  }
  public function formatRoll() {
    if(strlen($this->without_command_text) > 0) {
      $this->formatted_text = "**%%sender%% rolled %%rand%% with message \"%%message%%\"**";
    } else {
      $this->formatted_text = "**%%sender%% rolled %%rand%%**";
    }
  }
  public function formatBroadcast() {
    if(strlen($this->without_command_text) > 0) {
      $this->formatted_text = "BROADCAST BY (%%sender%%)📢📢: \"%%message%%\"";
    } else {
      $this->formatted_text = "BROADCAST BY (%%sender%%)📢📢: \"%%message%%\"";
    }
  }
  public function parseRoll() {
    $this->formatRoll();
    $this->parseBasic();
    $patterns = [
      '/%%rand%%/' => rand(0, 100),
      '/%%message%%/' => $this->without_command_text
    ];
    $this->formatted_text = Regex::replace(array_keys($patterns),array_values($patterns), $this->formatted_text)->result();
  }
  public function parseBroadcast() {
    $this->formatBroadcast();
    $this->parseBasic();
    $patterns = [
      '/%%message%%/' => $this->without_command_text
    ];
    $this->formatted_text = Regex::replace(array_keys($patterns),array_values($patterns), $this->formatted_text)->result();
  }
  public function parseBasic() {
    $patterns = [
      "/%%sender%%/" => $this->sender->username,
      "/%%sender_name%%/" => $this->sender->name,
      "/%%sender_level%%/" => $this->sender->level->value,
      "/%%all_other%%/" => $this->without_command_text,
      "/%%whom%%/" => $this->whom,
      "/%%type%%/" => $this->type,
    ];
    if(!empty($this->receiver)) {
      $patterns['/%%receiver_level%%/'] = $this->receiver->level->value;
      $patterns['/%%receiver_username%%/'] = $this->receiver->username;
    }
    $this->formatted_text = Regex::replace(array_keys($patterns), array_values($patterns), $this->formatted_text)->result();
  }


  public function isGameCommand() {
    switch ($this->command) {
      case 'bot':
        return true;
      default:
        return false;
    }
  }
  public function parseHelperCommand($model) {
    $to = $this->without_command_text;
    switch ($this->command) {
      case 'add':
        if(!empty($to)) {
          $user = User::where('username','=',$to)->first();
          $me = $this->sender;
          if(!$user) {
            event(new InfoCommand("Invalid user.", $this->sender->id, $this->type, $model->id));
            return true;
          }
          if($this->sender->id == $user->id) {
            event(new InfoCommand("You cannot be friend with yourself.", $this->sender->id, $this->type, $model->id));
            return true;
          }
          if($user) {
            if($me->isFriendWith($user)) {
              event(new InfoCommand("You are already friend with ".$user->username, $me->id, $this->type, $model->id));
              return true;
            }
            if($me->hasSentFriendRequestTo($user)) {
              event(new InfoCommand("You have already sent friend request to ".$user->username, $me->id, $this->type, $model->id));
              return true;
            }
            if($user->hasSentFriendRequestTo($me)) {
              $me->acceptFriendRequest($user);
              event(new InfoCommand("You and ".$user->username." are now friends.", $me->id, $this->type, $model->id,'info'));
              return;
            }
            #all good?
            $me->befriend($user);
            dispatch(new NotificationJob("friend_request_sent", (object)[
              'from' => $me,
              'to' => $user
            ]));
            event(new InfoCommand("Friend request sent to ".$user->username, $me->id, $this->type, $model->id,'info'));
          } else {
            event(new InfoCommand("User not found.", $me->id, $this->type, $model->id));
          }
        }
        return true;
        break;
      case 'whois':
        if(!empty($to)) {
          $user = User::where('username', '=', $to)->first();
          $me = $this->sender;
          if ($user) {
            event(new InfoCommand('Username: '.$user->username.', Level: '.$user->level->value.', Name: '.$user->name.', Gender: '.$user->gender.', Country: '.$user->country, $me->id, $this->type, $model->id,'info'));
            return true;
          } else {
            event(new InfoCommand("User ".$to." not found.", $me->id, $this->type, $model->id));
            return true;
          }
        }
        break;
      default:
        return false;
    }
  }
  public function parseCommonCommand($model) {
    $to = $this->without_command_text;
    $this->formatted_text = '';
    switch ($this->command) {
      case 'hug':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% gives everyone a big hug**";
        } else {
          $this->formatted_text = "**%%sender%% gives %%all_other%% a big hug**";
        }
        break;
      case 'bearhug':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% is getting ready to bearhug**";
        } else {
          $this->formatted_text = "**%%sender%% gives %%all_other%% a great, big, bone-crushing bearhug**";
        }
        break;
      case 'faint':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% faints**";
        } else {
          $this->formatted_text = "**%%sender%% faints on %%all_other%% shoulder**";
        }
        break;
      case 'bored':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% is extremely bored**";
        } else {
          $this->formatted_text = "**%%sender%% is bored by %%all_other%%**";
        }
        break;
      case 'yawn':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% yawns. How dull.**";
        } else {
          $this->formatted_text = "**%%sender%% yawns at %%all_other%%. How dull.**";
        }
        break;
      case 'sing':
        $song = commandSong();
        $this->formatted_text = "**%%sender%% sings a song. ".$song."**";
        break;
      case 'cry':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% is crying**";
        } else {
          $this->formatted_text = "**%%sender%% is crying over %%all_other%%'s shoulder**";
        }
        break;
      case 'cringe':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% cringes in terror**";
        } else {
          $this->formatted_text = "**%%sender%% away from %%all_other%%, terrified**";
        }
        break;
      case 'congrat':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% congratulates to all**";
        } else {
          $this->formatted_text = "**%%sender%% congratulates %%all_other%%**";
        }
        break;
      case 'confuse':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% is totally confused**";
        } else {
          $this->formatted_text = "**%%sender%% is confused with %%all_other%%. What are you on about?**";
        }
        break;
      case 'comfort':
        if(!empty($to)) {
          $this->formatted_text = "**%%sender%% comforts %%all_other%%**";
        }
        break;
      case 'cmon':
        if(!empty($to)) {
          $this->formatted_text = "**%%sender%% tells %%all_other%% to get a move on. ‘Cmon’**";
        }
        break;
      case 'doh':
        $this->formatted_text = '%%sender%% blinks, then slaps forehead and screams ‘DOH’';
        break;
      case 'chuckle':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% chuckles politely**";
        } else {
          $this->formatted_text = "**%%sender%% chuckles at %%all_other%%**";
        }
        break;
      case 'cheer':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% cheers**";
        } else {
          $this->formatted_text = "**%%sender%% enthusiastically cheers for %%all_other%%**";
        }
        break;
      case 'chicken':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% lets out a ‘be-GAWK!’ as though black_angelss were a chicken getting its tail feather plucked**";
        } else {
          $this->formatted_text = "**%%sender%% stares at %%all_other%%, then suddenly lets loose with a loud and pierching ‘be- GAWK!’**";
        }
        break;
      case 'droll':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% starts drooling all over the place**";
        } else {
          $this->formatted_text = "**%%sender%% drools all over %%all_other%%. Yuckz**";
        }
        break;
      case 'drunk':
        if(!empty($to)) {
          $this->formatted_text = "**%%sender%% calls out “I’m SOOOOOOOOO drunk!”**";
        }
        break;
      case 'duck':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% ducks out of the way**";
        } else {
          $this->formatted_text = "**%%sender%% ducks down hide from %%all_other%%**";
        }
        break;
      case 'eek':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% leaps onto a table and screams ‘Eeeek!’**";
        }
        break;
      case 'eh':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% is totally clues. Eh?**";
        } else {
          $this->formatted_text = "**%%sender%% looks at %%all_other%% VERY confused**";
        }
        break;
      case 'embarace':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% gives everyone a warm and loving embrace**";
        } else {
          $this->formatted_text = "**%%sender%% gives %%all_other%% a warm and loving embrace**";
        }
        break;
      case 'fart':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% lets off a real rip-roarer. Eeww**";
        } else {
          $this->formatted_text = "**%%sender%% lets off a stinking fart next to %%all_other%%. Eeww**";
        }
        break;
      case 'firebreath':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% performs a fire-breathing act!**";
        } else {
          $this->formatted_text = "**%%sender%% performs a fire- breathing act for %%all_other%%**";
        }
        break;
      case 'caff':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% bounces off the walls, buzzing on caffeine**";
        }
        break;
      case 'flirt':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% flirts with the room**";
        } else {
          $this->formatted_text = "**%%sender%% flirts with %%all_other%%**";
        }
        break;
      case 'burp':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% burps rudely**";
        } else {
          $this->formatted_text = "**Burpppppp! How rude %%all_other%%**";
        }
        break;
      case 'brb':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% will be right back**";
        } else {
          $this->formatted_text = "**%%sender%% tells %%all_other%% to wait a little while**";
        }
        break;
      case 'french':
        if(!empty($to)) {
          $this->formatted_text = "**%%sender%% gives %%all_other%% a deep and passionate kiss... it seems to take forever...**";
        }
        break;
      case 'frown':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% frowns in disgust**";
        } else {
          $this->formatted_text = "**%%sender%% of sender frowns at %%all_other%%**";
        }
        break;
      case 'boo':
        if(!empty($to)) {
          $this->formatted_text = "**%%sender%% runs up behind %%all_other%% and screams ‘BOOO!’**";
        }
        break;
      case 'bow':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% bow gracefully**";
        } else {
          $this->formatted_text = "**%%sender%% bows down before %%all_other%%**";
        }
        break;
      case 'blush':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% blushes a lovely shade of red**";
        } else {
          $this->formatted_text = "**%%sender%% turns away from %%all_other%% and blushes**";
        }
        break;
      case 'blowk':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% blows a kiss**";
        } else {
          $this->formatted_text = "**%%sender%% blows a kiss to %%all_other%%**";
        }
        break;
      case 'blink':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% blink eyes in disbelief!**";
        } else {
          $this->formatted_text = "**%%sender%% blink at %%all_other%% in disbelief!**";
        }
        break;
      case 'beer':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% yells “I AM THIRSTYYYYY!**";
        } else {
          $this->formatted_text = "**%%sender%% looks at %%all_other%%, pointing to the door, suggesting that it is pub time!**";
        }
        break;
      case 'gasp':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% gasps in astonishment!**";
        } else {
          $this->formatted_text = "**%%sender%% gasps at %%all_other%%**";
        }
        break;
      case 'beg':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% begs like a dog**";
        } else {
          $this->formatted_text = "**%%sender%% begs at %%all_other%%’s feet**";
        }
        break;
      case 'gee':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% can’t believe it. GEEEEEEEE.**";
        } else {
          $this->formatted_text = "**%%sender%% can’t believe what %%all_other%% just did. GEEEEEEE.**";
        }
        break;
      case 'giggle':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% giggles in a fit of uncontrollable mirth**";
        } else {
          $this->formatted_text = "**%%sender%% giggles maniacally at %%all_other%%’s manners**";
        }
        break;
      case 'bark':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% barks playfully. Ruff ruff**";
        } else {
          $this->formatted_text = "**%%sender%% barks playfully at %%all_other%%**";
        }
        break;
      case 'argh':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% screams loudly in frustration**";
        } else {
          $this->formatted_text = "**%%sender%% is frustrated with %%all_other%%. ARGHHH!**";
        }
        break;
      case 'apologize':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% apologizes**";
        } else {
          $this->formatted_text = "**%%sender%% apologizes to %%all_other%%**";
        }
        break;
      case 'agree':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% agrees**";
        } else {
          $this->formatted_text = "**%%sender%% agrees with %%all_other%%**";
        }
        break;
      case 'glare':
        if(!empty($to)) {
          $this->formatted_text = "**%%sender%% fixes %%all_other%% with an icy glare!**";
        }
        break;
      case 'gn':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% waves and takes off for dream land**";
        } else {
          $this->formatted_text = "**%%sender%% waves at %%all_other%%, “Good night!”**";
        }
        break;
      case 'greet':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% says “Hello all”**";
        } else {
          $this->formatted_text = "**%%sender%% bids %%all_other%% welcome**";
        }
        break;
      case 'grin':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% grins evilly**";
        } else {
          $this->formatted_text = "**%%sender%% grins at %%all_other%% with lust in eyes!**";
        }
        break;
      case 'groan':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% groans loudly**";
        } else {
          $this->formatted_text = "**%%sender%% groans loudly and looks at %%all_other%%**";
        }
        break;
      case 'happy':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% can’t resist smiling happily**";
        }
        break;
      case 'hfive':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% gives everyone a high five. Yeah!**";
        } else {
          $this->formatted_text = "**%%sender%% gives %%all_other%% a powerful high five. Yeaahh!**";
        }
        break;
      case 'hiccup':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% hiccups**";
        }
        break;
      case 'hmm':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% humms and umms in deep thoughts**";
        }
        break;
      case 'hold':
        if(!empty($to)) {
          $this->formatted_text = "**%%sender%% holds %%all_other%% lovingly**";
        }
        break;
      case 'honor':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% is extremely honored**";
        }
        break;
      case 'howl':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% lets loose a blood curdling howl at the moon. Hoooowwwll!**";
        }
        break;
      case 'hum':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% begins to hum. Dum dee doo..**";
        }
        break;
      case 'joy':
        if(empty($to)) {
          $this->formatted_text = "**Tears of joy form in %%sender%%'s eyes. Oh, how happy!**";
        }
        break;
      case 'kiss':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% puckers up, but who are you kissing exactly?**";
        } else {
          $this->formatted_text = "**%%sender%% kisses %%all_other%%**";
        }
        break;
      case 'lag':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% is seriously lagging. Lagmonster strikes again!**";
        }
        break;
      case 'laugh':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% laughs out loud**";
        } else {
          $this->formatted_text = "**%%sender%% laughs at %%all_other%%**";
        }
        break;
      case 'listen':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% sit down and listens to the murmur around the room**";
        } else {
          $this->formatted_text = "**%%sender%% looks at %%all_other%%, listening intently**";
        }
        break;
      case 'loser':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% admits, ‘I’m a loser!’**";
        } else {
          $this->formatted_text = "**%%sender%% points at %%all_other%% and yells ‘Loser!’**";
        }
        break;
      case 'love':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% seems to shine of internal peace, love, and happiness**";
        } else {
          $this->formatted_text = "**%%sender%% whispers to %%all_other%% sweet words of love**";
        }
        break;
      case 'martian':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% pulls out a ray gun and yells “Take me to your leader!**";
        } else {
          $this->formatted_text = "**%%sender%% pulls out a gun point to %%all_other%%, yells “Take me to your leader!**";
        }
        break;
      case 'miss':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% missed you all sooooooo much!**";
        } else {
          $this->formatted_text = "**%%sender%% is very happy to see %%all_other%% again**";
        }
        break;
      case 'mmm':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% drools. Mmmmm...**";
        }
        break;
      case 'thank':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% thanks everyone whole-heartedly**";
        } else {
          $this->formatted_text = "**%%sender%% thanks %%all_other%% whole-heartedly**";
        }
        break;
      case 'wave':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% is waving and saying BYE!!**";
        } else {
          $this->formatted_text = "**%%sender%% waves at %%all_other%%, SEE YOU!**";
        }
        break;
      case 'scream':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% screams in a mad fit**";
        } else {
          $this->formatted_text = "**%%sender%% screams at %%all_other%% - SHUT UP!**";
        }
        break;
      case 'mumble':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% mumbles incoherently**";
        }
        break;
      case 'smile':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% smiles happily**";
        }
        break;
      case 'nod':
        if(!empty($to)) {
          $this->formatted_text = "**%%sender%% nods at %%all_other%% secretly and gives approval!**";
        }
        break;
      case 'pinch':
        if(!empty($to)) {
          $this->formatted_text = "**%%sender%% pinches %%all_other%% - OUCH!!**";
        }
        break;
      case 'pock':
        if(!empty($to)) {
          $this->formatted_text = "**%%sender%% pokes %%all_other%% gracefully**";
        }
        break;
      case 'ponder':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% ponders the meaning of life**";
        }
        break;
      case 'rose':
        if(!empty($to)) {
          $this->formatted_text = "**%%sender%% blushes and gives a red rose to %%all_other%% - I love you!**";
        }
        break;
      case 'ring':
        if(!empty($to)) {
          $this->formatted_text = "**%%sender%% proposes %%all_other%% - Will you marry me?**";
        }
        break;
      case 'wink':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% winks suggestively. Ooooo...**";
        }
        break;
      case 'seduce':
        if(!empty($to)) {
          $this->formatted_text = "**%%sender%% seduces %%all_other%%, It's so hot - Let me take off my shirt**";
        }
        break;
      case 'shrug':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% shrugs and says - I don't know!**";
        }
        break;
      case 'shy':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% turns away and hides in a corner**";
        }
        break;
      case 'sigh':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% sighs with a relief**";
        }
        break;
      case 'slap':
        if(!empty($to)) {
          $this->formatted_text = "**%%sender%% slaps %%all_other%% across the face. OUCH!**";
        }
        break;
      case 'sneeze':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% sneezes and leaves the room. HACHEEW!!**";
        }
        break;
      case 'snore':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% is snoring loudly. ZZZZZZZZ...!!**";
        }
        break;
      case 'spit':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% spits on the ground**";
        } else {
          $this->formatted_text = "**%%sender%% spits on %%all_other%%**";
        }
        break;
      case 'stare':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% stares into space**";
        } else {
          $this->formatted_text = "**%%sender%% is staring deep into %%all_other%%'s eyes**";
        }
        break;
      case 'moo':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% mooes like a cow**";
        }
        break;
      case 'relax':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% thinks everyone should RELAX!**";
        }
        break;
      case 'sorry':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% apologises to everyone**";
        } else {
          $this->formatted_text = "**%%sender%% says - %%all_other%%, I am really sorry!!**";
        }
        break;
      case '8ball':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%%'s ".eightBallSong()."**";
        }
        break;
      case 'me':
        $this->formatted_text = "%%sender%% ".$to;
        break;
      case 'flames':
        if(!empty($to)) {
          $this->formatted_text = "**FLAMES - %%sender%% vs %%all_other%% = ".flamesOptions()."**";
        }
        break;
      case 'getmyluck':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%%'s Today's Luck: ".getMyLockOptions()."**";
        }
        break;
      case 'goal':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% scores a GOOOOOOAAAAAAL!**";
        }
        break;
      case 'red':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% has been sent off!**";
        }
        break;
      case 'dance':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% dances in ".getDanceOptions()."**";
        }
        break;
      case 'punch':
        if(!empty($to)) {
          $this->formatted_text = "**%%sender%% punches on %%all_other%%'s nose. OUCH!**";
        }
        break;
      case 'whistle':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% whistles appreciatively**";
        }
        break;
      case 'worship':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% falls to the ground in shameless worship of the creators from SWFTEA**";
        }
        break;
      case 'worthy':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% bows down to the ground screaming \"I'M NOT WORTHY!\"**";
        }
        break;
      case 'yeah':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% started yelling 'YEAHHHH!'**";
        }
        break;
      case 'warcry':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% lets out a loud warcry**";
        }
        break;
      case 'typo':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% fumbles fingers helplessly across the keypad**";
        }
        break;
      case 'whop':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% whips out a trusty pillow and WHOPS everyone upside da head!**";
        }
        break;
      case 'c':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%%  lets out an energetic, 'WOO HOO!**";
        }
        break;
      case 'tantrum':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% begins to scream, jumps up and down and bangs on the floor in a fot of unhappiness**";
        }
        break;
      case 'spock':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% raises an eyebrow**";
        }
        break;
      case 'smirk':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% smirks and lights a cigarette**";
        }
        break;
      case 'sick':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% is not feeling well**";
        }
        break;
      case 'shmoo':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% dances around the room, spreading love and hugs**";
        }
        break;
      case 'shiver':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% shivers from the cold. brrrrrr..**";
        }
        break;
      case 'salute':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% salutes smartly**";
        }
        break;
      case 'purr':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% purrs contentedly. MMEEEOOOWWW!**";
        }
        break;
      case 'power':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% yells, 'I just can't do it Caption! I don't have the power!'";
        }
        break;
      case 'challenge':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% challenge anyone to the Battle**";
        } else {
          $this->formatted_text = "**%%sender%% challenge %%all_other%% to Battle**";
        }
        break;
      case 'win':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% screams at the battle “I won!”**";
        } else {
          $this->formatted_text = "**%%sender%% beats %%all_other%% in the Battle**";
        }
        break;
      case 'gold':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% won a Gold Medal! Congratulations!";
        }
        break;
      case 'lose':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% lost the Battle.. Boo!**";
        } else {
          $this->formatted_text = "**%%sender%% lost to %%all_other%% in the Battle. Better luck next time**";
        }
        break;
      case 'medal':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% wants to win a Gold Medal";
        }
        break;
      case 'luck':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% screams “GoodLuck” to everyone in the Battle**";
        } else {
          $this->formatted_text = "**%%sender%% wishes %%all_other%% Good Luck for the Battle**";
        }
        break;
      case 'beibei':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% blesses everyone with Beibei Prosperity";
        }
        break;
      case 'jingjing':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% blesses everyone with Jingjing Happiness";
        }
        break;
      case 'huanhuan':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% blesses everyone with Huanhuan Passion";
        }
        break;
      case 'yingying':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% blesses everyone with Yingying Health";
        }
        break;
      case 'nini':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% spreads Nini Good Luck to everyone in the Battle";
        }
        break;
      case 'welcome':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% welcomes everyone to SWFTEA";
        }
        break;
      case 'winner':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% wonders who will win the Battle**";
        } else {
          $this->formatted_text = "**%%sender%% ask %%all_other%% “Who will win the Battle?”**";
        }
        break;
      case 'torch':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% runs with the Olympic Flame**";
        } else {
          $this->formatted_text = "**%%sender%% passes the Olympic torch to %%all_other%%**";
        }
        break;
      case 'proud':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% - I am proud of my country";
        }
        break;
      case 'taunt':
        if(!empty($to)) {
          $this->formatted_text = "**%%sender%% taunts %%all_other%% - “My country is doing better than yours”";
        }
        break;
      case 'champion':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% is the Champion";
        }
        break;
      case 'record':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% set a New World Record! in SWFTEA";
        }
        break;
      case 'frenzy':
        if(empty($to)) {
          $this->formatted_text = "**The crowd goes into a frenzy cheering for %%sender%%";
        }
        break;
      case 'hattrick':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% score a hat trick for the club";
        }
        break;
      case 'equalised':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% equalised and now is leveled up!";
        }
        break;
      case 'magnificent':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% scores a magnificent goal";
        }
        break;
      case 'milestone':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% scores a milestone goal";
        }
        break;
      case 'surprise':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% cannot believe it!";
        }
        break;
      case 'deserve':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% thinks, they deserved it";
        }
        break;
      case 'freekick':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% is a free specialist";
        }
        break;
      case 'penalty':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% won a penalty";
        }
        break;
      case 'handball':
        if(!empty($to)) {
          $this->formatted_text = "**%%sender%% is rewarded a penalty because of %%all_other%% handball";
        }
        break;
      case 'cheering':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% is cheering for the club";
        }
        break;
      case 'join':
        if(!empty($to)) {
          $this->formatted_text = "**%%sender%% and %%all_other%% joins the fan in cheering for their team";
        }
        break;
      case 'yellow':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% has booked with a yellow card!";
        }
        break;
      case 'tie':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% scores a tiebreaker!";
        }
        break;
      case 'watch':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% is going to watch the match tonight!";
        }
        break;
      case 'goldenboot':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% wins the Golden Boot Award! Congratulations!";
        }
        break;
      case 'manofthematch':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% has been chosen for today’s Man of The Match!";
        }
        break;
      case 'offside':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% scores a goal but after a referee consideration, it’s an offside. Boo !";
        }
        break;
      case 'pfa':
        if(empty($to)) {
          $this->formatted_text = "**Player of the Year belongs to %%sender%%";
        }
        break;
      case 'top':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%%'s club is top of the table at the moment";
        }
        break;
      case 'squirt':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% grabs a squirt gun and chases everyone around at hemp!**";
        } else {
          $this->formatted_text = "**%%sender%% grabs a squirt gun and squirt water all over %%all_other%%! Ha ha ha!**";
        }
        break;
      case 'tag':
        if(!empty($to)) {
          $this->formatted_text = "**%%sender%% tags %%all_other%% and dance around with glee. Shouting “YOU’IT!";
        }
        break;
      case 'censored':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% admits, ‘Im a (censored)!**";
        } else {
          $this->formatted_text = "**%%sender%% points at %%all_other%% and yells ‘(censored)!’**";
        }
        break;
      case 'showoff':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% - How do I look on my latest necklace design?";
        }
        break;
      case 'selfie':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% is asking for a selfie**";
        } else {
          $this->formatted_text = "**%%sender%% wants to take a selfie with %%all_other%%**";
        }
        break;
      case 'lunch':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% is going for a lunch**";
        }
        break;
      case 'breakfast':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% is going to have a breakfast**";
        }
        break;
      case 'dinner':
        if(empty($to)) {
          $this->formatted_text = "**%%sender%% is having a dinner**";
        }
        break;
      case 'intoxicated':
        if(empty($to)) {
          $this->formatted_text = "**GOD!! I am totally drunk**";
        } else {
          $this->formatted_text = "**HELP! %%all_other%% is totally intoxicated and out of control**";
        }
        break;
      default:
        event(new InfoCommand('Invalid '.$this->type.' command.',$this->sender->id, $this->type, $model->id));
        return;
    }
    $this->parseBasic();
    if(!empty($this->formatted_text)) {
      event(new SendMessage($this->type, $model->id, $this->sender,$this->formatted_text,'normal_quote'));
    }
    return;
  }
  public function parseGameCommand($model) {
    switch ($this->command) {
      case 'bot':
        $access = false;
        if($this->sender->can('start bot in any chatroom')) {
          $access = true;
        }
//        if(!$access && $model->user_id == $this->sender->id) {
//          $access = true;
//        }
//        if(!$access && $model->moderators->contains($this->sender->id)) {
//          $access = true;
//        }
        if($access) {
          if($this->whom == 'lowcard') {
            dispatch(new GameJob('set bot', $this->type, $model, $this->sender))->onQueue('high');
          }
          if($this->whom == 'guess') {
            dispatch(new DiceGameJob('set bot', $this->type, $model, $this->sender))->onQueue('high');
          }
          if($this->whom == 'cricket') {
            dispatch(new CricketGameJob('set bot', $this->type, $model, $this->sender))->onQueue('high');
          }
          if($this->whom == 'lucky7') {
            dispatch(new LuckSeven('set bot', $this->type, $model, $this->sender))->onQueue('high');
          }
        } else {
          event(new InfoCommand('You don\'t have permissions to add bot to this chatroom.',$this->sender->id, $this->type, $model->id));
        }
        if($this->whom == 'stop') {
          dispatch(new GameJob('clear bot', $this->type, $model, $this->sender))->onQueue('high');
        }
        break;
      default:

    }
  }
}