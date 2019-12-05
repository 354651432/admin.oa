<?php

namespace App\Models\Flow;

class FlowCond extends Model
{
    //
    protected $appends = ['value1', 'value2', 'value3'];

    public function getValue1Attribute()
    {
        $arr = json_decode($this->attributes['value']);
        return $arr[0] ?? null;
    }

    public function getValue2Attribute()
    {
        $arr = json_decode($this->attributes['value']);
        return $arr[1] ?? null;
    }

    public function getValue3Attribute()
    {
        return json_decode($this->attributes['value']) ?: [];
    }
}
