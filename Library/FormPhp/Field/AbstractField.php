<?php

namespace FormPhp\Field;

/**
 * Абстрактный класс поля ввода формы
 *
 */
class AbstractField
{
    /** @var string Метод передачи данных формы (POST||GET) */
    protected $method = 'POST';

    /** @var string Название элемента формы */
    protected $name;

    /** @var array Массив опций для элемента формы */
    protected $options = array();

    /** @var array  */
    protected $validators = array();

    /**
     * Добавление элемента к форме
     *
     * @param string $name Название элемента формы
     * @param array $options Массив опций
     */
    public function __construct($name, $options = array())
    {
        $this->name = $name;
        $this->options = $options;
    }

    /**
     * Получение значения, введённого пользователем
     *
     * @return mixed
     */
    protected function getValue()
    {
        $method = '_' . $this->method; // приводим к виду _POST или _GET
        if (!isset($GLOBALS[$method][$this->name])) {
            // Если параметр не указан, то null
            return null;
        }
        $value = $GLOBALS[$method][$this->name];
        return $value;
    }

    /**
     * Проверка валидности введённых пользователем данных в соответствии со всеми установленными валидаторами
     *
     * @return bool
     */
    public function isValid()
    {
        $value = $this->getValue();
        $result = true;
        foreach ($this->validators as $validator) {
            /** @var \FormPhp\Validator\AbstractValidator $validator */
            $valid = $validator->checkValue($value);
            $result = $result && $valid;
        }
        return $result;
    }

    /**
     * Установка метода передачи данных формы
     *
     * @param string $method Метод передачи данных формы (POST||GET)
     */
    public function setMethod($method)
    {
        $this->method = $method;
    }

    /**
     * Установка валидатора на поле
     *
     * @param \FormPhp\Validator\AbstractValidator $validator
     */
    public function setValidator($validator)
    {
        $this->validators[] = $validator;
    }
}
