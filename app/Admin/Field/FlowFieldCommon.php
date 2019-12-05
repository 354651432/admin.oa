<?php

namespace App\Admin\Field;

use Admin;
use App\Models\Flow\FlowForm;
use App\Models\Flow\UserFlowForm;
use DB;

trait FlowFieldCommon
{
    protected $message;

    /**
     * @var FlowForm|UserFlowForm
     */
    protected $flowForm;

    protected $isFull = false;

    /**
     * FlowFieldCommon constructor.
     * @param $flowForm
     * @throws \Exception
     */
    public function __construct($flowForm)
    {
        if (!$flowForm instanceof UserFlowForm && !$flowForm instanceof FlowForm) {
            throw new \Exception("控件错误:" . json_encode($flowForm));
        }

        parent::__construct($flowForm->name);
        $this->label = $flowForm->label;

        $this->flowForm = $flowForm;
        if ($flowForm->help_text) {
            $this->help($flowForm->help_text);
        }

        if (in_array("required", $this->getValidates($flowForm))) {
            $this->required();
        }

        $this->default($flowForm->default);

        $this->id = "x" . bin2hex(random_bytes(32));
    }

    private function getValidates($form)
    {
        if (empty($form->validate)) {
            return [];
        }

        $str = $form->validate;
        return explode("|", $str);
    }

    public function render()
    {
        $rowClass = 'col-xs-6';
        if ($this->isFull) {
            $rowClass = 'col-xs-12';
        }

        $this->addVariables([
            "rowClass" => $rowClass,
            "view" => $this->getView(),
        ]);
        Admin::script($this->script);

        if (is_string($this->id)) {
            $this->attribute("id", $this->id);
        }

        return view('field.row', $this->variables());
    }

    /**
     * @return array|mixed|null
     */
    public function dataSource()
    {
        if ($this->flowForm->datasource_id) {
            return $this->flowForm->datasource->json;
        }

        $datasource = trim($this->flowForm->datasource);
        if (preg_match('#^config\:([\w_.]+)$#', $datasource, $matches)) {
            $name = $matches[1];
            $datasource = config($name);
        }

        if ($this->flowForm->datasource_type == '0' && $datasource) {
            return json_decode($datasource, true);
        }

        if ($this->flowForm->datasource_type == '1' && $datasource) {
            return $this->executeSql($datasource);
        }

        return null;
    }

    private function executeSql($sql)
    {
        $params = Admin::user()->toArray();
        $sql = preg_replace_callback('#\{([\w\.]+)\}#', function ($matches) use ($params) {
            return array_get($params, $matches[1]);
        }, $sql);

        try {
            $data = DB::select($sql);
            $options = [];
            foreach ($data as $datum) {
                list($a, $b) = array_pad(array_values((array)$datum), 2, null);
                if (!isset($b)) {
                    $b = $a;
                }
                $options[$b] = $a;
            }
            return $options;
        } catch (\Exception $err) {
            admin_error($err->getMessage());
        }
        return [];
    }

    public function title(): string
    {
        return strval($this->value);
    }

    public function getJs()
    {
        $assert = static::getAssets();
        return $assert["js"];
    }

    public function getCss()
    {
        $assert = static::getAssets();
        return $assert["css"];
    }

    public function getHtml()
    {
        return strval($this->render());
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function getPostValue()
    {
        $name = $this->flowForm->name;
        return request($name);
    }

    public function validate(): bool
    {
        if (empty($this->flowForm->validate)) {
            return true;
        }

        if (empty($this->data)) {
            return true;
        }

        $validate = \Validator::make($this->data, [
            $this->flowForm->name => $this->flowForm->validate
        ]);

        if (!$validate->passes()) {
            $this->message = $validate->errors()->first("*");
            $this->message = str_replace($this->flowForm->name, $this->label, $this->message);
            return false;
        }

        return true;
    }

    public function hasFile(): bool
    {
        return false;
    }

    public function getMessage()
    {
        return $this->message;
    }

    protected function getElementClassSelector()
    {
        return "#" . $this->id;
    }

    protected function getElementClass()
    {
        return [];
    }

    public function getViewElementClasses()
    {
        if ($this->isFull) {
            return [
                'label' => "col-xs-2 noweight " . $this->getLabelClass(),
                'field' => "col-xs-10",
                'form-group' => 'form-group',
            ];
        }
        return [
            'label' => "col-xs-4 noweight " . $this->getLabelClass(),
            'field' => "col-xs-8",
            'form-group' => 'form-group',
        ];
    }

    public function sourceId()
    {
        return 0;
    }

    public function fill($data)
    {
        $this->data = $data;

        $name = $this->flowForm->name;
        if (is_array($data)) {
            $this->value = array_get($data, $name);
        }
    }

    public function getType(): string
    {
        return "scalar";
    }

    public function sumValidate($sumKey, $itemKey): bool
    {
        $sum = array_sum($this->value[$itemKey]);
        $sum = round($sum, 2);
        if ($sum == $this->value[$sumKey] && $sum >= 0) {
            return true;
        }

        $this->message = "表格内汇总数据错误，请再点击一次【计算汇总】按钮";
        return false;
    }
}
