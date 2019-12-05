<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2019/4/3
 * Time: 15:52
 */

namespace App\Services;

use Admin;
use App\Models\Flow\Flow;
use App\Models\Flow\FlowForm;
use App\Models\Flow\FlowNode;
use App\Models\Flow\UserFlow;
use App\Models\Flow\UserFlowForm;
use App\Models\Flow\UserFlowNode;
use App\User as Member;
use DB;
use Encore\Admin\Grid;
use Encore\Admin\Widgets\Form;
use Str;

class UserFlowServices
{

    /**
     * 生成  发起流程页面数据
     * @return array
     */
    public function buildUserFlowList()
    {
        $flows = Flow::with("nodes")
            ->orderBy("weight", "desc")
            ->get();

        $ret = [];
        foreach ($flows as $item) {
            $ret[$item['directory1']][$item['directory2']][] = $item;
        }
        return $ret;
    }

    /**
     * 发起流程 POST 数据处理
     * todo 增加单元测试
     * todo check字段验证
     * @param $id
     * @param array $data
     * @param $token
     * @return Flow
     * @throws \Exception
     */
    public function newUserFlow($id, array $data, $token)
    {
        $steps = $this->getSteps($id, $data);

        $flow = Flow::findOrFail($id);
        $form = new \App\Admin\Form\FlowForm($data, $flow);

        DB::beginTransaction();
        $userFlow = UserFlow::create([
            "flow_id" => $id,
            "title" => $form->title(),
            "user_id" => Admin::user()->id,
            "status" => 0,
            "notify_users" => $this->getNotifyUsers($flow),
            "step" => $steps[1]->step ?? 1,
            "token" => $token,
        ]);

        foreach ($data as $key => $datum) {

            $baseForm = $this->getForm($id, $key);
            if (empty($baseForm)) {
                admin_error("控件错误:$id,$key");
                DB::rollBack();
                return null;
            }

            $field = \App\Admin\Form\FlowForm::makeFlowField($baseForm);

            UserFlowForm::create([
                "userflow_id" => $userFlow->id,
                "name" => $key,
                "type" => $baseForm->type ?? "unknown",
                "value" => $datum ?? "",
                "label" => $baseForm->label,
                "datasource_id" => $field->sourceId(),
                "validate" => $baseForm->validate,
                "help_text" => $baseForm->help_text,
//                "version" => config("flow.version", 0),
            ]);
        }

        $service = new FlowService();
        $opers = $service->getOperators();
        $p = null;
        foreach ($steps as $step) {
            $oper = $step->getOperator(Admin::user()->id);
            if (empty($oper)) {
                admin_error("未找到操作者:$oper");
                DB::rollBack();
                return null;
            }
            $node = UserFlowNode::create([
                "userflow_id" => $userFlow->id,
                "step" => $step->step,
                "name" => $step->name,
                "op_name" => $opers[$step->op] ?? "",
                "op_user_id" => $oper->id,
                "result" => $step->result ?: 0,
                "type" => $step->type,
                "is_lock" => $step->is_lock,
                "next" => $step->next,
            ]);

            // 保存首结点，用于发邮件
            is_null($p) && $p = $node;
        }

        // 发邮件
        if (is_null($p) || $p->step != 1) {
            admin_error("首个结点错误");
            DB::rollBack();
            return;
        }

        $next = $p->allNext();
        $service = new UserFlowApplyService($p->id);
        if ($next->count() <= 0) {
            // 已经没有结点了 就让它结束吧
            $userFlow->status = 1;
            $userFlow->save();

            $service->updateCurrent($p);
            DB::commit();
            return $userFlow;
        }
        $service->updateCurrent($next);
        DB::commit();

        $mailService = new MailService();
        foreach ($next as $item) {
            $mailService->appoval($userFlow, $item);
        }

        return $userFlow;
    }

    /**
     * 获取模板对象
     * @param $id
     * @param $name
     * @return FlowForm
     */
    private function getForm($id, $name)
    {
        $flowForm = FlowForm::where('flow_id', $id)
            ->where('name', $name)->first();

        return $flowForm;
    }

