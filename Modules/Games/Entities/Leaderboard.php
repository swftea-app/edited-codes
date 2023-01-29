<?php

namespace Modules\Games\Entities;

use Illuminate\Database\Eloquent\Model;

class Leaderboard extends Model
{
    protected $fillable = ['username','type'];
}
