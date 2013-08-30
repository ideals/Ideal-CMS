<?php
namespace Ideal\Structure\Part\Admin;

use Ideal\Core\Pagination;
use Ideal\Core\Request;
use Ideal\Core\Util;

class ControllerAbstract extends \Ideal\Core\Admin\Controller
{
    /* @var $model Model */
    protected $model;

    public function indexAction()
    {
        $this->templateInit();

        // Считываем список элементов
        $request = new Request();
        $page = intval($request->page);
        $listing = $this->model->getList($page);
        $headers = $this->model->getHeaderNames();

        $this->parseList($headers, $listing);

        $this->view->pager = $this->model->getPager('page');
    }


    public function showCreateTemplateAction()
    {
        $request = new Request();
        $template = $request->template;
        $templateModelName = Util::getClassName($template, 'Template') . '\\Model';
        /* @var $model \Ideal\Core\Admin\Model */
        $model = new $templateModelName('не имет значения');
        $model->setFieldsGroup($request->name);
        $model->setObjectNew();
        echo $model->getFieldsList($model->fields, $model);
        exit;
    }


    public function showEditTemplateAction()
    {
        $request = new Request();

        $this->model->setObjectById($request->id);

        $template = $request->template;
        $templateModelName = Util::getClassName($template, 'Template') . '\\Model';
        $model = new $templateModelName($template, $this->model->object['prev_structure']);
        $model->setFieldsGroup($request->name);
        // Загрузка данных связанного объекта
        if (isset($this->model->object['ID'])) {
            $prevStructure = $this->model->object['prev_structure'] . '-' . $this->model->object['ID'];
            $model->setObjectByprevStructure($prevStructure);
        }

        echo $model->getFieldsList($model->fields, $model);
        exit;
    }

}