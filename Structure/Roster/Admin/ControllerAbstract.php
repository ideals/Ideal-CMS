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
        $onPage = $this->model->params['elements_cms'];
        $listing = $this->model->getList($page);
        $headers = $this->model->getHeaderNames();

        $this->parseList($headers, $listing);

        $pagination = new Pagination();
        $this->view->pages = $pagination->getPages($this->model->getListCount(),
            $onPage, $page, $request->getQueryWithout('page'), 'page');

        $this->view->pagePrev = $pagination->getPrev();
        $this->view->pageNext = $pagination->getNext();
    }

}
