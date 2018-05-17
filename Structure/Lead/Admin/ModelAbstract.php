<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\Lead\Admin;

use Ideal\Structure\Lead\LeadFilter;

class ModelAbstract extends \Ideal\Structure\Roster\Admin\ModelAbstract
{
    /** @var mixed null - если фильтр не установлен, Объект фильтра если фильтр был применён */
    public $filter = null;

    public function __construct($prevStructure)
    {
        parent::__construct($prevStructure);
        $this->filter = new LeadFilter();
    }

    public function getList($page = null)
    {
        $this->filter->setLeadModel($this);
        return parent::getList($page);
    }
}
