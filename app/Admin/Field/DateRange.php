<?php


namespace App\Admin\Field;


class DateRange extends \Encore\Admin\Form\Field\DateRange implements IFlowField
{

    use FlowFieldCommon {
        render as TraitRender;
    }
    protected $view = "field.date-range";

    /**
     * DateRange constructor.
     * @param $flowForm
     * @param mixed ...$arg
     * @throws \Exception
     */
    public function __construct($flowForm, ...$arg)
    {
        parent::__construct($flowForm->name . "[start]", [
            $flowForm->name . "[end]",
            $flowForm->label
        ]);

        $this->flowForm = $flowForm;
        if ($flowForm->help_text) {
            $this->help($flowForm->help_text);
        }

        if (in_array("required", $this->getValidates($flowForm))) {
            $this->required();
        }

        $this->default($flowForm->default);

        $this->id = "x" . bin2hex(random_bytes(32));
    }

    public function fill($data)
    {
        $this->data = $data;

        $name = $this->flowForm->name;
        $this->value = (array)array_get($data, $name);
    }

    public function render()
    {
        $this->options['locale'] = config('app.locale');

        $startOptions = json_encode($this->options);
        $endOptions = json_encode($this->options + ['useCurrent' => false]);
        $this->script = <<<EOT
            $('#{$this->id}_start').datetimepicker($startOptions);
            $('#{$this->id}_end').datetimepicker($endOptions);
            $("#{$this->id}_start").on("dp.change", function (e) {
                $('#{$this->id}_end').data("DateTimePicker").minDate(e.date);
            });
            $("#{$this->id}_end").on("dp.change", function (e) {
                $('#{$this->id}_start').data("DateTimePicker").maxDate(e.date);
            });
EOT;

        return $this->TraitRender();
    }

    public function getType(): string
    {
        return 'array';
    }

    public function title(): string
    {
        if (is_array($this->value) && $this->value) {
            return implode("-", $this->value);
        }

        return "";
    }
}
