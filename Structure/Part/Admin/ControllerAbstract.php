<?php
namespace Ideal\Structure\Part\Admin;

use Ideal\Core\Request;
use Ideal\Core\Util;

class ControllerAbstract extends \Ideal\Core\Admin\Controller
{

    /* @var $model Model */
    protected $model;

    public function indexAction()
    {
        $this->templateInit();

        // Считываем список элементов
        $request = new Request();
        $page = intval($request->page);
        $listing = $this->model->getList($page);
        $headers = $this->model->getHeaderNames();

        $this->parseList($headers, $listing);

        $this->view->pager = $this->model->getPager('page');
    }
}
