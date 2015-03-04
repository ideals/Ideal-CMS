<?php
namespace Ideal\Core\Admin;

use Ideal\Core;
use Ideal\Core\Config;
use Ideal\Core\Request;
use Ideal\Core\Util;
use Ideal\Core\View;
use Ideal\Structure;
use Ideal\Core\FileCache;

class Controller
{

    /** @var Model Модель соответствующая этому контроллеру */
    protected $model;

    /** @var array Путь к этой странице, включая и её саму */
    protected $path;

    /** @var View Объект вида — twig-шаблонизатор */
    protected $view;

    public function createAction()
    {
        $this->model->setPageDataNew();

        // Проверка ввода - если ок - сохраняем, если нет - сообщаем об ошибках
        $result = $this->model->parseInputParams(true);

        if ($result['isCorrect']) {
            $result = $this->model->createElement($result);
            $this->runClearFileCache();
        }

        echo json_encode($result);
        exit;
    }

    public function deleteAction()
    {
        $request = new Request();

        $result = array();
        $result['ID'] = intval($request->id);

        $this->model->setPageDataById($result['ID']);

        $result['isCorrect'] = $this->model->delete();

        if ($result['isCorrect'] == 1) {
            $this->runClearFileCache();
        }

        echo json_encode($result);
        exit;
    }

