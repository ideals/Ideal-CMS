<?php
/**
 * Абстрактный класс виджета. Все классы виджетов должны наследоваться от него
 */

namespace Ideal\Core;


abstract class Widget
{

    public function __construct($model)
    {
        $this->model = $model;
    }

    abstract public function getData();

}
