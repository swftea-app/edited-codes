<?php

namespace Modules\Program\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\UserSystem\Entities\User;

class MerchantTag extends Model
{
    protected $fillable = [];
    protected function underOf() {
      return $this->belongsTo(User::class,'user_of');
    }
    protected function user() {
      return $this->belongsTo(User::class,'user_id');
    }
}
