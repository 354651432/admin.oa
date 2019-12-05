<?php


namespace App\Admin\Exporter;

use Maatwebsite\Excel\Concerns\FromArray as IFromArray;

class FromArray implements IFromArray
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function array(): array
    {
        return $this->data;
    }
}
