<?php

namespace YandexSiteSearch;

use YandexSiteSearch\Exceptions\YandexSiteSearchException;

/**
 * Class YandexSiteSearch for work with Yandex Site Search
 */
class Client
{
    /**
     * Request factory
     *
     * @param string $user
     * @param string $key
     *
     * @return Request
     * @throws YandexSiteSearchException
     */
    public static function request(string $apiKey, string $searchId): Request
    {
        if (empty($apiKey) || empty($searchId)) {
            throw new YandexSiteSearchException(YandexSiteSearchException::EMPTY_USER_OR_KEY);
        }

        return new Request($apiKey, $searchId);
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
