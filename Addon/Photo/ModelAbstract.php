<?php
namespace Ideal\Addon\Photo;

use Ideal\Core\Config;
use Ideal\Core\View;

class ModelAbstract extends \Ideal\Addon\AbstractModel
{
    public function getPageData()
    {
        $this->setPageDataByPrevStructure($this->prevStructure);

        $mode = explode('\\', get_class($this->parentModel));
        if ($mode[3] == 'Site') {

            $config = Config::getInstance();

            $tplRoot = dirname(stream_resolve_include_path('Addon/Photo/index.twig'));
            $View = new View($tplRoot, $config->cache['templateSite']);
            $View->loadTemplate('index.twig');
            $View->images = json_decode($this->pageData['images']);
            $View->imagesRel = $this->fieldsGroup;
            $photoContent = $View->render();
            if (isset($this->pageData['content'])) {
                $this->pageData['content'] .= $photoContent;
            } else {
                $this->pageData['content'] = $photoContent;
            }
        }
        return $this->pageData;
    }
}
