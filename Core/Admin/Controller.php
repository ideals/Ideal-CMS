<?php
namespace Ideal\Core\Admin;

use Ideal\Core;
use Ideal\Core\Config;
use Ideal\Core\Request;
use Ideal\Core\Util;
use Ideal\Core\View;
use Ideal\Structure;

class Controller extends Core\Controller
{
    /* @var $model Model */
    protected $model;

    /**
     * Отображение структуры в браузере
     * @param \Ideal\Core\Admin\Router $router
     * @return mixed
     * @internal param $structure
     * @internal param $actionName
     * @internal param $path
     */
    function run(Router $router)
    {
        $this->path = $router->getPath();

        // Инициализация модели
        $path = $this->path;
        $end = array_pop($path);
        $prev = end($path);
        $modelName = Util::getClassName($end['structure'], 'Structure') . '\\Admin\\Model';

        // Определение пути структур
        $structurePath = $end['ID']; // для корневого раздела
        if (isset($end['structure_path'])) {
            // Если отображается подраздел
            $structurePath = $end['structure_path'];
        }
        if (isset($prev['structure']) AND ($prev['structure'] != $end['structure'])) {
            // Если отображаемая структура имеет другой тип, по сравнению с предыдущей
            $structurePath .= '-' . $end['ID'];
        }

        $this->model = new $modelName($structurePath);
        $this->model->setPath($this->path); // записываем полный путь

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
        $this->view->user = $user->data;

        // Отображение верхнего меню структур
        $this->view->structures = $config->structures;
        $this->view->activeStructureId = $this->path[0]['ID'];

        // Отображение хлебных крошек
        $pars = $breadCrumbs = array();
        foreach ($this->path as $v) {
            $pars[] = $v['ID'];
            $breadCrumbs[] = array(
                'link' => implode('-', $pars),
                'name' => $v['name']
            );
        }
        $this->view->breadCrumbs = $breadCrumbs;

        $this->view->toolbar = $this->model->getToolbar();

        $this->view->hideToolbarForm = !is_array($request->toolbar) OR (count($request->toolbar) == 0);

        $this->finishMod($actionName);

        return $this->view->render();
    }


    public function templateInit($tplName = '')
    {
        // Инициализация общего шаблона страницы
        $gblName = 'admin.twig';
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

        $config = Config::getInstance();
        $this->view = new View(array($gblRoot, $tplRoot), $config->isTemplateAdminCache);
        $this->view->loadTemplate($tplName);
    }


    public function getHttpStatus()
    {
        return 'X-Robots-Tag: noindex, nofollow';
    }


    public function parseList($headers, $list)
    {
        // Инициализируем объект запроса
        $request = new Request();

        // Отображение списка заголовков
        $this->view->headers = $headers;
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
                if ($key == $this->model->params['field_name']) {
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
            $tabLine .= '<li class="' . $isActive . '"><a href="#tab' . $num . '" data-toggle="tab">' . $tabName . '</a></li>';
            $tabsContent .= '<div class="tab-pane' . $isActive . '" id="tab' . $num . '">';
            $tabsContent .= $model->getFieldsList($tab);
            $tabsContent .= '</div>';
            $isActive = '';
        }
        $tabLine .= '</ul>';
        $tabsContent .= '</div>';
        print $tabLine;
        print $tabsContent;
    }

    // TODO перенести в контроллер юзера
    public function logoutAction()
    {
        $user = Structure\User\Model::getInstance();
        $user->logout();
        header('Location: index.php');
        exit;
    }


    public function showCreateAction()
    {
        $this->model->setObjectNew();
        // Отображаем список полей структуры part
        $this->showEditTabs();
        exit;
    }


    public function showEditAction()
    {
        $request = new Request();
        $this->model->setObjectById($request->id);
        // TODO доработать $this->model->getPath() так, чтобы в пути присутствовала и главная
        $this->showEditTabs();
        exit;
    }


    public function createAction()
    {
        $this->model->setObjectNew();

        // Проверка ввода - если ок - сохраняем, если нет - сообщаем об ошибках
        $result = $this->model->parseInputParams(true);

        if ($result['isCorrect']) {
            $result = $this->model->createElement($result);
        }

        echo json_encode($result);
        exit;
    }


    public function editAction()
    {
        $request = new Request();
        $this->model->setObjectById($request->id);

        // Проверка ввода - если ок - сохраняем, если нет - сообщаем об ошибках
        $result = $this->model->parseInputParams();

        // Проверяем не выбран ли другой прикреплённый шаблон
        if ($request->changeTemplate == 0) {
            $result = $this->model->checkTemplateChange($result);
        }

        if ($result['isCorrect'] == 1) {
            $result = $this->model->saveElement($result);
        }

        echo json_encode($result);
        exit;
    }


    public function deleteAction()
    {
        $request = new Request();

        $result = array();
        $result['ID'] = intval($request->id);

        $this->model->setObjectById($result['ID']);

        $result['isCorrect'] = $this->model->delete();

        echo json_encode($result);
        exit;
    }

}