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
            $addSelect = ', cp.name as cpName';
        }
        $sql = 'SELECT e.*' . $addSelect . ' FROM ' . $tableName  . ' AS e ';
        if (empty($this->where)) {
            $this->generateWhere();
        }
        if (empty($this->orderBy)) {
            $this->generateOrderBy();
        }
        $leftJoin = $this->generateLeftJoin();
        $sql .= $leftJoin . $this->where . $this->orderBy . ' GROUP BY e.ID';
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
        $leftJoin = '';
        $contactPersonStructure = $config->getStructureByName('Ideal_ContactPerson');
        if ($contactPersonStructure) {
            $contactPersonStructureTable = $config->getTableByName('Ideal_ContactPerson');
            $leftJoin = " LEFT JOIN {$contactPersonStructureTable} as cp ON cp.lead = e.ID ";
        }
        return $leftJoin;
    }
}
