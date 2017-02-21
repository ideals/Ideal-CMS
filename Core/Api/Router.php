<?php
namespace Ideal\Core\Api;

use Ideal\Core\Config;
use Ideal\Core\Request;
use Ideal\Structure\Error404;

/**
 *  Производит роутинг запросов к API системы
 */
class Router
{

    /** @var string Название контроллера обрабатывающего запрос */
    protected $controllerName = '';

    /** @var Model Модель для обработки 404-ых ошибок */
    protected $error404 = null;

    /** @var bool Флаг 404-ошибки */
    public $is404 = false;

    /**
     * Конструктор определяет модель обрабочика 404 ошибки.
     */
    public function __construct()
    {
        $this->error404 = new Error404\Model();
    }

    /**
     * Возвращает путь до контроллера ответственного за обработку запроса
     *
     * @return string Название контроллера
     */
    public function getControllerName()
    {

        if ($this->controllerName != '') {
            return $this->controllerName;
        }

        $url = $this->prepareUrl($_SERVER['REQUEST_URI']);
        $this->error404->setUrl($url);

        // Проверяем наличие адреса среди уже известных 404-ых
        $this->is404 = $this->error404->checkAvailability404();

        $request = new Request();
        $path = explode('/', $url);

        if (!$this->is404) {
            // Определяем название контроллера и экшена
            if (count($path) == 3) {
                $this->detectController($path[1]);
                if (!$this->is404) {
                    $request->action = $path[2];
                    if (!$request->action) {
                        // Не определён action
                        $this->is404 = true;
                        $this->error404->save404();
                    }
                }
            } elseif (count($path) == 4) {
                $this->detectController($path[2], 'Mods');
                list(, ,$this->controllerName) = explode('\\', $this->controllerName, 3);
                if (!$this->is404) {
                    $request->action = $path[3];
                    if (!$request->action) {
                        // Не определён action
                        $this->is404 = true;
                        $this->error404->save404();
                    }
                }
            } else {
                $this->is404 = true;
            }
        }

        return $this->controllerName ? $this->controllerName : '\\Ideal\\Core\\Api\\Controller';
    }

    /**
     * Устанавливает название контроллера
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
     * Возвращает статус 404-ошибки,
     */
    public function is404()
    {
        return $this->is404;
    }

    /**
     * Возвращает значение флага отпрваки сообщения о 404ой ошибке
     */
    public function send404()
    {
        return $this->error404->send404();
    }

    /**
     * Обёртка над методом сохранения 404 ошибки соответствующей модели
     */
    public function save404()
    {
        return $this->error404->save404();
    }

    /**
     * Ищет контроллер ответственный за обработку запроса
     * @param $controllerName string Часть имени контроллера ответственного за обработку запроса
     * @param $namespace string Место поиска контроллера
     */
    private function detectController($controllerName, $namespace = 'Ideal')
    {
        $config = Config::getInstance();

        // Возможные пути до начальных директорий в зависимости от запроса
        $searchPath = array(
            DOCUMENT_ROOT . DIRECTORY_SEPARATOR . $config->cmsFolder . DIRECTORY_SEPARATOR . $namespace . '.c',
            DOCUMENT_ROOT . DIRECTORY_SEPARATOR . $config->cmsFolder . DIRECTORY_SEPARATOR . $namespace
        );
        $controllerPath = '';
        foreach ($searchPath as $value) {
            if (is_dir($value)) {
                $directory = new \RecursiveDirectoryIterator($value);
                foreach (new \RecursiveIteratorIterator($directory) as $file) {
                    if ($file->getFilename() == $controllerName . 'Controller.php') {
                        $controllerPath = $file->getPathname();
                        break 2;
                    }
                }
            }
        }

        // Если контроллер не найден устанавливаем признак 404 ошибки
        if (!$controllerPath) {
            $this->is404 = true;
            $this->error404->save404();
        } else {
            $controllerPath = str_replace(stream_resolve_include_path($config->cmsFolder), '', $controllerPath);
            $controllerPath = str_replace('/', '\\', $controllerPath);
            $controllerPath = str_replace('.php', '', $controllerPath);
            $this->controllerName = $controllerPath;
        }
    }

    /**
     * Зачистка url перед роутингом по нему
     *
     * @param string $url
     * @param bool $stripQuery Нужно ли удалять символы после ?
     * @return string
     */
    protected function prepareUrl($url, $stripQuery = true)
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
}
