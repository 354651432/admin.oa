<?php


namespace App\Admin\Field;


use App\Admin\Form\FlowForm;
use Encore\Admin\Form\Field\Id;

class OrderId extends Id implements IFlowField
{
    use FlowFieldCommon {
        render as TraitRender;
    }

    protected $view = "admin::form.id";

    const DAILY_EXPENSE_PREFIX = 'B-DAILY';

    public function render()
    {
        if (FlowForm::$mode != 'apply') {
            $this->value = static::getOrderId(\Admin::user()->company);
        }

        return $this->TraitRender();
    }

    public static function getOrderId($company)
    {
        $companyTag = $company->contract_tag ?? "";
        return self::DAILY_EXPENSE_PREFIX . $companyTag . date('YmdHis') . mt_rand(1000, 9999);
    }
}
