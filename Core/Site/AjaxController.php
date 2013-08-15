<?php
namespace Ideal\Core\Site;

use Ideal\Core\Request;

class AjaxController extends \Ideal\Core\Controller
{
    public function run($router)
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
