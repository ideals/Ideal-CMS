<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2015 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\User\Admin;

use Ideal\Core\Request;
use Ideal\Structure\User;

/**
 * Класс, отвечающий за отображение списка пользователей в админке, а также
 * за отображение формы авторизации и её обработку
 */
class ControllerAbstract extends \Ideal\Core\Admin\Controller
{
    /**
     * {@inheritdoc}
     */
    public function finishMod($actionName)
    {
        if ($actionName == 'loginAction') {
            $this->view->header = '';
            $this->view->title = 'Вход в систему администрирования';
            $this->view->structures = array();
            $this->view->breadCrumbs = '';
        }
    }

    /**
     * Отображение списка пользователей
     */
    public function indexAction()
    {
        $this->templateInit();

        // Считываем список элементов
        $request = new Request();
        $page = intval($request->page);

        $listing = $this->model->getListAcl($page);
        $headers = $this->model->getHeaderNames();

        $this->parseList($headers, $listing);

        $this->view->pager = $this->model->getPager('page');
    }

    /**
     * Отображение формы авторизации, если пользователь не авторизован
     */
    public function loginAction()
    {
        // Проверяем что запрашивается json
        $jsonResponse = false;
        $pattern = "/.*json.*/i";
        if (preg_match($pattern, $_SERVER['HTTP_ACCEPT'])) {
            $jsonResponse = true;
        }

        $user = User\Model::getInstance();

        // Проверяем правильность логина и пароля
        if (isset($_POST['user']) && isset($_POST['pass'])) {
            // При ajax авторизации отдаём json ответы
            if ($jsonResponse) {
                if ($user->login($_POST['user'], $_POST['pass'])) {
                    echo json_encode(array('login' => 'true'));
                } else {
                    echo json_encode(array(
                        'errorResponse' => $user->errorMessage,
                        'login' => 'false'
                    ));
                }
                exit;
            } else {
                if ($user->login($_POST['user'], $_POST['pass'])) {
                    header('Location: ' . $_SERVER['REQUEST_URI']);
                }
            }
        }

        // Если запрашивается json при не авторизованном пользователе
        // отдаём ответ инициализирующий показ формы авторизации
        if ($jsonResponse) {
            echo json_encode(
                array(
                    'errorResponse' => 'not Login',
                )
            );
            exit;
        }

        $this->templateInit('Structure/User/Admin/login.twig');
        $this->view->message = $user->errorMessage;
    }

    /**
     * Экшен для вывода уведомления о запрещённом доступе к странице
     */
    public function accessDeniedAction()
    {
        $this->templateInit('Structure/User/Admin/access-denied.twig');
        $this->view->header = 'Доступ запрещён';
    }
}
