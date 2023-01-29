<?php

namespace Modules\InAppMail\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\InAppMail\Events\SendNotification;

class ReceivedAppMail extends Model
{
    protected $fillable = ['sender','receiver','subject','body', 'additional_data', 'receiver_id', 'sender_id'];
    protected $casts = [
      'additional_data' => 'array'
    ];
    protected static function boot() {
      parent::boot();

      static::created(function ($mail) {
        event(new SendNotification($mail->receiver_id, $mail));
      });
    }
}
