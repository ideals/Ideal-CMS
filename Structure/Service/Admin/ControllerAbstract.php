<?php
namespace Ideal\Structure\Service\Admin;

use Ideal\Core\Request;

class ControllerAbstract extends \Ideal\Core\Admin\Controller
{
    /* @var $model Model */
    protected $model;

    public function indexAction()
    {
        $this->templateInit('Structure/Service/Admin/index.twig');

        // Инициализируем объект запроса
        $request = new Request();
        $sepPar = strpos($request->par, '-');
        if ($sepPar === false) {
            $this->view->par = $request->par;
        } else {
            $this->view->par = substr($request->par, 0, $sepPar);
        }

        $this->view->items = $this->model->getMenu();// $structure['items'];

        $item = $this->model->getPageData();
        $this->view->ID = $item['ID'];

        list($module, $structure) = explode('_', $item['ID']);
        $module = ($module == 'Ideal') ? '' : $module . '/';
        $file = $module . 'Structure/Service/' . $structure . '/Action.php';
        ob_start();
        // TODO сделать уведомление об ошибке, в случае если такого файла нет
        include($file);
        $text = ob_get_contents();
        ob_end_clean();

        $this->view->text = $text;
    }

    /**
     * Магический метод, перехватывающий ajax-запросы и подключающий соответствующие файлы
     * @param string $name Название вызываемого метода
     * @param array $arguments Аргументы, передаваемые методу
     * @throws \Exception Исключение, если для вызываемого метода нет соответствующего файла
     */
    public function __call($name, $arguments) {
        $item = $this->model->getPageData();

        list($module, $structure) = explode('_', $item['ID']);
        $module = ($module == 'Ideal') ? '' : $module . '/';
        $file = $module . 'Structure/Service/' . $structure . '/' . $name . '.php';

        if (!stream_resolve_include_path($file)) {
            throw new \Exception("Файл $file не существует");
        }

        include($file);
    }
}
