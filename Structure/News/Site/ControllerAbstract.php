<?php
namespace Ideal\Structure\News\Site;

use Ideal\Core\Config;
use Ideal\Core\Request;
use Ideal\Core\Pagination;
use Ideal\Core\Util;

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

        $this->view->parts = $this->model->getList($page);
        $this->view->pager = $this->model->getPager('page');

    }


    public function detailAction()
    {
        $this->templateInit('Structure/News/Site/detail.twig');

        $this->view->text = $this->model->getText();
        $this->view->header = $this->model->getHeader();

        $config = Config::getInstance();
        $parentUrl = $this->model->getParentUrl();
        $this->view->allNewsUrl = substr($parentUrl, 0, strrpos($parentUrl, '/')) . $config->urlSuffix;
    }
}
