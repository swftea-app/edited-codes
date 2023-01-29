<?php

namespace Modules\ChatMini\Entities;

use Illuminate\Database\Eloquent\Model;

class AdminTransaction extends Model
{
    protected $fillable = [];
    public function comments() {
      return $this->hasMany('\\Modules\\ChatMini\\Entities\\TransactionComments','transaction_id','id');
    }
}
