<?php
namespace Ideal\Field\UrlAuto;

use Ideal\Field\Url;

class Controller extends Url\Controller
{
    protected static $instance;


    public function getInputText()
    {
        $url = new Url\Model();
        $value = array('url' => htmlspecialchars($this->getValue()));
        $link = $url->getUrlWithPrefix($value, $this->model->getParentUrl());
        $link = $url->cutSuffix($link);
        // Проверяем, является ли url этого объекта частью пути
        $addOn = '';
        if (($link{0} == '/') AND ($value != $link)) {
            // Выделяем из ссылки путь до этого объекта и выводим его перед полем input
            $path = substr($link, 0, strrpos($link, '/'));
            $addOn = '<span class="input-group-addon">' . $path . '/</span>';
        }
        return '<script type="text/javascript" src="Ideal/Field/UrlAuto/admin.js" />'
             . '<div class="input-group">' . $addOn
             . '<input type="text" class="form-control" name="' . $this->htmlName . '" id="' . $this->htmlName
             . '" value="' . $value['url'] . '"><span class="input-group-btn">'
             . '<button id="UrlAuto" type="button" class="btn btn-danger" onclick="javascript:setTranslit(this)">auto url off</button>'
             . '</span></div>';
    }
}
