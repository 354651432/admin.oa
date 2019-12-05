<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2019/4/3
 * Time: 15:39
 */

namespace App\Admin\Controllers\Flow;

use Admin;
use App\Admin\Field\Attachment;
use App\Admin\Form\FlowForm;
use App\Http\Controllers\Controller;
use App\Models\Flow\Comment;
use App\Models\Flow\Flow;
use App\Models\Flow\UserFlow;
use App\Models\Flow\UserFlowForm;
use App\Models\Flow\UserFlowNode;
use App\Services\UserFlowApplyService;
use App\Services\UserFlowServices;
use Encore\Admin\Grid\Tools;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\Table;

class UserFlowController extends Controller
{
    /**
     * 展示 所有流程模板
     * @param Content $content
     * @param UserFlowServices $userFlowServices
     * @return Content
     */
    public function index(Content $content, UserFlowServices $userFlowServices)
    {
        $data = $userFlowServices->buildUserFlowList();

        return $content->body(function (Row $row) use ($data) {
            foreach ($data as $key => $datum) {

                $row1 = new Row();
                foreach ($datum as $key1 => $item) {
                    $box1 = new Box($key1, view("userflow.list", ['data' => $item]));
                    $box1->style("success");
                    $box1->collapsable();
                    $box1->removable();
                    $row1->column(3, $box1);
                }

                $box = new Box($key, $row1);
                $box->style("success");
                $box->collapsable();
                $box->removable();
                $box->solid();

                $row->column(12, $box);
            }
        });
    }

    /**
     * 发起流程页面
     * @param $id
     * @param Content $content
     * @return Content
     * @throws \Exception
     */
    public function userflow($id, Content $content)
    {
        $data = request()->old();
        $userFlow = UserFlow::find(request("userflow"));
        if ($userFlow) {
            $data = array_merge($userFlow->buildData(), $data);
        }

        $flow = Flow::findOrFail($id);
        $form = new FlowForm($data, $flow);

        Admin::css("/css/apply.css");
        return $content
            ->header($flow->name)
            ->body(function (Row $row) use ($form) {
                $row->column(12, function ($col) use ($form) {
                    $col->row(new Box("发起流程", $form->showMode()));
                });
            });
    }

    /**
     * 发起流程 post 处理
     * @param $id
     * @param UserFlowServices $userFlowServices
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Exception
     */
    public function userflowPost($id, UserFlowServices $userFlowServices)
    {
        $form = new FlowForm([], Flow::findOrFail($id));

        if (!$form->validate()) {
            admin_error($form->getMessage());
            return back()->withInput();
        }

        try {
            $userFlowServices->newUserFlow($id, $form->getPostValue(), session('_flow_token'));
        } catch (\Exception $ex) {
            admin_error($ex->getMessage());
        }

        return redirect("/admin/userflows/record");
    }

    /**
     * 申请记录
     * @param Content $content
     * @param UserFlowServices $userFlowServices
     * @return Content
     */
    public function record(Content $content, UserFlowServices $userFlowServices)
    {
        $grid = $userFlowServices->recordGrid();

        return $content
            ->header("申请记录")
            ->description("列表")
            ->body($grid);
    }

    /**
     * 详情, 审批页面
     * @param $id
     * @param Content $content
     * @return Content
     */
    public function show($id, Content $content)
    {
        $userflowService = new UserFlowServices;
        $userFlow = UserFlow::findOrFail($id);
        if (!$userflowService->pasPermission($id)) {
            return abort(403, "用户无访问权限，请联系管理员");
        }

        Admin::css("/css/apply.css");
        return $content
            ->header($userFlow->flow->name ?? "")
            ->description($userFlow->statusText)
            ->row(function (Row $row) use ($id, $userflowService) {
                $form = $userflowService->buildShowForm($id);
                $row->column(12, new Box("表单数据", $form));

                $node = $userflowService->buildShowNode($id);
                $row->column(12, $node);

                $action = $userflowService->buildShowActions($id);
                $row->column(12, $action);
            });
    }

