<?php

namespace App\Admin\Field;

use Admin;
use App\Admin\Form\FlowForm;
use Storage;

class Attachment extends \Encore\Admin\Form\Field\Table implements IFlowField
{

    use FlowFieldCommon {
        render as TraitRender;
    }

    protected $view = "field.attachment";

    public function hasFile(): bool
    {
        return true;
    }

    public function getType(): string
    {
        return 'array';
    }

    public function render()
    {
        $this->isFull = true;
        $value = $this->getValue();
        foreach ($value as &$item) {
            $path = dirname($item["url"]);
            $name = $item["name"];
            $name = $this->urlencode($name);
            $item["url"] = $path . DIRECTORY_SEPARATOR . $name;
        }
        unset($item);

        $this->addVariables([
            "onlyShow" => FlowForm::$mode == 'apply',
            "data" => $value,
            "userflow_id" => request("id")
        ]);

        return $this->TraitRender();
    }

    /**
     * \urlencode 会把 空格编码成 + 导致包含空格的文件无法下载
     * @param $str
     * @return string
     */
    private function urlencode($str)
    {
        return (string)str_replace(['%', '&'], ['%25', '%26'], $str);
    }

    /**
     * @return array
     */
    public function getValue(): array
    {
        if (empty($this->value)) {
            return [];
        }

        // 兼容老版本数据
        if (isset($this->value["file"])) {
            return $this->value['file'];
        }

        return $this->value;
    }

    public function getPostValue()
    {
        $name = $this->flowForm->name;
        $files = array_get(request()->allFiles(), $name);
        if (empty($files)) {
            return [];
        }
        $data = [];
        $subdir = Admin::user()->id . uniqid();

        $time = date("Y-m-d H:i:s");
        $user = Admin::user()->name;
        foreach ($files as $file) {
            $path = Storage::disk("attatchment")
                ->putFileAs("/$subdir/", $file, $file->getClientOriginalName());
            $data[] = [
                "name" => $file->getClientOriginalName(),
                "url" => "/upload/attatchment/$path",
                "time" => $time,
                "user" => $user,
            ];
        }

        return $data;
    }
}
