<?php
namespace Ideal\Core\Site;

use Ideal\Core;
use Ideal\Core\Config;
use Ideal\Core\Request;
use Ideal\Core\View;

class Controller
{

    /** @var bool Включение листалки (пагинации) */
    protected $isPager = true;

    /* @var $model Model Модель соответствующая этому контроллеру */
    protected $model;

    /* @var $path array Путь к этой странице, включая и её саму */
    protected $path;

    /** @var string Имя файла с нестандартным шаблоном view */
    protected $tplName = '';

    /* @var $view View Объект вида — twig-шаблонизатор */
    protected $view;

    /**
     * Действие для отсутствующей страницы сайта (обработка ошибки 404)
     */
    public function error404Action()
    {
        $name = $title = 'Страница не найдена';
        $this->templateInit('404.twig');

        // Добавляем в path пустой элемент
        $path = $this->model->getPath();
        $path[] = array('ID' => '', 'name' => $name, 'url' => '404');
        $this->model->setPath($path);

        // Устанавливаем нужный нам title
        $pageData = $this->model->getPageData();
        $pageData['title'] = $title;
        $this->model->setPageData($pageData);
    }

    /**
     * Инициализация twig-шаблона сайта
     *
     * @param string $tplName    Название файла шаблона (с путём к нему), если не задан - будет index.twig
     * @param array  $tplFolders Список дополнительных папок с файлами шаблонов
     */
    public function templateInit($tplName = '', $tplFolders = array())
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
            if ($this->tplName == '') {
                $tplName = $moduleName . 'Structure/' . $structureName . '/Site/index.twig';
            } else {
                $tplName = $this->tplName;
            }
        }
        if (!stream_resolve_include_path($tplName)) {
            echo 'Нет файла шаблона ' . $tplName;
            exit;
        }
        $tplRoot = dirname(stream_resolve_include_path($tplName));
        $tplName = basename($tplName);

        // Построение полных путей для дополнительных папок шаблонов
        if (count($tplFolders) > 0) {
            foreach ($tplFolders as $k => $v) {
                $tplFolders[$k] = stream_resolve_include_path($v);
            }
        }

        // Определяем корневую папку системы для подключение шаблонов из любой вложенной папки через их путь
        $config = Config::getInstance();
        $cmsFolder = DOCUMENT_ROOT . '/' . $config->cmsFolder;

        $config = Config::getInstance();
        $folders = array_merge(array($tplRoot, $gblRoot, $cmsFolder), $tplFolders);
        $this->view = new View($folders, $config->cache['templateSite']);
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
     * Действие по умолчанию для большинства контроллеров внешней части сайта.
     * Выдёргивает контент из связанного шаблона и по этому контенту определяет заголовок (H1)
     *
     */
    public function indexAction()
    {
        $this->templateInit();

        // Выдёргиваем заголовок из template['content']
        $this->view->header = $this->model->getHeader();

        // Перенос данных страницы в шаблон
        $pageData = $this->model->getPageData();
        foreach ($pageData as $k => $v) {
            $this->view->$k = $v;
        }

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

    /**
     * Отображение структуры в браузере
     *
     * @param Router $router
     * @return string Содержимое отображаемой страницы
     */
    function run(Router $router)
    {
        $this->model = $router->getModel();

        // Определяем и вызываем требуемый action у контроллера
        if ($router->is404()) {
            $actionName = 'error404';
        } else {
            $request = new Request();
            $actionName = $request->action;
            if ($actionName == '') {
                $actionName = 'index';
            }
        }

        $actionName = $actionName . 'Action';
        $this->$actionName();

        $config = Config::getInstance();

        $this->view->domain = strtoupper($config->domain);
        $this->view->startUrl = $config->cms['startUrl'];
        $this->view->minifier = $config->cache['jsAndCss']; // флаг включения минификации js и css

        $this->view->breadCrumbs = $this->model->getBreadCrumbs();

        $this->view->year = date('Y');

        $helper = new Helper();
        $helpers = $helper->getVariables($this->model);
        foreach ($helpers as $k => $v) {
            $this->view->$k = $v;
        }

        $this->view->title = $this->model->getTitle();
        $this->view->metaTags = $this->model->getMetaTags($helper->xhtml);

        $this->finishMod($actionName);

        return $this->view->render();
    }

    /**
     * Внесение финальных изменений в шаблон, после всех-всех-всех
     *
     * @param string $actionName
     */
    public function finishMod($actionName)
    {
    }

    /**
     * Установка нестандартного шаблона View
     *
     * @param string $tplName Путь к файлу шаблона от Ideal или от Mods (не включая эти папки)
     */
    public function setTemplate($tplName)
    {
        $this->tplName = $tplName;
    }
}
