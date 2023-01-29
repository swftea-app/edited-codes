<?php

namespace Modules\Avatar\Entities;

use Illuminate\Database\Eloquent\Model;

class AvatarItem extends Model {
    protected $fillable = [];
    public function childs() {
      return $this->belongsToMany('\\Modules\\Avatar\\Entities\\AvatarItem','avatar_items_parent_relation','avatar_parent_id','avatar_child_id');
    }
    public function title() {
      return $this->belongsTo('\\Modules\\Avatar\\Entities\\AvatarKey','avatar_key_id','id');
    }
}
