<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2014 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\Service\UpdateCms;

use Ideal\Core\Config;

/**
 * Получение номеров версий установленной CMS и модулей
 *
 */
class Model
{
    /** @var string Сообщение об ошибке при попытке получения номеров версий */
    protected $errorText = '';

    /**
     * @return array Массив с номерами установленных версий
     */
    public function getVersions()
    {
        $config = Config::getInstance();
        // Путь к файлу README.md для cms
        $mods['Ideal-CMS'] = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/Ideal';

        // Ищем файлы README.md в модулях
        $modDirName = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/Mods';
        // Получаем разделы
        $modDirs = array_diff(scandir($modDirName), array('.', '..')); // получаем массив папок модулей
        foreach ($modDirs as $dir) {
            // Исключаем разделы, явно не содержащие модули
            if ((stripos($dir, '.') === 0) || (is_file($modDirName . '/' . $dir))) {
                unset($mods[$dir]);
                continue;
            }
            $mods[$dir] = $modDirName . '/' . $dir;
        }
        // Получаем версии для каждого модуля и CMS из update.log
        $versions = $this->getVersionFromFile($mods);

        return $versions;
    }

    /**
     * Получение версии из файла
     *
     * @param string $mods Папки с модулями и CMS
     * @return array Версия CMS  и модулей
     */
    protected function getVersionFromFile($mods)
    {
        $config = Config::getInstance();
        // Файл лога обновлений
        $log = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/' . 'update.log';

        // Проверяем файл update.log
        if (file_put_contents($log, '', FILE_APPEND) === false) {
            if (file_exists($log)) {
                $this->errorText = 'Файл ' . $log . ' недоступен для записи';
            } else {
                $this->errorText = 'Не удалось создать файл ' . $log;
            }
            return false;
        };

        // Получаем версии
        if (filesize($log) == 0) {
            $version = $this->getVersionFromReadme($mods);
            $this->putVersionLog($version, $log);
        } else {
            $version = $this->getVersionFromLog($log);
        }
        return $version;
    }

    /**
     * Получение версий из файла update.log
     *
     * @param string $log Файл с логом обновлений
     * @return array Версии модулей и обновлений
     */
    protected function getVersionFromLog($log)
    {
        $linesLog = file($log);
        $version = array();
        for ($i = count($linesLog) - 1; $i>=0; $i--) {
            // Удаление спец символов конца строки (необходимость в таком удалении возникает в ОС Windows)
            $linesLog[$i] = rtrim($linesLog[$i]);
            if ($linesLog[$i] != '[updateInfo]') {
                continue;
            }
            $buf['name'] = explode('=', $linesLog[$i + 1]);
            if (isset($version[$buf['name']['1']])) {
                continue;
            }
            $buf['ver'] = explode('=', $linesLog[$i + 2]);
            $version[$buf['name'][1]] = $buf['ver']['1'];
        }

        return $version;
    }

    /**
     * Запись версий в update.log
     *
     * @param array $version Версии полученные из Readme.ms
     * @param string $log Файл с логом обновлений
     */
    protected function putVersionLog($version, $log)
    {
        $lines = array();
        foreach ($version as $k => $v) {
            $lines[] = "[updateInfo]";
            $lines[] = "name={$k}";
            $lines[] = "version={$v}";
        }
        file_put_contents($log, implode("\r\n", $lines));
    }

    /**
     * Получение версий из Readme.md
     *
     * @param array $mods Массив состоящий из названий модулей и полных путей к ним
     * @return array Версии модулей или false в случае ошибки
     */
    protected function getVersionFromReadme($mods)
    {
        // Получаем файл README.md для cms
        $mdFile = 'README.md';
        $version = array();
        foreach ($mods as $k => $v) {
            $lines = file($v . '/' . $mdFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (($lines == false) || (count($lines) == 0)) {
                $this->errorText = 'Не удалось получить версию из ' . $v . '/' . $mdFile;
                return false;
            }
            // Получаем номер версии из первой строки
            // Формат номера: пробел+v.+пробел+номер-версии+пробел-или-конец-строки
            preg_match_all('/\sv\.(\s*)(.*)(\s*)/i', $lines[0], $ver);
            // Если номер версии не удалось определить — выходим
            if (!isset($ver[2][0]) || ($ver[2][0] == '')) {
                $this->errorText = 'Ошибка при разборе строки с версией файла';
                return false;
            }

            $version[$k] = $ver[2][0];
        }
        return $version;
    }

    /**
     * @return string
     */
    public function getErrorText()
    {
        return $this->errorText;
    }
}
