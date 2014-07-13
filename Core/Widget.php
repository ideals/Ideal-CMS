<?php
/**
 * Абстрактный класс виджета. Все классы виджетов должны наследоваться от него
 */

namespace Ideal\Core;

abstract class Widget
{

    /** @var  \Ideal\Core\Site\Model */
    protected $model;

    /** @var  string Префикс url для списка ссылок, генерируемых виджетом */
    protected $prefix;

    /** @var  string prev_structure для получения элементов в виджете */
    protected $prevStructure;

    /** @var  string get парметры url для списка ссылок, генерируемых виджетом */
    protected $query;

    public function __construct($model)
    {
        $this->model = $model;
    }

    abstract public function getData();

    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    public function setPrevStructure($prevStructure)
    {
        $this->prevStructure = $prevStructure;
    }

    public function setQuery($query)
    {
        $this->query = $query;
    }
}
