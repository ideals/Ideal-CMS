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
use Ideal\Core\Db;

/**
 * Получение номеров версий установленной CMS и модулей
 *
 */
class Model
{
    /** @var string Сообщение об ошибке при попытке получения номеров версий */
    protected $errorText = '';
    /** @var array Массив папок для обновления */
    protected $updateFolders = array();
    /** @var string Путь к файлу с логом обновлений */
    protected $log = '';

    /**
     * Инициализация путей к нужным папкам и файлам
     */
    public function __construct()
    {
        $config = Config::getInstance();
        $this->log = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/' . 'update.log';
    }

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
        // Файл лога обновлений
        $log = $this->log;

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
                    $this->errorText = 'Ошибка при разборе строки с версией файла';
                    return false;
                }

                $versions[$name] = $ver[2][0];
            }
        }

        return $versions;
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
        file_put_contents($log, implode("\n", $lines));
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

    /**
     * Завершение выполнения скрипта с выводом сообщения
     *
     * @param string $msg Сообщение которое нужно передать в качестве результата работы скрипта
     * @throws \Exception если аргумент функции не является строкой
     */
    public function uExit($msg)
    {
        if (!is_string($msg)) {
            throw new \Exception("Необходим аргумент типа строка");
        }
        $message = array(
            'message' => $msg
        );
        exit(json_encode($message));
    }

    /**
     * Удаление папки или её очистка
     *
     * @param string $dir   Папка которую необходимо удалить или очистить
     * @param bool   $clear Если значение ложь, то удаляем папку, если истина, очищаем
     * @return bool
     */
    public function removeDirectory($dir, $clear = false)
    {
        $res = true;
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            $res = (is_dir("$dir/$file")) ? $this->removeDirectory("$dir/$file") : unlink("$dir/$file");
        }
        if (!$clear) {
            return rmdir($dir);
        }
        return $res;
    }

    /**
     * Загрузка и распаковка архива с обновлением модуля
     *
     * @param string $updateName Название модуля
     * @param string $updateVersion Номер версии, на которую обновляемся
     * @throws \Exception
     */
    public function downloadUpdate($updateName, $updateVersion)
    {
        $updateUrl = $this->updateFolders['getFileScript'] . '?name=' . urlencode(serialize($updateName))
            . '&ver=' . $updateVersion;
        $file = file_get_contents($updateUrl);

        // Проверка получен ли ответ от сервера
        if (strlen($file) === 0) {
            $this->uExit('Не удалось получить файл обновления с сервера обновлений');
        }

        // Если вместо файла найдено сообщение, выводим его
        $prefix = substr($file, 0, 5);
        if ($prefix === "(msg)") {
            $msg = substr($file, 5, strlen($file));
            $msg = json_decode($msg);
            if (!isset($msg->message)) {
                $msg = array(
                    'message' => "Получен непонятный ответ: " . $file
                );
            }
            exit(json_encode($msg));
        }

        // Если получили md5
        if ($prefix !== "(md5)") {
            $this->uExit("Ответ от сервера некорректен:\n" . $file);
        }

        $fileGet = array(
            'md5'  => substr($file, 5, strpos($file, 'md5end') - 5),
            'file' => substr($file, strpos($file, 'md5end') + 6)
        );

        if (!isset($fileGet['md5'])) {
            $this->uExit('Не удалось получить хеш получаемого файла');
        }

        // Сохраняем полученный архив в свою папку (например, /www/example.com/tmp/update)
        $archive = $this->updateFolders['uploadDir'] . $updateName;
        file_put_contents($archive, $fileGet['file']);

        if (md5_file($archive) != $fileGet['md5']) {
            $this->uExit('Полученный файл повреждён (хеш не совпадает)');
        }

        // После успешной загрузки архива, распаковываем его
        $zip = new \ZipArchive;
        $res = $zip->open($archive);

        if ($res !== true) {
            $this->uExit('Не получилось из-за ошибки #' . $res);
        }

        // Очищаем папку перед распаковкой в неё файлов
        $this->removeDirectory(SETUP_DIR, true);

        // Распаковываем архив в папку
        $zip->extractTo(SETUP_DIR);
        $zip->close();
        unlink($archive);

        // Если разархивирование произошло успешно

        // Определяем путь к тому что мы обновляем, cms или модули
        $config = Config::getInstance();
        if ($updateName  == "Ideal-CMS") {
            // Путь к cms
            $updateCore = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/' . "Ideal";
        } else {
            // Путь к модулям
            $updateCore = DOCUMENT_ROOT . '/' . $config->cmsFolder. '/' . "Mods". '/' . $updateName;
        }

        // Переименовывем папку, которую собираемся заменить
        if (!rename($updateCore, $updateCore . '_old')) {
            $this->uExit('Не удалось переименовать папку ' . $updateCore);
        }

        // Перемещаем новую папку на место старой
        if (!rename(SETUP_DIR, $updateCore)) {
            $this->uExit('Не удалось переименовать папку ' . $updateCore);
        }
    }

    /**
     * Запись строки в log-файл
     * @param string $msg Строка для записи в log
     */
    public function writeLog($msg)
    {
        $msg = rtrim($msg) . "\n";
        file_put_contents($this->log, $msg, FILE_APPEND);
    }

    /**
     * Установка путей к папкам для обновления модулей
     *
     * @param $array
     */
    public function setUpdateFolders($array)
    {
        $this->updateFolders = array(
            'getFileScript' => $array['getFileScript'],
            'uploadDir' => $array['uploadDir']
        );
    }

    /**
     * Запуск скриптов обновления модуля
     *
     * @param string $updateName Название модуля
     * @param string $updateVersion Номер версии, на которую обновляемся
     */
    public function updateScripts($updateName, $updateVersion)
    {
        // Находим путь к последнему установленному скрипту модуля
        $logFile = file($this->log);
        $str = ($updateName == 'Ideal-CMS') ? '/Ideal/Setup/Update' : '/Mods/' . $updateName . '/Setup/Update';
        $lastScript = '';
        foreach ($logFile as $v) {
            if (strpos($v, $str) === 0) {
                $lastScript = $v;
            }
        }

        if ($lastScript != '') {
            // Находим номер версии и название файла последнего установленного скрипта
            $scriptArr = explode('/', $lastScript);
            $scriptEnd = array_pop($scriptArr);
            $currentVersion = array_pop($scriptArr);
        } else {
            $versions = $this->getVersions(); // получаем список установленных модулей
            $currentVersion = $versions[$updateName];
        }

        // Считываем названия папок со скриптами обновления
        $config = Config::getInstance();
        $updateFolder = DOCUMENT_ROOT . '/' . $config->cmsFolder . $str;
        $updates = array_diff(scandir($updateFolder), array('.', '..'));

        // Убираем из списка файлы
        foreach ($updates as $k => $v) {
            if (!is_dir($updateFolder . '/' . $v)) {
                unset($updates[$k]);
            }
        }

        // Сортируем папки по номерам версий
        usort($updates, function ($a, $b) {
            return version_compare($a, $b);
        });

        // Убираем из списка папки с установленными обновлениями
        foreach ($updates as $k => $v) {
            if (version_compare($v, $currentVersion) < 0) {
                unset($updates[$k]);
            }
        }

        // Составление списка скриптов для обновления
        $scripts = array();
        foreach ($updates as $folder) {
            $scriptFolder = $updateFolder . $folder;
            $files = array_diff(scandir($scriptFolder), array('.', '..'));
            foreach ($files as $file) {
                $file = $scriptFolder . '/' . $file;
                if (is_dir($file)) {
                    continue;
                }
                if ($lastScript == $file) {
                    // Нашли последний установленный скрипт, значит отсекаем все предыдущие скрипты
                    $scripts = array();
                    continue;
                }
                $scripts[] = $file;
            }
        }

        // Производим запуск скриптов обновления
        $db = Db::getInstance();
        foreach ($scripts as $script) {
            $ext = substr($script, strrpos($script, '.'));
            switch ($ext) {
                case '.php':
                    include $script;
                    break;
                case '.sql':
                    $query = file_get_contents($script);
                    $db->query($query);
                    break;
                default:
                    continue;
            };
            $this->writeLog($script);
        }
    }

}
