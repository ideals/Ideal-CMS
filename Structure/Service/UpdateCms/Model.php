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
use Ideal\Core\Util;

/**
 * Получение номеров версий установленной CMS и модулей
 *
 */
class Model
{

    /** @var array Ответ, возвращаемый при ajax-вызове */
    protected $answer = array('message' => array(), 'error' => false, 'data' => null);

    /** @var string Путь к файлу с логом обновлений */
    protected $log = '';

    /** @var array Массив папок для обновления */
    protected $updateFolders = array();

    /** @var string Название модуля */
    public $updateName = '';

    /** @var string Версия, на которую производится обновление */
    public $updateVersion = '';

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
        // Проверяем доступность файла лога на запись
        if (file_put_contents($log, '', FILE_APPEND) === false) {
            // Если файл лога не существует и создать его не удалось
            if ($fileNotExists) {
                $this->addAnswer('Не удалось создать файл лога ' . $log, 'error');
                exit;
            } else {
                $this->addAnswer('Файл лога обновлений создан ', 'info');
            }
            $this->addAnswer('Файл ' . $log . ' недоступен для записи', 'error');
            exit;
        }
        $this->log = $log;
    }

    /**
     * Задаём название и версию обновляемого модуля
     *
     * @param string $updateName    Название модуля
     * @param string $updateVersion Номер версии, на которую обновляемся
     */
    public function setUpdate($updateName, $updateVersion)
    {
        // todo Сделать защиту от хакеров на POST-переменные
        $this->updateName = $updateName;
        $this->updateVersion = $updateVersion;
    }

    /**
     * Загрузка архива с обновлениями
     * @throws \Exception
     */
    public function downloadUpdate()
    {
        $updateUrl = $this->updateFolders['getFileScript']
            . '?name=' . urlencode(serialize($this->updateName))
            . '&ver=' . $this->updateVersion;
        $file = file_get_contents($updateUrl);

        // Проверка получен ли ответ от сервера
        if (strlen($file) === 0) {
            $this->addAnswer('Не удалось получить файл обновления с сервера обновлений', 'error');
            exit;
        }

        // Если вместо файла найдено сообщение, выводим его
        $prefix = substr($file, 0, 5);
        if ($prefix === "(msg)") {
            $msg = substr($file, 5, strlen($file));
            $msg = json_decode($msg);
            if (!isset($msg->message)) {
                $this->addAnswer('Получен непонятный ответ: ' . $file, 'error');
            }
            $this->addAnswer($msg, 'warning');
            exit;
        }

        // Если получили md5
        if ($prefix !== "(md5)") {
            $this->addAnswer("Ответ от сервера некорректен:\n" . $file, 'error');
            exit;
        }

        $fileGet = array(
            'md5' => substr($file, 5, strpos($file, 'md5end') - 5),
            'file' => substr($file, strpos($file, 'md5end') + 6)
        );

        if (!isset($fileGet['md5'])) {
            $this->addAnswer('Не удалось получить хеш получаемого файла', 'error');
            exit;
        }

        // Сохраняем полученный архив в свою папку (например, /www/example.com/tmp/update)
        $archive = $this->updateFolders['uploadDir'] . '/' . $this->updateName;
        file_put_contents($archive, $fileGet['file']);

        if (md5_file($archive) != $fileGet['md5']) {
            $this->addAnswer('Полученный файл повреждён (хеш не совпадает)', 'error');
            exit;
        }

        $this->addAnswer('Загружен архив с обновлениями', 'success');
        // Возвращаем название загруженного архива
        return($archive);
    }

    /**
     * Распаковка архива
     *
     * @param string $archive Полный путь к файлу архива с новой версии
     * @return bool
     * @throws \Exception
     */
    public function unpackUpdate($archive)
    {
        $zip = new \ZipArchive;
        $res = $zip->open($archive);

        if ($res !== true) {
            $this->addAnswer('Не получилось из-за ошибки #' . $res, 'error');
            exit;
        }

        // Очищаем папку перед распаковкой в неё файлов
        $this->removeDirectory(SETUP_DIR, true);

        // Распаковываем архив в папку
        $zip->extractTo(SETUP_DIR);
        $zip->close();
        unlink($archive);
        $this->addAnswer('Распакован архив с обновлениями', 'success');
    }

    /**
     * Замена каталога со старой версией на каталог с новой версией
     *
     * @return string Путь к старому разделу
     * @throws \Exception
     */
    public function swapUpdate()
    {
        // Определяем путь к тому что мы обновляем, cms или модули
        $config = Config::getInstance();
        if ($this->updateName == "Ideal-CMS") {
            // Путь к cms
            $updateCore = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/' . "Ideal";
        } else {
            // Путь к модулям
            $updateCore = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/' . "Mods" . '/' . $this->updateName;
        }
        // Переименовываем папку, которую собираемся заменить
        if (!rename($updateCore, $updateCore . '_old')) {
            $this->addAnswer('Не удалось переименовать папку ' . $updateCore, 'error');
            exit;
        }
        // Перемещаем новую папку на место старой
        if (!rename(SETUP_DIR, $updateCore)) {
            $this->addAnswer('Не удалось переименовать папку ' . $updateCore, 'error');
            exit;
        }

        $util = new Util();
        $result = $util->chmod($updateCore, $config->cms['dirMode'], $config->cms['fileMode']);

        $this->addAnswer('Заменены файлы', 'success');
        return $updateCore . '_old';
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
     * Удаление папки или её очистка
     *
     * @param string $dir   Папка которую необходимо удалить или очистить
     * @param bool   $clear Если значение ложь, то удаляем папку, если истина, очищаем
     * @return bool
     */
    public function removeDirectory($dir, $clear = false)
    {
        $res = true;
        if (!file_exists($dir)) {
            // Если папки нет, то и удалять её не надо, а если требовалось очистить - возвращаем ошибку
            return !$clear;
        }
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
     * Получение списка скриптов
     *
     * @return array
     */
    public function getUpdateScripts()
    {
        // Находим путь к последнему установленному скрипту модуля
        $logFile = file($this->log);
        $str = ($this->updateName == 'Ideal-CMS') ?
            '/Ideal/setup/update' : '/Mods/' . $this->updateName . '/setup/update';
        $lastScript = '';
        foreach ($logFile as $v) {
            if (strpos($v, $str) === 0) {
                $lastScript = trim($v);
            }
        }

        if ($lastScript != '') {
            // Находим номер версии и название файла последнего установленного скрипта
            $scriptArr = explode('/', $lastScript);
            $scriptEnd = array_pop($scriptArr);
            $currentVersion = array_pop($scriptArr);
        } else {
            $versions = $this->getVersions(); // получаем список установленных модулей
            $currentVersion = $versions[$this->updateName];
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
        usort(
            $updates,
            function ($a, $b) {
                return version_compare($a, $b);
            }
        );

        // Убираем из списка папки с установленными обновлениями
        foreach ($updates as $k => $v) {
            if (version_compare($v, $currentVersion) < 0) {
                unset($updates[$k]);
            }
        }

        // Составление списка скриптов для обновления
        $scripts = array();
        foreach ($updates as $folder) {
            $scriptFolder = $updateFolder . '/' . $folder;
            $files = array_diff(scandir($scriptFolder), array('.', '..'));
            foreach ($files as $file) {
                $file = $str . '/' . $folder . '/' . $file;
                if (is_dir($scriptFolder . '/' . $file)) {
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

        $this->addAnswer(
            'Получен список скриптов в количестве: ' . count($scripts),
            'success',
            array('count' =>count($scripts))
        );

        return $scripts;
    }

    /**
     * Запуск скрипта обновления
     *
     * @param string $script
     */
    public function runScript($script)
    {
        // Производим запуск скриптов обновления
        $db = Db::getInstance();
        $config = Config::getInstance();
        $ext = substr($script, strrpos($script, '.'));
        switch ($ext) {
            case '.php':
                include DOCUMENT_ROOT . '/' . $config->cmsFolder . $script;
                break;
            case '.sql':
                $query = file_get_contents(DOCUMENT_ROOT . '/' . $config->cmsFolder . $script);
                $db->query($query);
                break;
            default:
                continue;
        };
        $this->writeLog($script);
        $this->addAnswer('Выполнен скрипт: ' . $script, 'success');
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

        // Проверяем файл update.log
        if (file_put_contents($log, '', FILE_APPEND) === false) {
            if (file_exists($log)) {
                $this->addAnswer('Файл ' . $log . ' недоступен для записи', 'error');
            } else {
                $this->addAnswer('Не удалось создать файл ' . $log, 'error');
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
     * Запись строки в log-файл
     *
     * @param string $msg Строка для записи в log
     */
    public function writeLog($msg)
    {
        $msg = rtrim($msg) . "\n";
        file_put_contents($this->log, $msg, FILE_APPEND);
    }
}
