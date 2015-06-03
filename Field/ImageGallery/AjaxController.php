<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2015 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Field\ImageGallery;

use Ideal\Core\Request;

/**
 * Класс AjaxController отвечает за составление списка изображений в фотогалерее
 *
 */
class AjaxController extends \Ideal\Core\AjaxController
{
    /**
     * Получение списка изображений в фотогалерее
     */

    public function getImageListAction()
    {
        $request = new Request();
        $imageGalleryModel = new Model();
        $filedlist = $imageGalleryModel->getImageList($request->urls);
        return $filedlist;
    }
}
