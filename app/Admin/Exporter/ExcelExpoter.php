<?php

namespace App\Admin\Exporter;

use Encore\Admin\Grid\Exporters\AbstractExporter;

use Maatwebsite\Excel\Facades\Excel;

class ExcelExpoter extends AbstractExporter
{
    private $name;

    public function __construct($name)
    {
        parent::__construct(null);
        $name || $name = date('Y-m-d');
        $this->name = $name . ".xls";
    }

    /**
     * {@inheritdoc}
     */
    public function export()
    {
        $titles = [];
        foreach ($this->grid->visibleColumns() as $column) {
            $titles[] = $column->getLabel();
        }

        $data = [$titles];
        $this->grid->build();
        foreach ($this->grid->rows() as $row) {
            $obj = [];
            foreach ($this->grid->visibleColumnNames() as $name) {
                if (in_array($name, ['__actions__', '__row_selector__'])) {
                    continue;
                }
                $obj[] = strip_tags($row->column($name));
            }

            $data[] = $obj;
        }

        Excel::download(new FromArray($data), $this->name)->send();
    }
}

