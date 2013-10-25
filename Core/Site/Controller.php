<?php
namespace Ideal\Core\Site;

use Ideal\Core;
use Ideal\Core\Config;
use Ideal\Core\View;
use Ideal\Core\Request;

class Controller
{
    /* @var $model Model Модель соответствующая этому контроллеру */
    protected $model;
    /* @var $path array Путь к этой странице, включая и её саму */
    protected $path;
    /* @var $view View Объект вида — twig-шаблонизатор */
    protected $view;
    /** @var bool Включение листалки (пагинации) */
    protected $isPager = true;

    /**
     * Отображение структуры в браузере
     * @param Router $router
     * @return string Содержимое отображаемой страницы
     */
    function run(Router $router)
    {
        /* @var $this->model Model Модель соответствующая этому контроллеру */
        $this->model = $router->getModel()->detectActualModel();

        $this->model->initPageData();

        // Определяем и вызываем требуемый action у контроллера
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

        $this->finishMod($actionName);

        return $this->view->render();
    }

    /**
     * Инициализация twig-шаблона сайта
     * @param string $tplName Название файла шаблона (с путём к нему), если не задан - будет index.twig
     */
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

    /**
     * Получение дополнительных HTTP-заголовков
     * По умолчанию система ставит только заголовок Content-Type, но и его можно
     * переопределить в этом методе.
     *
     * @return array Массив где ключи - названия заголовков, а значения - содержание заголовков
     */
    public function getHttpHeaders()
    {
        return array(
            // Дата последней модификации страницы
            // 'Last-Modified' => gmdate('D, d M Y H:i:s', $lastMod ) . ' GMT',
            // Затирание информации о языке, на котором написан сайт
            // 'X-Powered-By' => 'Hello, man!',
            // Дата завершения срока годности странички :)
            // 'Expires' => gmdate('D, d M Y H:i:s')+900 . ' GMT\r\n',
            // Варианты управления кэшем. Можно выбрать только один из вариантов.
            // 'Cache-Control' => 'no-store, no-cache, must-revalidate',
            // 'Cache-Control' => 'post-check=0, pre-check=0',
            // 'Cache-Control' => 'Pragma: no-cache',
        );
    }

    /**
     * Внесение финальных изменений в шаблон, после всех-всех-всех
     * @param string $actionName
     */
    public function finishMod($actionName)
    {
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
        $pageData = $this->model->getPageData();

        foreach ($pageData as $k => $v) {
            $this->view->$k = $v;
        }

        //$this->view->header = $this->model->getHeader($header);

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
