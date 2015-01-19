<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2014 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Field\Addon;

use Ideal\Core\Request;

/**
 * Класс AjaxController отвечает за операции по редактированию списка подключённых аддонов
 *
 */
class AjaxController extends \Ideal\Core\AjaxController
{
    /**
     * Добавление аддона к списку
     */
    public function addAction()
    {
        $request = new Request();
        $this->model->setPageDataById($request->id);

        $addonModel = new Model();
        $field = substr($request->addonField, strlen($request->groupName) + 1);
        $addonModel->setModel($this->model, $field, $request->groupName);

        // Получаем html-код новой вкладки, её заголовок и название
        $result = $addonModel->getTab($request->newId, $request->addonName);

        // Добавляем новый аддон в json-список аддонов
        $pageData = $this->model->getPageData();
        $json = json_decode($pageData[$field]);
        $json[] = array($request->newId, $request->addonName, $result['name']);
        $result['list'] = json_encode($json);

        echo json_encode($result);
    }
}