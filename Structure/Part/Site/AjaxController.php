<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2015 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */
namespace Ideal\Structure\Part\Site;

use Ideal\Core\Config;
use Ideal\Core\Util;
use Ideal\Core\Db;
use Mail\Sender;

class AjaxController extends \Ideal\Core\AjaxController
{

    /**
     * Исполняет php файл, который был привязан к странице через аддон
     */
    public function phpFileAction()
    {
        $end = end($this->model->getPath());
        $attachedAddons = json_decode($end['addon']);
        $tabId = 0;

        // Ишем в подключенных аддонах "Ideal_PhpFile".
        // Находим первый и запоминаем в какой он вкладке
        foreach ($attachedAddons as $addon) {
            if ($addon[1] == 'Ideal_PhpFile') {
                $tabId = $addon[0];
                break;
            }
        }

        // Если нашлась вкладка с аддоном "Ideal_PhpFile", то получаем этот аддон
        if ($tabId != 0) {
            $config = Config::getInstance();

            // Получаем значение поля prev_structure для аддона
            $prevStructure = $config->getStructureByName($end['structure']);
            $prevStructureFieldValue = $prevStructure['ID'] . '-' . $end['ID'];

            // Получаем модель аддона
            $addonModelName = Util::getClassName('Ideal_PhpFile', 'Addon') . '\\Model';
            $addonModel = new $addonModelName($prevStructureFieldValue);

            // Устанавливаем правилиные данные для модели
            $addonModel->setFieldsGroup('phpfile-' . $tabId);

            // Получаем данные из аддона
            $pageData = $addonModel->getPageData();

            // Сохраняем некоторрые экземпляры классов в сессию,
            // для доступности этих классов в файле обработки запроса формы.

            // Сохраняем экземпляр класса Db
            $db = Db::getInstance();
            $_SESSION['db'] = serialize($db);

            // Сохраняем экземпляр класса Sender
            $mailSender = new Sender();
            $_SESSION['mailSender'] = serialize($mailSender);

            // Сохраняем экземпляр класса Config
            $_SESSION['config'] = serialize($config);

            require_once DOCUMENT_ROOT . $pageData['php_file'];
        }
    }
}
