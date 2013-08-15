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
        $date = getdate($value);
        $dayName   = $this->htmlName . '_day';
        $monthName = $this->htmlName . '_month';
        $yearName  = $this->htmlName . '_year';
        $hourName  = $this->htmlName . '_hour';
        $minName   = $this->htmlName . '_min';
        $html = <<<HTML
        <input type="text" class="inline" name="{$dayName}" value="{$date['mday']}" style="width:20px;" maxlength="2">.
        <input type="text" class="inline" name="{$monthName}" value="{$date['mon']}" style="width:20px;" maxlength="2">.
        <input type="text" class="inline" name="{$yearName}" value="{$date['year']}" style="width:40px;" maxlength=4> [дд.мм.гггг]&nbsp;&nbsp;&nbsp;
        <input type="text" class="inline" name="{$hourName}" value="{$date['hours']}" style="width:20px;" maxlength="2">:
        <input type="text" class="inline" name="{$minName}" value="{$date['minutes']}" style="width:20px;" maxlength="2"> [чч:мм]
HTML;

        return $html;
    }


    public function getValueForList($values, $fieldName)
    {
        return date('d.m.Y &\nb\sp; h:i', $values[$fieldName]);
    }


    public function pickupNewValue()
    {
        $request = new Request();

        // Считываем все части даты из request, если хотя бы одна не указана - выходим

        $fieldName = $this->htmlName . '_day';
        $day = $request->$fieldName;
        if ($day < 1) return '';

        $fieldName = $this->htmlName . '_month';
        $month = $request->$fieldName;
        if ($month < 1) return '';

        $fieldName = $this->htmlName . '_year';
        $year = $request->$fieldName;
        if ($year < 1970) return '';

        $fieldName = $this->htmlName . '_hour';
        $hour = $request->$fieldName;
        if ($hour == '') return '';

        $fieldName = $this->htmlName . '_min';
        $min = $request->$fieldName;
        if ($min == '') return '';

        $this->newValue = mktime($hour, $min, 0, $month, $day, $year);

        return $this->newValue;
    }

}
