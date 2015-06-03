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
use Ideal\Structure\Service\UpdateCms\Versions;

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

    /** @var bool Признак тестового режима */
    protected $testMode = false;

    /** @var array Массив папок для обновления */
    protected $updateFolders = array();

    /** @var string Название модуля */
    public $updateName = '';

    /** @var string Версия, на которую производится обновление */
    public $updateVersion = '';

    /** @var string Текущая версия */
    public $currentVersion = '';

    /**
     * Инициализация файла лога обновлений
     */
    public function __construct()
    {
        $versions = new Versions();
        // Получаем название файла лога
        $this->log = $versions->getLogName();
        // Получаем сообщения, если возникли проблемы при работе с файлом лога
        if ($this->log === false) {
            $this->answer = $this->getAnswer();
            exit;
        }
    }

    /**
     * Задаём признак тестового режима
     *
     * @param bool $testMode Признак тестового режима
     */
    public function setTestMode($testMode)
    {
        $this->testMode = $testMode;
    }

    /**
     * Задаём название и версию обновляемого модуля
     *
     * @param string $updateName    Название модуля
     * @param string $updateVersion Номер версии, на которую обновляемся
     * @param string $currentVersion Номер текущей версии
     */
    public function setUpdate($updateName, $updateVersion, $currentVersion)
    {
        // todo Сделать защиту от хакеров на POST-переменные
        $this->updateName = $updateName;
        $this->updateVersion = $updateVersion;
        $this->currentVersion = $currentVersion;
    }

    /**
     * Загрузка архива с обновлениями
     * @throws \Exception
     */
    public function downloadUpdate()
    {
        $updateUrl = $this->updateFolders['getFileScript']
            . '?name=' . urlencode(serialize($this->updateName))
            . '&cVer=' . $this->currentVersion
            . '&ver=' . $this->updateVersion;
        $info = json_decode(file_get_contents($updateUrl), true);

        // Проверка на получение данных о получаемом обновлении
        if ($info === false || !isset($info['file']) || !isset($info['md5']) ||  !isset($info['version'])) {
            $this->addAnswer(
                'Не удалось получить данные о получаемом обновлении',
                'error'
            );
            exit;
        }

        // Название файла для сохранения
        $path = $this->updateFolders['uploadDir'] . '/' . $this->updateName;

        $fp = fopen($path, 'w');

        $updateUrl = $this->updateFolders['getFileScript']
            . '?file=' . $info['file'];

        $ch = curl_init($updateUrl);
        curl_setopt($ch, CURLOPT_FILE, $fp);

        $data = curl_exec($ch);

        curl_close($ch);
        fclose($fp);


        // Проверка на получение файла
        if ($data === false) {
            $this->addAnswer(
                'Не удалось получить файл обновления с сервера обновлений ' . $this->updateFolders['getFileScript'],
                'error'
            );
            exit;
        }
        // Проверка создан ли запрошенный файл
        if (!file_exists($path)) {
            $this->addAnswer('Не удалось создать файл.', 'error');
            exit;
        }

        if (md5_file($path) != $info['md5']) {
            $this->addAnswer('Полученный файл повреждён (хеш не совпадает)', 'error');
            exit;
        }

        $this->addAnswer('Загружен архив с обновлениями', 'success');
        // Возвращаем название загруженного архива
        return array('path' => $path, 'version' => $info['version']);
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
        $res = $zip->open($archive['path']);

        if ($res !== true) {
            $this->addAnswer('Не получилось из-за ошибки #' . $res, 'error');
            exit;
        }

        // Очищаем папку перед распаковкой в неё файлов
        $this->removeDirectory(SETUP_DIR, true);

        // Распаковываем архив в папку
        $zip->extractTo(SETUP_DIR);
        $zip->close();
        unlink($archive['path']);
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

        $result = Util::chmod($updateCore, $config->cms['dirMode'], $config->cms['fileMode']);

        if (count($result) != 0) {
            // Объединяем все пути, для которых не удалось изменить права в одну строку
            $paths = array_reduce(
                $result,
                function (&$result, $item) {
                    $result = $result . "<br />\n" . $item['path'];
                }
            );
            $this->addAnswer("Не удалось изменить права для следующих файлов/папок: <br />\n{$paths}", 'warning');
        }

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
        $updateFolder = SETUP_DIR . '/setup/update';
        $lastScript = '';
        foreach ($logFile as $v) {
            if (strpos($v, $updateFolder) === 0) {
                $lastScript = str_replace($updateFolder, '', trim($v));
            }
        }

        if ($lastScript != '') {
            // Находим номер версии и название файла последнего установленного скрипта
            $scriptArr = explode('/', $lastScript);
            $scriptEnd = array_pop($scriptArr);
            $currentVersion = array_pop($scriptArr);
        } else {
            $version = new Versions();
            $versions = $version->getVersions(); // получаем список установленных модулей
            $currentVersion = $versions[$this->updateName];
        }

        // Считываем названия папок со скриптами обновления
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
                $file = '/' . $folder . '/' . $file;
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
     * Выполнение скриптов до замены файлов
     *
     * @param $scripts Список всех скриптов, используемых при обновлении
     * @return array Список скриптов, которые нужно выполнить после замены файлов
     */
    public function runOldScript($scripts)
    {
        // Получаем элементы массива не содержащие в начале строки 'new_'
        $scriptsOld = preg_grep("(\/new_\.*)", $scripts, PREG_GREP_INVERT);
        foreach ($scriptsOld as $v) {
            $this->runScript(SETUP_DIR . '/setup/update' . $v);
        }
        $scripts = array_diff_key($scripts, $scriptsOld);
        $this->addAnswer(
            'Выполнено скриптов: ' . count($scriptsOld),
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
                include $script;
                break;
            case '.sql':
                $query = file_get_contents($script);
                $db->query($query);
                break;
            default:
                continue;
        };
        if (!$this->testMode) {
            $this->writeLog($script);
        }
        $this->addAnswer('Выполнен скрипт: ' . $script, 'success');
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
