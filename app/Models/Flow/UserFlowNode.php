<?php

namespace App\Models\Flow;

use Admin;
use App\User as Member;

class UserFlowNode extends Model
{
    //
    public static $results = [
        0 => "Waiting / 等待处理",
        1 => "Agree / 同意",
        2 => "拒绝",
        3 => "不同意继续",
        4 => "转签",
        5 => "加签",
        6 => "被动通过",
        7 => "发起请求",
    ];

    public static $types = [
        0 => "普通",
        1 => "并签",
        2 => "或签",
        3 => "加签",
        4 => "转签",
        5 => "再审",
    ];

    public function operator()
    {
        return $this->hasOne(Member::class, "id", "op_user_id")
            ->withoutGlobalScopes();
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, "userflow_node_id", "id");
    }

    public function userFlow()
    {
        return $this->hasOne(UserFlow::class, "id", "userflow_id");
    }

    /**
     * 所有后续结点
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function allNext()
    {
        return static::newQuery()
            ->where("userflow_id", $this->userflow_id)
            ->where("step", $this->next)
            ->get();
    }

    /**
     * 首个后续结点
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function firstNext()
    {
        return static::where("userflow_id", $this->userflow_id)
            ->where("step", $this->next)
            ->first();
    }

    public function getResultTextAttribute()
    {
        return static::$results[$this->attributes['result']];
    }

    public function getCurrentAttribute()
    {
        return $this->attributes['op_user_id'] == Admin::user()->id
            && $this->attributes['result'] == 0;
    }

    public function getTypeTextAttribute()
    {
        return static::$types[$this->type] ?? null;
    }

    public function getOperator($id)
    {
        return $this->operator;
    }
}
