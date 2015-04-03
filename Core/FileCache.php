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
 * Класс обеспечивает работу с файловым кэшем
 *
 * Пример инициализации процесса сохранения страницы в кэш:
 *   FileCache::saveCache('HTML-содержимое', 'адрес страницы');
 */
class FileCache
{

    /**
     * Сохраняет содержимое в файл кэша
     *
     * @param string $content Контент подлежащий сохранению
     * @param string $uri Путь, используется в построении иерархии директорий и имени самого файла.
     */
    public static function saveCache($content, $uri)
    {

        $config = Config::getInstance();
        $configCms = $config->cms;

        // TODO учесть параметры запроса
        $uriArray = array_values(array_filter(explode('/', $uri)));

        if (empty($uriArray)) {
            $uri .= $configCms['indexFile'];
            array_push($uriArray, $configCms['indexFile']);
        }

        // Путь до файла хранящего информацию о закэшированных страницах
        $cacheDir = DOCUMENT_ROOT . '/tmp/cache';

        // Проверяем на существование директорию для хранения информации о кэше.
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }

        $cacheFile = $cacheDir . '/site_cache.php';

        // Проверяем файл на существование
        if (file_exists($cacheFile)) {
            $cacheFileValue = require_once($cacheFile);
        } else {
            $cacheFileValue = array();
        }

        array_push($cacheFileValue, $uri);

        $fileName = array_pop($uriArray);
        $dirPath = DOCUMENT_ROOT . '/' . implode('/', $uriArray);

        // Проверяем на существование нужную директорию, если таковая отсутствует, то создаём её
        if (!is_dir($dirPath)) {
            mkdir($dirPath, 0777, true);
        }

        // Записываем файл кэша
        file_put_contents($dirPath . '/' . $fileName, $content);

        // Записываем информацию о кэше в файл
        $file = "<?php\n// @codingStandardsIgnoreFile\nreturn " . var_export($cacheFileValue, true) . ";\n";
        file_put_contents($cacheFile, $file);
    }
}
