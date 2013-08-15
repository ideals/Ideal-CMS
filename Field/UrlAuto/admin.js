var AUTO_URL_OFF = 'auto url off';
var AUTO_URL_ON = 'auto url on';

$('body').on('windowOpen', '#modalContent',function (e) {
    if ($("#UrlAuto") != null) {
        loadUrlAuto($("#UrlAuto"));
    }
});

function translit(str) {
    str = str.toLocaleLowerCase();
    var translitWord = {
        '*': '',
        '(': '',
        ')': '',
        '/': '-',
        '«': '',
        '»': '',
        '.': '',
        '№': 'N',
        '"': '',
        '\'': '',
        '?': '',
        ' ': '-',
        '&': '',
        ',': '',
        'а': 'a',
        'б': 'b',
        'в': 'v',
        'г': 'g',
        'д': 'd',
        'е': 'e',
        'ё': 'e',
        'ж': 'zh',
        'з': 'z',
        'и': 'i',
        'й': 'j',
        'к': 'k',
        'л': 'l',
        'м': 'm',
        'н': 'n',
        'о': 'o',
        'п': 'p',
        'р': 'r',
        'с': 's',
        'т': 't',
        'у': 'u',
        'ф': 'f',
        'х': 'h',
        'ц': 'c',
        'ч': 'ch',
        'ш': 'sh',
        'щ': 'shh',
        'ы': 'y',
        'э': 'e',
        'ю': 'yu',
        'я': 'ya',
        'ь': '',
        'ъ': ''
    }
    var transURL = '';
    for (var i = 0; i < str.length; i++) {
        var x = str.charAt(i);
        transURL += (typeof translitWord[x] === 'undefined') ? x : translitWord[x];
    }
    return transURL;
}

/**
 * Функция переключения состояния UrlAuto
 * @param e
 */
function setTranslit(e) {
    var input = $(e).parent().find('input');
    var butt = $(e);

    if (input.attr("readonly")) {
        butt.removeClass('btn-success');
        butt.addClass('btn-danger');
        butt.text(AUTO_URL_OFF);
        console.log(e.textContent);
        input.removeAttr("readonly");
    } else {
        butt.removeClass('btn-danger');
        butt.addClass('btn-success');
        butt.text(AUTO_URL_ON);
        console.log(e.textContent);
        input.attr("readonly", "readonly");
    }
}

/**
 * Проверка состояния включения UrlAuto
 * @param e
 */
function loadUrlAuto(e) {
    var input = $(e).parent().find('input');
    var butt = $(e).parent().find('button');
    var name = $("#general_name").val();
    name = translit(name);
    var url = $("#general_url").val();
    if (name !== url || butt.text() === AUTO_URL_ON) return;
    input.attr("readonly", "readonly");
    butt.text(AUTO_URL_ON);
    butt.removeClass('btn-danger');
    butt.addClass('btn-success');
}

$(document).ready(function () {
    $('body').on("keyup", "#general_name", function () {
        if ($('#general_url').attr("readonly")) {
            var name = $(this).val();
            var tran = translit(name);
            $('#general_url').val(tran);
        }
    });
});
