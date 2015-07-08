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
        $configCache = $config->cache;

        // Получаем чистый $uri без GET параметров
        list($uri) = explode('?', $uri, 2);

        $uriArray = self::getModifyUri($uri);

        $excludeCacheFileValue = explode("\n", $configCache['excludeFileCache']);

        // Удаляем первый слэш, для использования пути в проверке на исключения
        $stringToCheck = preg_replace('/\//', '', $uri, 1);

        $exclude = false;
        foreach ($excludeCacheFileValue as $pattern) {
            if (preg_match($pattern, $stringToCheck)) {
                $exclude = true;
            }
        }

        // Проверяем наличие рассматриваемого пути в исключениях
        if (!$exclude) {
            // Путь до файла хранящего информацию о закэшированных страницах
            $cacheDir = DOCUMENT_ROOT . $config->cms['tmpFolder'] . '/cache';

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
     * Проверяет файл информации о кэшировании на возможность записи
     */
    public static function checkFileCache()
    {
        $config = Config::getInstance();
        $cacheFile = DOCUMENT_ROOT . $config->cms['tmpFolder'] . '/cache/site_cache.php';
        $cacheDir = DOCUMENT_ROOT . $config->cms['tmpFolder'] . '/cache';
        // Проверяем файл на доступность для записи
        if (!is_writable($cacheFile)) {
            // Если не доступен, то для начала пробуем изменить права на всю папку со всеми волженностями
            Util::chmod($cacheDir, '777', '777');

            // Проверяем ещё раз на возможность записи
            if (!is_writable($cacheFile)) {
                // Если файл всё ещё недоступен, тогда проверяем папку на существование
                self::checkDir($cacheDir);

                // Затем проверяем на существование сам файл
                if (!file_exists($cacheFile)) {
                    // Если он не существует, то пытаемся его создать
                    $file = "<?php\n// @codingStandardsIgnoreFile\nreturn array();\n";
                    if (file_put_contents($cacheFile, $file) === false) {
                        return 'Не удалось создать файл информации о кэшировании';
                    } else {
                        return 'ok';
                    }
                }
            } else {
                return 'ok';
            }
            return 'Файл информации о кэшировании не доступен для записи';
        }
        return 'ok';
    }

    /**
     * Очищает весь файловый кэш
     */
    public static function clearFileCache()
    {
        $config = Config::getInstance();
        $cacheFile = DOCUMENT_ROOT . $config->cms['tmpFolder'] . '/cache/site_cache.php';
        $cacheFileValue = self::getConfigArrayFile($cacheFile);

        if (empty($cacheFileValue)) {
            // Если в кэше ничего нет, то и делать ничего не надо
            return;
        }

        // Удаляем закэшированные файлы
        foreach ($cacheFileValue as $path) {
            self::delCacheFileDir($path);
        }

        // Очищаем файл хранящий информацию о кэшировании
        $file = "<?php\n// @codingStandardsIgnoreFile\nreturn array();\n";
        file_put_contents($cacheFile, $file);
    }

    /**
     * Добовляет значение исключения файлового кэша
     */
    public static function addExcludeFileCache($string)
    {
        // Проверяем на существование файл кэша, при надобности удаляем
        preg_match('/^\/(.*)\/[imsxADSUXJu]{0,11}$/', $string, $cacheFiles);
        $cacheFiles = glob(stripcslashes($cacheFiles[1]));
        if (!empty($cacheFiles)) {
            foreach ($cacheFiles as $cacheFile) {
                self::excludePathFromCache("/$cacheFile");
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

    private static function getModifyUri(&$uri)
    {

        $config = Config::getInstance();
        $configCache = $config->cache;

        $uriArray = array_values(array_filter(explode('/', $uri)));

        $pageName = end($uriArray);
        reset($uriArray);

        // Если это главная страница или каталог
        if (!$pageName || !preg_match('/.*\..*$/', $pageName)) {
            $uri .= $configCache['indexFile'];
            array_push($uriArray, $configCache['indexFile']);
        }

        return $uriArray;
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

    /**
     * Удаляет файл кэша страницы и информацию о нём из общего файла
     *
     * @param string $path адрес страницы подлежащей исключению из кэша
     */
    private static function excludePathFromCache($path)
    {
        $config = Config::getInstance();
        $cacheFile = DOCUMENT_ROOT . $config->cms['tmpFolder'] . '/cache/site_cache.php';
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
    }
}
