<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\User as Member;

/**
 * 部门
 * Class Department
 * @package App\Models
 */
class Department extends Model
{
    protected $guarded = [];

    public function user()
    {
        return $this->hasOne(Member::class, "id", "manager_user_id");
    }
}
