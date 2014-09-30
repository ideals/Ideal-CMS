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

        $getFileScript = $this->srv . '/get.php';


        if (is_null($config->cms['tmpFolder']) || ($config->cms['tmpFolder'] == '')) {
            $this->updateModel->uExit('В настройках не указана папка для хранения временных файлов', true);
        }

        // Папка для хранения загруженных файлов обновлений
        $uploadDir = DOCUMENT_ROOT . $config->cms['tmpFolder'] . '/update';
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                $this->updateModel->uExit('Не удалось создать папку' . $uploadDir, true);
            }
        }

        // Папка для разархивации файлов новой CMS
        // Пример /www/example.com/tmp/setup/Update
        define('SETUP_DIR', $uploadDir . '/setup');
        if (!file_exists(SETUP_DIR)) {
            if (!mkdir(SETUP_DIR, 0755, true)) {
                $this->updateModel->uExit('Не удалось создать папку' . SETUP_DIR, true);
            }
        }

        $this->updateModel->setUpdateFolders(
            array(
                'getFileScript' => $getFileScript,
                'uploadDir' => $uploadDir
            )
        );

        if (!isset($_POST['version']) || !isset($_POST['name'])) {
            $this->updateModel->uExit('Непонятно, что обновлять. Не указаны version и name', true);
        } else {

            $this->updateModel->setUpdate($_POST['name'], $_POST['version']);
        }

        // Создаём сессию для хранения данных между ajax запросами
        session_start();
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

    public function ajaxDownloadAction()
    {
        // Скачиваем архив с обновлениями
        $_SESSION['update']['archive'] = $this->updateModel->downloadUpdate();
        exit(json_encode(true));
    }

    public function ajaxUnpackAction()
    {
        $archive = isset($_SESSION['update']['archive']) ? $_SESSION['update']['archive'] : null;
        if (!$archive) {
            $this->updateModel->uExit('Неполучен путь к файлу архива', true);
        }
        $result = $this->updateModel->unpackUpdate($archive);
        exit(json_encode($result));
    }

    public function ajaxSwapAction()
    {
        $_SESSION['oldFolder'] = $this->updateModel->swapUpdate();
        exit(json_encode(true));
    }

    public function ajaxGetUpdateScriptAction()
    {
        // Запускаем выполнение скриптов и запросов
        $_SESSION['scripts'] = $this->updateModel->getUpdateScripts();
        $this->updateModel->uExit(null, false, array('count' =>count($_SESSION['scripts'])));
    }

    public function ajaxRunScriptAction()
    {
        if (!isset($_SESSION['scripts'])) {
            exit(json_encode(true));
        }
        // Получаем скрипт, выполняемый в текущем ajax запросе
        $script = array_shift($_SESSION['scripts']);
        // Если все скрипты были выполнены ранее, возвращаем false
        if (!$script) {
            exit(json_encode(true));
        }
        // Запускаем выполнение скриптов и запросов
        $this->updateModel->runScript($script);
        $this->updateModel->uExit('Выполнен скрипт: ' . $script, false);
        exit(json_encode(true));
    }

    public function ajaxFinishAction($oldFolder)
    {
        // Модуль установился успешно, делаем запись в лог обновлений
        $this->updateModel->writeLog(
            'Installed ' . $this->updateModel->updateName . ' v. ' . $this->updateModel->updateVersion
        );

        // Получаем раздел со старой версией
        $oldFolder = isset($_SESSION['update']['oldFolder']) ? $_SESSION['update']['oldFolder'] : null;
        $oldFolderError = '';
        if (!$oldFolder) {
            $oldFolderError = ' Не удалось удалить раздел со старой версией.';
        }
        // Удаляем старую папку
        $this->updateModel->removeDirectory($oldFolder);

        $this->updateModel->uExit('Обновление завершено успешно' . $oldFolderError, false);
    }
}
