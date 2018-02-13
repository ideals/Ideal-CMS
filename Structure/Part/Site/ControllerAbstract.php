<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\Part\Site;

use Ideal\Core;
use Ideal\Core\Request;

class ControllerAbstract extends Core\Site\Controller
{

    /** @var bool Включение листалки (пагинации) */
    protected $isPager = false;

    public function indexAction()
    {
        parent::indexAction();

        $request = new Request();
        $page = intval($request->page);
        $this->view->parts = $this->model->getList($page);

        $this->view->pager = $this->model->getPager('page');
    }
}
