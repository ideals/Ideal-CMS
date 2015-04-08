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
        $excludeThisPath = false;
        $config = Config::getInstance();
        $configCache = $config->cache;

        // Получаем чистый $uri без GET параметров
        $queryString = http_build_query($_GET);
        if (!empty($queryString)) {
            $uri = str_replace('?' . $queryString, '', $uri);
            $excludeThisPath = true;
        }

        $uriArray = array_values(array_filter(explode('/', $uri)));

        if (empty($uriArray)) {
            $uri .= $configCache['indexFile'];
            array_push($uriArray, $configCache['indexFile']);
        }

        $excludeCacheFileValue = self::getConfigArrayFile(DOCUMENT_ROOT . '/tmp/cache/exclude_cache.php');

        // Проверяем наличие рассматриваемого пути в исключениях
        // TODO переделать на проверку по регулярному выражению из исключений
        if (array_search($uri, $excludeCacheFileValue) === false) {
            // Если данная страница ещё не в исключениях, но должна там быть
            if ($excludeThisPath) {
                self::excludePathFromCache($uri);
                return;
            }

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
     * Очищает весь файловый кэш
     */
    public static function clearFileCache()
    {
        $cacheFile = DOCUMENT_ROOT . '/tmp/cache/site_cache.php';
        $cacheFileValue = self::getConfigArrayFile($cacheFile);
        if (!empty($cacheFileValue)) {
            foreach ($cacheFileValue as $path) {
                self::delCacheFileDir($path);
            }
        }

        // Очищаем файл хранящий информацию о кэшировании
        $file = "<?php\n// @codingStandardsIgnoreFile\nreturn array();\n";
        file_put_contents($cacheFile, $file);
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
            return require($path);
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

    /**
     * Удаляет файл кэша и директории его нахождения, если они пустые
     *
     * @param string $path путь до удаляемого файла
     */
    private static function delCacheFileDir($path)
    {
        // Удаляем сам файл
        unlink(DOCUMENT_ROOT . $path);

        // Последовательная проверка каджого каталога из всей иерархии на возможность удаления
        $dirArray = array_values(array_filter(explode('/', $path)));
        array_pop($dirArray);
        if (!empty($dirArray)) {
            // Получаем массив с полными путями до каждого каталога в иерархии
            $implodeDirArrayElement = array();
            for ($i = 0; $i < count($dirArray); $i++) {
                // TODO продумать вариант получения пути по красивее
                $dirPath = implode('/', explode('/', implode('/', $dirArray), 0 - $i));
                $implodeDirArrayElement[] = DOCUMENT_ROOT . '/' . $dirPath;
            }

            // Попытка удаления каждого каталога из иерархии
            foreach ($implodeDirArrayElement as $dirPath) {
                if (count(glob($dirPath . '/*'))) {
                    break;
                }
                rmdir($dirPath);
            }
        }
    }

    /**
     * Удаляет файл кэша страницы и заносит путь в исключение
     *
     * @param string $path адрес страницы подлежащей исключению из кэша
     */
    private static function excludePathFromCache($path)
    {
        $cacheFile = DOCUMENT_ROOT . '/tmp/cache/site_cache.php';
        $cacheFileValue = self::getConfigArrayFile($cacheFile);
        $key = array_search($path, $cacheFileValue);
        if ($key !== false) {
            self::delCacheFileDir($path);
            unset($cacheFileValue[$key]);

            // Cбрасываем ключи массива
            $cacheFileValue = array_values($cacheFileValue);

            // Записываем информацию о кэше в файл
            $file = "<?php\n// @codingStandardsIgnoreFile\nreturn " . var_export($cacheFileValue, true) . ";\n";
            file_put_contents($cacheFile, $file);
        }

        // Добавляем адрес в исключения
        // Путь до файла хранящего информацию о исключениях
        $excludeCacheDir = DOCUMENT_ROOT . '/tmp/cache';

        self::checkDir($excludeCacheDir);

        $excludeCacheFile = $excludeCacheDir . '/exclude_cache.php';
        $excludeCacheFileValue = self::getConfigArrayFile($excludeCacheFile);

        // Проверяем путь на отсутствие в исключениях, для предотвращения дублирования
        if (array_search($path, $cacheFileValue) === false) {
            $excludeCacheFileValue[] = $path;

            // Записываем информацию о исключениях в файл
            $file = "<?php\n// @codingStandardsIgnoreFile\nreturn " . var_export($excludeCacheFileValue, true) . ";\n";
            file_put_contents($excludeCacheFile, $file);
        }
    }
}
