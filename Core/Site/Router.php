<?php
namespace Ideal\Core\Site;

use Ideal\Core\Config;
use Ideal\Core\PluginBroker;
use Ideal\Core\Request;
use Ideal\Core\Util;
use Ideal\Core\Db;
use Ideal\Structure\User;

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
        if (!$this->model->is404) {
            $this->model = $this->model->detectActualModel();
        }
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
            $is404 = self::matchesRules($known404List, $url);
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
            if ($model->is404) {
                self::save404($originalUrl, $known404);
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

    /**
     * Сохраняет информацию 404 ошибке в справочник/файл
     *
     * @param string $url Запрошенный адрес
     * @param bool| $known404
     */
    private function save404($url, $known404)
    {
        $config = Config::getInstance();
        $error404Structure = $config->getStructureByName('Ideal_Error404');
        $user = new User\Model();
        $isAdmin = $user->checkLogin();

        // Запускаем процесс обработки 404 страницы только если
        // существует структура "Ideal_Error404",
        // существует файл known404.php
        // пользователь не залогинен в админку
        if ($error404Structure !== false && $known404 !== false && !$isAdmin) {
            $known404Params = $known404->getParams();

            // Прверяем есть ли запрошенный url среди исключений
            $rules404List = array_filter(explode("\n", $known404Params['rules']['arr']['rulesExclude404']['value']));
            $notRec = self::matchesRules($rules404List, $url);

            if ($notRec !== true) {
                $db = DB::getInstance();
                $error404Table = $config->db['prefix'] . 'ideal_structure_error404';
                // Получаем данные о рассматриваемом url в справочнике "Ошибки 404"
                $par = array('url' => $url);
                $fields = array('table' => $error404Table);
                $rows = $db->select('SELECT * FROM &table WHERE url = :url LIMIT 1', $par, $fields);
                if (count($rows) == 0) {
                    // Добавляем запись в справочник
                    $dataList = $config->getStructureByName('Ideal_DataList');
                    $prevStructure = $dataList['ID'] . '-';
                    $par = array('structure' => 'Ideal_Error404');
                    $fields = array('table' => $config->db['prefix'] . 'ideal_structure_datalist');
                    $row = $db->select('SELECT ID FROM &table WHERE structure = :structure', $par, $fields);
                    $prevStructure .= $row[0]['ID'];
                    $params = array(
                        'prev_structure' => $prevStructure,
                        'date_create' => time(),
                        'url' => $url,
                        'count' => 1,
                    );
                    $db->insert($error404Table, $params);
                } elseif ($rows[0]['count'] < 10) {
                    // Увеличиваем счётчик посещения страницы
                    $values = array('count' => $rows[0]['count'] + 1);
                    $par = array('url' => $url);
                    $db->update($error404Table)->set($values)->where('url = :url', $par)->exec();
                } else {
                    // Переносим данные из справочника в файл с известными 404
                    $known404List = array_filter(explode("\n", $known404Params['known']['arr']['known404']['value']));
                    $known404List[] = $url;
                    $known404Params['known']['arr']['known404']['value'] = implode("\n", $known404List);
                    $known404->setParams($known404Params);
                    $known404->saveFile(DOCUMENT_ROOT . '/' . $config->cmsFolder . '/known404.php');
                    $par = array('url' => $url);
                    $db->delete($error404Table)->where('url = :url', $par)->exec();
                }
            }
        }
    }

    private function matchesRules($rules, $subject)
    {
        return array_reduce(
            $rules,
            function (&$res, $rule) {
                if (strpos($rule, '/') !== 0) {
                    $rule = '/' . addcslashes($rule, '/') . '/';
                }
                if (!empty($rule) && ($res === true || preg_match($rule, $res))) {
                    return true;
                }
                return $res;
            },
            $subject
        );
    }
}
