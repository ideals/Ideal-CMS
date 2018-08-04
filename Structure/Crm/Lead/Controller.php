<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\Crm\Lead;

use Ideal\Core\Util;
use Ideal\Core\View;
use Ideal\Core\Config;
use Ideal\Core\Request;
use Ideal\Structure\Interaction\Admin\Model as InteractionModel;

class Controller
{

    /** @var View */
    private $view;
    private $moduleName;
    private $structureName;
    private $crmName;
    private $model;

    public function run()
    {
        // Определение названия модуля из названия класса контроллера
        $parts = explode('\\', get_class($this));
        $moduleName = $parts[0];
        $this->moduleName = ($moduleName === 'Ideal') ? '' : $moduleName . '/';
        $this->structureName = $parts[2];
        $this->crmName = $parts[3];

        $this->templateInit();

        $request = new Request();
        $parParts = explode('-', $request->par);
        $par = reset($parParts);
        if ($this->moduleName == '') {
            $par .= '-Ideal';
        } else {
            $par .= '-' . $this->moduleName;
        }
        $par .= '_' . $this->crmName;
        $this->view->par = $par;

        $prevStructure = implode('-', array_slice($parParts, -2));
        $this->model = new Model($prevStructure);

        $listing = $this->model->getListAcl(1);
        $headers = $this->model->getHeaderNames();

        $this->parseList($headers, $listing);

        // Получаем идентификатор структуры лида
        $config = Config::getInstance();
        $leadStructure = $config->getStructureByName('Ideal_Lead');
        if ($leadStructure) {
            $this->view->leadStructureId = $leadStructure['ID'];
        } else {
            throw new \Exception('Не подключена структуры лида');
        }

        return $this->view->render();
    }

    /**
     * Инициализация twig-шаблона
     *
     * @param string $tplName Название файла шаблона (с путём к нему), если не задан - будет index.twig
     */
    public function templateInit($tplName = '')
    {
        // Инициализация шаблона страницы
        if ($tplName == '') {
            $tplName = $this->moduleName . 'Structure/' . $this->structureName . '/'. $this->crmName . '/index.twig';
        }
        $tplRoot = dirname(stream_resolve_include_path($tplName));
        $tplName = basename($tplName);

        if ($tplRoot == '') {
            // Если в структуре нет файла шаблона, пытаемся его найти в модуле
            $tplName = $this->moduleName . 'Structure/' . $this->structureName . '/' . $this->crmName . '/index.twig';
            if (!stream_resolve_include_path($tplName)) {
                echo 'Нет файла шаблона ' . $tplName;
                exit;
            }
            $tplRoot = dirname(stream_resolve_include_path($tplName));
            $tplName = basename($tplName);
        }

        // Инициализируем Twig-шаблонизатор
        $config = Config::getInstance();
        $this->view = new View(array($tplRoot), $config->cache['templateAdmin']);
        $this->view->loadTemplate($tplName);
    }

    public function parseList($headers, $list)
    {
        // Инициализируем объект запроса
        $request = new Request();

        // Отображение списка заголовков
        $this->view->headers = $headers;

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
                if (isset($this->model->params['field_name']) && $key == $this->model->params['field_name']
                    && (!isset($v['acl']) || $v['acl']['enter']) ) {
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
                'is_not_menu' => (isset($v['is_not_menu'])) ? $v['is_not_menu'] : 0,
                'acl_edit' => (isset($v['acl'])) ? $v['acl']['edit'] : 1,
                'acl_delete' => (isset($v['acl'])) ? $v['acl']['delete'] : 1,
                'acl_enter' => (isset($v['acl'])) ? $v['acl']['enter'] : 1,
                'structureId' => (isset($v['structureId'])) ? $v['structureId'] : '',
            );
        }
        $this->view->rows = $rows;
    }
}
