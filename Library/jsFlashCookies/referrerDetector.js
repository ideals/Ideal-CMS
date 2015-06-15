/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2015 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */
$(document).ready(function() {
    var mySwfStore = new SwfStore({
        namespace: "referrer_detector",
        swf_url: "/js/jsFlashCookies/storage.swf",

        // Если удалось подключиться к хранилищу флеш куков
        onready: function () {
            // Получаем значение реферра, которое было установлено на php
            var referrerCookiePhp = getCookie('referrer');

            // Получаем значение флеш куки реферра
            var referrerCookieFlash = mySwfStore.get('referrer');

            if (referrerCookieFlash == null) {
                mySwfStore.set('referrer', referrerCookiePhp);
            } else {
                // Если флешевое значение не пустое то сравниваем его со значением из php
                if (referrerCookiePhp != referrerCookieFlash) {
                    // Если значения не равны то приорететнее считается флешевое значение
                    document.cookie = "referrer=" + referrerCookieFlash + "; path=/;";
                }
            }
        },
        onerror: function () {
            console.error('swfStore failed to load');
        }
    });

    function getCookie(name) {
        var matches = document.cookie.match(new RegExp(
            "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
        ));
        return matches ? decodeURIComponent(matches[1]) : undefined;
    }
});