<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2015 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace FormPhp;

/**
 * Класс для работы с веб-формами
 *
 */
class Forms
{
    /** @var array Список полей ввода в форме */
    public $fields = array();

    /** @var string Название формы  */
    protected $formName;

    /** @var string Метод передачи данных формы (POST||GET) */
    protected $method = 'POST';

    /** @var string Html-текст формы */
    protected $text = '';

    /** @var string javascript формы */
    protected $js = '';

    /** @var array Массив валидаторов, применённых к элементам формы */
    protected $validators = array();

    /** @var bool Флаг отображения html-сущностей в XHTML или в HTML стиле */
    protected $xhtml = true;

    /** @var string Тип заказа */
    protected $orderType = 'Заявка с сайта';

    /**
     * Инициализируем сессии, если это нужно
     *
     * @param string $formName Название формы (используется в html-теге form)
     * @param bool $xhtml Если истина, то код полей ввода будет отображается в xhtml-стиле
     */
    public function __construct($formName, $xhtml = true)
    {
        /**  Будет работать только на PHP 5.4, здесь можно проверить не запрещены ли сессии PHP_SESSION_DISABLED
        if(session_status() != PHP_SESSION_ACTIVE) {
        session_start();
        }*/

        if (session_id() == '') {
            // Если сессия не инициализирована, инициализируем её
            session_start();
        }

        $this->xhtml = $xhtml;
        $this->formName = $formName;

        // Добавляем поля токена и реферера.
        $this->add('_token', 'token');
        $this->add('referer', 'referer');
    }

    /**
     * Возвращает токен и валидацию для присутствия на форме
     *
     * @return string
     */
    public function start()
    {
        $start = '';
        $start .= $this->fields['_token']->getInputText();
        $start .= $this->getValidatorsInput();
        return $start;
    }

    /**
     * Устанавливает тип заказа
     *
     * @param $orderType
     */
    public function setOrderType($orderType)
    {
        $this->orderType = $orderType;
    }

    /**
     * Добавление элемента к форме
     *
     * Options:
     * label, placeholder, help, default
     *
     * @param string $name    Название элемента формы
     * @param string $type    Тип элемента формы
     * @param array  $options Массив опций
     * @return $this
     * @throws \Exception
     */
    public function add($name, $type, $options = array())
    {
        $fieldName = '\\FormPhp\\Field\\' . ucfirst($type) . '\\Controller';
        if (!class_exists($fieldName)) {
            throw new \Exception('Не найден класс ' . $fieldName . ' для поля ввода');
        }
        /** @var \FormPhp\Field\AbstractField $field */
        $field = new $fieldName($name, $options);
        $field->setMethod($this->method);
        $field->setXhtml($this->xhtml);
        $this->fields[$name] = $field;
        return $this;
    }

    /**
     * Отображение поля ввода с json-массивом полей с валидаторами
     *
     * @return string
     */
    public function getValidatorsInput()
    {
        $arr = array();
        foreach ($this->fields as $name => $field) {
            /** @var $field \FormPhp\Field\AbstractField */
            $arr[$name] = $field->getValidators();
            if (count($arr[$name]) == 0) {
                unset($arr[$name]);
            }
        }
        $xhtml = ($this->xhtml) ? '/' : '';
        return '<input type="hidden" name="_validators" value="'
            . htmlspecialchars(json_encode($arr, JSON_FORCE_OBJECT)) . '" ' . $xhtml
            . '>' . "\n";
    }

    /**
     * Проверка на передачу данных формы методом POST
     *
     * @return bool
     */
    public function isPostRequest()
    {
        return (isset($_SERVER['REQUEST_METHOD']) && (strtoupper($_SERVER['REQUEST_METHOD']) == 'POST'));
    }

    /**
     * Получение значения переданного полем формы
     *
     * @param string $name Имя параметра
     * @return mixed Значение параметра (если не указан, то null)
     */
    public function getValue($name)
    {
        return $this->fields[$name]->getValue();
    }