    /**
     * 生成流程结点，去掉条件判断失败的结点
     * @param $id
     * @param $data
     * @return array
     * @throws \Exception
     */
    private function getSteps($id, $data)
    {
        // todo 单元测试
        $steps = FlowNode::query()->where('flow_id', $id)->get();

        if ($steps->count() == 0) {
            throw new \Exception("至少配置一个结点");
        }
        $first = $steps[0];
        $first->result = 7;
        $ret = [$first];

        $conditionService = new ConditionService();
        $data += $conditionService->buildUserData(Admin::user()->id);
        $cnt = count($steps);

        for ($i = 1; $i < $cnt; $i++) {

            if ($conditionService->buildCondition($data, $steps[$i]->conditions, $ret)) {
                $ret[] = $steps[$i];
            } elseif ($i > 0) {
                // 普通结点，或者 并或签 最后一个结点，删除后调整上一个结点的 next 属性
                if (
                    $steps[$i]->type == 0 ||
                    (isset($steps[$i + 1]) && $steps[$i + 1]->step != $steps[$i]->step)
                ) {
                    $last = last($ret);
                    foreach ($ret as &$item) {
                        if ($item->step == $last->step) {
                            $item->next = $steps[$i]->next;
                        }
                    }
                    unset($item);
                }
            }
        }

        return $ret;
    }

    /**
     * 根据当前用户归属，获取知会人列表
     * @param $flow
     * @return string
     */
    public function getNotifyUsers($flow)
    {
        return $flow->notify_users;
    }

    /**
     * 提醒
     * @param $node
     * @return string
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function notice($node)
    {
        app()->make(MailService::class)->notice($node);
        return "邮件提醒成功";
    }

    /**
     * 流程表单数据显示
     * todo number类型disabled掉以后 还可以操作bug修复
     * @param $id
     * @return Form
     * @throws \Exception
     */
    public function buildShowForm($id)
    {
        $userflow = UserFlow::findOrFail($id);
        $form = new \App\Admin\Form\FlowForm($userflow->buildData(), $userflow);

        return $form->applyMode();
    }

    /**
     * 流程审批结点展示
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     * @throws \Exception
     */
    public function buildShowNode($id)
    {
        $nodes = UserFlow::findorFail($id)->buildNode();

        $view = view('flow.apply-node')
            ->with("data", $nodes);

        return $view;
    }

    /**
     * 操作按钮显示
     * todo check其它不可操作场景，去掉相应按钮
     * @param $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View|null
     */
    public function buildShowActions($id)
    {
        try {
            $nodes = UserFlow::findOrFail($id)->buildNode();
            $node = null;
            foreach ($nodes as $item) {
                if ($item->current) {
                    $node = $item;
                    break;
                }
            }
        } catch (\Exception $ex) {
            $node = null;
        }

        return view("userflow.actions")
            ->with("node", $node)
            ->with("flow", UserFlow::find($id))
            ->with("users", Member::query()->pluck("name", "id"));
    }

    /**
     * 审批处理
     * todo 单元测试
     * @throws \Exception
     */
    public function apply()
    {
        $result = request("result");
        $node_id = request("node-id");
        $text = request("apply-text");
        $toUser = request("to-user");

        if (in_array($result, [4, 5]) && is_null($toUser)) {
            throw new \Exception("加转签用户不能为空");
        }

        $service = new UserFlowApplyService($node_id);

        if ($result == 1) {
            $service->pass($text);
        }

        if ($result == 2) {
            $service->deny($text);
        }

        if ($result == 3) {
            $service->denyAndContinue($text);
        }

        if ($result == 4) {
            $service->trans($text, $toUser);
        }

        if ($result == 5) {
            $service->addSign($text, $toUser);
        }
    }

