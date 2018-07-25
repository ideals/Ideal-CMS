<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\Crm\Lead;

use Ideal\Core\Request;

/**
 * Класс для построение бокового меню в разделе CRM и запуска скриптов выбранного пункта
 */
class Model extends \Ideal\Core\Admin\Model
{
    /**  /Ideal\Structure\Lead\Admin\Model Модель лида административной части */
    public $leadModel = null;

    public function __construct($prevStructure)
    {
        parent::__construct($prevStructure);
        $this->leadModel = new \Ideal\Structure\Lead\Admin\Model('');
    }

    /**
     * @return array список лидов
     * @throws \Exception
     */
    public function getLeadOrders()
    {
        $request = new Request();
        if ($request->leadId) {
            $this->leadModel->setPageDataById($request->leadId);
            $data = $this->leadModel->getLeadOrders();
            return $data;
        }
        return array();
    }

    public function getHeaderNames()
    {
        $this->params = $this->leadModel->params;
        $this->fields = $this->leadModel->fields;
        return parent::getHeaderNames();
    }

    public function getList($page = null)
    {
        return $this->leadModel->getList($page);
    }
}
