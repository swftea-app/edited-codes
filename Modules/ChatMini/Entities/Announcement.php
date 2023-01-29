<?php

namespace Modules\ChatMini\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Notifications\Jobs\NotificationJob;

class Announcement extends Model
{
    protected $fillable = [];
  protected static function boot() {
    parent::boot();
    static::created(function($model) {
//      dispatch(new NotificationJob("new_announcement_added",(object)[]));
    });
  }
}
