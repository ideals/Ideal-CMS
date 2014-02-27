function testConnection(e) {
    $(e).attr('disabled', 'disabled');
    var host = $('[name=dbHost]').val();
    var login = $('[name=dbLogin]').val();
    var pass = $('[name=dbPass]').val();
    var name = $('[name=dbName]').val();
    var prefix = $('[name=dbPrefix]').val();

    $.ajax({
        type: "POST",
        data: 'checkMysql=1&dbHost=' + host + '&dbLogin=' + login + '&dbPass=' + pass + '&dbName=' + name + '&dbPrefix=' + prefix,
        dataType: "json",
        async: false,
        success: function (answer) {
            alert(answer.text);
            if (answer.error) {
                $(e).removeAttr('disabled');
            } else {
                $('.form-horizontal').submit();
            }
        }
    });
}

$(document).ready(function () {
    $('#siteName').on('change keyup', function (e) {
        var val = e.target.value;
        val = val.toLowerCase();
        if (val.substr(0, 4) == 'www.') {
            val = val.substr(4);
        }
        $(".domain").each(function (indx, element) {
            $(element).html(val);
        });

    });
});