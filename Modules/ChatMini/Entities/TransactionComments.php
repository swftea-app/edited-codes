<?php

namespace Modules\ChatMini\Entities;

use Illuminate\Database\Eloquent\Model;

class TransactionComments extends Model
{
    protected $fillable = ['commentor','comment'];
}
