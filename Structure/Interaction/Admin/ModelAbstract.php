<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\Interaction\Admin;

use Ideal\Core\Config;
use Ideal\Core\Util;
use Ideal\Structure\Interaction\InteractionInterface;
use Ideal\Addon\ContactPerson\AdminModel as AddonContactPersonAdminModel;

class ModelAbstract extends \Ideal\Structure\Roster\Admin\ModelAbstract implements InteractionInterface
{
    /** Список взаимодействий */
    private $elements;

    /**
     * Собирет все возможные взаимодействия подключенные в конфиге
     * @param string $prevStructure пре-структура элемента
     */
    public function __construct($prevStructure)
    {
        parent::__construct($prevStructure);
        $config = Config::getInstance();

        // Ищем модели реализующие интерфейс "InteractionInterface"
        foreach ($config->structures as $structure) {
            $modelName = Util::getClassName($structure['structure'], 'Structure') . '\\Admin\\Model';
            if ($this instanceof $modelName) {
                continue;
            }
            $implements = class_implements($modelName);
            if ($implements && isset($implements['Ideal\Structure\Interaction\InteractionInterface'])) {
                $this->addElement(new $modelName(''));
            }
        }
    }

    /**
     * @param InteractionInterface $element
     */
    public function addElement(InteractionInterface $element)
    {
        $this->elements[] = $element;
    }

    public function getInteractions($contactPersons)
    {
        $config = Config::getInstance();
        $interactions = array();
        foreach ($this->elements as $element) {
            $structure = $config->getStructureByClass(get_class($element));
            $interactions[$structure['structure']] = $element->getInteractions($contactPersons);
        }
        return $interactions;
    }

    public function getHeader()
    {
        $config = Config::getInstance();
        $structure = $config->getStructureByClass(get_class($this));
        return $structure['name'];
    }

    public function getList($page = null)
    {
        $list = array();


        // Если список запрашивается с внутренних страниц раздела "CRM",
        // то преструктура содержит название стркутуры родителя
        $prevStructure = $this->getPrevStructure();
        $prevStructureParts = explode('-', $prevStructure);
        if ((int)$prevStructureParts[0] !== 0) {
            $list = parent::getList($page);
        } else {
            // Составляем престркутуру для получения списка контактных лиц из аддона "Контактное лицо"
            $config = Config::getInstance();
            $structure = $config->getStructureByName($prevStructureParts[0]);
            $prevStructure = $structure['ID'] . '-' . $prevStructureParts[1];

            // Получаем идентификаторы кантактных лиц из аддона
            $contactPersonAddon = new AddonContactPersonAdminModel($prevStructure);
            $contactPersonsList = $contactPersonAddon->getList();
            $contactPersons = array();
            foreach ($contactPersonsList as $contactPerson) {
                $contactPersons[$contactPerson['contact_person']] = $contactPerson['contact_person'];
            }
            $interactions = $this->getInteractions($contactPersons);

            // Формируем из всех взаимодействий общий список пригодный для отображения
            foreach ($interactions as $interactionType => $interactionsOfType) {
                $structure = $config->getStructureByName($interactionType);
                foreach ($interactionsOfType as $interaction) {
                    $interaction['interaction_type'] = $structure['name'];
                    $interaction['structureId'] = $structure['ID'];
                    $list[] = $interaction;
                }
            }
        }

        return $list;
    }
}
