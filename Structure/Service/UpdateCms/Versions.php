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
use Ideal\Core\Util;

/**
 * Класс для работы с версиями Ideal CMS
 *
 * Позволяет получать версии с сервера обновлений, а также извлекает установленные версии
 * из файла обновлений и из файла README.md установленной версии Ideal CMS
 */
class Versions
{
    /** @var array Ответ, возвращаемый при ajax-вызове */
    protected $answer = array('message' => array(), 'error' => false, 'data' => null);

    /** @var string Путь к файлу с логом обновлений */
    protected $log = '';

    /**
     * Инициализация файла лога обновлений
     */
    public function __construct()
    {
        $config = Config::getInstance();
        // Файл лога обновлений
        $log = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/' . 'update.log';
        // Проверяем существует ли файл лога
        $fileNotExists = false;
        if (!file_exists($log)) {
            $this->addAnswer('Файл лога обновлений не существует ' . $log, 'info');
            $fileNotExists = true;
        }
        $path =  realpath($log);
        if (!is_writable($path)) {
            // Если файл лога не существует и создать его не удалось
            if ($fileNotExists) {
                $this->addAnswer('Не удалось создать файл лога ' . $log, 'error');
                $this->log = false;
                return false;
            } else {
                Util::chmod($log, $config->cms['dirMode'], $config->cms['fileMode']);
                $this->addAnswer('Файл лога обновлений создан ', 'info');
            }
            $this->addAnswer('Файл ' . $log . ' недоступен для записи', 'error');
            $this->log = false;
            return false;
        }
        if (file_put_contents($log, '', FILE_APPEND) == false) {
            $this->addAnswer('Не удалось записать данные в файл ' . $log, 'error');
        }
        $this->log = $log;
    }

    /**
     * Получение версии админки, а также наименований модулей и их версий
     *
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
        // Файл лога обновлений
        $log = $this->log;

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
                $this->addAnswer('Не удалось получить версию из ' . $v . '/' . $mdFile, 'error');
                return false;
            }
            // Получаем номер версии из первой строки
            // Формат номера: пробел+v.+пробел+номер-версии+пробел-или-конец-строки
            preg_match_all('/\sv\.(\s*)(.*)(\s*)/i', $lines[0], $ver);
            // Если номер версии не удалось определить — выходим
            if (!isset($ver[2][0]) || ($ver[2][0] == '')) {
                $this->addAnswer('Ошибка при разборе строки с версией файла', 'error');
                return false;
            }

            $version[$k] = $ver[2][0];
        }
        return $version;
    }

    /**
     * Запись версий в update.log
     *
     * @param array  $version Версии полученные из Readme.ms
     * @param string $log     Файл с логом обновлений
     */
    protected function putVersionLog($version, $log)
    {
        $lines = array();
        foreach ($version as $k => $v) {
            $lines[] = 'Installed ' . $k . ' v.' . $v;
        }
        file_put_contents($log, implode("\n", $lines) . "\n");
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
        $versions = array();

        foreach ($linesLog as $v) {
            // Удаление спец символов конца строки (если пролез символ \r)
            $v = rtrim($v);
            if (strpos($v, 'Installed ') === 0) {
                // Строка содержит сведения об установленном модуле
                $v = substr($v, 10);
                $name = substr($v, 0, strpos($v, ' '));
                // Формат номера: пробел+v.+пробел+номер-версии+пробел-или-конец-строки
                preg_match_all('/\sv\.(\s*)(.*)(\s*)/i', $v, $ver);
                // Если номер версии не удалось определить — выходим
                if (!isset($ver[2][0]) || ($ver[2][0] == '')) {
                    $this->addAnswer('Ошибка при разборе строки с версией файла', 'error');
                    return false;
                }

                $versions[$name] = $ver[2][0];
            }
        }

        return $versions;
    }

    /**
     * Добавление сообщения, возвращаемого в ответ на ajax запрос
     *
     * @param array $message Сообщения возвращаемые в ответ на ajax запрос
     * @param string $type Статус сообщения, характеризующий так же наличие ошибки
     * @param mixed $data Данные передаваемые в ответ на ajax запрос
     * @throws \Exception
     */
    public function addAnswer($message, $type, $data = null)
    {
        if (!is_string($message) || !is_string($type)) {
            throw new \Exception("Необходим аргумент типа строка");
        }
        if (!in_array($type, array('error', 'info', 'warning', 'success'))) {
            throw new \Exception("Недопустимое значение типа сообщения");
        }
        $this->answer['message'][] = array($message, $type);
        if ($type == 'error') {
            $this->answer['error'] = true;
        }
        if ($data != null) {
            $this->answer['data'] = $data;
        }
    }

    /**
     * Получение результирующих данных
     *
     * @return array
     */
    public function getAnswer()
    {
        return $this->answer;
    }

    /**
     * Получение пути к файлу с логом обновлений
     *
     * @return string Путь к файлу с логом обновлений
     */
    public function getLogName()
    {
        return $this->log;
    }
}