    /**
     * 流程审批页面基础 Grid
     * @return Grid
     */
    private function grid()
    {
        $grid = new Grid(new UserFlow());

        $grid->model()->orderBy("created_at", "desc");
        $grid->column("id", "ID");
        $grid->column("flow.name", "流程");
        $grid->column("title", "标题");
        $grid->column("user.name", "申请人");
        $grid->column("created_at", "时间");
        $grid->column("statusText", "状态");

        $grid->column("progress", "进度")->display(function ($progress) {
            list($finished, $all) = $progress;

            if ($all > 0) {
                $rate = intval($finished / $all * 100);
            } else {
                $rate = 0;
            }

            $class = "progress-bar-info";
            if ($this->status === 1) {
                $class = "progress-bar-success";
            }
            if ($this->status == 2) {
                $class = "progress-bar-danger";
            }

            if ($this->status == 3) {
                $class = "progress-bar-muted";
            }

            return <<<HTML
            <div class="row" style="min-width: 100px;">
            <div class="col-sm-4" style="line-height: 30px;">
            {$finished}/{$all}
</div>
<div class="col-sm-8">
<div class="progress">
  <div class="progress-bar {$class}" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: {$rate}%;">
    <span class=""></span>
  </div>
</div>
</div>
</div>

HTML;

        });
        $grid->column("operator", "当前结点");

        $grid->actions(function (Grid\Displayers\Actions $actions) {
            $actions->disableDelete();
            $actions->disableView();
            $actions->disableEdit();

            $id = $actions->getKey();

            $link = route("userflow", ["id" => $id]);
            $actions->append(<<<HTML
            <a href="{$link}">处理</a>
HTML
            );
        });

        $grid->disableCreation();
        $grid->disableExport();

        $grid->filter(function (Grid\Filter $filter) {
            $filter->like("title", "标题");
            $filter->like("flow.name", "流程");
            $filter->equal("user_id", "申请人")
                ->select(Member::withOutGlobalScopes()->pluck("name", "id"));

            $filter->between('created_at', '创建时间')->date();
        });

        return $grid;
    }

    /**
     * 申请 记录
     * @return Grid
     */
    public function recordGrid()
    {
        $grid = $this->grid();

        if (Admin::user()->isRole('administrator')) {
            return $grid;
        }

        $grid->model()->where("user_id", Admin::user()->id);
        return $grid;
    }

    /**
     * 我的审批
     * @return Grid
     */
    public function recvGrid()
    {
        $grid = $this->grid();

        $grid->model()
            ->whereHas("nodes", function ($query) {
                $query->where('is_current', 1)
                    ->where("op_user_id", Admin::user()->id);
            })
            ->where("status", 0);

        return $grid;
    }

    /**
     * 审批记录
     * 所有本人操作过的流程记录
     */
    public function apprGrid()
    {
        $grid = $this->grid();
        $grid->model()->whereHas("nodes", function ($query) {
            $query
                ->where("op_user_id", Admin::user()->id)
                ->where("step", ">", 1)
                ->where("result", ">", 0);
        });

        return $grid;
    }

    /**
     * 知会流程
     * @return Grid
     */
    public function notifyGrid()
    {
        $grid = $this->grid();

        $userId = Admin::user()->id;
        $grid->model()
            ->where("status", 1)
            ->whereRaw("find_in_set($userId,notify_users)");

        return $grid;
    }

    /**
     * 流程详情页鉴权
     * @param $id
     * @return bool
     */
    public function pasPermission($id)
    {
        // 管理员
        if (Admin::user()->isRole("admin")) {
            return true;
        }

        // 站内链接
        if (Str::contains(request()->server("HTTP_REFERER"), request()->getHost())) {
            return true;
        }

        $userflow = UserFlow::find($id);
        // 知会人
        if (in_array(Admin::user()->id, explode(",", $userflow->notify_users))) {
            return true;
        }

        // 流程结点相关人
        foreach ($userflow->nodes as $node) {
            if ($node->op_user_id == Admin::user()->id) {
                return true;
            }
        }

        return false;
    }
}
