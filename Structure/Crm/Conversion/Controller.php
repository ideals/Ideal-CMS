<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\Crm\Conversion;

use Ideal\Core\Util;
use Ideal\Core\View;
use Ideal\Core\Config;
use Ideal\Core\Request;

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
        $this->view->par = $request->par;

        $prevStructure = implode('-', array_slice($parParts, -2));
        $this->model = new Model($prevStructure);

        // Разбор параметров фильтрации и группировки
        if ($request->fromDate) {
            $fromTimestamp = strtotime(str_replace('.', '-', $request->fromDate));
        } else {
            $fromTimestamp = time() - 2678400;
        }
        if ($request->toDate) {
            $toTimestamp = strtotime(str_replace('.', '-', $request->toDate));
            if ($toTimestamp > time()) {
                $toTimestamp = time();
            }
        } else {
            $toTimestamp = time();
        }

        $interval = 'day';
        if ($request->grouping) {
            $interval = $request->grouping;
        }
        $this->view->interval = $interval;

        $newLead = 0;
        if ($request->newLead) {
            $newLead = $request->newLead;
        }
        $this->view->newLead = $newLead;

        // Получаем дату с которой формировать графики. По умолчанию 30 дней назад
        $this->view->fromDate = date('d.m.Y', $fromTimestamp);

        // Получаем дату до которой формировать графики. По умолчанию текущий день.
        $this->view->toDate = date('d.m.Y', $toTimestamp);

        $this->model->setFromTimestamp($fromTimestamp);
        $this->model->setToTimestamp($toTimestamp);
        $this->model->setInterval($interval);
        $this->model->setNewLead($newLead);

        $data = $this->model->getPageData();
        foreach ($data as $key => $item) {
            $this->view->$key = $item;
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
