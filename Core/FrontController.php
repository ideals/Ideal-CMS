<?php
namespace Ideal\Core;

use Ideal\Core\Admin;
use Ideal\Core\Site;

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
        if ($mode == 'admin') {
            $router = new Admin\Router();
        } else {
            $router = new Site\Router();
            $this->referer();
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
            $this->emailError404();
        } else {
            $httpHeaders = $controller->getHttpHeaders();

            $config = Config::getInstance();
            $configCache = $config->cache;

            // Если запрошена страница из пользовательской части и включён кэш, то сохранить её
            if ($mode != 'admin' && isset($configCache['fileCache']) && $configCache['fileCache']) {
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

    /**
     * Отправка письма о 404-ой ошибке, если url не зарегистрирован в $config->cms['known404']
     */
    protected function emailError404()
    {
        $config = Config::getInstance();

        if (isset($config->cms['known404']) && !empty($config->cms['known404'])) {
            $known404 = explode("\n", $config->cms['known404']);

            $url = ltrim($_SERVER['REQUEST_URI'], '/'); // убираем ведущий слэш, для соответствия .htaccess

            $result = array_reduce(
                $known404,
                function (&$res, $rule) {
                    if (strpos($rule, '/') !== 0) {
                        // Если правило не оформлено, как regexp, то оформляем его
                        $rule = '/' . $rule . '/';
                    }
                    if (!empty($rule) && ($res == 1 || preg_match($rule, $res))) {
                        return 1;
                    }
                    return $res;
                },
                $url
            );

            if ($result === 1) {
                // Если в массиве известных битых ссылок наш url найден, то не регистрируем ошибку
                return;
            }
        }
        $from = empty($_SERVER['HTTP_REFERER']) ? 'Прямой переход.' : 'Переход со страницы ' . $_SERVER['HTTP_REFERER'];
        Util::addError('Страница не найдена (404). ' . $from);
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
