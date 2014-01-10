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
        return '<div class="input-group">'
            . '<input type="text" class="form-control" name="' . $this->htmlName
            . '" id="' . $this->htmlName
            . '" value="' . $value .'"><span class="input-group-btn">'
            . '<button class="btn" onclick="showFinder(\'' . $this->htmlName . '\'); return false;" >Выбрать</button>'
            . '</span></div>';
    }

}