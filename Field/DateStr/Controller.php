<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Field\Date;

use Ideal\Core\Request;
use Ideal\Field\AbstractController;

/**
 * Поле, содержащее дату в формате MySQL Timestamp
 *
 * Для редактирования подключается jQuery-плагин datetimepicker.js, который использует библиотеку Moment.
 * Пример объявления в конфигурационном файле структуры:
 *     'date_create' => array(
 *         'label' => 'Дата создания',
 *         'sql'   => 'timestamp',
 *         'type'  => 'Ideal_DateStr'
 *     ),
 */
class Controller extends AbstractController
{

    /** {@inheritdoc} */
    protected static $instance;

    /**
     * {@inheritdoc}
     */
    public function getInputText()
    {
        $value = $this->getValue();
        $date = empty($value) ? '' : date('d.m.Y H:i:s', strtotime($value));
        $htmlName = $this->htmlName;
        $html = <<<HTML
<link href="Ideal/Library/datetimepicker/build/css/bootstrap-datetimepicker.min.css" rel="stylesheet" type="text/css" >
<script type="text/javascript" src="Ideal/Library/moment/moment.js"></script>
<script type="text/javascript" src="Ideal/Library/moment/locale/ru.js"></script>
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
            format: 'DD.MM.YYYY HH:mm:ss',
            locale: 'ru'
        });
    });
</script>
HTML;

        return $html;
    }

    /**
     * {@inheritdoc}
     */
    public function getValueForList($values, $fieldName)
    {
        return date('d.m.Y &\nb\sp; H:i', strtotime($values[$fieldName]));
    }

    /**
     * {@inheritdoc}
     */
    public function pickupNewValue()
    {
        $request = new Request();

        $fieldName = $this->htmlName;
        $newValue = $request->$fieldName;

        if (empty($newValue)) {
            return '0';
        }

        $dateTime = date_create_from_format('d.m.Y H:i:s', $newValue);
        if ($dateTime === false) {
            // Ошибка в формате введённой даты
            return '';
        }

        $this->newValue = $dateTime->format('Y-m-d H:i:s');
        return $this->newValue;
    }
}
