<?php

namespace App\Models\Flow;

class UserFlowDataSource extends Model
{

    public function getJsonAttribute($value)
    {
        if (is_string($value)) {
            return json_decode($value, true) ?: [];
        }

        return $value;
    }

    public function setJsonAttribute($value)
    {
        if (!is_string($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        $this->attributes['json'] = $value;
    }

    public static function saveJson($json)
    {
        if (empty($json)) {
            return null;
        }
        if (!is_string($json)) {
            $json = json_encode($json, JSON_UNESCAPED_UNICODE);
        }
        $md5 = md5($json);
        if ($model = static::whereMd5($md5)->first()) {
            return $model;
        }

        return static::create([
            "json" => $json,
            "md5" => $md5,
        ]);
    }
}
