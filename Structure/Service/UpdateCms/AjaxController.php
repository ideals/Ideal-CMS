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
 * Обновление IdealCMS или одного модуля
 *
 */
class AjaxController extends \Ideal\Core\AjaxController
{
    /** @var string Сервер обновлений */
    protected $srv = 'http://idealcms.ru/update';

    /** @var Model  */
    protected $updateModel;

    public function __construct()
    {
        $config = Config::getInstance();
        $this->updateModel = new Model();

        $getFileScript = $this->srv . '/getNext.php';


        if (is_null($config->cms['tmpFolder']) || ($config->cms['tmpFolder'] == '')) {
            $this->updateModel->addAnswer('В настройках не указана папка для хранения временных файлов', 'error');
            exit;
        }

        // Папка для хранения загруженных файлов обновлений
        $uploadDir = DOCUMENT_ROOT . $config->cms['tmpFolder'] . '/update';
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                $this->updateModel->addAnswer('Не удалось создать папку' . $uploadDir, 'error');
                exit;
            }
        }

        // Папка для разархивации файлов новой CMS
        // Пример /www/example.com/tmp/setup/Update
        define('SETUP_DIR', $uploadDir . '/setup');
        if (!file_exists(SETUP_DIR)) {
            if (!mkdir(SETUP_DIR, 0755, true)) {
                $this->updateModel->addAnswer('Не удалось создать папку' . SETUP_DIR, 'error');
                exit;
            }
        }

        $this->updateModel->setUpdateFolders(
            array(
                'getFileScript' => $getFileScript,
                'uploadDir' => $uploadDir
            )
        );

        // Создаём сессию для хранения данных между ajax запросами
        if (session_id() == '') {
            session_start();
        }

        if (!isset($_POST['version']) || !isset($_POST['name'])) {
            $this->updateModel->addAnswer('Непонятно, что обновлять. Не указаны version и name', 'error');
            exit;
        } else {
            $this->updateModel->setUpdate($_POST['name'], $_POST['version'], $_POST['currentVersion']);
        }

        if (isset($_SESSION['update'])) {
            if ($_SESSION['update']['name'] != $this->updateModel->updateName ||
                $_SESSION['update']['version'] != $this->updateModel->updateVersion) {
                unset($_SESSION['update']);
            }
        }
        if (!isset($_SESSION['update'])) {
            $_SESSION['update'] = array(
                'name' => $this->updateModel->updateName,
                'version' => $this->updateModel->updateVersion,
            );
        }
    }

    /**
     * Загрузка архива с обновлениями
     */
    public function ajaxDownloadAction()
    {
        // Скачиваем архив с обновлениями
        $_SESSION['update']['archive'] = $this->updateModel->downloadUpdate();
        exit;
    }

    // Распаковка архива с обновлением
    public function ajaxUnpackAction()
    {
        $archive = isset($_SESSION['update']['archive']) ? $_SESSION['update']['archive'] : null;
        if (!$archive) {
            $this->updateModel->addAnswer('Неполучен путь к файлу архива', 'error');
            exit;
        }
        $this->updateModel->unpackUpdate($archive);
        exit;
    }

    /**
     * Получение скриптов, которые необходимо выполнить для перехода на новую версию
     */
    public function ajaxGetUpdateScriptAction()
    {
        // Запускаем выполнение скриптов и запросов
        $_SESSION['update']['scripts'] = $this->updateModel->getUpdateScripts();
        exit;
    }

    /**
     * Замена старого каталога на новый
     */
    public function ajaxSwapAction()
    {
        $_SESSION['update']['oldFolder'] = $this->updateModel->swapUpdate();
        exit;
    }

    /**
     * Выполнение одного скрипта из списка полученных скриптов
     */
    public function ajaxRunScriptAction()
    {
        if (!isset($_SESSION['update']['scripts'])) {
            exit;
        }
        $scripts = &$_SESSION['update']['scripts'];

        // Проверяем, есть ли скрипты, которые нужно выполнить до замены файлов админки
        if (isset($scripts['pre']) && count($scripts['pre']) > 0) {
            $scriptFile = array_shift($scripts['pre']);
        } elseif (isset($scripts['after']) && count($scripts['after']) > 0) {
            $scriptFile = array_shift($scripts['after']);
        } else {
            exit;
        }

        // Запускаем выполнение скриптов и запросов
        $displayErrors = ini_get('display_errors');
        ini_set('display_errors', 'On');
        $this->updateModel->runScript($scriptFile);
        ini_set('display_errors', $displayErrors);
        exit;
    }

    /**
     *
     */
    public function ajaxEndVersionAction()
    {
        // Записываем текущую версию в сессию
        $_SESSION['update']['currentVersion'] = $_SESSION['update']['archive']['version'];
        // Модуль установился успешно, делаем запись в лог обновлений
        $this->updateModel->writeLog(
            'Installed ' . $this->updateModel->updateName . ' v. ' . $_SESSION['update']['currentVersion']
        );

        // Получаем раздел со старой версией
        $oldFolder = isset($_SESSION['update']['oldFolder']) ? $_SESSION['update']['oldFolder'] : null;
        if (!$oldFolder) {
            $this->updateModel->addAnswer('Не удалось удалить раздел со старой версией.', 'warning');
        }
        // Удаляем старую папку
        $this->updateModel->removeDirectory($oldFolder);
        $data = null;
        if ($_SESSION['update']['archive']['version'] != $this->updateModel->updateVersion) {
            $data = array('next' => 'true', 'currentVersion' => $_SESSION['update']['currentVersion']);
        }
        $this->updateModel->addAnswer(
            'Обновление на версию ' . $_SESSION['update']['currentVersion'] . ' произведено успешно',
            'success',
            $data
        );
        exit;
    }

    /**
     * Последний этап выполнения обновления
     */
    public function ajaxFinishAction()
    {
        $this->updateModel->addAnswer('Обновление завершено успешно', 'success');
        exit;
    }

    public function __destruct()
    {
        $result = $this->updateModel->getAnswer();
        echo json_encode($result);
    }
}
