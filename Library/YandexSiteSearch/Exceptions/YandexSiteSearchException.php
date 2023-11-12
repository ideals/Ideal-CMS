<?php

namespace YandexSiteSearch\Exceptions;

/**
 * Class YandexSiteSearchException for work with Yandex SiteSearch
 */
class YandexSiteSearchException extends \Exception
{
    const EMPTY_USER_OR_KEY = 'Не указан user и/или key';
    const EMPTY_QUERY = 'Задан пустой поисковый запрос — элемент query не содержит данных';
}
