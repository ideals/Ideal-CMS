<?php
namespace Ideal\Structure\Roster\Admin;

use Ideal\Core\Request;
use Ideal\Core\Pagination;

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
        $listing = $this->model->getList($page);
        $headers = $this->model->getHeaderNames();

        $this->parseList($headers, $listing);

        $this->view->pager = $this->model->getPager('page');
    }

}
