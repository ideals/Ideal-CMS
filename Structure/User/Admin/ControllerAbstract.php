<?php
namespace Ideal\Structure\User\Admin;

use Ideal\Structure\User;
use Ideal\Core\Request;

class ControllerAbstract extends \Ideal\Core\Admin\Controller
{

    // Определяем функцию, формирующую список и загоняющую его в шаблон
    // $n - номер элемета, с которого нужно начинать выводить список

    /**
     * Отображение формы логина
     *
     */
    public function loginAction()
    {
        $user = User\Model::getInstance();

        // Проверяем правильность логина и пароля
        if (isSet($_POST['user']) and isSet($_POST['pass'])) {
            if ($user->login($_POST['user'], $_POST['pass'])) {
                header('Location: ' . $_SERVER['REQUEST_URI']);
                exit;
            }
        }

        $this->templateInit('Structure/User/Admin/login.twig');
        $this->view->message = $user->errorMessage;
    }


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


    public function finishMod($actionName)
    {
        if ($actionName == 'loginAction') {
            $this->view->header = '';
            $this->view->title = 'Вход в систему администрирования';
            $this->view->structures = array();
            $this->view->breadCrumbs = '';
        }
    }
}