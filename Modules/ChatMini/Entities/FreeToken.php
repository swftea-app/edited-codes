<?php

namespace Modules\ChatMini\Entities;

use Illuminate\Database\Eloquent\Model;

class FreeToken extends Model
{
    protected $fillable = [];
    public function pickers() {
      return $this->belongsToMany("\\Modules\\userSystem\\Entities\\User","tokens_user","token_id","user_id");
    }
}
