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

        //Получаем чистый $uri без GET параметров
        $queryString = http_build_query($_GET);
        if (!empty($queryString)) {
            $uri = str_replace('?' . $queryString, '', $uri);
        }

        $uriArray = array_values(array_filter(explode('/', $uri)));

        if (empty($uriArray)) {
            $uri .= $configCms['indexFile'];
            array_push($uriArray, $configCms['indexFile']);
        }

        $excludeCacheFileValue = self::getConfigArrayFile(DOCUMENT_ROOT . '/tmp/cache/exclude_cache.php');

        // Проверяем наличие рассматриваемого пути в исключениях
        if (array_search($uri, $excludeCacheFileValue) === false) {
            // Путь до файла хранящего информацию о закэшированных страницах
            $cacheDir = DOCUMENT_ROOT . '/tmp/cache';

            self::checkDir($cacheDir);

            $cacheFile = $cacheDir . '/site_cache.php';
            $cacheFileValue = self::getConfigArrayFile($cacheFile);

            array_push($cacheFileValue, $uri);

            $fileName = array_pop($uriArray);
            $dirPath = DOCUMENT_ROOT . '/' . implode('/', $uriArray);

            self::checkDir($dirPath);

            // Записываем файл кэша
            file_put_contents($dirPath . '/' . $fileName, $content);

            // Записываем информацию о кэше в файл
            $file = "<?php\n// @codingStandardsIgnoreFile\nreturn " . var_export($cacheFileValue, true) . ";\n";
            file_put_contents($cacheFile, $file);
        }
    }

    /**
     * Проверяет файл-массив на существование и возвращает его значение
     *
     * @param string $path путь к файлу
     * @return array массив с содержимым файла
     */
    private static function getConfigArrayFile($path)
    {
        if (file_exists($path)) {
            return require_once($path);
        } else {
            return array();
        }
    }

    /**
     * Проверяет на существование нужную директорию, если таковая отсутствует, то создаёт её
     *
     * @param string $path путь к файлу
     */
    private static function checkDir($path)
    {
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
    }
}
