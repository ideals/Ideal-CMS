<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\Crm\Admin;

use Ideal\Core\Config;

/**
 * Класс для построение бокового меню в разделе Сервис и запуска скриптов выбранного пункта
 */
class ModelAbstract extends \Ideal\Core\Admin\Model
{
    /** @var array Массив с пунктами бокового меню */
    protected $menu = array();

    /**
     * {@inheritdoc}
     */
    public function detectPageByIds($path, $par)
    {
        $menu = $this->getMenu();
        // Если par не указан, то активен первый пункт бокового меню
        $item = reset($menu);

        $first = reset($par);
        if ($first) {
            // Если $par указан, то находим активный пункт бокового меню
            foreach ($menu as $item) {
                if ($item['ID'] == $first) {
                    break;
                }
            }
        }

        $this->setPageData($item);
        $path[] = $item;

        $this->path = $path;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPageData()
    {
        $data = parent::getPageData();

        // Определяем контроллер, который нам нужно запустить на основе выбранного пункта раздела
        $structure = explode('_', $data['ID']);
        $class = '\\' . $structure[0] . '\\Structure\\Crm\\' . $structure[1] . '\\Controller';
        $controller = new $class();

        // Запускаем контроллер - получаем html-код, который нужно отобразить
        $data['content'] = $controller->run();
        return $data;
    }

    /**
     * Получение списка пунктов бокового меню
     *
     * @return array Массив с пунктами бокового меню
     */
    public function getMenu()
    {
        if (count($this->menu) > 0) {
            return $this->menu;
        }

        // Считываем конфиги из папки Ideal/Service и Custom/Service
        $actions = array_merge(
            $this->getActions('Ideal/Structure/Crm'),
            $this->getActions('Ideal.c/Structure/Crm'),
            $this->getModulesActions('Mods'),
            $this->getModulesActions('Mods.c')
        );

        // Сортируем экшены по полю pos
        usort(
            $actions,
            function ($a, $b) {
                return ($a['pos'] - $b['pos']);
            }
        );

        $this->menu = $actions;
        return $actions;
    }

    /**
     * Получение пунктов бокового меню на основе содержимого папок Structure\Service
     *
     * @param string $folder Путь к папке в которой ищем вложенные папки с экшенами пункта Сервис
     * @return array Массив с пунктами бокового меню
     */
    protected function getActions($folder)
    {
        $config = Config::getInstance();
        $actions = array();
        $dir = stream_resolve_include_path($config->cmsFolder . '/' . $folder);
        if ($handle = opendir($dir)) {
            while (false !== ($file = readdir($handle))) {
                if ($file == '.' || $file == '..' || $file == 'Admin') {
                    continue;
                }
                if (!is_dir($dir . '/' . $file)) {
                    continue;
                } // пропускаем файлы, работаем только с папками

                $file = $dir . '/' . $file . '/config.php';
                if (!file_exists($file)) {
                    // Если конфигурационного файла нет, то никакого пункта в меню Сервис не добавляем
                    continue;
                }
                $action = include($file);
                $actions[$action['ID']] = $action;
            }
        }
        return $actions;
    }

    /**
     * Получение пунктов бокового меню из подключенных модулей
     *
     * @param string $folder Путь к папке в которой ищем вложенные папки с экшенами пункта Сервис
     * @return array Массив с пунктами бокового меню
     */
    protected function getModulesActions($folder)
    {
        $config = Config::getInstance();
        $actions = array();
        $dir = stream_resolve_include_path($config->cmsFolder . '/' . $folder);
        if ($handle = opendir($dir)) {
            while (false !== ($file = readdir($handle))) {
                if ($file == '.' || $file == '..' || $file == '.hg') {
                    continue;
                }
                if (!is_dir($dir . '/' . $file)) {
                    continue;
                } // пропускаем файлы, работаем только с папками

                $actions = array_merge(
                    $actions,
                    $this->getActions($folder . '/' . $file . '/Structure/Crm')
                );
            }
        }
        return $actions;
    }
}
