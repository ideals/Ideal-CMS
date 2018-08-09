<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\Crm\Lead;

/**
 * Класс для получения списка лидов и взаимодействий разных типов
 */
class Model extends \Ideal\Core\Admin\Model
{
    /**  \Ideal\Structure\Lead\Admin\Model|\Ideal\Structure\Interaction\Admin\Model
     * Вспомогательная модель для отображения списка
     */
    public $subModel = null;

    public function __construct($prevStructure)
    {
        parent::__construct($prevStructure);
        $prevStructureParts = explode('-', $prevStructure);
        $prevStructureParts = array_slice($prevStructureParts, -2);
        if ((int)$prevStructureParts[0] !== 0) {
            $this->subModel = new \Ideal\Structure\Lead\Admin\Model($prevStructure);
        } else {
            $this->subModel = new \Ideal\Structure\Interaction\Admin\Model($prevStructure);
        }
    }

    public function getHeaderNames()
    {
        $this->params = $this->subModel->params;
        $this->fields = $this->subModel->fields;
        return parent::getHeaderNames();
    }

    public function getList($page = null)
    {
        $list = array();
        $tempList = $this->subModel->getList($page);

        // Если получаем списко лидов, то
        // складываем данные по контактным лицам в элемент массива списка лидов
        if ($this->subModel instanceof \Ideal\Structure\Lead\Admin\Model) {
            foreach ($tempList as $lead) {
                if (!isset($list[$lead['ID']])) {
                    $list[$lead['ID']] = array_slice($lead, 0, 4);
                }
                $list[$lead['ID']]['contactPerson'][] = array_slice($lead, 4);
            }
        } else {
            $list = $tempList;
        }

        return $list;
    }

    public function getHeader()
    {
        return $this->subModel->getHeader();
    }

    public function getPath()
    {
        return $this->subModel->getPath();
    }

    public function getPager($pageName)
    {
        return $this->subModel->getPager($pageName);
    }
}
