<?php
namespace Ideal\Field\Url;

use Ideal\Field\AbstractController;

class Controller extends AbstractController
{
    protected static $instance;


    public function getInputText()
    {
        $url = new Model();
        $value = array('url' => htmlspecialchars($this->getValue()));
        $link = $url->getUrlWithPrefix($value, $this->model->getParentUrl());
        // Проверяем, является ли url этого объекта частью пути
        $addOn = '';
        if (($link{0} == '/') AND ($value != $link)) {
            // Выделяем из ссылки путь до этого объекта и выводим его перед полем input
            $path = substr($link, 0, strrpos($link, '/'));
            $addOn = '<span class="add-on">' . $path . '/</span>';
        }
        return '<div class="input-prepend">' . $addOn
             . '<input type="text" class="input span3" name="' . $this->htmlName . '" id="' . $this->htmlName
             . '" value="' . $value['url'] . '">'
             . '</div>';
    }


    public function getValueForList($values, $fieldName)
    {
        $url = new Model($fieldName);
        $link = $url->getUrlWithPrefix($values, $this->model->getParentUrl());
        if ($link == '---') {
            // Если это страница внутри главной, то просто возвращаем поле url
            $link = $values[$fieldName];
        } else {
            // Если это не страница внутри Главной, то делаем ссылку
            $link = '<a href="' . $link . '" target="_blank">' . $link . '</a>';
        }
        return $link;
    }


    public function pickupNewValue()
    {
        // В url не нужны пробелы ни спереди, ни сзади
        $value = trim(parent::pickupNewValue());
        return $value;
    }

}