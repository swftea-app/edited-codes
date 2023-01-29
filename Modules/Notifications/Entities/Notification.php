<?php

namespace Modules\Notifications\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Notifications\Events\SendNotification;
use Modules\Notifications\Jobs\NotificationJob;

class Notification extends Model
{
    protected $fillable = [];
    protected $casts = [
      "params" => "array"
    ];
  protected static function boot() {
    parent::boot();
    static::created(function($notification) {
      event(new SendNotification($notification));
    });
  }
}