    /**
     * 审批记录页面
     * @param Content $content
     * @param UserFlowServices $userFlowServices
     * @return Content
     */
    public function approval(Content $content, UserFlowServices $userFlowServices)
    {
        $grid = $userFlowServices->apprGrid();

        return $content
            ->header("审批记录")
            ->body($grid);
    }

    /**
     * 审批 post 处理
     * @param UserFlowServices $userFlowServices
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws \Exception
     */
    public function approvalPost(UserFlowServices $userFlowServices)
    {
        try {
            $userFlowServices->apply();
        } catch (\Exception $ex) {
            admin_error($ex->getMessage());
            return back();
        }
        return redirect("/admin/userflows/recv");
    }

    /**
     * 知会流程
     * @param Content $content
     * @param UserFlowServices $userFlowServices
     * @return Content
     */
    public function notify(Content $content, UserFlowServices $userFlowServices)
    {
        $grid = $userFlowServices->notifyGrid();

        return $content
            ->header("知会流程")
            ->body($grid);
    }

    /**
     * 我的审批页面
     * @param Content $content
     * @param UserFlowServices $userFlowServices
     * @return Content
     */
    public function recv(Content $content, UserFlowServices $userFlowServices)
    {
        $grid = $userFlowServices->recvGrid();

        return $content
            ->header("我的审批")
            ->body($grid);

    }

    /**
     * 撤回
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function cancel($id)
    {
        $flow = UserFlow::find($id);
        if (!$flow->canCancel()) {
            admin_success("流程不可撤回");

            return back();
        }
        $flow->status = 3;
        $flow->save();

        admin_success("操作成功");

        return back();
    }

    /**
     * 补充意见
     */
    public function addComment()
    {
        $id = request("id");
        $content = request("content");

        if (empty($id)) {
            admin_error("结点 id 不能为空");
            return back();
        }

        if (empty($content)) {
            admin_error("内容不能为空");
            return back();
        }

        Comment::create([
            "userflow_node_id" => $id,
            "type" => 1,
            "content" => $content,
            "user_id" => Admin::user()->id,
        ]);

        return back();
    }

    /**
     * 补充附件上传接口
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function attachment($id)
    {
        $id = $this->getId($id);
        if (!$id) {
            return abort(403);
        }

        $userflowForm = UserFlowForm::where("name", request("name"))
            ->where("userflow_id", $id)->firstOrFail();
        $attachment = new Attachment($userflowForm);
        $attachment->value($userflowForm->value);

        $files = $attachment->getPostValue();
        if (empty($files)) {
            return back();
        }

        $userflowForm->value = array_merge($attachment->getValue(), $files);
        $userflowForm->save();

        return back();
    }

    /**
     * 批量同意
     */
    public function pass()
    {
        $ids = request("ids");
        $text = request("text");
        if (empty($ids)) {
            return ["message" => "数据错误"];
        }
        $idArr = explode(",", $ids);
        foreach ($idArr as $id) {
            $nodeId = UserFlowNode::where("userflow_id", $id)
                ->where("is_current", 1)
                ->where("op_user_id", Admin::user()->id)
                ->where("result", 0)
                ->value("id");
            if (empty($nodeId)) {
                continue;
            }
            $service = new UserFlowApplyService($nodeId);
            $service->pass($text);
        }

        return ["message" => "操作成功"];

    }

    /**
     * 批量同意确认信息
     */
    public function passConfirm()
    {
        $ids = request("ids");
        $idArr = explode(",", $ids);
        $data = UserFlow::whereIn("id", $idArr)->get()->map(function ($item) {
            return [$item->id, $item->title];
        })->toArray();

        return (new Table(["ID", "标题"], $data))->render();
    }

    /**
     * 提醒
     */
    public function notice()
    {
        $nodeId = request("nodeId");
        $node = UserFlowNode::findOrFail($nodeId);
        if ($node->result > 0) {
            return ["message" => "流程非当前结点"];
        }
        if ($node->userFlow->status > 0) {
            return ["message" => "流程已结束"];
        }

        $message = app()->make(UserFlowServices::class)->notice($node);
        return compact('message');
    }
}
