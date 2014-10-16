<?php
namespace Ideal\Structure\YandexSearch\Site;

use Ideal\Core\Config;
use Ideal\Core\Request;

class ControllerAbstract extends \Ideal\Core\Site\Controller
{

    /** @var $model Model */
    protected $model;

    public function indexAction()
    {
        parent::indexAction();

        $request = new Request();
        $page = intval($request->{'page'});
        $page = ($page == 0) ? 1 : $page;

        $query = $request->{'search'};
        if (empty($query)) {
            $this->view->content = 'Пустой поисковый запрос';
            return true;
        }
        $this->model->setQuery($query);

        $this->view->query = $query;
        $list = $this->model->getList($page);
        if (!is_array($list)) {
            $this->view->content = $list;
            return true;
        }
        $this->view->parts = $list;
        $this->view->pager = $this->model->getPager('page');
    }
}
