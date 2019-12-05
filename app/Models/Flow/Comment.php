<?php

namespace App\Models\Flow;

use App\User as Member;

class Comment extends Model
{
    //

    public static $types = [0 => "意见", 1 => "补充"];

    public function getTypeTextAttribute()
    {
        return static::$types[$this->type];
    }

    public function user()
    {
        return $this->hasOne(Member::class, "id", "user_id");
    }
}
