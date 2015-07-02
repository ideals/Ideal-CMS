<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2015 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */
namespace Ideal\Structure\Service\Cache;

use Ideal\Addon\SiteMap;
use Ideal\Core\FileCache;
use Ideal\Core\Memcache;
use Ideal\Core\View;
use Ideal\Core\Config;

/**
 * Сброс всего кэширования
 *
 */
class AjaxController extends \Ideal\Core\AjaxController
{

    /**
     * Действие срабатывающее при нажатии на кнопку "Очистить кэш"
     */
    public function clearCacheAction()
    {
        $config = Config::getInstance();
        $configCache = $config->cache;

        // Очищаем файловый кэш
        if (isset($configCache['fileCache']) && $configCache['fileCache']) {
            FileCache::clearFileCache();
        }

        // Очищаем Memcache
        $memcache = Memcache::getInstance();
        $memcache->flush();

        // Очищаем twig кэш
        View::clearTwigCache();

        // Удаляем сжатый css
        if (file_exists(DOCUMENT_ROOT . '/css/all.min.css')) {
            unlink(DOCUMENT_ROOT . '/css/all.min.css');
        }

        // Удаляем сжатый js
        if (file_exists(DOCUMENT_ROOT . '/js/all.min.js')) {
            unlink(DOCUMENT_ROOT . '/js/all.min.js');
        }

        print json_encode(array('text' => 'ok'));
        exit;
    }

    /**
     * Действие срабатывающее при нажатии на кнопку "Очистить кэш"
     */
    public function dellCacheFilesAction()
    {
        $delPages = array();
        $pageList = new SiteMap\Model('0-1');
        $pages = $pageList->getList();
        foreach ($pages as $page) {
            if (FileCache::delCacheFileDir($page['link'])) {
                $delPages[] = $page['link'];
            }
        }
        $delPages = implode("<br />", $delPages);
        print json_encode(array('text' => $delPages));
        exit;
    }
}
