<?php

namespace Modules\UserSystem\Entities;

use App\Traits\FriendableHotFix;
use Carbon\Carbon;
use Encore\Admin\Facades\Admin;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Lexx\ChatMessenger\Traits\Messagable;
use Modules\Program\Entities\MerchantTag;
use Overtrue\LaravelLike\Traits\Liker;
use Spatie\Permission\Traits\HasRoles;
use Thomaswelton\LaravelGravatar\Facades\Gravatar;

class User extends Authenticatable
{
  use Notifiable, HasRoles, HasApiTokens, FriendableHotFix, Liker, Messagable;
  protected $fillable = [
    'username',
    'email',
    'password'
  ];
  protected $guarded = [];
  protected $hidden = [
    'password',
    'remember_token',
    'email_verified_at',
    'created_at',
    'updated_at',
    'pin',
    'email',
    'credit',
    'program_expiry',
    'tag_id',
  ];
  protected $appends = ['profile_picture','registered_since','presence','color'];
  protected $with = ['level'];
  protected $casts = [
    'avatar'
  ];

  protected static function boot() {
    parent::boot();
  }

  public function toArray() {
    if(Admin::user()) {
      $this->makeVisible($this->hidden);
    }
    return parent::toArray();
  }

  public function adminlte_image()
  {
    return $this->getProfilePictureAttribute();
  }

  public function getProfilePictureAttribute() {
    if($this->picture == NULL) {
      return Gravatar::src($this->email);
    } else {
      return $this->picture;
    }
  }
  public function getRegisteredSinceAttribute() {
    return Carbon::parse($this->created_at)->diffForHumans();
  }
  public function getColorAttribute() {
    $roles = $this->roles->pluck('name')->toArray();
    $staff = '#F18008';
    $ga = '#F7C600';
    // room admin #ffff00
    $mentor_head = '#ff00ff';
    $mentor = '#ff0000';
    $merchant = '#630094';
    $user = '#3C8DBC';
    $legends = '#2a2d7c';
    $champ = '#ffffff';

    if(in_array('Official', $roles)) {
      return $staff;
    }
    if(in_array('Global Admin', $roles)) {
      return $ga;
    }
    if(in_array('Mentor Head', $roles)) {
      return $mentor_head;
    }
    if(in_array('Mentor', $roles)) {
      return $mentor;
    }
    if(in_array('Merchant', $roles)) {
      return $merchant;
    }
    if(in_array('Legends', $roles)) {
      return $legends;
    }
    if($this->champ_till != null) {
      $champ_till = Carbon::parse($this->champ_till);
      $now = Carbon::now();
      if($champ_till->isAfter($now)) {
        return $champ;
      }
    }
    return $user;
  }

  public function adminlte_desc() {
    $roles = $this->roles()->pluck('name')->toArray();
    if (count($roles) > 0) {
      if ($this->id == 1) {
        return __("Super Admin");
      }
      return implode(",", $roles);
    } else {
      return __("No roles selected.");
    }
  }

  public function scopeRegistrations($query, $before = 0) {
    return $query->whereDate('created_at', '=', today()->subDays($before))->count();
  }

  public function level() {
    return $this->hasOne('\\Modules\\Level\\Entities\\Level')->orderBy('id','DESC')->latest();
  }
  public function profile() {
    return $this->hasOne('\\Modules\\UserSystem\\Entities\\Profile')->latest();
  }
  public function levels() {
    return $this->hasMany('\\Modules\\Level\\Entities\\Level');
  }
  public function tags() {
    return $this->hasMany('\\Modules\\UserSystem\\Entities\\User','tag_id','id');
  }
  public function alltags() {
    $now = Carbon::now();
    return $this->hasMany(MerchantTag::class,'user_of')->where('expire_at','>=', $now->toDateTimeString());
  }
  public function taggedBy() {
    return $this->hasOne('\\Modules\\UserSystem\\Entities\\User','id','tag_id');
  }
  public function meTaggedBy() {
    $now = Carbon::now();
    return $this->hasOne(MerchantTag::class,'user_id')->where('expire_at','>=', $now->toDateTimeString());
  }
  public function gifts() {
    return $this->morphMany('\\Modules\\Gift\\Entities\\Gift', 'giftable');
  }
  public function sentgifts() {
    return $this->hasMany('\\Modules\\Gift\\Entities\\Gift');
  }
  public function footprints() {
    return $this->belongsToMany('\\Modules\\UserSystem\\Entities\\User', "user_footprints","footprint_of","footprint_by");
  }
  public function emoticons() {
    return $this->belongsToMany('\\Modules\\Emoticon\\Entities\\EmotionCategory', "emoticon_category_users","user_id","emotion_category_id");
  }
  public function badges() {
    return $this->belongsToMany('\\Modules\\Badge\\Entities\\Badge', "user_badges","user_id","badge_id");
  }
  public function favouriteChatrooms() {
    return $this->belongsToMany('\\Modules\\Chatroom\\Entities\\Chatroom', "chatroom_favourites");
  }
  public function histories() {
    return $this->hasMany('\\Modules\\AccountHistory\\Entities\\AccountHistory');
  }
  public function logins() {
    return $this->hasMany('\\Modules\\LoginTracker\\Entities\\LoginTracker')->where('action','=','login');
  }
  public function notifications() {
    return $this->hasMany('\\Modules\\Notifications\\Entities\\Notification');
  }
  public function scopeHistory($query, $type = 'credit') {
    return $query->histories()->where('type', '=', $type);
  }
  public function getPresenceAttribute() {
    switch ($this->{"pres"}) {
      case 'online':
        return "Online";
      case 'offline':
        return 'Offline';
      case 'busy':
        return 'Busy';
      case 'away':
        return 'Away';
      default:
        return 'Offline';
    }
  }
}
