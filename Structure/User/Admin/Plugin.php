<?php
namespace Ideal\Structure\User\Admin;

use Ideal\Core\Admin\Router;
use Ideal\Core\Request;
use Ideal\Structure;

class Plugin
{
    public function onPostDispatch(Router $router)
    {
        // Регистрируем объект пользователя
        $user = Structure\User\Model::getInstance();

        // Инициализируем объект запроса
        $request = new Request();

        $_SESSION['IsAuthorized'] = true;

        // Если пользователь не залогинен — запускаем модуль авторизации
        if (!$user->checkLogin()) {
            $_SESSION['IsAuthorized'] = false;
            $request->action = 'login';
            $router->setControllerName('\\Ideal\\Structure\\User\\Admin\\Controller');
        }
    }
}