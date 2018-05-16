<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\Crm\Inbox;

use Ideal\Core\Config;
use Ideal\Core\Request;

/**
 * Класс для построение бокового меню в разделе Сервис и запуска скриптов выбранного пункта
 */
class Model extends \Ideal\Core\Admin\Model
{
    /** @var array Массив с пунктами бокового меню */
    protected $menu = array();

    /**
     * {@inheritdoc}
     */
    public function detectPageByIds($path, $par)
    {
        $this->path = $path;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPageData()
    {
        $data = parent::getPageData();
        $orderModel = new \Ideal\Structure\Order\Model();
        $orders = $orderModel->getCrmOrders();
        foreach ($orders as &$item) {
            $item['date_create_str'] = date('d.m.Y H:i', $item['date_create']);
        }
        unset($item);

        $data['inbox'] = $orders;
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

    public function detectActualModel()
    {
        // Если дошли до определения это модели, то нужно отдавать модель соответствующей структуры
        $model = new \Ideal\Structure\Order\Admin\Model('');
        $model->setVars($this);
        return $model;
    }

    /**
     * Получает данные от соответствующей модели структуры
     *
     * @return bool|mixed Данные элемента структуры или false в случае отсутствия идентификатора в запросе
     * @throws \Exception
     */
    public function getOrderInfo()
    {
        $model = new \Ideal\Structure\Order\Admin\Model('');
        $request = new Request();
        if ($request->id) {
            $model->setPageDataById($request->id);
            return $model->getPageData();
        }
        return false;
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
