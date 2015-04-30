<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2015 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */
namespace Ideal\Structure\Service\CheckCmsFiles;

use Ideal\Core\Config;

/**
 * Проверка целостности файлов системы
 *
 */
class AjaxController extends \Ideal\Core\AjaxController
{

    /**
     * Действие срабатывающее при нажатии на кнопку "Проверка целостности файлов"
     */
    public function checkCmsFilesAction()
    {
        $config = Config::getInstance();
        $cmsFolder = DOCUMENT_ROOT . '/' . $config->cmsFolder;

        // Получаем актуальную информацию о хэшах системных файлов
        $actualSystemFilesHash = self::getAllSystemFiles($cmsFolder . '/Ideal', $cmsFolder);

        // Подгружаем имеющуюся информацию о хэшах системных файлов
        $existingSystemFilesHash = require_once($cmsFolder . '/Ideal/setup/prepare/hash_files.php');

        // Получаем список новых системных файлов
        $newFiles = array_diff_key($actualSystemFilesHash, $existingSystemFilesHash);

        // Получаем список системных файлов которые были удалены
        $delFiles = array_diff_key($existingSystemFilesHash, $actualSystemFilesHash);

        // Получаем список файлов, которые были изменены
        $changeFiles = array_diff($actualSystemFilesHash, $existingSystemFilesHash);
        $changeFiles = array_diff($changeFiles, $newFiles);

        // Получаем строковое представление всех различий
        $changeFiles = implode('<br />', array_keys($changeFiles));
        $delFiles = implode('<br />', array_keys($delFiles));
        $newFiles = implode('<br />', array_keys($newFiles));

        print json_encode(array('newFiles' => $newFiles, 'delFiles' => $delFiles, 'changeFiles' => $changeFiles));
        exit;
    }

    /**
     * Получает массив, где ключи это путь до файла, а значения это хэш файла
     *
     * @param string $folder Путь до сканируемой папки
     * @param string $cmsFolder Путь до корневой папки системы
     * @return array Массив где ключами являются пути до файлов, а значениями их хэши
     */
    public static function getAllSystemFiles($folder, $cmsFolder)
    {
        $systemFiles = array();
        $files = scandir($folder);
        foreach ($files as $file) {
            // Отбрасываем не нужные каталоги и файлы
            if (preg_match('/^\..*?|hash_files\.php$/isU', $file)) {
                continue;
            }
            // Если директория, то запускаем сбор внутри директории
            if (is_dir($folder . '/' . $file)) {
                $systemFiles = array_merge($systemFiles, self::getAllSystemFiles($folder . '/' . $file, $cmsFolder));
            } else {
                $fileKeyArray = ltrim(str_replace($cmsFolder, '', $folder) . '/' . $file, '/');
                $systemFiles[$fileKeyArray] = hash_file('crc32b', $folder . '/' . $file);
            }
        }
        return $systemFiles;
    }
}
