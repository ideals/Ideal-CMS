<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\Roster\Admin;

use Ideal\Core\Request;

class ControllerAbstract extends \Ideal\Core\Admin\Controller
{

    /* @var $model ModelAbstract */
    protected $model;

    public function indexAction()
    {
        $this->templateInit();

        $request = new Request();

        // Считываем список элементов
        $page = intval($request->page);
        $listing = $this->model->getListAcl($page);
        $headers = $this->model->getHeaderNames();

        $this->parseList($headers, $listing);

        $this->view->pager = $this->model->getPager('page');
    }
}
