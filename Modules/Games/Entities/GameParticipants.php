<?php

namespace Modules\Games\Entities;

use Illuminate\Database\Eloquent\Model;

class GameParticipants extends Model {
  protected $casts = [
    'extra_info' => 'array'
  ];
  protected $fillable = ['round','username','extra_info','score'];
}
