<?php
namespace Ideal\Core\Site;

use Ideal\Core;
use Ideal\Core\Config;
use Ideal\Core\Request;
use Ideal\Core\View;
use Ideal\Structure\User;

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
     * @param string $tplName Название файла шаблона (с путём к нему), если не задан - будет index.twig
     * @param array $tplFolders Список дополнительных папок с файлами шаблонов
     */
    public function templateInit($tplName = '', $tplFolders = array())
    {
        // Если вьюха уже установлена, то ничего делать не надо
        // todo для переустановки вьюхи надо придумать отдельный метод, когда это потребуется
        if (isset($this->view)) {
            return;
        }
        // Инициализация общего шаблона страницы
        $gblName = 'site.twig';
        if (!stream_resolve_include_path($gblName)) {
            echo 'Нет файла основного шаблона ' . $gblName;
        }
        $gblRoot = dirname(stream_resolve_include_path($gblName));

        // Инициализация шаблона страницы
        if ($tplName == '') {
            if ($this->tplName == '') {
                $tplName = $this->getPathToTwigTemplate('index.twig');
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
        $tplName = '';
        $pageData = $this->model->getPageData();

        //Определяем шаблон для отображения
        if (!empty($pageData['template'])) {
            $tplName = $this->getPathToTwigTemplate($pageData['template']);
        }

        $this->templateInit($tplName);

        // Выдёргиваем заголовок из addonName[key]['content']
        $this->view->header = $this->model->getHeader();

        // Перенос данных страницы в шаблон
        foreach ($pageData as $k => $v) {
            $this->view->$k = $v;
        }

        $request = new Request();
        $page = intval($request->page);

        if ($page > 1) {
            // На страницах листалки описание категории отображать не надо
            $addons = json_decode($this->view->addon);
            foreach ($addons as $addon) {
                list(, $addonName) = explode('_', $addon[1]);
                $addonName = strtolower($addonName);
                foreach ($this->view->$addonName as $key => $value) {
                    $this->view->$addonName[$key]['content'] = '';
                }
            }
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
    public function run(Router $router)
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

        if (method_exists($this, $actionName)) {
            // Вызываемый action существует, запускаем его
            $this->$actionName();
        } else {
            // Вызываемый action отсутствует, запускаем 404 ошибку
            $this->error404Action();
            $this->model->is404 = true;
        }

        $config = Config::getInstance();

        $this->view->domain = strtoupper($config->domain);
        $this->view->startUrl = $config->cms['startUrl'];
        $this->view->minifier = $config->cache['jsAndCss']; // флаг включения минификации js и css

        $this->view->breadCrumbs = $this->model->getBreadCrumbs();

        $this->view->year = date('Y');

        // Определение места выполнения скрипта (на сайте в production, или локально в development)
        $this->view->isProduction = $config->domain == str_replace('www.', '', $_SERVER['HTTP_HOST']);

        // Определение залогинен пользователь в админку или нет
        $user = new User\Model();
        $this->view->isAdmin = $user->checkLogin();

        $helper = new Helper();
        $helpers = $helper->getVariables($this->model);
        foreach ($helpers as $k => $v) {
            $this->view->$k = $v;
        }

        $this->view->title = $this->model->getTitle();
        $this->view->metaTags = $this->model->getMetaTags($helper->xhtml);

        // Проводим финальные модификации контента в контроллере отображаемой структуры
        $this->finishMod($actionName);

        // Twig рендерит текст странички из шаблона
        $text = $this->view->render();

        // Проводим финальные модификации страницы, общие для всех страниц
        if (method_exists($helper, 'finishMod')) {
            $text = $helper->finishMod($text);
        }

        return $text;
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

    /**
     * Получение пути до twig шаблона структуры
     *
     * @param string $tplName Тип класса (например, Structure или Field)
     * @return string
     */
    private function getPathToTwigTemplate($tplName)
    {
        $parts = explode('\\', get_class($this));
        $moduleName = ($parts[0] == 'Ideal') ? '' : $parts[0] . '/';
        return $moduleName . $parts[1] . '/' . $parts[2] . '/Site/' . $tplName;
    }
}
