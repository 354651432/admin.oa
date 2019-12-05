<?php

namespace App\Admin\Field;

use App\Models\Flow\UserFlowDataSource;

class Select extends \Encore\Admin\Form\Field\Select implements IFlowField
{

    protected $config = ["width" => "100%"];

    use FlowFieldCommon {
        render as TraitRener;
    }

    protected $view = "field.select";

    public function title(): string
    {
        $options = $this->dataSource();
        if ($this->value === '') {
            return \Arr::first($options) ?? "";
        }
        return $options[$this->value] ?? "";
    }

    protected function getElementClass()
    {
        return [];
    }

    public function render()
    {
        $this->options($this->dataSource());
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

    public function sourceId()
    {
        return UserFlowDataSource::saveJson($this->dataSource())->id ?? 0;
    }
}
