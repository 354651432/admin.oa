<?php

namespace App\Models\Flow;

use App\User as Member;

class UserFlow extends Model
{
    protected $appends = ['statusText', 'progress', 'operator'];

    public static $status = [
        0 => "待审",
        1 => "通过",
        2 => "拒绝",
        3 => "撤回",
    ];

    private $dataCache;

    private $nodeArr = [];

    //
    public function flow()
    {
        return $this->hasOne(Flow::class, "id", "flow_id");
    }

    public function user()
    {
        return $this->hasOne(Member::class, "id", "user_id")
            ->withoutGlobalScopes();
    }

    public function forms()
    {
        return $this->hasMany(UserFlowForm::class, "userflow_id", "id");
    }

    public function getFormByName($name)
    {
        foreach ($this->forms as $form) {
            if ($form->name == $name) {
                return $form;
            }
        }

        return null;
    }

    public function nodes()
    {
        return $this->hasMany(UserFlowNode::class, "userflow_id", "id");
    }

    /**
     * 头结点
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function header()
    {
        return UserFlowNode::query()
            ->where("userflow_id", $this->id)
            ->where("step", 1)
            ->first();
    }

    /**
     * 查找当前结点
     * @return \Illuminate\Database\Eloquent\Builder|mixed
     */
    public function current()
    {
        $nodes = $this->buildNode();

        return array_last($nodes);
    }

    public function buildNode()
    {
        if ($this->nodeArr) {
            return $this->nodeArr;
        }
        $p = $this->header();

        if (empty($p)) {
            return $this->nodeArr;
        }
        $this->nodeArr = [$p];

        $cnt = 0;
        $hasUnhandlerNode = false;
        while ($cnt++ < 100) {
            if ($hasUnhandlerNode) {
                break;
            }

            $nodes = $p->allNext();
            if ($nodes->count() <= 0) {
                break;
            }

            foreach ($nodes as $node) {
                // 被动通过结点不显示
                if ($node->result == 6) {
                    continue;
                }

                // 流程完成生，或签的待审结点不展示
                if ($this->status > 0 && $node->result == 0) {
                    continue;
                }

                $this->addNode($this->nodeArr, $node);

                // 转签 加签结点
                if (in_array($node->result, [4, 5])) {
                    $p1 = $node;

                    $cnt1 = 0;
                    while ($p1 = $p1->firstNext()) {
                        if ($cnt1++ > 100) {
                            break;
                        }
                        // 流程完成生，或签的待审结点不展示
                        if ($this->status > 0) {
                            continue;
                        }
                        if (in_array($p1->type, [3, 4, 5])) {
                            $this->addNode($this->nodeArr, $p1);
                            if ($p1->result == 0) {
                                $hasUnhandlerNode = true;
                                break;
                            }
                        } else {
                            break;
                        }
                    }
                }
            }

            $p = array_last($this->nodeArr);
            if ($p->result == 0) {
                break;
            }
        }

        $this->nodeArr = array_filter($this->nodeArr, function ($item) {
            return $item->result > 0 || $item->is_current == 1;
        });
        return $this->nodeArr;
    }

    /**
     *添加结点 保证不成环
     * @param $arr
     * @param $node
     * @throws \Exception
     */
    private function addNode(&$arr, $node)
    {
        foreach ($arr as $item) {
            if ($item->id == $node->id) {
                throw new \Exception("结点错误:" . json_encode($node));
                return;
            }
        }
        $arr[] = $node;
    }

    public function getStatusTextAttribute()
    {
        return static::$status[$this->attributes['status']] ?? "";
    }

    public function getProgressAttribute()
    {
        $all = 0;
        $finished = 0;
        foreach ($this->nodes as $node) {
            $all++;
            if ($node->result > 0) {
                $finished++;
            }
        }
        return [$finished, $all];
    }

    public function getOperatorAttribute()
    {
        $node = $this->nodes->where("is_current", 1)->first();
        if (!$node) {
            return null;
        }

        return ($node->name ?: $node->op_name) . '/' . ($node->operator->name ?? null);
    }

    public function buildData()
    {
        if ($this->dataCache) {
            return $this->dataCache;
        }
        $ret = [];
        foreach ($this->forms as $form) {
            $ret[$form->name] = $form->value;
        }

        return $this->dataCache = $ret;
    }

    /**
     * 限制撤回
     * @return bool
     */
    public function canCancel()
    {
        if ($this->status != 0) {
            return false;
        }

        if ($this->user_id != \Admin::user()->id) {
            return false;
        }

        foreach ($this->nodes as $node) {
            if ($node->result > 0 && $node->is_lock) {
                return false;
            }
        }

        return true;
    }
}
