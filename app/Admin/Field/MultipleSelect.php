<?php


namespace App\Admin\Field;


use App\Models\Flow\UserFlowDataSource;

class MultipleSelect extends \Encore\Admin\Form\Field\MultipleSelect implements IFlowField
{
    use FlowFieldCommon {
        render as TraitRener;
    }

    protected $view = "field.multipleselect";

    public function title(): string
    {
        $this->options($this->dataSource());
        $value = array_filter($this->value, function ($item) {
            return isset($item);
        });
        $arr = array_only($this->options, $value);
        return implode("-", $arr);
    }

    public function getPostValue()
    {
        $name = $this->flowForm->name;
        $value = request($name);
        if (!is_array($value)) {
            return $value;
        }

        return array_filter($value, [$this, "notNull"]);
    }

    public function notNull($item)
    {
        return isset($item);
    }

    public function render()
    {
        if (is_array($this->value)) {
            $this->value = array_filter($this->value, [$this, "notNull"]);
        } else {
            $this->value = [];
        }

        if (empty($this->options)) {
            $this->options($this->dataSource());
        }
        $this->addVariables([
            'options' => $this->options,
        ]);
        $this->attribute('data-value', implode(',', (array)$this->value()));

        $configs = array_merge([
            'allowClear' => true,
            'placeholder' => [
                'id' => '',
                'text' => $this->label,
            ],
        ], $this->config);

        $configs = json_encode($configs);
        $this->script = "$(\"{$this->getElementClassSelector()}\").select2($configs);";

        return $this->TraitRener();
    }

    public function getType(): string
    {
        return 'array';
    }

    public function sourceId()
    {
        return UserFlowDataSource::saveJson($this->dataSource())->id ?? 0;
    }
}
