<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2015 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Field\Referer;

use Ideal\Field\AbstractController;

/**
 * Поле для работы с источником посещений
 *
 * Пример объявления в конфигурационном файле структуры:
 *     'name' => array(
 *         'label' => 'Источник посещения',
 *         'sql'   => 'varchar(255) not null',
 *         'type'  => 'Ideal_Referer'
 *     ),
 */
class Controller extends AbstractController
{

    /** @inheritdoc */
    protected static $instance;

    /**
     * {@inheritdoc}
     */
    public function getInputText()
    {
        $value = htmlspecialchars($this->getValue());
        return
            '<input type="text" class="form-control" name="' . $this->htmlName
            . '" id="' . $this->htmlName
            . '" value="' . $value . '">';
    }

    /**
     * {@inheritdoc}
     */
    public function getValueForList($values, $fieldName)
    {
        $value = parent::getValueForList($values, $fieldName);
        // Отлавливаем прямой переход
        if ($value == 'null') {
            $value = 'Прямой переход';
        } elseif (strripos($value, 'yandex') !== false) { // Отлавливаем яндекс
            $value = 'Яндекс';
        } elseif (strripos($value, 'google') !== false) { // Отлавливаем гугл
            $value = 'Google';
        } else { // Отлавливаем другие сайты
            $value = 'Другие сайты';
        }
        return $value;
    }
}
