<?php

namespace App\Models\Flow;

use App\User as Member;

class OperatorFlow extends Model
{
    //
    public function operator()
    {
        return $this->hasOne(Operator::class, "id", "op_id");
    }

    public function user()
    {
        return $this->hasOne(Member::class, "id", "user_id");
    }
}
