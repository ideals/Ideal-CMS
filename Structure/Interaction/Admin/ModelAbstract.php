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
use Ideal\Core\Request;
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
        if ($this->toParent()) {
            $list = parent::getList($page);
        } else {
            $list = $this->getElementsList();

            // Учитываем пагинацию
            $request = new Request();
            $offset = $request->page && $request->page != 1 ? ($request->page - 1) * $this->params["elements_cms"] : 0;
            $list = array_slice($list, $offset, $this->params["elements_cms"]);
        }

        return $list;
    }

    public function getPath()
    {
        // Так как структура "Взаимодействий" доступна только лишь в разделе "CRM",
        // то при запросе пути формируем отдельный дополнительный элемент пути.
        // Необходимо для правильного отображения хлебных крошек.
        $request = new Request();
        $path =  parent::getPath();
        $config = Config::getInstance();
        $par = explode('-', $request->par);
        $structure = $config->getStructureById($par[0]);
        $path[] = array('ID' => end($par), 'name' => $this->getHeader(), 'structure' => $structure['structure']);
        return $path;
    }

    public function getListCount()
    {
        if ($this->toParent()) {
            $listCount = parent::getListCount();
        } else {
            $list = $this->getElementsList();
            $listCount = count($list);
        }
        return $listCount;
    }


    /**
     * Собирает все элементы всех возможных "Взаимодействий"
     *
     * @return array
     */
    private function getElementsList()
    {
        $list = array();
        $prevStructureParts = $this->getPrevStructureParts();

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
        return $list;
    }


    /**
     * Разбивает преструктуру на части
     *
     * @return array части преструктуры
     */
    private function getPrevStructureParts()
    {
        $prevStructure = $this->getPrevStructure();
        return explode('-', $prevStructure);
    }

    /**
     * Проверяет надобность запроса списка элементов от родительского класса
     *
     * @return bool флаг надобности запроса списка элементов от родительского класса
     */
    private function toParent()
    {
        // Если преструктура содержит название структуры родителя,
        // то список запрашивается с внутренних страниц раздела "CRM".
        // Требуется генерация списка особым способом.
        $prevStructureParts = $this->getPrevStructureParts();
        if ((int)$prevStructureParts[0] !== 0) {
            return true;
        }
        return false;
    }
}
