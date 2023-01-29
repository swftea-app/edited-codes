<?php

namespace Modules\Avatar\Entities;

use Illuminate\Database\Eloquent\Model;

class AvatarKey extends Model {
    protected $fillable = [];
    public function items() {
      return $this->hasMany('\\Modules\\Avatar\\Entities\\AvatarItem','avatar_key_id','id');
    }
}
