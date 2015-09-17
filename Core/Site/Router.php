<?php
namespace Ideal\Core\Site;

use Ideal\Core\Config;
use Ideal\Core\PluginBroker;
use Ideal\Core\Request;
use Ideal\Core\Util;

class Router
{

    /** @var string Название контроллера активной страницы */
    protected $controllerName = '';

    /** @var Model Модель активной страницы */
    protected $model = null;

    /**
     * Производит роутинг исходя из запрошенного URL-адреса
     *
     * Конструктор генерирует событие onPreDispatch, затем определяет модель активной страницы
     * и генерирует событие onPostDispatch.
     * В результате работы конструктора инициализируются переменные $this->model и $this->ControllerName
     */
    public function __construct()
    {
        // Проверка на простой AJAX-запрос
        $request = new Request();
        if ($request->mode == 'ajax') {
            $controllerName = $request->controller . '\\AjaxController';
            if ($request->controller != '' && class_exists($controllerName)) {
                // Если контроллер в запросе указан И запрошенный класс существует
                // то устанавливаем контроллер и завершаем роутинг
                $this->controllerName = $controllerName;
                return;
            }
            // Если параметры ajax-вызова неправильные, то обрабатываем запрос как не-ajax
            unset($_REQUEST['mode']);
        }

        $pluginBroker = PluginBroker::getInstance();
        $pluginBroker->makeEvent('onPreDispatch', $this);

        if (is_null($this->model)) {
            $this->model = $this->routeByUrl();
        }

        $pluginBroker->makeEvent('onPostDispatch', $this);

        // Инициализируем данные модели
        $this->model->initPageData();

        // Определяем корректную модель на основании поля structure
        $this->model = $this->model->detectActualModel();
    }

    /**
     * Определение модели активной страницы и пути к ней на основе запрошенного URL
     *
     * @return Model Модель активной страницы
     */
    protected function routeByUrl()
    {
        $config = Config::getInstance();

        // Находим начальную структуру
        $path = array($config->getStartStructure());
        $prevStructureId = $path[0]['ID'];

        // Вырезаем стартовый URL
        $url = ltrim($_SERVER['REQUEST_URI'], '/');
        // Удаляем параметры из URL (текст после символов "?" и "#")
        $url = preg_replace('/[\?\#].*/', '', $url);
        // Убираем начальные слэши и начальный сегмент, если cms не в корне сайта
        $url = ltrim(substr($url, strlen($config->cms['startUrl'])), '/');

        // Если запрошена главная страница
        if ($url == '') {
            $model = new \Ideal\Structure\Home\Site\Model('0-' . $prevStructureId);
            $model = $model->detectPageByUrl($path, '/');
            return $model;
        }

        $is404 = false;
        $known404 = false;
        if (file_exists(DOCUMENT_ROOT . '/' . $config->cmsFolder . '/known404.php')) {
            $known404 = new \Ideal\Structure\Service\SiteData\ConfigPhp();
            $known404->loadFile(DOCUMENT_ROOT . '/' . $config->cmsFolder . '/known404.php');
            $known404List = $known404->getParams();
            $known404List = array_filter(explode("\n", $known404List['known']['arr']['known404']['value']));
            $is404 = array_reduce(
                $known404List,
                function (&$res, $rule) {
                    if (strpos($rule, '/') !== 0) {
                        $rule = '/' . addcslashes($rule, '/') . '/';
                    }
                    if (!empty($rule) && ($res === true || preg_match($rule, $res))) {
                        return true;
                    }
                    return $res;
                },
                $url
            );
            $is404 = $is404 === true ? true : false;
        }

        // Определяем оставшиеся элементы пути
        $modelClassName = Util::getClassName($path[0]['structure'], 'Structure') . '\\Site\\Model';
        /* @var $model Model */
        $model = new $modelClassName('0-' . $prevStructureId);

        if ($is404 !== true) {
            // Определяем, заканчивается ли URL на правильный суффикс, если нет — 404
            $originalUrl = $url;
            $lengthSuffix = strlen($config->urlSuffix);
            if ($lengthSuffix > 0) {
                $suffix = substr($url, -$lengthSuffix);
                if ($suffix != $config->urlSuffix) {
                    $is404 = true;
                }
                $url = substr($url, 0, -$lengthSuffix); // убираем суффикс из url
            }

            // Проверка, не остался ли в конце URL слэш
            if (substr($url, -1) == '/') {
                // Убираем завершающие слэши, если они есть
                $url = rtrim($url, '/');
                // Т.к. слэшей быть не должно (если они — суффикс, то они убираются выше)
                // то ставим 404-ошибку
                $is404 = true;
            }

            // Разрезаем URL на части
            $url = explode('/', $url);

            // Запускаем определение пути и активной модели по $par
            $model = $model->detectPageByUrl($path, $url);
            if ($model->is404 == false && $is404) {
                // Если роутинг нашёл нужную страницу, но суффикс неправильный
                $model->is404 = true;
            }
        } else {
            $model->is404 = true;
        }
        return $model;
    }

    /**
     * Возвращает название контроллера для активной страницы
     *
     * @return string Название контроллера
     */
    public function getControllerName()
    {
        if ($this->controllerName != '') {
            return $this->controllerName;
        }

        $request = new Request();
        if ($request->mode == 'ajax-model' && $request->controller != '') {
            // Если это ajax-вызов с явно указанным namespace класса ajax-контроллера
            return $request->controller . '\\AjaxController';
        }

        $path = $this->model->getPath();

        if (count($path) == 0 && $this->model->is404) {
            // Не смогли построить ни одного элемента пути и получили 404 ошибку
            $config = Config::getInstance();
            $path = array(
                $config->getStartStructure(),
            );
            $this->model->setPath($path);
        }

        if (count($path) == 0) {
            Util::addError('Не удалось построить путь. Модель: ' . get_class($this->model));
            $this->model->is404 = true;
            // todo отображение 404 ошибки
        }
        $end = array_pop($path);
        $prev = array_pop($path);

        if ($end['url'] == '/') {
            // Если запрошена главная страница, принудительно устанавливаем структуру Ideal_Home
            $structure = 'Ideal_Home';
        } elseif (!isset($end['structure'])) {
            // Если в последнем элементе нет поля structure (например в новостях), то берём название
            // структуры из предыдущего элемента пути
            $structure = $prev['structure'];
        } else {
            // В обычном случае название отображаемой структуры определяется по соответствующему
            // полю последнего элемента пути
            $structure = $end['structure'];
        }

        if ($request->mode == 'ajax-model' && $request->controller == '') {
            // Если это ajax-вызов без указанного namespace класса ajax-контроллера,
            // то используем namespace модели
            return Util::getClassName($end['structure'], 'Structure') . '\\Site\\AjaxController';
        }

        $controllerName = Util::getClassName($structure, 'Structure') . '\\Site\\Controller';

        return $controllerName;
    }

    /**
     * Устанавливает название контроллера для активной страницы
     *
     * Обычно используется в обработчиках событий onPreDispatch, onPostDispatch
     *
     * @param $name string Название контроллера
     */
    public function setControllerName($name)
    {
        $this->controllerName = $name;
    }

    /**
     * Возвращает объект модели активной страницы
     *
     * @return Model Инициализированный объект модели активной страницы
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @param $model Model Устанавливает модель, найденную роутером (обычно использется в плагинах)
     */
    public function setModel($model)
    {
        $this->model = $model;
    }

    /**
     * Возвращает статус 404-ошибки, есть он или нет
     */
    public function is404()
    {
        return $this->model->is404;
    }
}