    /**
     * @param string $name Имя параметра
     * @return mixed Значение параметра (если не указан, то null)
     * @deprecated
     */
    public function getParam($name)
    {
        return $this->getValue($name);
    }

    /**
     * Проверка валидности всех введённых пользователем данных
     *
     * @return bool
     */
    public function isValid()
    {
        $token = $this->getValue('_token');
        if (is_null($token)) {
            // Токен не установлен
            return false;
        }
        if (crypt(session_id(), $token) != $token) {
            // Токен не сопадает с сессией
            return false;
        }

        $result = true;
        foreach ($this->fields as $name => $field) {
            /** @var \FormPhp\Field\AbstractField $field */
            $valid = $field->isValid();
            $result = $result && $valid;
            // todo сделать к каждому полю уведомление об ошибках и метод, выдающий все сообщения об ошибках
        }

        return $result;
    }

    /**
     * Отображение формы, css- или js-скриптов
     */
    public function render()
    {
        if (!isset($_REQUEST['mode']) || ($_REQUEST['mode'] == 'form')) {
            echo $this->text;
            return;
        }

        switch ($_REQUEST['mode']) {
            case 'css':
                header('Content-type: text/css; charset=utf-8');
                echo $this->renderCss();
                break;
            case 'js':
                header('Content-Type: text/javascript; charset=utf-8');
                echo $this->renderJs();
                break;
        }
    }

    /**
     * Получение текста на отображение
     *
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Установка метода передачи данных формы
     *
     * @param string $method Метод передачи данных формы (POST||GET)
     * @throws \Exception
     */
    public function setMethod($method)
    {
        $method = strtoupper($method);
        $availableMethods = array('POST', 'GET');
        if (!in_array($method, $availableMethods)) {
            throw new \Exception('Метод может быть только ' . implode('или', $availableMethods));
        }

        // Проставляем метод для всех полей
        foreach ($this->fields as $name => $field) {
            /** @var \FormPhp\Field\AbstractField $field */
            $field->setMethod($method);
        }

        $this->method = $method;
    }

    /**
     * Ручная установка текста формы
     *
     * @param string $text Текст формы
     */
    public function setText($text)
    {
        $this->text = $text;
    }

    /**
     * Ручная установка общего javascript формы
     *
     * @param string $js
     */
    public function setJs($js)
    {
        $this->js .= $js . "\n";
    }

    /**
     * Установка валидатора на элемент формы
     *
     * @param string       $name Название элемента формы
     * @param string|array $validator Название, список или класс валидатора
     * @throws \Exception
     */
    public function setValidator($name, $validator)
    {
        if (is_string($validator)) {
            if (!isset($this->fields[$name])) {
                throw new \Exception('Не найден элемент формы с именем ' . $name);
            }
            $fieldName = '\\FormPhp\\Validator\\' . ucfirst($validator) . '\\Controller';
            if (!class_exists($fieldName)) {
                throw new \Exception('Не найден класс ' . $fieldName . ' для валидатора');
            }
            $this->validators[$validator] = new $fieldName();
            /** @var \FormPhp\Field\AbstractField $field */
            $field = $this->fields[$name];
            $field->setValidator($this->validators[$validator]);
        }

    }

    /**
     * Генерирование css-скрипта, общего для всей формы
     *
     * Css генерируется на основе общего скрипта для формы, плюс css-скрипты для полей ввода
     * и валидаторов
     *
     * @return string
     */
    protected function renderCss()
    {
        return file_get_contents(__DIR__ .'/form.css');
    }

    /**
     * Определение кастомного кода отправки формы
     *
     * @return string Кастомный код отправки формы
     * @throws \Exception
     */
    protected function getSenderJs()
    {
        $js = '';
        foreach ($this->fields as $v) {
            /** @var $v \FormPhp\Field\AbstractField */
            $nextJs = $v->getSenderJs();
            if (!empty($js) && !empty($nextJs) && ($js != $nextJs)) {
                throw new \Exception('Ошибка! Найдено несколько классов отправки формы');
            }
            $js = $nextJs;
        }
        return $js;
    }

