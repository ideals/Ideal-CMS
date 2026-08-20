<?php

/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Core;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TemplateWrapper;

/**
 * Класс вида View, обеспечивающий отображение переданных в него данных
 * в соответствии с указанным twig-шаблоном
 */
class View
{
    protected TemplateWrapper $template;

    protected Environment $twig;

    /** @var array Массив для хранения переменных, передаваемых во View */
    protected array $vars = [];

    /**
     * Инициализация шаблонизатора
     *
     * @param string|array $pathToTemplates Путь или массив путей к папкам, где лежат используемые шаблоны
     * @param bool $isCache
     */
    public function __construct($pathToTemplates, $isCache = false)
    {
        // Определяем корневую папку системы для подключение шаблонов из любой вложенной папки через их путь
        $config = Config::getInstance();
        $cmsFolder = DOCUMENT_ROOT . '/' . $config->cmsFolder;

        // Папки от которых строится путь до шаблона
        $idealFolders = ['Ideal.c', 'Ideal', 'Mods.c', 'Mods'];
        foreach ($idealFolders as $k => $v) {
            if (file_exists($cmsFolder . '/' . $v)) {
                $idealFolders[$k] = $cmsFolder . '/' . $v;
            } else {
                unset($idealFolders[$k]);
            }
        }

        $pathToTemplates = is_string($pathToTemplates) ? [$pathToTemplates] : $pathToTemplates;

        $pathToTemplates = array_merge([$cmsFolder], $pathToTemplates, $idealFolders);

        $loader = new FilesystemLoader($pathToTemplates);

        $config = Config::getInstance();
        $params = [];
        if ($isCache) {
            $cachePath = DOCUMENT_ROOT . $config->cms['tmpFolder'] . '/templates';
            $params['cache'] = stream_resolve_include_path($cachePath);
            if ($params['cache'] == false) {
                if (mkdir($cachePath, 0777, true)) {
                    $params['cache'] = stream_resolve_include_path($cachePath);
                } else {
                    Util::addError('Не удалось определить путь для кэша шаблонов: ' . $cachePath);
                    exit;
                }
            }
        }

        $this->twig = new Environment($loader, $params);
    }

    /**
     * Магический метод для проверки наличия запрашиваемой переменной
     *
     * @param string $name Название переменной
     * @return bool Инициализирована эта переменная или нет
     */
    public function __isset(string $name)
    {
        return isset($this->vars[$name]);
    }

    /**
     * Установка значения элемента, передаваемого во View
     *
     * @param string $name Название переменной
     * @param mixed $value Значение переменной
     */
    public function __set(string $name, $value)
    {
        $this->vars[$name] = $value;
    }

    /**
     * Чистит все файлы twig кэширования
     */
    public static function clearTwigCache($path = ''): void
    {
        $config = Config::getInstance();
        $cachePath = empty($path) ? DOCUMENT_ROOT . $config->cms['tmpFolder'] . '/templates' : $path;

        if ($objs = glob($cachePath . '/*')) {
            foreach ($objs as $obj) {
                is_dir($obj) ? self::clearTwigCache($obj) : unlink($obj);
            }
        }

        if (!empty($path)) {
            rmdir($cachePath);
        }
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
    public function &__get(string $name)
    {
        if (is_scalar($this->vars[$name])) {
            $property = $this->vars[$name];
        } else {
            $property = &$this->vars[$name];
        }

        return $property;
    }

    /**
     * Загрузка в шаблонизатор файла с twig-шаблоном
     *
     * @param string $fileName Название twig-файла
     */
    public function loadTemplate($fileName): void
    {
        $this->template = $this->twig->load($fileName);
    }

    public function render()
    {
        return $this->template->render($this->vars);
    }
}
