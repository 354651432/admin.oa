<?php

namespace App\Models\Flow;

use App\User as Member;

class Operator extends Model
{
    //
    protected $table = 'flow_operators';

    public function user()
    {
        return $this->hasOne(Member::class, "id", "user_id");
    }

    public function rules()
    {
        return $this->hasMany(OperatorFlow::class, "op_id", "id");
    }

    /**
     * 获取操作用户
     * @param $userId
     * @return mixed
     */
    public function getUser($userId)
    {
        return $this->user;
    }

}
