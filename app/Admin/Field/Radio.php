<?php


namespace App\Admin\Field;


use App\Models\Flow\UserFlowDataSource;

class Radio extends \Encore\Admin\Form\Field\Radio implements IFlowField
{
    use FlowFieldCommon {
        render as TraitRendor;
    }

    protected $view = "field.radio";

    public function getElementClassSelector()
    {
        return "." . $this->id;
    }

    public function render()
    {
        $this->options($this->dataSource());
        $this->addElementClass($this->id);
        $this->script = "$('{$this->getElementClassSelector()}').iCheck({radioClass:'iradio_minimal-blue'});";
        $this->addVariables(['options' => $this->options, 'checked' => $this->checked, 'inline' => $this->inline]);

        return $this->TraitRendor();
    }

    public function sourceId()
    {
        return UserFlowDataSource::saveJson($this->dataSource())->id ?? 0;
    }
}
