<?php
namespace Ideal\Core;

use Ideal\Core\Admin;
use Ideal\Core\Site;

class FrontController
{
    /**
     * Запуск FrontController'а
     *
     * Проводится роутинг, определяется контроллер страницы и отображаемый текст.
     * Выводятся HTTP-заголовки и отображается текст, сгенерированный с помощью view в controller
     *
     * @param string $mode Режим работы admin или site
     */
    function run($mode)
    {
        // Запускаем роутер, для получения навигационной цепочки
        if ($mode == 'admin') {
            $router = new Admin\Router();
        } else {
            $router = new Site\Router();
        }

        // Определяем имя контроллера для отображения запрошенной страницы
        $controllerName = $router->getControllerName();

        // Запускаем нужный контроллер и передаём ему навигационную цепочку
        /* @var $controller Admin\Controller */
        $controller = new $controllerName();

        // Запускаем в работу контроллер структуры
        $content = $controller->run($router);

        if ($router->is404()) {
            $httpHeaders = array('HTTP/1.0 404 Not Found');
        } else {
            $httpHeaders = $controller->getHttpHeaders();
        }

        $this->sendHttpHeaders($httpHeaders); // вывод http-заголовков

        echo $content; // отображение страницы
    }

    /**
     * Формирование заголовков (отдаются браузерам, паукам и проч.)
     *
     * @param array $httpHeaders
     */
    function sendHttpHeaders($httpHeaders)
    {
        $isContentType = false;
        foreach ($httpHeaders as $k => $v) {
            if ($k == intval($k)) {
                // Ключ не указан, значит выводим только значение
                header($v . "\r\n");
            } else {
                // Ключ указан, значит выводим и ключ и значение
                header($k . ': ' . $v . "\r\n");
            }
            // Проверяем, не переопределён ли Content-Type
            if (strtolower($k) == 'content-type') {
                $isContentType = true;
            }
        }

        if (!$isContentType) {
            // Content-Type пользователем не изменён, отображаем стандартный
            header("Content-Type: text/html; charset=utf-8");
        }
    }
}
