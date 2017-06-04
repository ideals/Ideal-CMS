<?php
namespace Ideal\Core;

use Ideal\Core\Admin;
use Ideal\Core\Site;
use Ideal\Core\Api;
use Ideal\Structure\User;

/**
 * Front Controller объединяет всю обработку запросов, пропуская запросы через единственный объект-обработчик.
 *
 * После обработки запроса в роутере, фронт-контроллер запускает финальный контроллер, название которого,
 * вместе с моделью данных, определяется в роутере.
 */
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
    public function run($mode)
    {
        // Запускаем роутер, для получения навигационной цепочки
        if ($mode == 'api') {
            $router = new Api\Router();
        } elseif ($mode == 'admin') {
            $router = new Admin\Router();
        } else {
            $router = new Site\Router();
            $this->referer();
        }

        if ($router->is404() && ($mode !== 'site' || isset($_REQUEST['mode']))) {
            // Если 404 и не просто страничка сайта, то роутим заново, как 404-ую public части сайта
            unset($_REQUEST['mode']);
            $this->run('site');
            return;
        }

        // Определяем имя контроллера для отображения запрошенной страницы
        $controllerName = $router->getControllerName();

        // Запускаем нужный контроллер и передаём ему навигационную цепочку
        /* @var $controller Admin\Controller */
        $controller = new $controllerName();

        if ($router->is404() && ($mode !== 'site' || isset($_REQUEST['mode']))) {
            // Если 404 и не просто страничка сайта, то роутим заново, как 404-ую public части сайта
            unset($_REQUEST['mode']);
            $this->run('site');
            return;
        }

        // Запускаем в работу контроллер структуры
        $content = $controller->run($router);

        if ($router->is404() && ($mode !== 'site' || isset($_REQUEST['mode']))) {
            // Если 404 и не просто страничка сайта, то роутим заново, как 404-ую public части сайта
            unset($_REQUEST['mode']);
            $this->run('site');
            return;
        }

        if ($router->is404()) {
            $httpHeaders = array('HTTP/1.0 404 Not Found');
            $router->send404();
        } else {
            $httpHeaders = $controller->getHttpHeaders();

            $config = Config::getInstance();
            $configCache = $config->cache;

            // Если запрошена страница из пользовательской части, включён кэш и действие совершил не администратор,
            // то сохранить её
            $user = new User\Model();
            if (!$user->checkLogin() && in_array($mode, array('api', 'admin'))
                && isset($configCache['fileCache']) && $configCache['fileCache']) {
                $model = $router->getModel();
                $pageData = $model->getPageData();
                if (isset($pageData['date_mod'])) {
                    $modifyTime = $pageData['date_mod'];
                } else {
                    $modifyTime = time();
                }
                FileCache::saveCache($content, $_SERVER['REQUEST_URI'], $modifyTime);
            }
        }

        $this->sendHttpHeaders($httpHeaders); // вывод http-заголовков

        echo $content; // отображение страницы
    }

    /**
     * Формирование заголовков (отдаются браузерам, паукам и проч.)
     *
     * @param array $httpHeaders
     */
    protected function sendHttpHeaders($httpHeaders)
    {
        $isContentType = false;
        foreach ($httpHeaders as $k => $v) {
            if (is_numeric($k)) {
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

    /**
     * Получение реферера пользователя и установка реферера в куки
     */
    public function referer()
    {
        // Проверяем есть ли в куках информация о реферере
        if (!isset($_COOKIE['referer'])) {
            // Если информации о реферере нет в куках то добавляем её туда
            if (!empty($_SERVER['HTTP_REFERER'])) {
                $referer = $_SERVER['HTTP_REFERER'];
            } else {
                $referer = 'null';
            }
            setcookie("referer", $referer, time() + 315360000);
        }
    }
}
