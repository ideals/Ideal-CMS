<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\Crm\Inbox;

use Ideal\Core\Config;
use Ideal\Core\View;

class Controller
{
    /** @var View */
    private $view;

    public function run()
    {
        $this->templateInit();
        $model = new Model('');
        $data = $model->getPageData();
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
        // Определение названия модуля из названия класса контроллера
        $parts = explode('\\', get_class($this));
        $moduleName = $parts[0];
        $moduleName = ($moduleName === 'Ideal') ? '' : $moduleName . '/';
        $structureName = $parts[2];
        $crmName = $parts[3];

        // Инициализация шаблона страницы
        if ($tplName == '') {
            $tplName = $moduleName . 'Structure/' . $structureName . '/'. $crmName . '/index.twig';
        }
        $tplRoot = dirname(stream_resolve_include_path($tplName));
        $tplName = basename($tplName);

        if ($tplRoot == '') {
            // Если в структуре нет файла шаблона, пытаемся его найти в модуле
            $tplName = $moduleName . 'Structure/' . $structureName . '/' . $crmName . '/index.twig';
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