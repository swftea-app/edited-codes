<?php

namespace Modules\Emoticon\Entities;

use Illuminate\Database\Eloquent\Model;

class Emoticon extends Model
{
    protected $fillable = [];
    public function category() {
      return $this->belongsTo(EmotionCategory::class,"emotion_category_id","id");
    }
}
