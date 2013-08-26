<?php
namespace Ideal\Core\Site;

use Ideal\Structure\Part\Widget\MainMenu;
use Ideal\Core\Config;

class Helper
{
    public $xhtml = false;

    public function getVariables($model)
    {
        // Получаем данные из виджета главного меню
        $mainMenu = new MainMenu($model);
        $vars['mainMenu'] = $mainMenu->getData();

        // Получаем телефон из конфига site_data.php
        $config = Config::getInstance();
        $vars['phone'] = $config->phone;

        return $vars;
    }

}
