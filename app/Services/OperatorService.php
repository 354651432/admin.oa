<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Flow\Operator;
use App\User as Member;

class OperatorService
{
    private static $map = [
        '_self' => 'self',
//        '_higher' => 'higher',
        '_departor' => 'departor',
    ];

    public function getOperator($op, $user)
    {
        if (array_key_exists($op, static::$map)) {
            $method = static::$map[$op];
            $user = call_user_func([$this, $method], $user);
        } else {
            $user = Operator::query()
                ->where('alias', $op)
                ->first()
                ->getUser($user);
        }

        if (is_null($user)) {
            throw new \Exception("末找到操作者{$op}");
        }

        return $user;
    }

    /**
     * 发起人
     * @param $userId
     * @return mixed
     */
    private function self($userId)
    {
        return Member::withoutGlobalScopes()->whereId($userId)->first();
    }

    /**
     * 直属上级
     * @param $userId
     * @return mixed
     */
    private function higher($userId)
    {
        $user = $this->self($userId);

        return $user->higherRelation ?: $user;
    }

    /**
     * 部门领导
     *  // todo 角色部门对应关系需要自行实现
     * @param $userId
     * @return mixed
     */
    private function departor($userId)
    {
        return Department::find(1)->user;
    }
}
