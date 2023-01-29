<?php

namespace Modules\Games\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\ChatMini\Events\InfoCommand;
use Modules\Games\Events\SendGameMessage;

class Game extends Model
{
    protected $fillable = [];
    protected static function boot() {
      parent::boot();
      static::created(function ($game) {
        if($game->game == "lowcard") {
          $message = 'LowCard Bot has been added to the '.$game->type;
          $bot = 'Lowcard Bot';
          event(new SendGameMessage($game->type, $game->owner_id,$bot, $message, (object) [
            'game' => 'lowcard',
            'img_width' => 17.78,
            'img_height' => 24,
          ]));

          $message = "Play LowCard. Type !start to start a new game, !start < amount > for custom entry.";
          event(new SendGameMessage($game->type, $game->owner_id,$bot, $message, (object) [
            'game' => 'lowcard',
            'img_width' => 17.78,
            'img_height' => 24,
          ]));
        }
        if($game->game == "cricket") {
          $message = 'Cricket Bot has been added to the '.$game->type;
          $bot = 'Cricket Bot';
          event(new SendGameMessage($game->type, $game->owner_id,$bot, $message, (object) [
            'game' => 'cricket',
            'img_width' => 16,
            'img_height' => 16,
          ]));

          $message = "Play Cricket. Type !start to start a new game, !start < amount > for custom entry.";
          event(new SendGameMessage($game->type, $game->owner_id,$bot, $message, (object) [
            'game' => 'cricket',
            'img_width' => 16,
            'img_height' => 16,
          ]));
        }
        if($game->game == "dice-1") {
          $message = 'Guess Bot has been added to the '.$game->type;
          $bot = 'Guess Bot';
          event(new SendGameMessage($game->type, $game->owner_id,$bot, $message, (object) [
            'game' => 'dice',
            'img_width' => 18,
            'img_height' => 18,
          ]));


          $message = 'Play Guess. Type !start to start a new round.';
          event(new SendGameMessage($game->type, $game->owner_id, $bot, $message, (object) [
            'game' => 'dice',
            'img_width' => 18,
            'img_height' => 18,
          ]));
        }
        if($game->game == "lucky7") {
          $message = 'Lucky 7 Bot has been added to the '.$game->type;
          $bot = 'Lucky 7 Bot';
          event(new SendGameMessage($game->type, $game->owner_id,$bot, $message, (object) [
            'game' => 'lucky7',
            'img_width' => 18,
            'img_height' => 18,
          ]));


          $message = 'Play Lucky 7. Type !start to start a new round.';
          event(new SendGameMessage($game->type, $game->owner_id, $bot, $message, (object) [
            'game' => 'lucky7',
            'img_width' => 18,
            'img_height' => 18,
          ]));
        }
      });
      static::updated(function ($game) {
        $changes = $game->getChanges();
        if(array_key_exists("phase", $changes))  {
          $old_phase = $game->getOriginal('phase');
          $new_phase = $game->phase;
          if ($game->game == "lowcard") {
            if($old_phase == 0 && $new_phase == 1) {
              # started
              if($game->game == "lowcard") {
                $message = 'LowCard started (#'.$game->game_id.'). !j to join, cost credits '.$game->amount.' [30 sec]';
                $bot = 'Lowcard Bot';
                event(new SendGameMessage($game->type, $game->owner_id,$bot, $message, (object) [
                  'game' => 'lowcard',
                  'img_width' => 17.78,
                  'img_height' => 24,
                ]));
              }
            }
            if($old_phase == 1 && $new_phase == 0) {
              # started
              if($game->game == "lowcard") {

              }
              $game->participants()->delete();
            }
            if($old_phase == 1 && $new_phase == 2) {
              # started
              if($game->game == "lowcard") {
                $players = $game->participants->pluck('username')->toArray();
                $message = 'Game begins. Lowest card is out! Players ('.count($players).') '.implode(", ", $players);
                $bot = 'Lowcard Bot';
                event(new SendGameMessage($game->type, $game->owner_id,$bot, $message, (object) [
                  'game' => 'lowcard',
                  'img_width' => 17.78,
                  'img_height' => 24,
                ]));
              }
            }
            if($old_phase == 3 && $new_phase == 2) {
              # started
              if($game->game == "lowcard") {

              }
            }
            if($new_phase == 0) {
              # started
              if($game->game == "lowcard") {

              }
            }
          }
          if ($game->game == "cricket") {
            if($old_phase == 0 && $new_phase == 1) {
              # started
              if($game->game == "cricket") {
                $message = 'Cricket started (#'.$game->game_id.'). !j to join, cost credits '.$game->amount.' [30 sec]';
                $bot = 'Cricket Bot';
                event(new SendGameMessage($game->type, $game->owner_id,$bot, $message, (object) [
                  'game' => 'cricket',
                  'img_width' => 16,
                  'img_height' => 16,
                ]));
              }
            }

            if($old_phase == 1 && $new_phase == 2) {
              # started
              if($game->game == "cricket") {
                $players = $game->participants->pluck('username')->toArray();
                $message = 'Game begins. Maximum run holder wins the game! Players ('.count($players).') '.implode(", ", $players);
                $bot = 'Cricket Bot';
                event(new SendGameMessage($game->type, $game->owner_id,$bot, $message, (object) [
                  'game' => 'cricket',
                  'img_width' => 16,
                  'img_height' => 16,
                ]));
              }
            }
            if($old_phase == 3 && $new_phase == 2) {
              # started
              if($game->game == "lowcard") {

              }
            }
            if($new_phase == 0) {
              # started
              if($game->game == "lowcard") {

              }
            }
          }
          if($game->game == "dice-1") {
            if($old_phase == 0 && $new_phase == 1) {
              # started
              $message = 'Guess game started ['.diceGameId($game->game_id).']. Type !b [group] [amount] to place credits. Available groups are: '.getDiceGroupsName().'. [45 seconds]';
              $bot = 'Guess Bot';
              event(new SendGameMessage($game->type, $game->owner_id,$bot, $message, (object) [
                'game' => 'dice',
                'img_width' => 18,
                'img_height' => 18,
              ]));
            }
            if($old_phase == 1 && $new_phase == 2) {
              # completed

            }
            if($old_phase == 2 && $new_phase == 0) {
              # completed

            }
          }
          if($game->game == "lucky7") {
            if($old_phase == 0 && $new_phase == 1) {
              # started
              $message = 'Lucky 7 game started ['.diceGameId($game->game_id).']. Type !b [group] [amount] to place credits. Available groups are: '.getLuckySevenGroupsName().'. [45 seconds]';
              $bot = 'Lucky 7 Bot';
              event(new SendGameMessage($game->type, $game->owner_id,$bot, $message, (object) [
                'game' => 'lucky7',
                'img_width' => 18,
                'img_height' => 18,
              ]));
            }
            if($old_phase == 1 && $new_phase == 2) {
              # completed

            }
            if($old_phase == 2 && $new_phase == 0) {
              # completed

            }
          }
        }
        if(array_key_exists("round", $changes))  {
          $old_round = $game->getOriginal('round');
          $new_round = $game->round;
          if($game->game == "lowcard") {
            if($new_round > $old_round) {

            }
          }
        }
      });
      static::deleted(function ($game) {
        if($game->game == "lowcard") {
          $message = 'LowCard Bot has been stopped by '.$game->action_man;
          $bot = 'Lowcard Bot';
          event(new SendGameMessage($game->type, $game->owner_id,$bot, $message, (object) [
            'game' => 'lowcard',
            'img_width' => 17.78,
            'img_height' => 24,
          ]));
        }
        if($game->game == "cricket") {
          $message = 'Cricket Bot has been stopped by '.$game->action_man;
          $bot = 'Cricket Bot';
          event(new SendGameMessage($game->type, $game->owner_id,$bot, $message, (object) [
            'game' => 'cricket',
            'img_width' => 16,
            'img_height' => 16,
          ]));
        }
        if($game->game == "dice-1") {
          $message = 'Guess Bot has been stopped by '.$game->action_man;
          $bot = 'Guess Bot';
          event(new SendGameMessage($game->type, $game->owner_id,$bot, $message, (object) [
            'game' => 'dice',
            'img_width' => 18,
            'img_height' => 18,
          ]));
        }
        if($game->game == "lucky7") {
          $message = 'Lucky 7 Bot has been stopped by '.$game->action_man;
          $bot = 'Luck 7 Bot';
          event(new SendGameMessage($game->type, $game->owner_id,$bot, $message, (object) [
            'game' => 'lucky7',
            'img_width' => 18,
            'img_height' => 18,
          ]));
        }
      });
    }
    public function participants() {
      return $this->hasMany('\Modules\Games\Entities\GameParticipants','game_id','id');
    }
}
