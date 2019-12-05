<?php

namespace App\Admin\Field;

class Text extends \Encore\Admin\Form\Field\Text implements IFlowField
{
    use FlowFieldCommon {
        render as TraitRender;
    }

    protected $view = "field.text";

    public function render()
    {
        $this->defaultAttribute('type', 'text')
            ->defaultAttribute('id', $this->id)
            ->defaultAttribute('name', $this->elementName ?: $this->formatName($this->column))
            ->defaultAttribute('value', old($this->elementName ?: $this->column, $this->value()))
            ->defaultAttribute('class', 'form-control ' . $this->getElementClassString())
            ->defaultAttribute('placeholder', $this->getPlaceholder());

        return $this->TraitRender();
    }
}
