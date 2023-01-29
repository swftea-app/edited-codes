<?php

namespace Modules\Chat\Entities;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Chat\Events\MessageSent;

class Message extends Model {
  protected $fillable = ['raw_text','full_text','type','formatted_text','user_id','extra_info'];
  protected $casts = [
    'extra_info' => 'array'
  ];
  protected static function boot() {
    parent::boot();
    static::created(function($model) {
      $sender_color = $model->sender->color;
      # Send message to chatroom channel on message created.
      if($model->sender->hasRole('User')) {
        $is_mod = DB::table('chatroom_moderators')
          ->where('chatroom_id','=', $model->messageable_id)
          ->where('user_id','=', $model->sender->id)
          ->count();
        $is_owner = DB::table('chatrooms')
          ->where('id','=', $model->messageable_id)
          ->where('user_id','=', $model->sender->id)
          ->count();
        if($is_owner) {
          $sender_color = "#F7C600";
        }
        if($is_mod) {
          $sender_color = "#F7C600";
        }
      }
      $sender_details = [
        'id' => $model->sender->id,
        'username' => $model->sender->username,
        'name' => $model->sender->name,
        'color' => $sender_color,
        'main_status' => $model->sender->main_status == null ? '': $model->sender->main_status,
        'profile_picture' => $model->sender->profile_picture,
        'level' => $model->sender->level,
        'registered_since' => $model->sender->registered_since,
      ];
      event(new MessageSent($model, $sender_details));
    });
  }
  public function __construct(array $attributes = []) {
    parent::__construct($attributes);
    if(property_exists($this, 'user_id') && !isset($this->user_id)) {
      $this->user_id = auth()->user()->id;
    }
  }
  public function sender() {
    return $this->belongsTo("\\Modules\\UserSystem\\Entities\\User","user_id","id");
  }
  public function messageable() {
    return $this->morphTo();
  }
  public function scopeRegistrations($query, $before = 0) {
    return $query->whereDate('created_at', '=', today()->subDays($before))->count();
  }
}
