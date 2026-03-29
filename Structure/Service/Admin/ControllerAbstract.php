<?php

/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\Service\Admin;

use Ideal\Core\Admin\Controller;
use Ideal\Core\Request;

class ControllerAbstract extends Controller
{
    /* @var $model Model */
    protected $model;

    /**
     * Магический метод, перехватывающий ajax-запросы и подключающий соответствующие файлы
     *
     * @param string $name Название вызываемого метода
     * @param array $arguments Аргументы, передаваемые методу
     * @throws \Exception Исключение, если для вызываемого метода нет соответствующего файла
     */
    public function __call(string $name, array $arguments)
    {
        $item = $this->model->getPageData();

        [$module, $structure] = explode('_', $item['ID']);
        $module = ($module === 'Ideal') ? '' : $module . '/';
        $file = $module . 'Structure/Service/' . $structure . '/' . $name . '.php';

        if (!stream_resolve_include_path($file)) {
            throw new \Exception(sprintf('Файл %s не существует', $file));
        }

        include($file);
    }

    public function indexAction(): void
    {
        $this->templateInit('Structure/Service/Admin/index.twig');

        // Инициализируем объект запроса
        $request = new Request();
        $sepPar = strpos($request->par, '-');
        $this->view->par = $sepPar === false ? $request->par : substr($request->par, 0, $sepPar);

        $this->view->items = $this->model->getMenu(); // $structure['items'];

        $item = $this->model->getPageData();
        $this->view->ID = $item['ID'];

        [$module, $structure] = explode('_', $item['ID']);
        $module = ($module === 'Ideal') ? '' : $module . '/';
        $file = $module . 'Structure/Service/' . $structure . '/Action.php';
        ob_start();
        // TODO сделать уведомление об ошибке, в случае если такого файла нет
        include($file);
        $text = ob_get_contents();
        ob_end_clean();

        $this->view->text = $text;
    }
}
