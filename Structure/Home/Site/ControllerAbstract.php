<?php
namespace Ideal\Structure\Home\Site;

use Ideal\Structure\Part;

class ControllerAbstract extends Part\Site\Controller
{

    public function error404Action()
    {
        $this->templateInit('Structure/Home/Site/404.twig');
        $pageData = $this->model->getPageData();
        $pageData['title'] = 'Страница не найдена';
        $this->model->setPageData($pageData);
    }


}