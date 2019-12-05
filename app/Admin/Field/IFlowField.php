<?php


namespace App\Admin\Field;


use App\Models\Flow\FlowForm;
use App\Models\Flow\UserFlowForm;

interface IFlowField
{
    /**
     * IFlowField constructor.
     * @param FlowForm|UserFlowForm $flowForm
     */
    public function __construct($flowForm);

    /**
     * 数据填充 Field类有默认实现
     * @param $data
     * @return mixed
     */
    public function fill($data);

    public function getJs();

    public function getCss();

    public function getHtml();

    public function getScript();

    public function getOptions();

    public function getType(): string;

    public function getPostValue();

    public function validate(): bool;

    public function getMessage();

    public function hasFile(): bool;

    /**
     * 生成流程标题逻辑
     * @return string
     */
    public function title(): string;

    /**
     * 发起流程时的备份数据表id
     * @return mixed
     */
    public function sourceId();
}
