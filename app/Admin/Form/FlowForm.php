<?php

namespace App\Admin\Form;

use Admin;
use App\Admin\Field\FlowBaseInfo;
use App\Admin\Field\IFlowField;
use App\Models\Flow\Flow;
use App\Models\Flow\UserFlow;
use Encore\Admin\Form\Field;
use Encore\Admin\Widgets\Form;
use Illuminate\Http\Request;
use Session;

class FlowForm extends Form
{
    // 配置页 edit 发起 show 审批 apply
    public static $mode = "show";

    protected $flow;

    /**
     * @var Field[]|IFlowField[]
     */
    protected $fieldsMap = [];
    private $message = "";

    /**
     * FlowForm constructor.
     * @param array $data
     * @param Flow|UserFlow $flow
     * @throws \Exception
     */
    public function __construct(array $data, $flow)
    {
        $this->flow = $flow;
        foreach ($flow->forms as $form) {
            $field = static::makeFlowField($form);
            if (empty($field)) {
                continue;
            }
            $field->fill($data);
            $this->fieldsMap[$form->name] = $field;
        }

        parent::__construct($data);
    }

    /**
     * post数据生成title
     * @return mixed|string
     */
    public function title()
    {
        if (empty($this->flow->title_fiels)) {
            return $this->flow->name;
        }

        $titles = [];
        $cols = explode(",", $this->flow->title_fiels);
        foreach ($cols as $col) {
            if (!array_key_exists($col, $this->fieldsMap)) {
                continue;
            }

            $field = $this->fieldsMap[$col];
            $title = $field->title();
            $title && $titles[] = $title;
        }

        if (empty($titles)) {
            return $this->flow->name;
        }

        return implode("-", $titles);
    }

    /**
     * post 数据验证
     * @param Request $request
     * @return bool
     */
    public function validate(Request $request = null)
    {
        $data = $this->getPostValue();

        foreach ($this->fieldsMap as $field) {
            $field->fill($data);
            if (!$field->validate()) {
                $this->message = $field->getMessage();
                return false;
            }
        }
        return true;
    }

    /**
     * @return array
     */
    public function getPostValue()
    {
        static $ret = [];
        if ($ret) {
            return $ret;
        }

        foreach ($this->fieldsMap as $key => $field) {
            $ret[$key] = $field->getPostValue();
        }

        return $ret;
    }

    public function render()
    {
        $this->setAction("/admin/userflow/list/{$this->flow->id}");

        foreach ($this->fieldsMap as $field) {
            $this->pushField($field);
        }

        return parent::render();
    }

    public function hasFile()
    {
        foreach ($this->fieldsMap as $field) {
            if ($field->hasFile()) {
                return true;
            }
        }
        return false;
    }

    /**
     * 验证错误信息
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * 发起模式
     * @return $this
     * @throws \Exception
     */
    public function showMode()
    {
        static::$mode = 'show';
        $uid = Admin::user()->id;
        $token = $uid . bin2hex(random_bytes(32));
        Session::put("_flow_token", $token);

        return $this;
    }

    /**
     * 审批模式
     * @return $this
     */
    public function applyMode()
    {
        static::$mode = 'apply';
        $this->disableSubmit();
        $this->disableReset();

        $this->attribute(['id' => "flow-form"]);

        $baseInfo = new FlowBaseInfo($this->flow);
        $this->pushField($baseInfo);
        foreach ($this->fieldsMap as $field) {
            $field->disable();
        }

        return $this;
    }

    /**
     * IFlowField 工厂方法
     * @param \App\Models\Flow\FlowForm|\App\Models\Flow\UserFlowForm $form
     * @return IFlowField
     * @throws \Exception
     */
    public static function makeFlowField($form)
    {
        $cls = '\App\Admin\Field\\' . $form->type;
        if (!class_exists($cls)) {
            throw new \Exception("{$form->type} class is not exists");
        }

        if (!class_implements($cls, IFlowField::class)) {
            throw new \Exception("{$form->type} class should implement IFlowField");
        }

        return new $cls($form);
    }
}
