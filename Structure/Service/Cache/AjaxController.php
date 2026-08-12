<?php

/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\Service\Cache;

use App\Cache\ClearCache;
use Ideal\Addon\SiteMap\SiteModel;
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
    public function clearCacheAction(): void
    {
        (new ClearCache())->execute();
        print json_encode(['text' => 'ok'], JSON_THROW_ON_ERROR);

        exit;
    }

    /**
     * Действие срабатывающее при нажатии на кнопку "Очистить кэш"
     */
    public function dellCacheFilesAction(): void
    {
        $config = Config::getInstance();
        $delPages = [];
        $pageList = new SiteModel('0-1');
        $pages = $pageList->getList();
        foreach ($pages as $page) {
            $path = $config->cms['tmpFolder'] . '/cache/fileCache' . $page['link'];
            if (FileCache::delCacheFileDir($path)) {
                $delPages[] = $page['link'];
            }
        }

        $delPages = implode("<br />", $delPages);
        print json_encode(['text' => $delPages]);
        exit;
    }
}
