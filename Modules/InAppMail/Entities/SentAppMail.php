<?php

namespace Modules\InAppMail\Entities;

use Illuminate\Database\Eloquent\Model;

class SentAppMail extends Model
{
    protected $fillable = [];
    public function receiver() {
      return $this->belongsTo('\Modules\UserSystem\Entities\User','receiver_id','id');
    }
}
