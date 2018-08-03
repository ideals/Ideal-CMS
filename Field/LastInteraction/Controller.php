<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Field\LastInteraction;

use Ideal\Core\Config;
use Ideal\Field\AbstractController;
use Ideal\Structure\Interaction\Admin\Model as InteractionModel;
use Ideal\Addon\ContactPerson\AdminModel as AddonContactPersonAdminModel;

/**
 * Поле, недоступное для редактирования пользователем в админке.
 *
 * Отображается в виде скрытого поля ввода <input type="hidden" />
 *
 * Используется в структуре "Ideal_Lead" для отображения последнего взаимодействия с контактным лицом
 * отнесённым к лиду
 *
 * Пример объявления в конфигурационном файле структуры:
 *     'lastInteraction' => array(
 *         'label' => 'Дата последнего взаимодействия',
 *         'type' => 'Ideal_LastInteraction'
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
        $value = '';

        // Составляем престркутуру для получения списка контактных лиц из аддона "Контактное лицо"
        $config = Config::getInstance();
        if (isset($this->model->subModel)) {
            $structure = $config->getStructureByClass(get_class($this->model->subModel));
        } else {
            $structure = $config->getStructureByClass(get_class($this->model));
        }
        $prevStructure = $structure['ID'] . '-' . $values['ID'];

        // Получаем идентификаторы кантактных лиц из аддона
        $contactPersonAddon = new AddonContactPersonAdminModel($prevStructure);
        $contactPersonsList = $contactPersonAddon->getList();
        $contactPersons = array();
        foreach ($contactPersonsList as $contactPerson) {
            $contactPersons[$contactPerson['contact_person']] = $contactPerson['contact_person'];
        }

        $interaction = new InteractionModel('');
        $interactions = $interaction->getInteractions($contactPersons);

        // Получаем дату самого последнего взаимодействия
        // Помним что даты в базе данных хранятся в timestamp
        $lastDate = 0;
        foreach ($interactions as $interactionType) {
            foreach ($interactionType as $interaction) {
                if ($interaction['date_create'] > $lastDate) {
                    $lastDate = $interaction['date_create'];
                }
            }
        }

        if ($lastDate) {
            $value = date('d.m.Y', $lastDate);
        }

        return $value;
    }
}
