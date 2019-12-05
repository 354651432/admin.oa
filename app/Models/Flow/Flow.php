<?php

namespace App\Models\Flow;

use Admin;

class Flow extends Model
{
    //

    protected $with = ['forms', 'nodes'];

    protected $appends = ['flowRulesText'];

    protected static function boot()
    {
        static::saving(function ($model) {

            if (is_array($model->title_fiels)) {
                $model->title_fiels = implode(",", $model->title_fiels);
            }

            if (is_array($model->can_apply)) {
                $model->can_apply = implode(",", $model->can_apply);
            }

            if (is_array($model->notify_users)) {
                $model->notify_users = implode(",", $model->notify_users);
            }

            if (empty($model->title_fiels)) {
                $model->title_fiels = "";
            }
        });
        parent::boot();
    }

    public function forms()
    {
        return $this->hasMany(FlowForm::class, "flow_id", "id");
    }

    public function nodes()
    {
        return $this->hasMany(FlowNode::class, "flow_id", "id");
    }

    public function getFlowAttribute()
    {
        return null;
    }

    public function getFlowRulesTextAttribute()
    {
        return null;
    }

    public function setWeightAttribute($value)
    {
        $this->attributes["weight"] = $value ?: 0;
    }

    public function nameExists($name)
    {
        foreach ($this->forms as $form) {
            if ($form->name == $name) {
                return true;
            }
        }

        return false;
    }

    public function hasPermission()
    {
        if (!$this->can_apply) {
            return true;
        }

        $ids = explode(',', $this->can_apply);
        return in_array(Admin::user()->id, $ids);
    }

    public function validate($data)
    {
        $validate = $this->forms->pluck("validate", "name")->toArray();
        $validate = array_filter($validate);

        return \Validator::make($data, $validate);
    }
}
