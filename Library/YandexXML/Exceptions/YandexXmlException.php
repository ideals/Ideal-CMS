<?php

namespace YandexXML\Exceptions;

/**
 * Class YandexXmlException for work with YandexXml
 *
 * @author   Mihail Bubnov <bubnov.mihail@gmail.com>
 *
 * @package  YandexXml
 */
class YandexXmlException extends \Exception
{
    const EMPTY_USER_OR_KEY = 'Не указан user и/или key';
    const EMPTY_QUERY = 'Задан пустой поисковый запрос — элемент query не содержит данных';
}
