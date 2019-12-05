<?php

namespace App\Services;

use App\Mail\FlowMail;
use App\Models\Flow\UserFlow;
use App\Models\Flow\UserFlowNode;
use App\User as Member;
use Illuminate\Support\Facades\Mail;

class MailService
{

    /**
     * 发送测试邮件
     */
    public function mailTest()
    {
        $testMailAddress = env("TEST_MAIL");
        Mail::to(explode(",", $testMailAddress))
            ->queue(new FlowMail("mail..test", [], ""));
    }

    /**
     * 审批通知邮件
     * @param UserFlow $userFlow
     * @param UserFlowNode $node
     * @param string $type
     */
    public function appoval(UserFlow $userFlow, UserFlowNode $node, $type = "")
    {
        $flowName = $userFlow->flow->name ?? null;
        $guishu = $userFlow->user->flow->name ?? null;
        $this->send("mail.userflow", [
            "name" => $node->operator->name ?? "未命名",
            "text" => "您有{$type}流程--{$flowName}（{$guishu}）需要审批",
            "id" => $userFlow->id,
            "url" => url("/admin/userflows/recv"),
            "action" => "审批",
        ], $node->operator->email);
    }

    /**
     * 转签邮件
     * @param UserFlow $userFlow
     * @param UserFlowNode $node
     */
    public function tran(UserFlow $userFlow, UserFlowNode $node)
    {
        $this->appoval($userFlow, $node, "转签");
    }

    /**
     * 加签邮件
     * @param UserFlow $userFlow
     * @param UserFlowNode $node
     */
    public function addsign(UserFlow $userFlow, UserFlowNode $node)
    {
        $this->appoval($userFlow, $node, "加签");
    }

    /**
     * 通过邮件
     * @param UserFlow $userFlow
     * @param string $txt
     */
    public function pass(UserFlow $userFlow, $txt = "已经通过")
    {
        $this->send("mail.userflow", [
            "name" => $userFlow->user->name ?? null,
            "text" => "您{$userFlow->created_at}申请的流程{$txt}",
            "id" => $userFlow->id,
            "url" => url("/admin/userflows/record"),
            "action" => "查看",
        ], $userFlow->user->email);
    }

    /**
     * 提醒
     * @param $node
     */
    public function notice($node)
    {
        $this->send("mail.userflow", [
            "name" => $node->operator->name ?? "未命名",
            "text" => "您有流程需要审批",
            "id" => $node->userFlow->id,
            "url" => route("userflow", ["id" => $node->userFlow->id]),
            "action" => "审批",
        ], $node->operator->email, "流程审批邮件【提醒】");
    }

    /**
     * 拒绝邮件
     * @param UserFlow $userFlow
     */
    public function deny(UserFlow $userFlow)
    {
        $this->pass($userFlow, "被拒绝");
    }

    /**
     * 知会邮件
     * @param UserFlow $userFlow
     */
    public function notify(UserFlow $userFlow)
    {
        // 知会流程不发邮件
        if (empty($userFlow->notify_users)) {
            return;
        }

        $userIds = explode(",", $userFlow->notify_users);
        $toArr = [];
        foreach ($userIds as $userId) {
            $user = Member::find($userId);
            if (!$user) {
                continue;
            }
            $toArr[] = $user->email;
        }

        $this->send("mail.userflow", [
            "name" => $user->name,
            "text" => "有流程知会您",
            "id" => $userFlow->id,
            "url" => env("APP_URL") . "/admin/userflow/record",
            "action" => "查看",
        ], $toArr);
    }

    /**
     * Mail::send 的封装
     * @param $view
     * @param $data
     * @param $to
     * @param string $title
     */
    private function send($view, $data, $to, $title = "")
    {
        if (empty(env("MAIL_DRIVER"))) {
            return;
        }

        if (is_string($to)) {
            $to = explode(",", $to);
        }

        $to = array_filter($to ?? []);
        if (empty($to)) {
            return;
        }

        Mail::to($to)
            ->queue(new FlowMail($view, $data, $title));
    }
}
