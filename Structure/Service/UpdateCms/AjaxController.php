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

        // Файл лога обновлений
        $log = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/' . 'update.log';

        if (!file_exists($log)) {
            $this->updateModel->uExit('Файл лога обновлений не существует ' . $log);
        }

        if (file_put_contents($log, '', FILE_APPEND) === false) {
            $this->updateModel->uExit('Файл ' . $log . ' недоступен для записи');
        }

        if (is_null($config->cms['tmpFolder']) || ($config->cms['tmpFolder'] == '')) {
            $this->updateModel->uExit('В настройках не указана папка для хранения временных файлов');
        }

        // Папка для хранения загруженных файлов обновлений
        $uploadDir = DOCUMENT_ROOT . $config->cms['tmpFolder'] . '/update';
        if (!file_exists($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                $this->updateModel->uExit('Не удалось создать папку' . $uploadDir);
            }
        }

        // Папка для разархивации файлов новой CMS
        // Пример /www/example.com/tmp/setup/Update
        define('SETUP_DIR', $uploadDir . '/setup');
        if (!file_exists(SETUP_DIR)) {
            if (!mkdir(SETUP_DIR, 0755, true)) {
                $this->updateModel->uExit('Не удалось создать папку' . SETUP_DIR);
            }
        }

        $this->updateModel->setUpdateFolders(
            array(
                'getFileScript' => $getFileScript,
                'uploadDir' => $uploadDir
            )
        );

        // todo Сделать защиту от хакеров на POST-переменные
        if (!isset($_POST['version']) || !isset($_POST['name'])) {
            $this->updateModel->uExit('Непонятно, что обновлять. Не указаны version и name');
        }
    }

    public function ajaxDownloadAction()
    {
        // Скачиваем и распаковываем архив с обновлениями
        $this->updateModel->downloadUpdate($_POST['name'], $_POST['version']);

        // todo разбить по отдельным вызовам
        $this->ajaxUpdateAction();
        $this->ajaxFinishAction();
    }

    public function ajaxUnpackAction()
    {

    }

    public function ajaxSwapAction()
    {

    }

    public function ajaxGetUpdateAction()
    {

    }

    public function ajaxRunAction()
    {
        // Запускаем выполнение скриптов и запросов
        $this->updateModel->updateScripts($_POST['name'], $_POST['version']);
    }

    public function ajaxFinishAction()
    {
        // Модуль установился успешно, делаем запись в лог обновлений
        $this->updateModel->writeLog('Installed ' . $_POST['name'] . ' v. ' . $_POST['version']);

        // Определяем путь к тому что мы обновляем, cms или модули
        $config = Config::getInstance();
        if ($_POST['name'] == "Ideal-CMS") {
            // Путь к cms
            $updateCore = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/' . "Ideal";
        } else {
            // Путь к модулям
            $updateCore = DOCUMENT_ROOT . '/' . $config->cmsFolder . '/' . "Mods" . '/' . $_POST['name'];
        }

        // Удаляем старую папку
        $this->updateModel->removeDirectory($updateCore . '_old');

        $this->updateModel->uExit('Обновление завершено успешно');
    }
}
