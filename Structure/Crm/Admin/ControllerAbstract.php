<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\Crm\Admin;

use Ideal\Core\Request;

class ControllerAbstract extends \Ideal\Core\Admin\Controller
{

    /* @var $model Model */
    protected $model;

    /**
     * Магический метод, перехватывающий ajax-запросы и подключающий соответствующие файлы
     *
     * @param string $name      Название вызываемого метода
     * @param array  $arguments Аргументы, передаваемые методу
     * @throws \Exception Исключение, если для вызываемого метода нет соответствующего файла
     */
    public function __call($name, $arguments)
    {
        $item = $this->model->getPageData();

        list($module, $structure) = explode('_', $item['ID']);
        $module = ($module == 'Ideal') ? '' : $module . '/';
        $file = $module . 'Structure/Service/' . $structure . '/' . $name . '.php';

        if (!stream_resolve_include_path($file)) {
            throw new \Exception("Файл $file не существует");
        }

        include($file);
    }

    public function indexAction()
    {
        $this->templateInit('Structure/Crm/Admin/index.twig');

        // Инициализируем объект запроса
        $request = new Request();
        $sepPar = strpos($request->par, '-');
        if ($sepPar === false) {
            $this->view->par = $request->par;
        } else {
            $this->view->par = substr($request->par, 0, $sepPar);
        }

        $this->view->items = $this->model->getMenu();

        $item = $this->model->getPageData();
        $this->view->ID = $item['ID'];

        $this->view->content = $item['content'];
    }

    /**
     * В ответ на запрос сервера получает данные о лиде из соответствующего модуля и возвращает их
     *
     * @return string Данные о лиде в json формате
     */
    public function showDataAction()
    {
        $path = $this->model->getPath();

        // Определяем контроллер, которому нужно передать управление на основе выбранного пункта раздела
        $endPath = end($path);
        $structure = explode('_', $endPath['ID']);
        $class = '\\' . $structure[0] . '\\Structure\\Crm\\' . $structure[1] . '\\Controller';
        $controller = new $class();

        // Запускаем метод контроллера - получаем даные, лида которые нужно отобразить
        $data = $controller->showDataAction();
        echo json_encode(
            array(
                'data' => $data
            )
        );
    }
}
