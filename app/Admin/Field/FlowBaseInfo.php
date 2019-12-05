<?php


namespace App\Admin\Field;


use Encore\Admin\Form\Field;

/**
 * 用于展示流程基本信息
 * Class FlowBaseInfo
 * @package App\Admin\Field
 */
class FlowBaseInfo extends Field
{
    private $userFlow;

    public function __construct($userFlow)
    {
        $this->userFlow = $userFlow;
    }

    public function render()
    {
        return view("field.flow-base-info")
            ->with("userFlow", $this->userFlow);
    }
}
