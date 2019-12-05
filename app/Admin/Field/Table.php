<?php

namespace App\Admin\Field;

use App\Admin\Form\FlowForm;
use App\Models\Flow\UserFlowDataSource;
use Encore\Admin\Form\Field;

abstract class Table extends Field implements IFlowField
{

    public function getType(): string
    {
        return 'array';
    }

    protected $view = "field.table";

    use FlowFieldCommon {
        render as TraitRender;
    }

    abstract protected function makeControls();

    /**
     * 配置需要保存的数据源
     * @return array
     */
    public function dataSource()
    {
        return [];
    }

    public function sourceId()
    {
        return UserFlowDataSource::saveJson($this->dataSource())->id ?? 0;
    }

    public function fill($data)
    {
        $this->data = $data;
        $name = $this->flowForm->name;

        $this->value = array_get($data, $name);
    }

    private function makeName($name)
    {
        return "{$this->column}[{$name}][]";
    }

    public function render()
    {
        if (FlowForm::$mode == 'apply') {
            $options = $this->flowForm->datasource->json ?? [];
        } else {
            $options = $this->dataSource();
        }
        $this->options = $options;

        $this->isFull = true;
        list($titles, $row, $scripts) = $this->template();

        $data = $this->translateDefault();
        $controls = [];
        if ($data) {
            foreach ($data as $datum) {
                foreach ($row as $item) {
                    $item->fill((array)$datum);
                    if (FlowForm::$mode == 'apply') {
                        $item->disable();
                    }
                }
                $controls[] = $this->cloneArray($row);
            }
        } else {
            $controls = [$row];
        }

        return $this->TraitRender()
            ->with([
                "name" => $this->column,
                "label" => $this->label,
                "titles" => $titles,
                "scripts" => $scripts,
                "controls" => $controls,
                "onlyShow" => FlowForm::$mode == 'apply',
                "data" => $this->value,
                "labelClass" => implode(' ', $this->labelClass),
                "id" => $this->id,
            ]);
    }

    protected function template()
    {
        $controls = $this->makeControls();
        $titles = [];
        $htmls = [];
        $scripts = [];

        foreach ($controls as $title => $control) {
            /** @var Field $control */
            $control->setWidth(12, 0);
            if (method_exists($control, "prepend")) {
                $control->prepend("");
            }

            $control->placeholder($title);
            $control->setElementName($this->makeName($control->id));
            if ($this->attributes['required'] ?? false) {
                $control->required();
            }

            $cls = $this->getClassName();
            $control->addElementClass($cls . '-' . $control->id);

            $titles[] = $title;
            $control->render();
            unset($control->attributes["value"]);

            $control->setLabelClass(["hide"]);
            $scripts[] = $control->getScript();
            $htmls[] = $control;
        }
        return [$titles, $htmls, $scripts];
    }

    private function getClassName()
    {
        $cls = get_called_class();
        return substr($cls, strrpos($cls, "\\") + 1);
    }

    /**
     * @return array
     */
    private function translateDefault()
    {
        if (empty($this->value)) {
            return [];
        }

        $arr = (array)$this->value;

        // 解析版本 0 的数据格式
        $names = [];
        foreach ($arr as $name => &$item) {
            // 以 _ 开关的字段用来作汇总等特殊用途
            if ($name[0] == '_') {
                continue;
            }

            $names[] = $name;
            // todo 处理Select 多生成数据问题，就不用 filter了
            $item = array_filter($item, function ($it) {
                return isset($it);
            });

            $item = array_values($item);
        }
        unset($item);

        $len = count(array_first($arr));

        $ret = [];
        for ($i = 0; $i < $len; $i++) {
            $row = [];
            foreach ($names as $name) {
                $row[$name] = $arr[$name][$i] ?? null;
            }

            $ret[] = $row;
        }

        return $ret;
    }

    private function cloneArray($row)
    {
        $ret = [];
        foreach ($row as $item) {
            $ret[] = clone $item;
        }
        return $ret;
    }
}
