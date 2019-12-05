<?php

namespace App\Models\Flow;

class UserFlowForm extends Model
{
    //
    public function getValueAttribute($value)
    {
        $field = \App\Admin\Form\FlowForm::makeFlowField($this);
        if ($field->getType() == 'array') {
            return json_decode($value, true);
        }

        if ($field->getType() == 'object') {
            return json_decode($value);
        }

        return $value;
    }

    public function setValueAttribute($value)
    {
        $field = \App\Admin\Form\FlowForm::makeFlowField($this);
        if ($field->getType() != 'scalar') {
            $this->attributes['value'] = json_encode($value);
            return;
        }

        $this->attributes['value'] = $value;
    }

    public function userFlow()
    {
        return $this->hasOne(UserFlow::class, "id", "userflow_id");
    }

    public function datasource()
    {
        return $this->hasOne(UserFlowDataSource::class, "id", "datasource_id");
    }
}
