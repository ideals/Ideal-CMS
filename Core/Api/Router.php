<?php

/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Core\Api;

use Ideal\Core\Config;
use Ideal\Core\PluginBroker;

/**
 *  Производит роутинг запросов к API системы
 */
class Router
{
    /** @var bool Флаг 404-ошибки */
    public $is404 = false;

    /** @var string Название контроллера обрабатывающего запрос */
    protected string $controllerName;

    /**
     * Конструктор генерирует события onPreDispatch и onPostDispatch,
     * а так же определяет модель обрабочика 404 ошибки.
     */
    public function __construct()
    {
        $pluginBroker = PluginBroker::getInstance();
        $pluginBroker->makeEvent('onPreDispatch', $this);

        $this->controllerName = $this->detectController($_SERVER['REQUEST_URI']);

        $pluginBroker->makeEvent('onPostDispatch', $this);
    }

    /**
     * Возвращает путь до контроллера ответственного за обработку запроса
     *
     * @return string Название контроллера
     */
    public function getControllerName(): string
    {
        return $this->controllerName;
    }

    /**
     * Устанавливает название контроллера
     *
     * Обычно используется в обработчиках событий onPreDispatch, onPostDispatch
     *
     * @param $name string Название контроллера
     */
    public function setControllerName(string $name): void
    {
        $this->controllerName = $name;
    }

    /**
     * Возвращает статус 404-ошибки,
     */
    public function is404()
    {
        return $this->is404;
    }

    /**
     * Возвращает значение флага отправки сообщения о 404ой ошибке
     */
    public function send404(): bool
    {
        return false;
    }

    /**
     * Зачистка url перед роутингом по нему
     *
     * @param string $url
     * @param bool $stripQuery Нужно ли удалять символы после ?
     */
    protected function prepareUrl($url, $stripQuery = true): string
    {
        $config = Config::getInstance();

        // Вырезаем стартовый URL
        $url = ltrim($url, '/');

        // Удаляем параметры из URL (текст после символа "#")
        $url = preg_replace('/[\#].*/', '', $url);

        if ($stripQuery) {
            // Удаляем параметры из URL (текст после символа "?")
            $url = preg_replace('/[\?\#].*/', '', $url);
        }

        // Убираем начальные слэши и начальный сегмент, если cms не в корне сайта
        $url = ltrim(substr($url, strlen($config->cms['startUrl'])), '/');

        return $url;
    }

    /**
     * Ищет контроллер ответственный за обработку запроса
     * @param string $realUrl
     */
    private function detectController($realUrl): string
    {
        $url = $this->prepareUrl($realUrl);
        $realPath = explode('/', $url);

        if (count($realPath) < 2) {
            // Неправильный url, выдаём 404
            return $this->create404();
        }

        // Убираем слово api из начала пути
        array_shift($realPath);

        // Проверяем, не является ли это вызовом апи системы
        $path = array_merge(['Ideal', 'Api'], $realPath);

        $controllerName = '\\' . implode('\\', $path) . 'Controller';

        if (!class_exists($controllerName)) {
            if (count($realPath) < 2) {
                // Названия мода в запрашиваемом контроллере нет, а в Ideal он не нашёлся — бросаем 404
                return $this->create404();
            }

            $modName = array_shift($realPath);
            $realPath = array_merge([$modName, 'Api'], $realPath);
            $controllerName = '\\' . implode('\\', $realPath) . 'Controller';
            if (!class_exists($controllerName)) {
                // Подходящего контроллера не нашлось, значит выдаём 404
                return $this->create404();
            }
        }

        return $controllerName;
    }

    private function create404(): string
    {
        $this->is404 = true;
        return Controller::class;
    }
}
