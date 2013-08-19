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
        $this->templateInit();

        $prev = $this->path[(count($this->path) - 2)];
        $end = end($this->path);
        $className = Util::getClassName($prev['structure'], 'Structure') . '\\Site\\Model';
        $part = new $className($end['structure_path']);
        $part->object = $this->model->object;

        $header = '';
        $templatesVars = $part->getTemplatesVars();

        if (isset($templatesVars['template']['content'])) {
            list($header, $text) = $part->extractHeader($templatesVars['template']['content']);
            $templatesVars['template']['content'] = $text;
        }

        foreach($templatesVars as $k => $v) {
            $this->view->$k = $v;
        }

        $this->view->header = $this->model->getHeader($header);

        $request = new Request();
        $page = intval($request->page);

        $this->view->parts = $this->model->getList($page);

        // Отображение листалки
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
