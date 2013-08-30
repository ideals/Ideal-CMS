<?php
namespace Ideal\Core\Site;

use Ideal\Core;
use Ideal\Core\Config;
use Ideal\Core\View;
use Ideal\Core\Request;
use Ideal\Core\Pagination;

class Controller extends Core\Controller
{
    /** @var $model Model */
    protected $model;

    /** @var bool Включение листалки (пагинации) */
    protected $isPager = true;

    /**
     * Отображение структуры в браузере
     * @param Router $router
     * @return
     * @internal param $structure
     * @internal param $actionName
     * @internal param $path
     */
    function run(Router $router)
    {
        $this->path  = $router->getPath();
        $this->model = $router->getModel();

        $controllerModelClass = str_replace('Controller', 'Model', get_called_class());
        $modelClass = get_class($this->model);
        if ($controllerModelClass != $modelClass) {
            // Если определенная роутером модель не совпадает с моделью контроллера,
            // то нужно определить модель контроллера, передав ей path и prevStructure
            $end = end($this->path);
            $prevStructure = $this->model->getprevStructure() . '-' . $end['ID'];
            $this->model = new $controllerModelClass($prevStructure);
            $this->model->setPath($this->path);
        }
        $this->model->object = end($this->path);

        $request = new Request();
        $actionName = $request->action;
        if ($actionName == '') {
            $actionName = 'index';
        }

        $actionName = $actionName . 'Action';
        $this->$actionName();

        $config = Config::getInstance();

        $this->view->domain = strtoupper($config->domain);

        $this->view->breadCrumbs = $this->model->getBreadCrumbs();

        $this->view->year = date('Y');

        $helper = new Helper();
        $helpers = $helper->getVariables($this->model);
        foreach($helpers as $k => $v) {
            $this->view->$k = $v;
        }

        $this->view->title = $this->model->getTitle();
        $this->view->metaTags = $this->model->getMetaTags($helper->xhtml);

        return $this->view->render();
    }


    public function templateInit($tplName = '')
    {
        // Инициализация общего шаблона страницы
        $gblName = 'site.twig';
        if (!stream_resolve_include_path($gblName)) {
            echo 'Нет файла основного шаблона ' . $gblName;
        }
        $gblRoot = dirname(stream_resolve_include_path($gblName));

        $parts = explode('\\', get_class($this));

        $moduleName = $parts[0];
        $moduleName = ($moduleName == 'Ideal') ? '' : $moduleName . '/';
        $structureName = $parts[2];

        // Инициализация шаблона страницы
        if ($tplName == '') {
            $tplName = $moduleName . 'Structure/' . $structureName . '/Site/index.twig';
        }
        if (!stream_resolve_include_path($tplName)) {
            echo 'Нет файла шаблона ' . $tplName;
            exit;
        }
        $tplRoot = dirname(stream_resolve_include_path($tplName));
        $tplName = basename($tplName);

        $config = Config::getInstance();
        $this->view = new View(array($gblRoot, $tplRoot), $config->isTemplateCache);
        $this->view->loadTemplate($tplName);
    }


    public function getHttpStatus()
    {
        return '';
    }


    public function getLastMod()
    {
        return '';
    }


    /**
     * Действие по умолчанию для большинства контроллеров внешней части сайта.
     * Выдёргивает контент из связанного шаблона и по этому контенту определяет заголовок (H1)
     *
     */
    public function indexAction()
    {
        $this->templateInit();

        $header = '';
        $templatesVars = $this->model->getTemplatesVars();

        if (isset($templatesVars['template']['content'])) {
            list($header, $text) = $this->model->extractHeader($templatesVars['template']['content']);
            $templatesVars['template']['content'] = $text;
        }

        foreach ($templatesVars as $k => $v) {
            $this->view->$k = $v;
        }

        $this->view->header = $this->model->getHeader($header);

        $request = new Request();
        $page = intval($request->page);

        if ($page > 1) {
            // На страницах листалки описание категории отображать не надо
            $template = $this->view->template;
            $template['content'] = '';
            $this->view->template = $template;
            // Страницы листалки неиндексируются, но ссылки с них — индексируются
            $this->model->metaTags['robots'] = 'follow, noindex';
        }
    }

}
