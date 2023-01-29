<?php

namespace Modules\SwfteaMission\Entities;

use Illuminate\Database\Eloquent\Model;
use Modules\UserSystem\Entities\User;

class MissionParticiants extends Model
{
    protected $fillable = [];
    public function user() {
      return $this->belongsTo(User::class,'user_id');
    }
}
