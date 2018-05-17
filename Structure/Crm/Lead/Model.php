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
 * Класс для построение бокового меню в разделе CRM и запуска скриптов выбранного пункта
 */
class Model extends \Ideal\Core\Admin\Model
{
    /**
     * {@inheritdoc}
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $leadModel = new \Ideal\Structure\Lead\Admin\Model('');
        $leadList = $leadModel->getList(1);
        $data['leads'] = $leadList;
        return $data;
    }
}
