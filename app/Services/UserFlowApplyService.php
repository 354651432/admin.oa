<?php

namespace App\Services;

use Admin;
use App\Models\Flow\Comment;
use App\Models\Flow\UserFlow;
use App\Models\Flow\UserFlowNode;
use App\User as Member;
use DB;
use Exception;

/**
 * 流程扭转
 * Class UserFlowApplyService
 * @package App\Services
 */
class UserFlowApplyService
{

    /**
     * @var UserFlowNode
     */
    private $node;

    private $srcNode;

    public function __construct($node_id)
    {
        $this->node = UserFlowNode::findOrFail($node_id);
    }

    /**
     * 通过
     * @param null $text
     * @param int $result
     * @throws Exception
     */
    public function pass($text = null, $result = 1)
    {
        DB::beginTransaction();
        if ($this->node->result != 0) {
            DB::rollBack();
            return;
        }
        $text && $this->saveText($text);

        $this->node->update(["result" => $result]);
        if ($this->node->next > 0 || $this->getSameStepNodes()->count() > 0) {
            // 最后一个结点不更新is_current
            $this->node->update(['is_current' => 0]);
        }
        // 普通结点
        if ($this->node->type == 0) {
            $this->flowNext();
        }

        // 并签
        if ($this->node->type == 1) {
            $nodes = $this->getSameStepNodes();
            if ($nodes->count() <= 0) {
                $this->flowNext();
            }
        }

        // 或签
        if ($this->node->type == 2) {
            $this->flowNext();
            $nodes = $this->getSameStepNodes();
            foreach ($nodes as $node) {
                $node->update(['result' => 6]);
            }
        }

        // 加签
        if ($this->node->type == 3) {
            $next = $this->node->firstNext();
            if ($next->type == 5) {
                // 更新再审结点为当前结点
                $next->update(["is_current" => 1]);
            }

            // 加签后面也可能 没有再审结点
            $src = $this->getSrc();
            if ($next->type == 0 && $src->type == 0) {
                $this->flowNext();
            }

            if ($next->type == 0 && $src->type == 1) {
                $nodes = $this->getSameStepNodes($src->step);
                if ($nodes->count() <= 0) {
                    $this->flowNext();
                }

                // 再审结点处理
                $node = $this->node->firstNext();
                if ($node->type == 5) {
                    $node->update(['is_current' => 1]);
                }
            }

            if ($src->type == 2) {
                $nodes = $this->getSameStepNodes($src->step);
                foreach ($nodes as $node) {
                    $node->update(['result' => 6]);
                }
            }
        }

        // 转签 再审
        if ($this->node->type == 4 || $this->node->type == 5) {
            $src = $this->getSrc();
            // src 结点的类型，只能是 普通结点，并签，或者签， 不可以是转签，加签或者其它类型
            if (!in_array($src->type, [0, 1, 2])) {
                throw new Exception("转签原始结点类型错误,id:{$this->node->src_id}");
            }
            if ($src->type == 0) {
                $this->flowNext();
            }

            if ($src->type == 1) {
                $nodes = $this->getSameStepNodes($src->step);
                if ($nodes->count() <= 0) {
                    $this->flowNext();
                }
            }

            if ($src->type == 2) {
                $this->flowNext();
                $nodes = $this->getSameStepNodes($src->step);
                foreach ($nodes as $node) {
                    $node->update(['result' => 6]);
                }
            }
        }
        DB::commit();
    }

    /**
     * 拒绝
     * @param $text
     * @throws Exception
     */
    public function deny($text)
    {
        DB::beginTransaction();
        if ($this->node->result != 0) {
            DB::rollBack();
            return;
        }
        $this->saveText($text);

        UserFlow::query()->where("id", $this->node->userflow_id)
            ->update(["status" => 2]);

        $this->node->update(["result" => 2]);
        DB::commit();

        // 发邮件
        $service = new MailService();
        $service->deny(UserFlow::find($this->node->userflow_id));
    }

    /**
     * 不同意继续
     * @param $text
     * @throws \Exception
     */
    public function denyAndContinue($text)
    {
        if ($this->node->next == 0) {
            throw new Exception("最后一步不可以不同意继续");
        }
        $this->pass($text, 3);
    }

    /**
     * 转签
     * @param $text
     * @param $to_user
     * @throws Exception
     */
    public function trans($text, $to_user)
    {
        DB::beginTransaction();
        if ($this->node->result != 0) {
            DB::rollBack();
            return;
        }
        $this->saveText($text);

        if ($this->node->src_id) {
            $src_id = $this->getSrc()->id;
        } else {
            $src_id = $this->node->id;
        }

        $nextNode = UserFlowNode::create([
            "userflow_id" => $this->node->userflow_id,
            "step" => $this->getNewStep(),
            "op_user_id" => UserFlowGrant::transformUser(Member::find($to_user))->id,
            "src_id" => $src_id,
            "type" => 4,
            "next" => $this->node->next,
        ]);

        $this->node->update(['result' => 4, 'next' => $nextNode->step, 'is_current' => 0]);
        $this->updateCurrent($nextNode);
        DB::commit();

        // 发邮件
        $service = new MailService();
        $service->tran(UserFlow::find($this->node->userflow_id), $nextNode);
    }

