<?php

namespace Modules\Chatroom\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\UserSystem\Entities\User;

class Chatroom extends Model {
  protected $fillable = [];
  protected $appends = ['capacity'];
  public function members() {
    return $this->belongsToMany("\\Modules\\UserSystem\\Entities\\User","chatroom_users","chatroom_id","user_id");
  }
  public function recentUser() {
    return $this->belongsToMany("\\Modules\\UserSystem\\Entities\\User","chatroom_recent_visited","chatroom_id","user_id");
  }
  public function favouritesOf() {
    return $this->belongsToMany("\\Modules\\UserSystem\\Entities\\User","chatroom_favourites","chatroom_id","user_id");
  }
  public function moderators() {
    return $this->belongsToMany("\\Modules\\UserSystem\\Entities\\User","chatroom_moderators","chatroom_id","user_id");
  }
  public function blockedMembers() {
    return $this->belongsToMany("\\Modules\\UserSystem\\Entities\\User","chatroom_blocked_users","chatroom_id","user_id");
  }
  public function mutedMembers() {
    return $this->belongsToMany("\\Modules\\UserSystem\\Entities\\User","chatroom_muted_users","chatroom_id","user_id");
  }
  public function kickedMembers() {
    return $this->belongsToMany("\\Modules\\UserSystem\\Entities\\User","chatroom_kicked_users","chatroom_id","user_id");
  }
  public function scopeRegistrations($query, $before = 0) {
    return $query->whereDate('created_at', '=', today()->subDays($before))->count();
  }
  public function getCapacityAttribute() {
    if($this->id == 739) {
      return 5000;
    }
    if($this->id == 469) {
      return 50;
    }
    if($this->id == 470) {
      return 50;
    }

    $user_level = DB::table('levels')->select(['value'])->where('user_id','=',$this->user_id)->orderBy('id','DESC')->first();
    $level = $user_level->value;
    if($level < 10) {
      return 25;
    } elseif ($level < 25) {
      return 40;
    } else if($level < 75) {
      return 60;
    } else {
      return 80;
    }
  }
  public function scopeJoin($query, $user) {
    $this->members()->attach($user);
  }
  public function scopeLeave($query, $user, $message = null) {
    $this->members()->detach($user);
    if($message != null) {
      if(is_object($user)) {
        if($user->can('enter left secretly in room')) {

        } else {
          $this->messages()->create([
            'type' => 'roomleave',
            'raw_text' => $message,
            'full_text' => $message,
            'formatted_text' => $message,
            'user_id' => $user->id,
          ]); // room message added
        }
      } else {
        $user = User::where('id','=', $user)->first();
        if($user->can('enter left secretly in room')) {

        } else {
          $this->messages()->create([
            'type' => 'roomleave',
            'raw_text' => $message,
            'full_text' => $message,
            'formatted_text' => $message,
            'user_id' => $user->id,
          ]); // room message added
        }
      }
    }
  }
  public function messages() {
    return $this->morphMany('\\Modules\\Chat\\Entities\\Message', 'messageable');
  }
  public function gifts() {
    return $this->hasMany('\\Modules\\Gift\\Entities\\Gift','type_id','id')->where('type','=','chatroom');
  }
  public function user() {
    return $this->belongsTo('\\Modules\\UserSystem\\Entities\\User');
  }
  public function game() {
    return $this->hasOne('\\Modules\\Games\\Entities\\Game','owner_id','id')->where('type','=','chatroom');
  }
}
