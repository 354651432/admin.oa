<?php

namespace App\Models\Flow;

class FlowForm extends Model
{

    /**
     * @var array
     */
    private $controls;

    public function getHtmlAttribute()
    {
        return strval($this->getAssert("html"));
    }

    public function getJsAttribute()
    {
        return $this->getAssert("js");
    }

    public function getCssAttribute()
    {
        return $this->getAssert("css");
    }

    public function getScriptAttribute()
    {
        return $this->getAssert("script");
    }

    public function getDataAttribute()
    {
        if (!empty($this->attributes['data'])) {
            return $this->attributes['data'];
        }

        return $this->getAssert("data");
    }

    private function getAssert($key)
    {
        if (empty($this->controls) && $this->type) {
            $field = \App\Admin\Form\FlowForm::makeFlowField($this);
            $html = $field->getHtml();
            $js = $field->getJs();
            $css = $field->getCss();
            $script = $field->getScript();
            $data = $field->getOptions();
            $this->controls = compact('html', 'js', 'css', 'script', 'data');
        }

        return $this->controls[$key] ?? "";
    }

    public function setNameAttribute($value)
    {
        $this->attributes['name'] = trim($value);
    }

    public function setDatasourceAttribute($value)
    {
        $this->attributes['datasource'] = trim($value);
    }

    public function setLabelAttribute($value)
    {
        $this->attributes['label'] = trim($value);
    }
}
