/**
 * Скрипт сравнения поля пароля и проверочного поля пароля.
 */
$(function(){
    $('input:password').bind('input', function () {
            var password = '#general_password';
            var password_check = '#general_password-check';
            var password_group = '#general_password-control-group';
            var password_ico = '#general_password-ico';
            if ($(password).val() == $(password_check).val()){
                if (!$(password_group).hasClass('has-success')){
                    $(password_group).addClass('has-success');
                }
                if ($(password_group).hasClass('has-error')){
                    $(password_group).removeClass('has-error');
                }
                if ($(password_ico).hasClass('glyphicon-exclamation-sign')){
                    $(password_ico).removeClass('glyphicon-exclamation-sign');
                }
                if (!$(password_ico).hasClass('glyphicon-ok')){
                    $(password_ico).addClass('glyphicon-ok');
                }
            } else {
                if (!$(password_group).hasClass('has-error')){
                    $(password_group).addClass('has-error');
                }
                if ($(password_group).hasClass('has-success')){
                    $(password_group).removeClass('has-success');
                }

                if ($(password_ico).hasClass('glyphicon-ok')){
                    $(password_ico).removeClass('glyphicon-ok');
                }
                if (!$(password_ico).hasClass('glyphicon-exclamation-sign')){
                    $(password_ico).addClass('glyphicon-exclamation-sign');
                }
               // $('#general_password-help').html('Пароли должны совпадать');
            }
    });
})