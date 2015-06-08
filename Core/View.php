<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2015 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Core;

/**
 * Класс вида View, обеспечивающий отображение переданных в него данных
 * в соответствии с указанным twig-шаблоном
 */
class View
{

    /** @var \Twig_TemplateInterface */
    protected $template;

    /** @var \Twig_Environment * */
    protected $templater;

    /** @var array Массив для хранения переменных, передаваемых во View */
    protected $vars = array();

    /**
     * Инициализация шаблонизатора
     *
     * @param string|array $pathToTemplates Путь или массив путей к папкам, где лежат используемые шаблоны
     * @param bool $isCache
     */
    public function __construct($pathToTemplates, $isCache = false)
    {
        // Подгружаем Twig
        require_once 'Library/Twig/Autoloader.php';
        \Twig_Autoloader::register();

        $loader = new \Twig_Loader_Filesystem($pathToTemplates);

        $config = Config::getInstance();
        $params = array();
        if ($isCache) {
            $cachePath = DOCUMENT_ROOT . $config->cms['tmpFolder'] . '/templates';
            $params['cache'] = stream_resolve_include_path($cachePath);
            if ($params['cache'] == false) {
                Util::addError('Не удалось определить путь для кэша шаблонов: ' . $cachePath);
                exit;
            }
        }
        $this->templater = new \Twig_Environment($loader, $params);
    }

    /**
     * Получение переменной View
     *
     * Передача по ссылке используется для того, чтобы в коде была возможность изменять значения
     * элементов массива, хранящегося во View. Например:
     *
     * $view->addonName[key]['content'] = 'something new';
     *
     * @param string $name Название переменной
     * @return mixed Переменная
     */
    public function &__get($name)
    {
        if (is_scalar($this->vars[$name])) {
            $property = $this->vars[$name];
        } else {
            $property = &$this->vars[$name];
        }
        return $property;
    }

    /**
     * Магический метод для проверки наличия запрашиваемой переменной
     *
     * @param string $name Название переменной
     * @return bool Инициализирована эта переменная или нет
     */
    public function __isset($name)
    {
        return isset($this->vars[$name]);
    }

    /**
     * Установка значения элемента, передаваемого во View
     *
     * @param string $name Название переменной
     * @param mixed $value Значение переменной
     */
    public function __set($name, $value)
    {
        $this->vars[$name] = $value;
    }

    /**
     * Загрузка в шаблонизатор файла с twig-шаблоном
     *
     * @param string $fileName Название twig-файла
     */
    public function loadTemplate($fileName)
    {
        $this->template = $this->templater->loadTemplate($fileName);
    }

    public function render()
    {
        return $this->template->render($this->vars);
    }

    /**
     * Чистит все файлы twig кэширования
     */
    public static function clearTwigCache($path = '')
    {
        $config = Config::getInstance();
        if (empty($path)) {
            $cachePath = DOCUMENT_ROOT . $config->cms['tmpFolder'] . '/templates';
        } else {
            $cachePath = $path;
        }
        if ($objs = glob($cachePath . '/*')) {
            foreach ($objs as $obj) {
                is_dir($obj) ? self::clearTwigCache($obj) : unlink($obj);
            }
        }
        if (!empty($path)) {
            rmdir($cachePath);
        }
    }
}
