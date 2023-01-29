<?php

namespace Modules\UserSystem\Entities;

use Illuminate\Database\Eloquent\Model;
use Overtrue\LaravelLike\Traits\Likeable;

class Profile extends Model
{
  use Likeable;
    protected $fillable = ['name'];
}
