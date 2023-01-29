<?php

namespace Modules\Gift\Entities;

use Illuminate\Database\Eloquent\Model;

class Gift extends Model
{
  protected $fillable = ['name','price','icon','key','type_id','user_id','discount','type','receiver_id','gift_url'];
  protected static function boot() {
    parent::boot();
    static::created(function ($gift) {
      // Create notification
    });
  }

  public function giftable() {
    return $this->morphTo();
  }
}
