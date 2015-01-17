<?php

namespace FormPhp\Validator;

/**
 * Абстрактный класс валидатора для элемента формы
 *
 */
abstract class AbstractValidator
{
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
        return strtolower(end(array_slice(explode('\\', get_class($this)), -2, 1)));
    }

    /**
     * Получение javascript для валидации на стороне клиента
     * @return string
     */
    abstract public function getCheckJs();
}
