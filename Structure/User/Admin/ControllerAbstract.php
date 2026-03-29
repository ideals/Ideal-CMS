<?php

/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\User\Admin;

use Ideal\Core\Admin\Controller;
use Ideal\Structure\User\Model;
use Ideal\Core\Request;

/**
 * Класс, отвечающий за отображение списка пользователей в админке, а также
 * за отображение формы авторизации и её обработку
 */
class ControllerAbstract extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function finishMod($actionName): void
    {
        if ($actionName == 'loginAction') {
            $this->view->header = '';
            $this->view->title = 'Вход в систему администрирования';
            $this->view->structures = [];
            $this->view->breadCrumbs = '';
        }
    }

    /**
     * Отображение списка пользователей
     */
    public function indexAction(): void
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
    public function loginAction(): void
    {
        // Проверяем что запрашивается json
        $jsonResponse = false;
        $pattern = "/.*json.*/i";
        if (preg_match($pattern, $_SERVER['HTTP_ACCEPT'])) {
            $jsonResponse = true;
        }

        // Если запрашивается не json и не html версия, то вероятнее всего это бот
        if (!$jsonResponse && !preg_match("/.*html.*/i", $_SERVER['HTTP_ACCEPT'])) {
            throw new \Exception('Какой-то робот пытается зайти на страницу админки.');
        }

        $user = Model::getInstance();

        // Проверяем правильность логина и пароля
        if (isset($_POST['user']) && isset($_POST['pass'])) {
            // При ajax авторизации отдаём json ответы
            if ($jsonResponse) {
                if ($user->login($_POST['user'], $_POST['pass'])) {
                    echo json_encode(['login' => 'true']);
                } else {
                    echo json_encode([
                        'errorResponse' => $user->errorMessage,
                        'login' => 'false',
                    ]);
                }

                exit;
            }

            if ($user->login($_POST['user'], $_POST['pass'])) {
                header('Location: ' . $_SERVER['REQUEST_URI']);
            }

        } else {
            // На странице авторизации отдавать 404 заголовок
            $this->model->is404 = true;
        }

        // Если запрашивается json при не авторизованном пользователе
        // отдаём ответ инициализирующий показ формы авторизации
        if ($jsonResponse) {
            echo json_encode(
                [
                    'errorResponse' => 'not Login',
                ],
            );
            exit;
        }

        $this->templateInit('Structure/User/Admin/login.twig');
        $this->view->message = $user->errorMessage;
    }

    /**
     * Экшен для вывода уведомления о запрещённом доступе к странице
     */
    public function accessDeniedAction(): void
    {
        $this->templateInit('Structure/User/Admin/access-denied.twig');
        $this->view->header = 'Доступ запрещён';
    }
}
