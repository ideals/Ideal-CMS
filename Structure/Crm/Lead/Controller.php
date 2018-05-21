<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\Crm\Lead;

use Ideal\Core\Config;
use Ideal\Core\Request;
use Ideal\Core\View;

class Controller
{

    /** @var View */
    private $view;
    private $moduleName;
    private $structureName;
    private $crmName;

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
        list($par) = explode('-', $request->par);
        if ($this->moduleName == '') {
            $par .= '-Ideal';
        } else {
            $par .= '-' . $this->moduleName;
        }
        $par .= '_' . $this->crmName;
        $this->view->par = $par;

        $model = new Model('');
        $data = $model->getPageData();
        foreach ($data as $key => $item) {
            $this->view->$key = $item;
        }

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
}