    public function editAction()
    {
        $request = new Request();
        $this->model->setPageDataById($request->id);

        // Проверка ввода - если ок - сохраняем, если нет - сообщаем об ошибках
        $result = $this->model->parseInputParams();

        // Проверяем не выбран ли другой прикреплённый шаблон
        // TODO узнать является ли эта проверка издержками работы с шаблонами, когда их можно было сменить?
        if ($request->changeTemplate == 0) {
            $result = $this->model->checkTemplateChange($result);
        }

        if ($result['isCorrect'] == 1) {
            $result = $this->model->saveElement($result);
            $this->runClearFileCache();
        }

        echo json_encode($result);
        exit;
    }

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
     * Инициализация админского twig-шаблона
     *
     * @param string $tplName Название файла шаблона (с путём к нему), если не задан - будет index.twig
     */
    public function templateInit($tplName = '')
    {
        // Инициализация общего шаблона страницы
        $gblName = 'admin.twig';
        if (!stream_resolve_include_path($gblName)) {
            echo 'Нет файла основного шаблона ' . $gblName;
        }
        $gblRoot = dirname(stream_resolve_include_path($gblName));

        // Определение названия модуля из названия класса контроллера
        $parts = explode('\\', get_class($this));
        $moduleName = $parts[0];
        $moduleName = ($moduleName == 'Ideal') ? '' : $moduleName . '/';
        $structureName = $parts[2];

        // Инициализация шаблона страницы
        if ($tplName == '') {
            $tplName = $moduleName . 'Structure/' . $structureName . '/Admin/index.twig';
        }
        $tplRoot = dirname(stream_resolve_include_path($tplName));
        $tplName = basename($tplName);

        if ($tplRoot == '') {
            // Если в структуре нет файла шаблона, пытаемся его найти в модуле
            $tplName = $moduleName . 'Structure/' . $structureName . '/Admin/index.twig';
            if (!stream_resolve_include_path($tplName)) {
                echo 'Нет файла шаблона ' . $tplName;
                exit;
            }
            $tplRoot = dirname(stream_resolve_include_path($tplName));
            $tplName = basename($tplName);
        }

        // Инициализируем Twig-шаблонизатор
        $config = Config::getInstance();
        $this->view = new View(array($gblRoot, $tplRoot), $config->cache['templateAdmin']);
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
            'X-Robots-Tag' => 'noindex, nofollow'
        );
    }

    // TODO перенести в контроллер юзера

    public function logoutAction()
    {
        $user = Structure\User\Model::getInstance();
        $user->logout();
        header('Location: index.php');
        exit;
    }

    public function parseList($headers, $list)
    {
        // Инициализируем объект запроса
        $request = new Request();

        // Отображение списка заголовков
        $this->view->headers = $headers;

        if ($request->par == '') {
            $request->par = 1;
        }
        $this->view->par = $request->par;

        // Отображение списка элементов
        $rows = array();
        foreach ($list as $k => $v) {
            $fields = '';
            foreach ($headers as $key => $v2) {
                $type = $this->model->fields[$key]['type'];
                $fieldClassName = Util::getClassName($type, 'Field') . '\\Controller';
                $fieldModel = $fieldClassName::getInstance();
                $fieldModel->setModel($this->model, $key);
                $value = $fieldModel->getValueForList($v, $key);
                if (isset($this->model->params['field_name']) && $key == $this->model->params['field_name']) {
                    // На активный элемент ставим ссылку
                    $par = $request->par . '-' . $v['ID'];
                    $value = '<a href="index.php?par=' . $par . '">' . $value . '</a>';
                }
                $fields .= '<td>' . $value . '</td>';
            }
            $rows[] = array(
                'ID' => $v['ID'],
                'row' => $fields,
                'is_active' => (isset($v['is_active'])) ? $v['is_active'] : 1,
                'is_not_menu' => (isset($v['is_not_menu'])) ? $v['is_not_menu'] : 0
            );
        }
        $this->view->rows = $rows;
    }

    /**
     * Генерация контента страницы для отображения в браузере
     *
     * @param Router $router
     * @return string Содержимое отображаемой страницы
     */
    public function run(Router $router)
    {
        $this->model = $router->getModel();

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
        $this->view->cmsFolder = '/' . $config->cmsFolder;
        $this->view->title = $this->model->getTitle();
        $this->view->header = $this->model->getHeader();

        // Регистрируем объект пользователя
        /* @var $user Structure\User\Model */
        $user = Structure\User\Model::getInstance();
        if (isset($user->data['ID'])) {
            $prev = $user->data['prev_structure'];
            // todo обычно юзеры всегда на первом уровне, но нужно доделать чтобы работало не только для первого уровня
            $user->data['par'] = substr($prev, strrpos($prev, '-') + 1);
        }
        $this->view->user = $user->data;


        // Отображение верхнего меню структур
        $this->view->structures = $config->structures;
        $path = $this->model->getPath();
        $this->view->activeStructureId = $path[0]['ID'];

        // Отображение хлебных крошек
        $pars = $breadCrumbs = array();
        foreach ($path as $v) {
            $pars[] = $v['ID'];
            $breadCrumbs[] = array(
                'link' => implode('-', $pars),
                'name' => $v['name']
            );
        }
        $this->view->breadCrumbs = $breadCrumbs;

        $this->view->toolbar = $this->model->getToolbar();

        $this->view->hideToolbarForm = !is_array($request->toolbar) || (count($request->toolbar) == 0);

        // Определение места выполнения скрипта (на сайте в production, или локально в development)
        $this->view->isProduction = $config->domain == str_replace('www.', '', $_SERVER['HTTP_HOST']);

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

    public function showCreateAction()
    {
        $this->model->setPageDataNew();
        // Отображаем список полей структуры part
        $this->showEditTabs();
        exit;
    }

    protected function showEditTabs($values = '')
    {
        $model = $this->model;
        // Выстраиваем список табов
        $defaultName = 'Основное';
        $tabs = array($defaultName => array());
        foreach ($model->fields as $fieldName => $field) {
            if (isset($field['tab'])) {
                $tabs[$field['tab']][$fieldName] = $field;
            } else {
                $tabs[$defaultName][$fieldName] = $field;
            }
        }
        $tabLine = '<ul class="nav nav-tabs" id="tabs">';
        $tabsContent = '<div class="tab-content" id="tabs-content">';
        $isActive = ' active';
        $num = 0;
        foreach ($tabs as $tabName => $tab) {
            $num++;
            $tabLine .= '<li class="' . $isActive . '"><a href="#tab' . $num . '" data-toggle="tab">' . $tabName
                . '</a></li>';
            $tabsContent .= '<div class="tab-pane' . $isActive . '" id="tab' . $num . '">';
            $tabsContent .= $model->getFieldsList($tab);
            $tabsContent .= '</div>';
            $isActive = '';
        }
        $tabLine .= '</ul>';
        $tabsContent .= '</div>';
        echo json_encode(
            array(
                'tabs' => $tabLine,
                'content' => $tabsContent
            )
        );
    }

    public function showEditAction()
    {
        $request = new Request();
        $this->model->setPageDataById($request->id);
        // TODO доработать $this->model->getPath() так, чтобы в пути присутствовала и главная
        $this->showEditTabs();
        exit;
    }

    /**
     * Запуск очищения файлового кэша.
     */
    public function runClearFileCache()
    {
        $config = Config::getInstance();
        $configCache = $config->cache;

        // Очищаем файловый кэш  при условии что кэширование включено.
        // Если кэширование выключено кэш должен быть пуст
        if (isset($configCache['fileCache']) && $configCache['fileCache']) {
            FileCache::clearFileCache();
        }
    }
}
