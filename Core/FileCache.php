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
     * @param int $modifyTime Timestamp представление даты последнего изменения информации о странице
     */
    public static function saveCache($content, $uri, $modifyTime)
    {
        $config = Config::getInstance();
        $configCache = $config->cache;

        // Получаем чистый $uri без GET параметров
        list($uri) = explode('?', $uri, 2);

        // Удаляем первый слэш, для использования пути в проверке на исключения
        $stringToCheck = preg_replace('/\//', '', $uri, 1);

        $uriArray = self::getModifyUri($uri);

        $excludeCacheFileValue = explode("\n", $configCache['excludeFileCache']);

        $excludeCacheFileValue = array_filter($excludeCacheFileValue);

        $exclude = false;
        foreach ($excludeCacheFileValue as $pattern) {
            if (preg_match($pattern, $stringToCheck)) {
                $exclude = true;
            }
        }

        // Проверяем наличие рассматриваемого пути в исключениях
        if (!$exclude) {
            // Путь до общего каталога закэшированных страниц
            $cacheDir = DOCUMENT_ROOT . $config->cms['tmpFolder'] . '/cache/fileCache';

            self::checkDir($cacheDir);

            $fileName = array_pop($uriArray);
            if (!empty($uriArray)) {
                $dirPath = $cacheDir . '/' . implode('/', $uriArray);
            } else {
                $dirPath = $cacheDir;
            }

            self::checkDir($dirPath);

            // Записываем файл кэша
            if (file_put_contents($dirPath . '/' . $fileName, $content) !== false) {
                touch($dirPath . '/' . $fileName, $modifyTime);
            }
        }
    }

    /**
     * Очищает весь файловый кэш
     */
    public static function clearFileCache()
    {
        $config = Config::getInstance();
        $ds = DIRECTORY_SEPARATOR;
        $dir = DOCUMENT_ROOT . $config->cms['tmpFolder'] . $ds . 'cache' . $ds . 'fileCache';
        if (is_dir($dir)) {
            $it = new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS);
            $files = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($files as $file) {
                if ($file->isDir()) {
                    rmdir($file->getRealPath());
                } else {
                    unlink($file->getRealPath());
                }
            }
            rmdir($dir);
        }
    }

    /**
     * Добовляет значение исключения файлового кэша
     *
     * @param string $string Адрес для исключения из кэширования
     *
     * @return bool Флаг, отражающий успешность добавления адреса в исключения
     */
    public static function addExcludeFileCache($string)
    {
        $config = Config::getInstance();

        // Проверяем на существование файл кэша, при надобности удаляем
        preg_match('/^\/(.*)\/[imsxADSUXJu]{0,11}$/', $string, $cacheFiles);

        // Добавляем путь до общей папки хранения файлового кэширования
        if (!empty($cacheFiles[1])) {
            $cacheFiles[1] = $config->cms['tmpFolder'] . '/cache/fileCache/' . $cacheFiles[1];
            $cacheFiles[1] = ltrim($cacheFiles[1], '/');
        }
        $cacheFiles = glob(stripcslashes($cacheFiles[1]));
        if (!empty($cacheFiles)) {
            foreach ($cacheFiles as $cacheFile) {
                self::delCacheFileDir('/' . $cacheFile);
            }
        }

        $config = Config::getInstance();
        $configSD = new \Ideal\Structure\Service\SiteData\ConfigPhp();
        $file = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/site_data.php';
        $configSD->loadFile($file);
        $params = $configSD->getParams();
        $excludeCacheFileValue = explode("\n", $params['cache']['arr']['excludeFileCache']['value']);
        if (array_search($string, $excludeCacheFileValue) === false) {
            $excludeCacheFileValue[] = $string;
            $params['cache']['arr']['excludeFileCache']['value'] = implode("\n", $excludeCacheFileValue);
            $configSD->setParams($params);
            $file = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/site_data.php';
            if ($configSD->saveFile($file) === false) {
                return false;
            } else {
                return true;
            }
        } else {
            return true;
        }
    }

    public static function getModifyUri(&$uri)
    {

        $config = Config::getInstance();
        $configCache = $config->cache;

        $uriArray = array_values(array_filter(explode('/', $uri)));

        $pageName = end($uriArray);
        reset($uriArray);

        // Если это главная страница или каталог
        if (!$pageName || !preg_match('/.*\..*$/', $pageName)) {
            if (!preg_match('/.*\/$/', $uri)) {
                $uri .= '/';
            }
            $uri .= $configCache['indexFile'];
            array_push($uriArray, $configCache['indexFile']);
        }

        return $uriArray;
    }

    /**
     * Проверяет на существование нужную директорию, если таковая отсутствует, то создаёт её
     *
     * @param string $path путь к папке
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
     * @return bool
     */
    public static function delCacheFileDir($path)
    {

        self::getModifyUri($path);

        // Удаляем сам файл
        if (file_exists(DOCUMENT_ROOT . $path)) {
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
            return true;
        } else {
            return false;
        }
    }
}
