<?php

/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Core;

use Ideal\Core\Site\Model;

/**
 * Абстрактный класс виджета. Все классы виджетов должны наследоваться от него
 */
abstract class Widget
{
    /** @var Model Модель страницы с данными */
    protected $model;

    /** @var string Префикс url для списка ссылок, генерируемых виджетом */
    protected $prefix;

    /** @var string prev_structure для получения элементов в виджете */
    protected $prevStructure;

    /** @var string GET-параметры url для списка ссылок, генерируемых виджетом */
    protected $query;

    /**
     * При инициализации виджета необходимо передать модель страницы с данными
     */
    public function __construct($model)
    {
        $this->model = $model;
    }

    /**
     * Основной метод получения даных из виджета
     *
     * Возвращает массив с данными, которые напрямую передаются в twig-шаблон под именами ключей
     *
     * @return array
     */
    abstract public function getData();

    /**
     * Установка префикса для ссылок, генерируемых виджетом
     *
     * @param string $prefix
     */
    public function setPrefix($prefix): void
    {
        $this->prefix = $prefix;
    }

    /**
     * Установка prev_structure для получения элементов в виджете
     *
     * @param string $prevStructure
     */
    public function setPrevStructure($prevStructure): void
    {
        $this->prevStructure = $prevStructure;
    }

    /**
     * Установка GET-параметров url для списка ссылок, генерируемых виджетом
     *
     * @param string $query GET-параметры, в формате QUERY_STRING
     */
    public function setQuery($query): void
    {
        $this->query = $query;
    }
}
