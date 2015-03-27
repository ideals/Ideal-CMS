<?php
namespace Ideal\Core;

/**
 * Class Filecache
 *  Класс обеспечивает работу с файловым кэшем
 * @package Ideal\Core
 */
class Filecache
{

    /**
     * Сохраняет содержимое в файл кэша
     * @param $content
     *  Контент подлежащий сохранению
     * @param $uri
     *  Путь, используется в построении иерархии директорий и имени самого файла.
     */
    public static function saveCache($content, $uri)
    {
        // TODO учесть параметры запроса
        $uri = array_values(array_filter(explode('/', $uri)));
        $fileName = array_pop($uri);
        $dirPath = DOCUMENT_ROOT . '/tmp/cache/' . implode('/', $uri);

        // Проверяем на существование нужную директорию, если таковая отсутствует, то создаём её
        if (!is_dir($dirPath)) {
            mkdir($dirPath);
        }

        // Записываем файл кэша
        file_put_contents($dirPath . '/' . $fileName, $content);
    }
}
