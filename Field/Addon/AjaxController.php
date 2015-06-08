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

        if ($request->id == 0) {
            // Если аддон подключается к ещё несозданному элементу, то данные модели из БД взять не получится
            $this->model->setPageData(array());
        } else {
            $this->model->setPageDataById($request->id);
        }

        $addonModel = new Model();
        $field = substr($request->addonField, strlen($request->groupName) + 1);
        $addonModel->setModel($this->model, $field, $request->groupName);

        // Получаем html-код новой вкладки, её заголовок и название
        $result = $addonModel->getTab($request->newId, $request->addonName);

        // Возвращаем информацию только о новом подключенном аддоне
        $json = array();
        $json[] = array($request->newId, $request->addonName, $result['name']);
        $result['list'] = json_encode($json);

        return json_encode($result);
    }
}
