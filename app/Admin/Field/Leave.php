<?php

namespace App\Admin\Field;

use Encore\Admin\Form\Field;

class Leave extends Table
{

    protected $view = 'field.leave';

    protected function makeControls()
    {
        return [
            '请假类型' => tap(new Field\Select('type'),
                function ($control) {
                    $control->options($this->options ?: $this->defaultDataSource());
                }),
            '开始时间' => tap(new Field\Date('from'),
                function ($control) {
                    $control->setElementClass("input-from");
                }),
            '结束时间' => tap(new Field\Date('to'),
                function ($control) {
                    $control->setElementClass("input-to");
                }),
            '总计天数' => tap(new Field\Text('days'),
                function ($control) {
                    $control->setElementClass('input-dates');
                }),
        ];
    }

    public function dataSource()
    {
        return [
            1 => "事假",
            "病假",
            "年假",
            "婚假",
            "丧假",
            "产假",
            "陪产假",
            "其他",
            "调休",
        ];
    }

    private function defaultDataSource()
    {
        return [
            "事假",
            "病假",
            "年假",
            "婚假",
            "丧假",
            "产假",
            "陪产假",
            "其他",
            "调休",
        ];
    }
}
