<?php
namespace Ideal\Structure\Part\Site;

use Ideal\Core;
use Ideal\Core\Request;
use Ideal\Core\Pagination;

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

        $this->view->pager = $this->model->getPager($page, $request->getQueryWithout('page'));
    }

}
