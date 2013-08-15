<?php
namespace Ideal\Structure\Home\Site;

use Ideal\Structure\Part;

class ControllerAbstract extends Part\Site\Controller
{

    public function indexAction()
    {
        $this->templateInit();

        $header = '';
        $templatesVars = $this->model->getTemplatesVars();

        if (isset($templatesVars['template']['content'])) {
            list($header, $text) = $this->model->extractHeader($templatesVars['template']['content']);
            $templatesVars['template']['content'] = $text;
        }

        foreach($templatesVars as $k => $v) {
            $this->view->$k = $v;
        }

        $this->view->header = $this->model->getHeader($header);

    }


    public function error404Action()
    {
        $this->templateInit('Structure/Home/Site/404.twig');
        $this->model->object['title'] = 'Страница не найдена';
    }


}