<?php
namespace Ideal\Structure\Lead;

use Ideal\Core\Filter;
use Ideal\Core\Config;

class LeadFilter extends Filter
{

    /** @var \Ideal\Structure\Lead\Admin\ModelAbstract Объект модели лида */
    protected $leadModel = array();

    public function getSql()
    {
        $tableName = $this->leadModel->getTableName();
        $config = Config::getInstance();
        $contactPersonStructure = $config->getStructureByName('Ideal_ContactPerson');
        $addSelect = '';
        if ($contactPersonStructure) {
            $addSelect = ', cp.name as cpName, cp.email as cpEmail, cp.phone as cpPhone, \'\' as lastInteraction';
        }
        $sql = 'SELECT e.*' . $addSelect . ' FROM ' . $tableName  . ' AS e ';
        if (empty($this->where)) {
            $this->generateWhere();
        }
        if (empty($this->orderBy)) {
            $this->generateOrderBy();
        }
        $leftJoin = $this->generateLeftJoin();
        $sql .= $leftJoin . $this->where . $this->orderBy;
        return $sql;
    }

    public function getSqlCount()
    {
        $tableName = $this->leadModel->getTableName();
        $sql = 'SELECT COUNT(e.ID) FROM ' . $tableName . ' AS e ';
        if (empty($this->where)) {
            $this->generateWhere();
        }
        $sql .= $this->where;
        return $sql;
    }

    /**
     * Устанавливает объект модели лида
     *
     * @param $leadModel \Ideal\Structure\Lead\Admin\ModelAbstract Объект модели лида
     */
    public function setLeadModel($leadModel)
    {
        $this->leadModel = $leadModel;
    }

    /**
     * Генерирует where часть запроса
     *
     */
    protected function generateWhere()
    {
        $this->where = '';
    }

    /**
     * Генерирует часть запроса отвечающую за присоединение таблиц
     *
     */
    protected function generateLeftJoin()
    {
        $config = Config::getInstance();
        $leadStructure = $config->getStructureByName('Ideal_Lead');
        $contactPersonAddonTable = $config->getTableByName('Ideal_ContactPerson', 'Addon');
        $leftJoin = " LEFT JOIN {$contactPersonAddonTable} as cpa";
        $leftJoin .= "  ON cpa.prev_structure = CONCAT_WS('-', {$leadStructure['ID']}, e.ID)";
        $contactPersonStructureTable = $config->getTableByName('Ideal_ContactPerson');
        $leftJoin .= " LEFT JOIN {$contactPersonStructureTable} as cp ON cp.ID = cpa.contact_person ";
        return $leftJoin;
    }

    /**
     * Генерирует order by часть запроса
     */
    protected function generateOrderBy()
    {
        $this->orderBy = ' ORDER BY e.ID';
    }
}
