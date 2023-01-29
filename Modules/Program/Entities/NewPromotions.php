<?php

namespace Modules\Program\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\Notifications\Jobs\NotificationJob;

class NewPromotions extends Model {
    protected $fillable = [];
    protected static function boot() {
      parent::boot();
      static::created(function ($promotion) {
        dispatch(new NotificationJob('promotion_accepted', $promotion));
      });
    }
    public function user() {
      return $this->belongsTo('\\Modules\\UserSystem\\Entities\\User');
    }
    public function under() {
      return $this->belongsTo('\\Modules\\UserSystem\\Entities\\User','under_of','id');
    }
}
