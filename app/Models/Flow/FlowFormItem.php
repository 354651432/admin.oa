<?php

namespace App\Models\Flow;

class FlowFormItem extends Model
{
    //

    protected static function boot()
    {
        parent::boot();
        self::saving(function ($model) {
            $model->attributes = array_filter($model->attributes);
        });
    }
}