    /**
     * Генерирование js-скрипта, общего для всей формы
     *
     * Js генерируется на основе общих js-скриптов для формы, плюс js-скрипты для полей ввода
     * и валидаторов
     *
     * @return string
     * @throws \Exception
     */
    protected function renderJs()
    {
        $js = array();

        foreach ($this->validators as $v) {
            /** @var $v \FormPhp\Validator\AbstractValidator */
            $js[] = $v->getCheckJs();
        }

        foreach ($this->fields as $v) {
            /** @var $v \FormPhp\Field\AbstractField */
            $js[get_class($v)] = $v->getJs();
        }

        $js[] = $this->getSenderJs();

        $this->js = "jQuery(document).ready(function () {\n var $ = jQuery;\n"
            . implode("\n", $js)
            . file_get_contents(__DIR__ .'/form.js')
            . $this->js
            . "\n"  . '})';

        return $this->js;
    }

    /**
     * Отправление писем получателям
     *
     * @param string $from От имени кого отправляется почта
     * @param string $to Список получателей
     * @param string $title Заголовок письма
     * @param string $body Тело письма
     * @param $html bool Флаг, если true значит текст содержит html, false - обычный текст.
     * @return bool Признак принятия почты к отправке
     */
    public function sendMail($from, $to, $title, $body, $html = false)
    {
        if (!class_exists('\Mail\Sender')) {
            // Окружение не инициализировано и продвинутого класса отправки почты нет
            // Поэтому шлём самое простое письмо
            $response = mail($to, $title, $body, 'From: ' . $from);
            return $response;
        }

        $sender = new \Mail\Sender();

        // Устанавливаем заголовок письма
        $sender->setSubj($title);

        // Если были переданы файлы, то прикрепляем их к письму
        if (isset($_FILES['file']['name']) && !empty($_FILES['file']['name'])) {
            foreach ($_FILES as $file) {
                if ($file['name'] == '') {
                    continue;
                }
                $sender->fileAttach($file['tmp_name'], $file['type'], $file['name']);
            }
        }

        // Если был установлен флаг html, то устанавливаем текст как html.
        // В противном случае тело письма устанавливается как обычный текст
        if ($html) {
            $sender->setHtmlBody($body);
        } else {
            $sender->setPlainBody($body);
        }

        $response = $sender->sent($from, $to);

        return $response;
    }

    /**
     *
     * Сохраняем в базу информацию о заказе
     *
     * @param string $name Имя заказчика
     * @param string $email E-mail заказчика
     * @param string $content Текст заказа
     * @param int $price Сумма заказа
     */
    public function saveOrder($name, $email, $content = '', $price = 0)
    {
        // Записываем в базу, только если доступны нужные классы
        if (class_exists('\Ideal\Core\Db') && class_exists('\Ideal\Core\Config')) {

            // Получаем подключение к базе
            $db = \Ideal\Core\Db::getInstance();

            // Получаем конфигурационные данные сайта
            $config = \Ideal\Core\Config::getInstance();

            // Формируем название таблицы, в которую записывается информация о заказе
            $orderTable = $config->db['prefix'] . 'ideal_structure_order';

            // Получаем идентификатор справочника "Заказы с сайта" для построения поля "prev_structure"
            $prevStructure = '3-';
            $par = array('structure' => 'Ideal_Order');
            $fields = array('table' => $config->db['prefix'] . 'ideal_structure_datalist');
            $row = $db->select('SELECT ID FROM &table WHERE structure = :structure', $par, $fields);
            $prevStructure .= $row[0]['ID'];

            // Записываем данные
            $db->insert(
                $orderTable,
                array(
                    'prev_structure' => $prevStructure,
                    'date_create' => time(),
                    'name' => $name,
                    'email' => $email,
                    'price' => $price,
                    'referer' => $this->getValue('referer'),
                    'content' => $content,
                    'order_type' => $this->orderType
                )
            );
        }
    }
}
