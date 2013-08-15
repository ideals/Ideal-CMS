<?php
namespace Ideal\Core;

/**
 * Абстрактный класс, определяющий методы, используемые как контроллерами внешней части,
 * так и контроллерами админки.
 */ 
abstract class Controller {
    protected $model;
    protected $path;
    protected $view;

    /**
     * Если необходимо - метод возвращает дату последнй модификации страницы
     * @return string
     */
    public function getLastMod()
    {
        return '';
    }


    public function finishMod($actionName)
    {
    }

}