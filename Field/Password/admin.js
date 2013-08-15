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
                if (!$(password_group).hasClass('success')){
                    $(password_group).addClass('success');
                }
                if ($(password_group).hasClass('error')){
                    $(password_group).removeClass('error');
                }
                if ($(password_ico).hasClass('icon-exclamation-sign')){
                    $(password_ico).removeClass('icon-exclamation-sign');
                }
                if (!$(password_ico).hasClass('icon-ok')){
                    $(password_ico).addClass('icon-ok');
                }
            } else {
                if (!$(password_group).hasClass('error')){
                    $(password_group).addClass('error');
                }
                if ($(password_group).hasClass('success')){
                    $(password_group).removeClass('success');
                }

                if ($(password_ico).hasClass('icon-ok')){
                    $(password_ico).removeClass('icon-ok');
                }
                if (!$(password_ico).hasClass('icon-exclamation-sign')){
                    $(password_ico).addClass('icon-exclamation-sign');
                }
               // $('#general_password-help').html('Пароли должны совпадать');
            }
    });
})