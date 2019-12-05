<?php


namespace App\Admin\Field;


use App\Admin\Form\FlowForm;

class Url extends Text
{
    public function render()
    {
        $this->isFull = true;
        if (FlowForm::$mode == 'apply') {
            $data = $this->variables();

            return view("field.row", $data)
                ->with("view", "field.url")
                ->with("rowClass", "col-xs-12");
        }
        return parent::render();
    }
}
