<?php

namespace App\Models\Flow;

use App\Services\FlowService;

class FlowNode extends Model
{
    //
    public static $types = [
        0 => "普通",
        1 => "并签",
        2 => "或签",
    ];

    protected $with = ['conditions'];

    public function conditions()
    {
        return $this->hasMany(FlowCond::class, "flow_node_id", "id");
    }

    public function getOperator($useId)
    {
        $flowService = new FlowService();
        return $flowService->getOperator($this->op, $useId);
    }
}
