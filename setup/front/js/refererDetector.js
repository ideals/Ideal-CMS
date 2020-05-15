/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2019 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */
$(document).ready(function () {
    // Получаем значение реферера, которое было установлено на php
    var refererCookiePhp = getCookie('referer');

    // Если на php не было задано значение реферера, то инициализируем переменную с текущим значением
    if (refererCookiePhp === undefined) {
        refererCookiePhp = document.referrer;
    }

    setCoockieReferer(refererCookiePhp);

    function getCookie(name) {
        var matches = document.cookie.match(new RegExp(
            "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
        ));
        return matches ? decodeURIComponent(matches[1]) : undefined;
    }

    function setCoockieReferer(refererCookie) {

        if (refererCookie.trim() == '') {
            refererCookie = 'null';
        }

        var now = new Date();
        var time = now.getTime();
        var expireTime = time + 315360000;
        now.setTime(expireTime);
        document.cookie = 'referer=' + refererCookie + ';expires=' + now.toGMTString() + ';path=/';
    }
});