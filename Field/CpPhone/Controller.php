<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Field\CpPhone;

use Ideal\Field\AbstractController;

/**
 * Поле, недоступное для редактирования пользователем в админке.
 *
 * Отображается в виде скрытого поля ввода <input type="hidden" />
 *
 * Используется в структуре "Ideal_Lead" для отображения телефонов из списка Контактных лиц отнесённых к лиду
 *
 * Пример объявления в конфигурационном файле структуры:
 *     'cpPhone' => array(
 *         'label' => 'Телефон',
 *         'type' => 'Ideal_CpPhone'
 *     ),
 */
class Controller extends AbstractController
{

    /** {@inheritdoc} */
    protected static $instance;

    /**
     * {@inheritdoc}
     */
    public function showEdit()
    {
        $this->htmlName = $this->groupName . '_' . $this->name;
        $input = $this->getInputText();
        return $input;
    }

    /**
     * {@inheritdoc}
     */
    public function getInputText()
    {
        return '<input type="hidden" id="' . $this->htmlName
        . '" name="' . $this->htmlName
        . '" value="' . $this->getValue() . '">';
    }

    /**
     * @inheritdoc
     */
    public function getValueForList($values, $fieldName)
    {
        $listValue = '';
        $valuesList = array();
        if (isset($values["contactPerson"])) {
            foreach ($values["contactPerson"] as $contactPerson) {
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
