<?php

namespace App\Admin\Field;


use Encore\Admin\Form\Field;

/**
 * 日常报销
 * Class DaliyFee
 * @package App\Admin\Field
 */
class DaliyFee extends Table
{

    protected $view = "field.daliy-fee";

    protected function makeControls()
    {
        return [
            "日期" => new Field\Date("date"),
            "费用类型" => tap(new Field\Select("type"),
                function (Field\Select $item) {
                    $item->options([
                        "差旅费-市外-在途费用",
                        "差旅费-市外-员工住宿费",
                        "差旅费-市外-出差补助",
                        "业务招待费",
                        "其他",
                    ]);
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
