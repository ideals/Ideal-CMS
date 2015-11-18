<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2015 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Core;

use Ideal\Structure\Error404;

/**
 * Контроллер, вызываемый при работе с ajax-вызовами
 */
class AjaxController
{
    /** @var Model Модель соответствующая этому контроллеру */
    protected $model;

    /* @var View Объект вида — twig-шаблонизатор */
    protected $view;

    /** @var Model Модель для обработки 404-ых ошибок */
    protected $Error404 = null;

    /**
     * Генерация контента страницы для отображения в браузере
     *
     * @param \Ideal\Core\Site\Router | \Ideal\Core\Admin\Router $router
     * @return string Содержимое отображаемой страницы
     */
    public function run($router)
    {
        $this->model = $router->getModel();

        // Определяем и вызываем требуемый action у контроллера
        $request = new Request();
        $actionName = $request->action;
        if ($actionName == '') {
            $actionName = 'index';
        }
        $actionName = $actionName . 'Action';

        if (method_exists($this, $actionName)) {
            // Вызываемый action существует, запускаем его
            $text = $this->$actionName();
        } else {
            // Вызываемый action отсутствует, запускаем 404 ошибку
            $this->Error404 = new Error404\Model();

            // Вырезаем стартовый URL
            // Так как это аякс запрос, то нужен весь адрес со всеми параметрами
            $url = ltrim($_SERVER['REQUEST_URI'], '/');
            $this->Error404->setUrl($url);

            // Проверяем наличие адреса среди уже известных 404-ых
            $is404 = $this->Error404->checkAvailability404();
            if ($is404 !== true) {

                // Сохраняем/обновляем информацию о 404
                $this->Error404->save404();
            }

            // Назначаем в роутере модель обработки 404-ых ошибок
            $router->setError404($this->Error404);

            $config = Config::getInstance();

            // Находим начальную структуру
            $path = array($config->getStartStructure());
            $prevStructureId = $path[0]['ID'];

            // Определяем оставшиеся элементы пути
            $modelClassName = Util::getClassName($path[0]['structure'], 'Structure') . '\\Site\\Model';

            // Объявляем модель начальной структуры для удобства работы с 404
            $model = new $modelClassName('0-' . $prevStructureId);
            $model->is404 = true;

            // Назначаем роутеру объявленную модель
            $router->setModel($model);

            $text = $this->error404Action();
        }

        return $text;
    }

    /**
     * Получение дополнительных HTTP-заголовков
     * По умолчанию система ставит только заголовок Content-Type, но и его можно
     * переопределить в этом методе.
     *
     * @return array Массив где ключи - названия заголовков, а значения - содержание заголовков
     */
    public function getHttpHeaders()
    {
        return array(
            'X-Robots-Tag' => 'noindex, nofollow'
        );
    }

    /**
     * Генерация шаблона отображения
     *
     * @param string $tplName
     */
    public function templateInit($tplName = '')
    {
        if (!stream_resolve_include_path($tplName)) {
            echo 'Нет файла шаблона ' . $tplName;
            exit;
        }
        $tplRoot = dirname(stream_resolve_include_path($tplName));
        $tplName = basename($tplName);

        // Определяем корневую папку системы для подключение шаблонов из любой вложенной папки через их путь
        $config = Config::getInstance();
        $cmsFolder = DOCUMENT_ROOT . '/' . $config->cmsFolder;

        $folders = array_merge(array($tplRoot, $cmsFolder));
        $this->view = new View($folders, $config->cache['templateSite']);
        $this->view->loadTemplate($tplName);
    }

    /**
     * Действие для отсутствующей страницы сайта (обработка ошибки 404)
     */
    public function error404Action()
    {
        $this->templateInit('404.twig');

        // Twig рендерит текст странички из шаблона
        return $this->view->render();
    }
}
