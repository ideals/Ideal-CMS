<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Addon\Photo;

use Ideal\Addon\AbstractSiteModel;
use Ideal\Core\Config;
use Ideal\Core\View;

class SiteModel extends AbstractSiteModel
{
    public function getPageData()
    {
        $this->setPageDataByPrevStructure($this->prevStructure);

        $config = Config::getInstance();

        $tplRoot = dirname(stream_resolve_include_path('Addon/Photo/index.twig'));
        $view = new View($tplRoot, $config->cache['templateSite']);
        $view->loadTemplate('index.twig');
        $view->images = json_decode($this->pageData['images'], true);
        $view->images = $view->images ?: [];
        $view->imagesRel = $this->fieldsGroup;
        $photoContent = $view->render();
        if (isset($this->pageData['content'])) {
            $this->pageData['content'] .= $photoContent;
        } else {
            $this->pageData['content'] = $photoContent;
        }

        return $this->pageData;
    }
}
