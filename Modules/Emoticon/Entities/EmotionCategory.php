<?php

namespace Modules\Emoticon\Entities;

use Encore\Admin\Traits\AdminBuilder;
use Encore\Admin\Traits\ModelTree;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Modules\UserSystem\Entities\User;

class EmotionCategory extends Model {
  use ModelTree;
    protected $fillable = [];
    protected $appends = ['purchased'];
    public function __construct(array $attributes = []) {
      parent::__construct($attributes);
      $this->setParentColumn('id');
      $this->setOrderColumn('order');
      $this->setTitleColumn('title');
    }
    public function emoticons() {
      return $this->hasMany(Emoticon::class,"emotion_category_id","id");
    }
    public function users() {
      return $this->belongsToMany('\\Modules\\UserSystem\\Entities\\User', "emoticon_category_users","emotion_category_id","user_id");
    }
    public static function findByUser(User $user, $paginate = 25) {
      return $user->emoticons()->with(['emoticons'])->orderByDesc('id')->paginate($paginate);
    }
    function getPurchasedAttribute() {
      if(Auth::check()) {
        return (bool) $this->users()->where('id','=',auth()->user()->id)->count();
      } else {
        return false;
      }
    }
}
