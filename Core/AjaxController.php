<?php
namespace Ideal\Core\Site;

use Ideal\Core\Request;

class AjaxController extends \Ideal\Core\Site\Controller
{
    public function getHttpStatus()
    {
        return '';
    }

    public function getLastMod()
    {
        return '';
    }

    public function run(Router $router)
    {
        $request = new Request();
        $actionName = $request->action;
        if ($actionName == '') {
            $actionName = 'index';
        }

        $actionName = $actionName . 'Action';

        $this->$actionName();
    }
}
