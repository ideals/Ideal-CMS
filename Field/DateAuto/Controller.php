<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2017 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Field\DateAuto;

use Ideal\Field\Date;

/**
 * Поле, содержащее дату в формате timestamp, автоматически обновляющуюся при каждом редактировании
 *
 * При открытии окна редактирования с этим полем, в этом поле будет установлена актуальная дата
 * todo сделать, чтобы ввод даты был блокирован и секунды/минуты/часы/дни в дате тикали с ходом времени
 * и по кнопочке можно было разблокировать автоматическую дату и ввести данные вручную
 *
 * Пример объявления в конфигурационном файле структуры:
 *     'date_mod' => array(
 *         'label' => 'Дата модификации',
 *         'sql'   => 'int(11) not null',
 *         'type'  => 'Ideal_DateAuto'
 *     ),
 */
class Controller extends Date\Controller
{

    /** {@inheritdoc} */
    protected static $instance;

    /** @var bool Флаг необходимости получить текущую дату и время, либо считывать сохранённые из БД */
    protected $getNow = false;

    /**
     * {@inheritdoc}
     */
    public function getInputText()
    {
        $this->getNow = true;
        $html = parent::getInputText();
        $this->getNow = false;
        return $html;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue()
    {
        return ($this->getNow) ? time() : parent::getValue();
    }
}
