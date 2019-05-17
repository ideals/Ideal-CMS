<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\Tag\Site;

use Ideal\Core\Config;
use Ideal\Core\Request;

/**
 * Класс отвечающий за отображение списка тегов indexAction() и списка элементов в теге detailAction()
 */
class ControllerAbstract extends \Ideal\Core\Site\Controller
{
    /** @var Model */
    protected $model;

    public function indexAction()
    {
        parent::indexAction();

        // Получаем полный список тегов
        $this->view->tags = $this->model->getList();
    }

    public function detailAction()
    {
        $this->templateInit('Structure/Tag/Site/detail.twig');

        parent::indexAction();

        // Получаем полный список тегов
        $this->view->tags = $this->model->getList();

        $request = new Request();
        $page = (int)$request->page;
        $this->view->elements = $this->model->getElements($page);
        $this->view->pager = $this->model->getElementsPager('page');
    }
}