    /**
     * 加签
     * @param $text
     * @param $to_user
     * @throws Exception
     */
    public function addSign($text, $to_user)
    {
        DB::beginTransaction();
        if ($this->node->result != 0) {
            DB::rollBack();
            return;
        }
        $this->saveText($text);

        if ($this->node->src_id) {
            $src_id = $this->getSrc()->id;
        } else {
            $src_id = $this->node->id;
        }
        // 没加转签过，或者再审核结点，才添加再审结点
        if (empty($this->node->src_id) || $this->node->type == 5) {
            // 添加再审结点
            $recheckNode = UserFlowNode::create([
                "userflow_id" => $this->node->userflow_id,
                "step" => $this->getNewStep(),
                "op_user_id" => $this->node->op_user_id,
                "src_id" => $src_id,
                "type" => 5,
                "next" => $this->node->next,
            ]);
        }

        if (isset($recheckNode)) {
            $nextStep = $recheckNode->step;
        } else {
            $nextStep = $this->node->next;
        }

        $nextNode = UserFlowNode::create([
            "userflow_id" => $this->node->userflow_id,
            "step" => $this->getNewStep(),
            "op_user_id" => UserFlowGrant::transformUser(Member::find($to_user))->id,
            "src_id" => $src_id,
            "type" => 3,
            "next" => $nextStep,
        ]);

        $this->node->update(['result' => 5, 'next' => $nextNode->step, 'is_current' => 0]);
        $this->updateCurrent($nextNode);
        DB::commit();

        // 发邮件
        $service = new MailService();
        $service->addsign(UserFlow::find($this->node->userflow_id), $nextNode);
    }

    /**
     * 并签，或者签时 获取同一步骤未完成的 结点
     * @param null $step
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    private function getSameStepNodes($step = null)
    {
        // 考虑转出去的情况
        $step = $step ?: $this->node->step;
        $arr = UserFlowNode::where("userflow_id", $this->node->userflow_id)
            ->where("step", $step)
            ->whereIn("result", [0, 4, 5])
            ->whereIn("type", [1, 2])
            ->get();

        $ret = [];
        foreach ($arr as $item) {
            $ret[] = $item;
        }

        foreach ($ret as &$item) {
            $cnt = 0;
            while (true) {
                if (++$cnt > 100) {
                    break;
                }

                if ($item->result <= 0) {
                    break;
                }

                if ($item->id == $this->node->id) {
                    break;
                }

                if (in_array($item->firstNext()->type, [0, 1, 2])) {
                    break;
                }

                $item = $item->firstNext();
            }
        }

        unset($item);
        $ret = array_filter($ret, function ($item) {
            return $item->result == 0;
        });

        return collect($ret);
    }

    /**
     * 加签 转签时，生成新步骤
     * @return int
     */
    private function getNewStep()
    {
        $maxStep = UserFlowNode::query()
            ->where("userflow_id", $this->node->userflow_id)
            ->orderBy(DB::raw("step+0"), "desc")
            ->value("step");

        return intval($maxStep) + 1;
    }

    /**
     * 保存审批备注
     * @param $text
     * @param int $type
     */
    public function saveText($text, $type = 0)
    {
        if (empty($text)) {
            return;
        }
        Comment::create([
            "userflow_node_id" => $this->node->id,
            "content" => $text,
            "type" => $type,
            "user_id" => Admin::user()->id ?? 0,
        ]);
    }

    /**
     * 结点下移
     */
    private function flowNext()
    {
        $next = $this->node->allNext();

        $userflow = UserFlow::find($this->node->userflow_id);
        if ($next->count() <= 0) {
            // 流程结束
            $userflow->update(["status" => 1]);

            // 发邮件
            $service = new MailService();
            $service->pass(UserFlow::find($this->node->userflow_id));
            $service->notify(UserFlow::find($this->node->userflow_id));
            return;
        }

        if ($this->node->next > 0) {
            $this->updateCurrent($next);
        }

        foreach ($next as $item) {
            // 发邮件
            $service = new MailService();
            $service->appoval($userflow, $item);
        }
    }

    /**
     * 对于加签，转签，获取 原始结点
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model|null
     */
    private function getSrc()
    {
        return $this->srcNode ?: $this->srcNode = UserFlowNode::query()->find($this->node->src_id);
    }

    /**
     * 更新当前操作者
     * @param $nodes
     */
    public function updateCurrent($nodes)
    {
        if ($nodes instanceof \Illuminate\Database\Eloquent\Model) {
            $nodes->update(['is_current' => 1]);
        } else {
            foreach ($nodes as $node) {
                $node->update(['is_current' => 1]);
            }
        }
    }
}
