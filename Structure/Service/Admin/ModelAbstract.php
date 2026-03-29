<?php

/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\Service\Admin;

use Ideal\Core\Admin\Model;
use Ideal\Core\Config;

/**
 * Класс для построение бокового меню в разделе Сервис и запуска скриптов выбранного пункта
 */
class ModelAbstract extends Model
{
    /** @var array Массив с пунктами бокового меню */
    protected $menu = [];

    /**
     * {@inheritdoc}
     */
    public function detectPageByIds($path, $par): self
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
     * Получение списка пунктов бокового меню
     *
     * @return array Массив с пунктами бокового меню
     */
    public function getMenu(): array
    {
        if (count($this->menu) > 0) {
            return $this->menu;
        }

        // Считываем конфиги из папки Ideal/Service и Custom/Service
        $actions = array_merge(
            $this->getActions('Ideal/Structure/Service'),
            $this->getActions('Ideal.c/Structure/Service'),
            $this->getModulesActions('Mods'),
            $this->getModulesActions('Mods.c'),
        );

        // Сортируем экшены по полю pos
        usort(
            $actions,
            fn(array $a, array $b) => $a['pos'] - $b['pos'],
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
    protected function getActions(string $folder): array
    {
        $config = Config::getInstance();
        $actions = [];
        $dir = stream_resolve_include_path($config->cmsFolder . '/' . $folder);
        if ($handle = opendir($dir)) {
            while (false !== ($file = readdir($handle))) {
                if (in_array($file, ['.', '..', 'Admin'], true)) {
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
    protected function getModulesActions(string $folder): array
    {
        $config = Config::getInstance();
        $actions = [];
        $dir = stream_resolve_include_path($config->cmsFolder . '/' . $folder);
        if ($handle = opendir($dir)) {
            while (false !== ($file = readdir($handle))) {
                if (in_array($file, ['.', '..', '.hg'], true)) {
                    continue;
                }

                if (!is_dir($dir . '/' . $file)) {
                    continue;
                } // пропускаем файлы, работаем только с папками

                $actions = array_merge(
                    $actions,
                    $this->getActions($folder . '/' . $file . '/Structure/Service'),
                );
            }
        }

        return $actions;
    }
}
