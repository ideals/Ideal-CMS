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

    /** @var bool Флаг отображения html-сущностей в XHTML или в HTML стиле */
    protected $xhtml = true;

    /** @var string JavaScript необходимыя для работы поля */
    protected $js;

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
     * Получение списка валидаторов, привязанных к этому полю
     *
     * @return array Список валидаторов
     */
    public function getValidators()
    {
        $arr = array();
        foreach ($this->validators as $validator) {
            /** @var $validator \FormPhp\Validator\AbstractValidator */
            $arr[] = $validator->getName();
        }
        return $arr;
    }

    /**
     * Получение значения, введённого пользователем
     *
     * @return mixed
     */
    public function getValue()
    {
        if (class_exists('\Ideal\Core\Request')) {
            $request = new \Ideal\Core\Request();
            $fieldName = $this->name;
            return $request->$fieldName;
        } else {
            $method = '_' . $this->method; // приводим к виду _POST или _GET
            if (!isset($GLOBALS[$method][$this->name])) {
                // Если параметр не указан, то null
                return null;
            }
            $value = htmlspecialchars($GLOBALS[$method][$this->name]);
            return $value;
        }
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
     * Установка отображения html-сущностей
     *
     * @param bool $xhtml Если истина, то код полей ввода будет отображается в xhtml-стиле
     */
    public function setXhtml($xhtml)
    {
        $this->xhtml = $xhtml;
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

    /**
     * Получение js кода, необходимого для работы поля
     */
    public function getJs()
    {
        return $this->js;
    }

    /**
     * Получение js-кода отправки формы, отличного от стандартного
     *
     * @return string js-код отправки формы
     */
    public function getSenderJs()
    {
        return '';
    }

    /**
     * Получение текста, подписывающего это поле ввода (тег label)
     *
     * @return string Строка содержащая текст подписи
     */
    public function getLabelText()
    {
        return '';
    }

    /**
     * Возвращает строку, содержащую html-код элемента ввода данных
     *
     * @return string html-код элементов ввода
     */
    public function getInputText()
    {
        return '';
    }
}
