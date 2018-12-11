<?php

namespace FormPhp\Validator;

/**
 * Абстрактный класс валидатора для элемента формы
 *
 */
abstract class AbstractValidator
{
    protected $errorMsg = "Поля, выделенные красным, заполнены неверно!";

    /** @var array Массив опций для валидатора */
    protected $options = array();

    /**
     * Добавление валидатора
     *
     * @param array $options Массив опций
     */
    public function __construct($options = array())
    {
        $this->options = $options;
    }

    /**
     * Проверка введённого пользователем значения
     *
     * @param string $value Введённое пользователем значение
     * @return bool
     */
    abstract public function checkValue($value);

    /**
     * Получение имени валидатора для последующего вызова в js-функциях
     * @return string
     */
    public function getName()
    {
        $class = array_slice(explode('\\', get_class($this)), -2, 1);
        return strtolower(end($class));
    }

    /**
     * Получение javascript для валидации на стороне клиента
     * @return string
     */
    abstract public function getCheckJs();

    /**
     * Получение текста сообщения об ошибке
     * @return string
     */
    public function getErrorMsg()
    {
        return $this->errorMsg;
    }
}
