<?php

namespace App\Admin\Field;

class Number extends Text
{
    public function getPostValue()
    {
        $name = $this->flowForm->name;
        return request($name, "");
    }

    public function render()
    {
        $this->value = $this->value ?: 0;
        $this->defaultAttribute("type", "number")
            ->defaultAttribute("step", "0.01");

        return parent::render();
    }
}
