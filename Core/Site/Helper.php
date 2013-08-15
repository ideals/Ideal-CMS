<?php
namespace Ideal\Core\Site;

use Ideal\Structure\Part\Widget\MainMenu;

class Helper
{
    public $xhtml = false;

    public function getVariables($model)
    {
        $mainMenu = new MainMenu($model);
        $vars['mainMenu'] = $mainMenu->getData();
        return $vars;
    }

}
