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
        $addonModel->setModel($this->model, $request->addonField, $request->groupName);

        echo '111';
    }

}
