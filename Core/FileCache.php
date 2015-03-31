<?php
namespace Ideal\Core;

/**
 * Class FileCache
 *  Класс обеспечивает работу с файловым кэшем
 * @package Ideal\Core
 */
class FileCache
{

    /**
     * Сохраняет содержимое в файл кэша
     * @param string $content Контент подлежащий сохранению
     * @param string $uri Путь, используется в построении иерархии директорий и имени самого файла.
     */
    public static function saveCache($content, $uri)
    {

        // Исключаем главную страницу из кэширования
        // TODO Продумать вариант кэширования главной, пока просто исключаем
        if ($uri != '/') {
            // Путь до файла хранящего информацию о закэшированных страницах
            $cacheDir = DOCUMENT_ROOT . '/tmp/cache';

            // Проверяем на существование директорию для хранения информации о кэше.
            if (!is_dir($cacheDir)) {
                mkdir($cacheDir);
            }

            $cacheFile = $cacheDir . '/site_cache.php';

            // Проверяем файл на существование
            if (file_exists($cacheFile)) {
                $cacheFileValue = require_once($cacheFile);
            } else {
                $cacheFileValue = array();
            }

            array_push($cacheFileValue, $uri);

            // TODO учесть параметры запроса
            $uri = array_values(array_filter(explode('/', $uri)));
            $fileName = array_pop($uri);
            $dirPath = DOCUMENT_ROOT . '/' . implode('/', $uri);

            // Проверяем на существование нужную директорию, если таковая отсутствует, то создаём её
            if (!is_dir($dirPath)) {
                mkdir($dirPath);
            }

            // Записываем файл кэша
            file_put_contents($dirPath . '/' . $fileName, $content);

            // Записываем информацию о кэше в файл
            $file = "<?php\n// @codingStandardsIgnoreFile\nreturn " . var_export($cacheFileValue, true) . ";\n";
            file_put_contents($cacheFile, $file);
        }
    }
}
