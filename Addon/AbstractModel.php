<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2017 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Addon;

/**
 * Абстрактный класс, реализующий паттерн Decorator для определения нужного вида класса модели аддона
 *
 * Определяет, в админской или внешней части запрашивается модель аддона и эмулирует работу с соответствующим классом
 * Для определения, после создания объкта модели нужно вызвать либо:
 * $addon->setParentModel($model)
 * либо
 * $addon->setModel('Admin') или $addon->setModel('Site')
 */
class AbstractModel
{
    /** @var  \Ideal\Core\Admin\Model */
    protected $model;
    protected $prevStructure;

    public function __construct($prevStructure)
    {
        $this->prevStructure = $prevStructure;
    }

    /**
     * Устанавливает модель данных, в которой находится аддон и инициализирует декорируемую модель
     *
     * Вызывается чтобы создать декорируемую модель данных аддона
     * В данном случае тип модели аддона (Site или Admin) определяется на основании модели $model, в которой этот
     * аддон содержится.
     *
     * @param \Ideal\Core\Model $model Либо админская, либо сайтовая модель данных, в которой находится аддон
     */
    public function setParentModel($model)
    {
        $mode = explode('\\', get_class($model));
        $this->setModel($mode[3]);
        $this->model->setParentModel($model);
    }

    /**
     * Иницилизирует декорируемую модель в соответствии с типом $type
     *
     * @param string $type Тип декорируемой модели — либо 'Site', либо 'Admin'
     */
    public function setModel($type)
    {
        $class = str_replace('Model', $type . 'Model', get_class($this));
        $this->model = new $class($this->prevStructure);
    }

    public function __set($name, $value)
    {
        if (empty($this->model)) {
            throw new \Exception('Empty Model');
        }
        $this->model->$name = $value;
    }

    public function __get($name)
    {
        if (empty($this->model)) {
            throw new \Exception('Empty Model');
        }
        if (!isset($this->model->$name)) {
            throw new \Exception('Not Set Field ' . $name);
        }
        return $this->model->$name;
    }

    public function __call($name, $arguments)
    {
        if (empty($this->model)) {
            throw new \Exception('Empty Model');
        }
        return call_user_func_array(array($this->model, $name), $arguments);
    }
}
