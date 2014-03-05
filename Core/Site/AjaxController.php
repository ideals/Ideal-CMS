<?php
namespace Ideal\Core\Site;

use Ideal\Core\Request;

class AjaxController extends \Ideal\Core\Site\Controller
{
    public function run(\Ideal\Core\Site\Router $router)
    {
        $request = new Request();
        $actionName = $request->action;
        if ($actionName == '') {
            $actionName = 'index';
        }

        $actionName = $actionName . 'Action';

        $this->$actionName();

    }


    public function getHttpStatus()
    {
        return '';
    }


    public function getLastMod()
    {
        return '';
    }
}
