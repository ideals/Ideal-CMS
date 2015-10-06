/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2015 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */
$(document).ready(function() {
    var mySwfStore = new SwfStore({
        namespace: "referer_detector",
        swf_url: "/js/jsFlashCookies/storage.swf",

        // Если удалось подключиться к хранилищу флеш куков
        onready: function () {
            var refererCookiePhp = getPhpCookieReferer();

            // Получаем значение флеш куки реферера
            var refererCookieFlash = mySwfStore.get('referer');

            if (refererCookieFlash == null) {
                mySwfStore.set('referer', refererCookiePhp);
            }

            // Сравниваем флешевое значение со значением из php
            if (refererCookiePhp != refererCookieFlash) {
                var currentRefererValue = '';

                // Если значения не равны и флешевая кука существует, то она считается приорететной
                if (refererCookieFlash != null) {
                    currentRefererValue = refererCookieFlash;
                } else {
                    currentRefererValue = refererCookiePhp;
                }
                setCoockieReferer(currentRefererValue);
            }
        },
        onerror: function () {
            console.log('У вас отключен флэш.');
            var refererCookiePhp = getPhpCookieReferer();
            setCoockieReferer(refererCookiePhp);
        }
    });

    function getCookie(name) {
        var matches = document.cookie.match(new RegExp(
            "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
        ));
        return matches ? decodeURIComponent(matches[1]) : undefined;
    }

    function getPhpCookieReferer() {
        // Получаем значение реферера, которое было установлено на php
        var refererCookiePhp = getCookie('referer');

        // Если на php не было задано значение реферера, то инициализируем переменную с текущим значением
        if (refererCookiePhp == undefined) {
            refererCookiePhp = document.referrer;
        }

        return refererCookiePhp;
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