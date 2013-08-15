<?php
namespace Ideal\Core;

use Ideal\Core\Admin;
use Ideal\Core\Site;

class FrontController
{
    /**
     * Формирование заголовков (отдаются браузерам, паукам и проч.)
     * @param string $httpStatus HTTP status страницы
     * @param int|string $lastMod Дата последней модификации страницы в формате UNIX TIMESTAMP
     */
    function sendHttpHeaders($httpStatus = '', $lastMod = 0)
    {
        // Отображаем дополнительный header, если он нужен
        if ($httpStatus != '') {
            header($httpStatus);
        }

        header("Content-Type: text/html; charset=utf-8");

        // Дата последней модификации, если она указана
        if ($lastMod != 0) {
            @ header("Last-Modified: " . gmdate("D, d M Y H:i:s", $this->lastMod ) . " GMT\r\n");
        }

        // Затираем сообщение о том, что это PHP-скрипт
        header('X-Powered-By: Hello, man!');

        // Тут разные наброски HTTP-статусов, можно раскоменить нужное по желанию
        //@ header("Expires: " . gmdate("D, d M Y H:i:s")+900 . " GMT\r\n");
        // HTTP/1.1
        //@ header("Cache-Control: no-store, no-cache, must-revalidate\r\n");
        //@ header("Cache-Control: post-check=0, pre-check=0\r\n", false);
        // HTTP/1.0
        //header("Pragma: no-cache");
    }


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

        if ($router->is404) {
            $httpStatus = 'HTTP/1.0 404 Not Found';
        } else {
            $httpStatus = $controller->getHttpStatus();
        }

        $lastMod = $controller->getLastMod();

        $this->sendHttpHeaders($httpStatus, $lastMod); // вывод http-заголовков

        echo $content; // отображение страницы
    }
}