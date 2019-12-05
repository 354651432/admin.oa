<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2019/4/2
 * Time: 17:23
 */

namespace App\Services;


use App\User as Member;

class ConditionService
{
    private function cond_eq($left, $right)
    {
        return $left == $this->number($right);
    }

    private function cond_ne($left, $right)
    {
        return $left != $this->number($right);
    }

    private function cond_gt($left, $right)
    {
        return $left > $this->number($right);
    }

    private function cond_ge($left, $right)
    {
        return $left >= $this->number($right);
    }

    private function cond_lt($left, $right)
    {
        return $left < $this->number($right);
    }

    private function cond_le($left, $right)
    {
        return $left <= $this->number($right);
    }

    private function cond_between($left, $right)
    {
        if (is_string($right)) {
            $right = json_decode($right, true);
        }

        return $left >= $this->number($right[0]) && $left < $this->number($right[1]);
    }

    private function cond_in($left, $right)
    {
        if (is_string($right)) {
            $right = json_decode($right, true);
        }

        if (is_array($left)) {
            return (bool)array_intersect($left, $right);
        }

        return in_array($left, $right);
    }

    private function cond_not_in($left, $right)
    {
        return !$this->cond_in($left, $right);
    }

    private function cond_has($left, $right)
    {
        return (bool)array_intersect($left, $right);
    }

    private function cond_not_has($left, $right)
    {
        return !$this->cond_has($left, $right);
    }

    public function buildCondition($data, $conditions, $nodes = [])
    {
        if (empty($conditions)) {
            return true;
        }

        foreach ($conditions as $condition) {
            $left = $data[$condition->name] ?? 0;
            if (is_array($left) && isset($left['_sum'])) {
                $left = $left['_sum'];
            }

            if (!isset($left) && !in_array($condition->op, ["has", "not_has"])) {
//                throw new \Exception("条件字段不存在");
            }

            if ($condition->op == 'between') {
                $right = [$condition->value1, $condition->value2];
            } elseif ($condition->op == 'in' || $condition->op == 'not_in') {
                $right = $condition->value3;
            } elseif ($condition->op == 'has' || $condition->op == 'not_has') {
                $left = array_map(function ($node) {
                    return $node->step;
                }, $nodes);
                $left = array_unique($left);

                $right = json_decode($condition->value);
            } else {
                $right = $condition->value;
            }

            $result = $this->{"cond_" . $condition->op}($left, $right);
            if ($result === false) {
                return false;
            }
        }
        return true;
    }

    /**
     * k w 单位处理
     * @param $right
     * @return string
     */
    public function number($right)
    {
        if (is_string($right) && preg_match('#^\d+[kKwW]$#', $right)) {
            $last = $right[strlen($right) - 1];
            $replace = strtolower($last) == 'k' ? '000' : '0000';

            return substr($right, 0, -1) . $replace;
        }

        return $right;
    }

    public function buildUserData($userId)
    {
        return [];
    }
}
