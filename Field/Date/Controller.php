<?php
namespace Ideal\Field\Date;

use Ideal\Field\AbstractController;
use Ideal\Core\Request;

class Controller extends AbstractController
{
    protected static $instance;


    public function getInputText()
    {
        $value = $this->getValue();
        $date = date('d.m.Y H:i:s', $value);
        $htmlName = $this->htmlName;
        $html = <<<HTML
<link href="Ideal/Library/datetimepicker/build/css/bootstrap-datetimepicker.min.css" rel="stylesheet" type="text/css" >
<script type="text/javascript" src="Ideal/Library/moment/moment.js"></script>
<script type="text/javascript" src="Ideal/Library/moment/lang/ru.js"></script>
<script type="text/javascript" src="Ideal/Library/datetimepicker/src/js/bootstrap-datetimepicker.js"></script>

<div id="picker_{$htmlName}" class="input-group date">
    <span class="input-group-addon">
        <span class="glyphicon glyphicon-calendar" ></span>
    </span>
    <input type="text" class="form-control" name="{$htmlName}" value="{$date}" >
</div>

<script type="text/javascript">
    $(function () {
        $('#picker_{$htmlName}').datetimepicker({
            useSeconds: true,
            language: 'ru'
        });
    });
</script>
HTML;

        return $html;
    }


    public function getValueForList($values, $fieldName)
    {
        return date('d.m.Y &\nb\sp; H:i', $values[$fieldName]);
    }


    public function pickupNewValue()
    {
        $request = new Request();

        $fieldName = $this->htmlName;
        $newValue = $request->$fieldName;

        $dateTime = date_create_from_format('d.m.Y H:i:s', $newValue);
        if ($dateTime === false) {
            // Ошибка в формате введённой даты
            return '';
        }

        $this->newValue = $dateTime->getTimestamp();
        return $this->newValue;
    }
}
