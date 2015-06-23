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
            // Получаем значение реферера, которое было установлено на php
            var refererCookiePhp = getCookie('referer');

            // Получаем значение флеш куки реферера
            var refererCookieFlash = mySwfStore.get('referer');

            if (refererCookieFlash == null) {
                mySwfStore.set('referer', refererCookiePhp);
            } else {
                // Если флешевое значение не пустое то сравниваем его со значением из php
                if (refererCookiePhp != refererCookieFlash) {
                    // Если значения не равны то приорететнее считается флешевое значение
                    document.cookie = "referer=" + refererCookieFlash + "; path=/;";
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