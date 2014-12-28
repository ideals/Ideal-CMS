<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2014 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Core;

/**
 * Класс для работы с веб-формами
 *
 */
class Form
{
    /** @var bool Флаг отображения html-сущностей в XHTML или в HTML стиле */
    protected $xhtml = true;

    /**
     * Инициализируем сессии, если это нужно
     *
     * @param bool $xhtml Если истина, то код полей ввода будет отображается в xhtml-стиле
     */
    public function __construct($xhtml = true)
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
    }

    /**
     * Получение скрытого поля с токеном для CSRF-защиты
     *
     * @return string Скрытое поле с токеном
     */
    public function getTokenInput()
    {
        $xhtml = ($this->xhtml) ? '/' : '';
        return '<input type="hidden" name="_token" value="' . crypt(session_id()) . '" ' . $xhtml . '>' . "\n";
    }

    /**
     * Проверка валидности всех введённых пользователем данных
     *
     * @return bool
     */
    public function isValid()
    {
        if (!isset($_REQUEST['_token'])) {
            // Токен не установлен
            return false;
        }
        if (crypt(session_id(), $_REQUEST['_token']) != $_REQUEST['_token']) {
            // Токен не сопадает с сессией
            return false;
        }
        return true;
    }
}
