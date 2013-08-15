<?php
namespace Ideal\Field\Image;

use Ideal\Field\AbstractController;

class Controller extends AbstractController
{
    protected static $instance;


    public function getInputText()
    {
        $value = htmlspecialchars($this->getValue());
        // TODO сделать возможность посмотреть картинку по щелчку на ссылке (не закрывая окна)
        $this->widthEditField = 'span4';
        return '<div class="input-append">'
            . '<input type="text" class="' . $this->widthEditField
            . '" name="' . $this->htmlName
            . '" id="' . $this->htmlName
            . '" value="' . $value .'"> '
            . '<button class="btn" onclick="showFinder(\'' . $this->htmlName . '\'); return false;" >Выбрать</button>'
            . '</div>';
    }

}