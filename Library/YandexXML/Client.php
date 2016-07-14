<?php

namespace YandexXML;

use YandexXML\Exceptions\YandexXmlException;

/**
 * Class YandexXml for work with Yandex.XML
 *
 * @author   Anton Shevchuk <AntonShevchuk@gmail.com>
 * @author   Mihail Bubnov <bubnov.mihail@gmail.com>
 * @link     http://anton.shevchuk.name
 * @link     http://yandex.hohli.com
 *
 * @package  YandexXml
 */
class Client
{
    /**
     * Request factory
     *
     * @param  string $user
     * @param  string $key
     * @throws YandexXmlException
     * @return Request
     */
    public static function request($user, $key)
    {
        if (empty($user) or empty($key)) {
            throw new YandexXmlException(YandexXmlException::EMPTY_USER_OR_KEY);
        }

        return new Request($user, $key);
    }

    /**
     * Highlight text
     *
     * @param  \simpleXMLElement|string $text
     * @return string
     */
    public static function highlight($text)
    {
        if ($text instanceof \SimpleXMLElement) {
            $text = $text->asXML();
        }
        $text = str_replace('<hlword>', '<mark>', $text);
        $text = str_replace('</hlword>', '</mark>', $text);
        $text = strip_tags($text, '<mark>');
        return $text;
    }

    /**
     * Return page bar array
     *
     * @param  integer $total   Pages
     * @param  integer $current Current page started from 0
     * @return array
     */
    public static function pageBar($total, $current)
    {
        $total = $total - 1;

        $pageBar = array();

        $pageBar['prev'] = ($current > 0) ? $current - 1 : false;

        if ($total <= 10) {
            $start = 0;
            $last = $total;
        } else {
            $start = ($current - 3 > 0) ? ($current - 3) : 0;
            $last = ($current + 3 < $total) ? ($current + 3) : $total;
        }

        if ($total > 10 && $start > 0) {
            $pageBar[0] = 1;
        }

        $pageBar['prev-dots'] = $total >= 10 && $current >= 5;

        for ($i = $start; $i <= $last; $i++) {
            if ($i == $current) {
                $pageBar['current'] = $i+1;
            } else {
                $pageBar[$i] = $i+1;
            }
        }

        $pageBar['next-dots'] = $total >= 10 && $current <= $total - 5;

        if ($total >= 10 && $last < $total) {
            $pageBar[$total] = $total + 1;
        }

        $pageBar['next'] = ($current < $total) ? $current + 1 : false;

        return $pageBar;
    }
}
