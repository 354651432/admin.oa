<?php


namespace App\Admin\Field;


use Encore\Admin\Form\Field;

/**
 * 出差申请
 * Class ApplyFee
 * @package App\Admin\Field
 */
class ApplyFee extends Table
{

    protected $view = "field.apply-fee";

    protected function makeControls()
    {
        return [
            "项目" => new Field\Text("project"),
            "费用类型" => tap(new Field\Select("type"),
                function ($item) {
                    $item->options(["市外-在途费用", "市外-员工住宿费", "市外-出差补助", "业务招待费", "其它"]);
                }),
            "金额" => new Field\Decimal("fee"),
            "摘要" => new Field\Text("desc"),
        ];
    }

    public function validate(): bool
    {
        return $this->sumValidate("_sum", "fee");
    }
}
