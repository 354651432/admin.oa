<?php
/**
 * Created by PhpStorm.
 * User: user
 * Date: 2019/4/2
 * Time: 15:28
 */

namespace App\Services;

use Admin;
use App\Models\Flow\Flow;
use App\Models\Flow\FlowCond;
use App\Models\Flow\FlowForm;
use App\Models\Flow\FlowNode;
use App\Models\Flow\Operator;
use App\Models\Flow\UserFlowNode;
use DB;

class FlowService
{

    /**
     *  预览
     * @param $data
     * @param $nodes
     * @param $user
     * @return array
     * @throws \Exception
     */
    public function preview($data, $nodes, $user)
    {
        // todo 调用 实际的发起逻辑
        $arr = [];
        $conditionService = new ConditionService();

        $data += $conditionService->buildUserData($user);
        foreach ($nodes as $node) {
            $cond = $conditionService->buildCondition($data, $node->conditions, $arr);
            if (!$cond) {
                continue;
            }
            $model = new UserFlowNode([
                "op_user_id" => $this->getOperator($node->op, $user)->id ?? 0,
                "name" => $node->name ?? '',
                "result" => 1,
                "user_id" => $user,
                "type" => $node->type,
                "step" => $node->step,
                "id" => 0,
                "created_at" => date("Y-m-d H:i:s")
            ]);

            $arr[] = $model;
        }

        return $arr;
    }

    /**
     * 获取操作者用户
     * @param $op
     * @param $user
     * @return mixed
     * @throws \Exception
     */
    public function getOperator($op, $user)
    {
        return app(OperatorService::class)
            ->getOperator($op, $user);
    }

    /**
     * 可选操作者列表
     * @return array
     */
    public function getOperators()
    {
        static $ret = null;
        if ($ret) {
            return $ret;
        }
        $preOperator = [
            '_self' => "发起人",
//            '_higher' => "直属上级",
            '_departor' => "部门负责人",
        ];
        $operators = Operator::query()
            ->orderBy("name")
            ->pluck("name", "alias")
            ->toArray();
        return $ret = $preOperator + $operators;
    }

    /**
     * 撸下一个结点
     * @param $nodes
     * @return mixed
     */
    private function linkNodes($nodes)
    {
        $cnt = count($nodes);
        for ($i = 0; $i < $cnt - 1; $i++) {
            // 普通结点
            if ($nodes[$i]['type'] == '0') {
                $nodes[$i]['next'] = $nodes[$i + 1]['step'];
            } else {
                $j = $i;
                while (isset($nodes[$j + 1]) && $nodes[$j]['step'] == $nodes[$j + 1]['step']) {
                    $j++;
                }

                // 流程以多结点同时审批结束
                if (isset($nodes[$j + 1])) {
                    $nodes[$i]['next'] = $nodes[$j + 1]['step'];
                } else {
                    $nodes[$i]['next'] = 0;
                }
            }
        }

        $nodes[$i]['next'] = 0;

        return $nodes;
    }

    /**
     * 新增，修改表单 POST 处理
     * @param $id
     * @param $forms
     * @throws \Exception
     */
    public function storeForms($id, $forms)
    {
        DB::beginTransaction();
        FlowForm::query()->where("flow_id", $id)->delete();
        foreach ($forms as $form) {
            FlowForm::create([
                "flow_id" => $id,
                "name" => $form["name"],
                "type" => $form["type"],
                "value" => $form["value"] ?? "",
                "datasource" => $form["datasource"] ?? "",
                "datasource_type" => $form["datasource_type"] ?? "0",
                "validate" => $form["validate"] ?? "",
                "label" => $form["label"],
                "help_text" => $form["help_text"] ?? "",
            ]);
        }
        DB::commit();
    }

    /**
     * 新增，修改模板结点
     * @param $id
     * @param $nodes
     * @throws \Exception
     */
    public function storeNodes($id, $nodes)
    {
        $nodes = $this->linkNodes($nodes);
        DB::beginTransaction();

        $flowNodes = FlowNode::where("flow_id", $id)->get();
        foreach ($flowNodes as $flowNode) {
            foreach ($flowNode->conditions as $item) {
                $item->delete();
            }
            $flowNode->delete();
        }

        foreach ($nodes as $node) {
            $nodeObj = FlowNode::create([
                "flow_id" => $id,
                "step" => $node["step"],
                "next" => $node["next"],
                "name" => $node["name"] ?? "",
                "op" => $node["op"],
                "is_lock" => ($node["is_lock"] ?? 0) ? 1 : 0,
                "type" => $node["type"],
            ]);
            $conditions = $node["conditions"];
            if ($conditions) {
                $this->storeCondition($conditions, $nodeObj->id);
            }
        }
        DB::commit();
    }

    /**
     * 保存条件
     * @param $conditions
     * @param $id
     */
    private function storeCondition($conditions, $id)
    {
        foreach ($conditions as $condition) {
            if ($condition["op"] == 'between') {
                $value = [$condition['value1'], $condition['value2']];
            } elseif (in_array($condition["op"], ["in", "not_in"])) {
                $value = $condition['value3'];
            } else {
                $value = $condition['value'];
            }

            FlowCond::create([
                "flow_node_id" => $id,
                "name" => $condition["name"],
                "op" => $condition["op"],
                "value" => is_array($value) ? json_encode($value) : $value,
            ]);
        }
    }

    /**
     * 简单验证 表单验证规则写的是否正确
     * @param $forms
     * @return string
     */
    public function validateCheck($forms)
    {
        $data = [];
        foreach ($forms as $name => $form) {
            $data[$name] = $form['validate'] ?? "";
        }

        try {
            $validator = \Validator::make($data, $data);
            $validator->passes();
        } catch (\Exception $ex) {
            return $ex->getMessage();
        }

        return "";
    }

    /**
     * privew 使用
     * 渲染 js,css,script
     * @param $forms
     * @throws \Exception
     */
    public function buildAssert($forms)
    {
        foreach ($forms as $form) {
            if (empty($form->type)) {
                continue;
            }
            $field = \App\Admin\Form\FlowForm::makeFlowField(new FlowForm((array)$form));
            $html = $field->getHtml();
            $js = $field->getJs();
            $css = $field->getCss();
            $script = $field->getScript();

            $form->html = $html;
            Admin::js($js);
            Admin::css($css);
            Admin::script($script);
        }
    }

    /**
     * @param $id
     * @param array $data
     * @return string
     */
    public function buildDataList($id, array $data)
    {
        $html = "<datalist id=\"$id\">";
        foreach ($data as $datum) {
            $html .= "<option value=\"$datum\">$datum</option>";
        }
        $html .= "</datalist>";
        return $html;
    }
}
