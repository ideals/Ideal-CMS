<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Field\OuterList;

use Ideal\Field\AbstractController;

/**
 * Поле, недоступное для редактирования пользователем в админке.
 *
 * Отображается в виде скрытого поля ввода <input type="hidden" />
 * Используется в структуре "Ideal_Lead" для отображения в списке элементов данных нескольких лиц, отнесённых к лиду
 *
 * Пример объявления в конфигурационном файле структуры:
 *     'cpEmail' => array(
 *         'label' => 'Почта',
 *         'type' => 'Ideal_CpEmail',
 *         'array' => 'contactPerson',
 *     ),
 */
class Controller extends \Ideal\Field\Hidden\Controller
{
    /** {@inheritdoc} */
    protected static $instance;

    /**
     * @inheritdoc
     */
    public function getValueForList($values, $fieldName)
    {
        $listValue = '';
        $valuesList = array();
        $arrayName = $this->field['array'];
        if (isset($values[$arrayName])) {
            foreach ($values[$arrayName] as $contactPerson) {
                if (isset($contactPerson[$fieldName])) {
                    $valuesList[] = $contactPerson[$fieldName];
                }
            }
        }
        if ($valuesList) {
            $listValue .= '<ul class="list-group"><li class="list-group-item">';
            $listValue .= implode('</li><li class="list-group-item">', $valuesList) . '</li></ul>';
        }
        return $listValue;
    }
}
