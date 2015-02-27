<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2015 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */
namespace Ideal\Structure\Tag\Site;

use Ideal\Core\Config;

/**
 * Класс отвечающий за отображение списка тегов indexAction() и списка элементов в теге detailAction()
 */
class ControllerAbstract extends \Ideal\Core\Site\Controller
{
    /** @var Model */
    protected $model;

    public function detailAction()
    {
        $this->templateInit('Structure/Tag/Site/detail.twig');

        parent::indexAction();

        $this->view->elemTag = $this->model->getElemTag();

        $config = Config::getInstance();
        $parentUrl = $this->model->getParentUrl();
        $this->view->parentUrl = substr($parentUrl, 0, strrpos($parentUrl, '/')) . $config->urlSuffix;
    }
}
