/*! http://mths.be/placeholder v2.1.0 by @mathias */
(function(factory) {
    if (typeof define === 'function' && define.amd) {
        // AMD
        define(['jquery'], factory);
    } else {
        // Browser globals
        factory(jQuery);
    }
}(function($) {

    // Opera Mini v7 doesn’t support placeholder although its DOM seems to indicate so
    var isOperaMini = Object.prototype.toString.call(window.operamini) == '[object OperaMini]';
    var isInputSupported = 'placeholder' in document.createElement('input') && !isOperaMini;
    var isTextareaSupported = 'placeholder' in document.createElement('textarea') && !isOperaMini;
    var valHooks = $.valHooks;
    var propHooks = $.propHooks;
    var hooks;
    var placeholder;

    if (isInputSupported && isTextareaSupported) {

        placeholder = $.fn.placeholder = function() {
            return this;
        };

        placeholder.input = placeholder.textarea = true;

    } else {

        var settings = {};

        placeholder = $.fn.placeholder = function(options) {

            var defaults = {customClass: 'placeholder'};
            settings = $.extend({}, defaults, options);

            var $this = this;
            $this
                .filter((isInputSupported ? 'textarea' : ':input') + '[placeholder]')
                .not('.'+settings.customClass)
                .bind({
                    'focus.placeholder': clearPlaceholder,
                    'blur.placeholder': setPlaceholder
                })
                .data('placeholder-enabled', true)
                .trigger('blur.placeholder');
            return $this;
        };

        placeholder.input = isInputSupported;
        placeholder.textarea = isTextareaSupported;

        hooks = {
            'get': function(element) {
                var $element = $(element);

                var $passwordInput = $element.data('placeholder-password');
                if ($passwordInput) {
                    return $passwordInput[0].value;
                }

                return $element.data('placeholder-enabled') && $element.hasClass('placeholder') ? '' : element.value;
            },
            'set': function(element, value) {
                var $element = $(element);

                var $passwordInput = $element.data('placeholder-password');
                if ($passwordInput) {
                    return $passwordInput[0].value = value;
                }

                if (!$element.data('placeholder-enabled')) {
                    return element.value = value;
                }
                if (value === '') {
                    element.value = value;
                    // Issue #56: Setting the placeholder causes problems if the element continues to have focus.
                    if (element != safeActiveElement()) {
                        // We can't use `triggerHandler` here because of dummy text/password inputs :(
                        setPlaceholder.call(element);
                    }
                } else if ($element.hasClass(settings.customClass)) {
                    clearPlaceholder.call(element, true, value) || (element.value = value);
                } else {
                    element.value = value;
                }
                // `set` can not return `undefined`; see http://jsapi.info/jquery/1.7.1/val#L2363
                return $element;
            }
        };

        if (!isInputSupported) {
            valHooks.input = hooks;
            propHooks.value = hooks;
        }
        if (!isTextareaSupported) {
            valHooks.textarea = hooks;
            propHooks.value = hooks;
        }

        $(function() {
            // Look for forms
            $(document).delegate('form', 'submit.placeholder', function() {
                // Clear the placeholder values so they don't get submitted
                var $inputs = $('.'+settings.customClass, this).each(clearPlaceholder);
                setTimeout(function() {
                    $inputs.each(setPlaceholder);
                }, 10);
            });
        });

        // Clear placeholder values upon page reload
        $(window).bind('beforeunload.placeholder', function() {
            $('.'+settings.customClass).each(function() {
                this.value = '';
            });
        });

    }

    function args(elem) {
        // Return an object of element attributes
        var newAttrs = {};
        var rinlinejQuery = /^jQuery\d+$/;
        $.each(elem.attributes, function(i, attr) {
            if (attr.specified && !rinlinejQuery.test(attr.name)) {
                newAttrs[attr.name] = attr.value;
            }
        });
        return newAttrs;
    }

    function clearPlaceholder(event, value) {
        var input = this;
        var $input = $(input);
        if (input.value == $input.attr('placeholder') && $input.hasClass(settings.customClass)) {
            if ($input.data('placeholder-password')) {
                $input = $input.hide().nextAll('input[type="password"]:first').show().attr('id', $input.removeAttr('id').data('placeholder-id'));
                // If `clearPlaceholder` was called from `$.valHooks.input.set`
                if (event === true) {
                    return $input[0].value = value;
                }
                $input.focus();
            } else {
                input.value = '';
                $input.removeClass(settings.customClass);
                input == safeActiveElement() && input.select();
            }
        }
    }

    function setPlaceholder() {
        var $replacement;
        var input = this;
        var $input = $(input);
        var id = this.id;
        if (input.value === '') {
            if (input.type === 'password') {
                if (!$input.data('placeholder-textinput')) {
                    try {
                        $replacement = $input.clone().attr({ 'type': 'text' });
                    } catch(e) {
                        $replacement = $('<input>').attr($.extend(args(this), { 'type': 'text' }));
                    }
                    $replacement
                        .removeAttr('name')
                        .data({
                            'placeholder-password': $input,
                            'placeholder-id': id
                        })
                        .bind('focus.placeholder', clearPlaceholder);
                    $input
                        .data({
                            'placeholder-textinput': $replacement,
                            'placeholder-id': id
                        })
                        .before($replacement);
                }
                $input = $input.removeAttr('id').hide().prevAll('input[type="text"]:first').attr('id', id).show();
                // Note: `$input[0] != input` now!
            }
            $input.addClass(settings.customClass);
            $input[0].value = $input.attr('placeholder');
        } else {
            $input.removeClass(settings.customClass);
        }
    }

    function safeActiveElement() {
        // Avoid IE9 `document.activeElement` of death
        // https://github.com/mathiasbynens/jquery-placeholder/pull/99
        try {
            return document.activeElement;
        } catch (exception) {}
    }

}));

// The end of placeholder plugin

jQuery('input, textarea').placeholder({customClass: 'form-placeholder'});

jQuery.fn.form = function (options, messages, methods) {
    options = $.extend({
        ajaxUrl: '/',
        ajaxDataType: 'text',
        location: false
    }, options);
    var messagesOrig = $.extend({
        ajaxError: 'Форма не отправилась. Попробуйте повторить отправку позже.',
        notValid: 'Поля заполнены неверно!',
        errors: [],
        validate: true
    }, messages);
    messages = $.extend({}, messagesOrig);
    //
    function alert(message, status) {
        status = status || null;
        methods.alert(message, status)
    }

    methods = $.extend({
        // Валидация формы
        validate: function () {
            var $form = $(this);

            $form.find('.error-text').remove();
            var values = $form.find('[name]');
            var check = $.parseJSON(values.filter('[name = "_validators"]').val());
            messages = $.extend({}, messagesOrig);

            var isValid = true;
            for (var field in check) {
                var input = values.filter('[name = "' + field + '"]');
                for (var k in check[field]) {
                    var fn = 'validate' + ucfirst(check[field][k]);
                    var value = '';
                    if (input.filter('select').size()) {
                        value = input.find(':selected').val();
                    } else {
                        value = (typeof input.val() == 'undefined') ? '' : input.val();
                    }
                    messages = eval(fn)(value, messages);
                    if (messages.validate == false) {
                        isValid = false;
                        input.addClass('error-' + check[field][k]);
                        if (messages.errors[messages.errors.length - 1] != '') {
                            input.parent().append("<div class='error-text'>" + messages.errors[messages.errors.length - 1] + "</div>");
                        }
                    } else {
                        input.removeClass('error-' + check[field][k]);
                    }
                }
            }
            return isValid;
        },
        // Инициализация yaCounter
        initYaCounter: function () {
            if (typeof this.yaCounter != 'undefined') {
                return;
            }
            var yaCounterName = $(this).find('[name = "_yaCounter"]').val();
            this.yaCounter = {};
            eval('var yaCounterName = typeof ' + yaCounterName + ' == \'undefined\' ? false : yaCounterName');
            if (yaCounterName !== false) {
                this.yaCounter.reachGoal = function (metka) {
                    eval('var yaCounter = ' + yaCounterName);
                    yaCounter.reachGoal(metka);
                }
            } else {
                this.yaCounter.reachGoal = function (opt) {}
            }
        },
        // Инициализация googleAnalytics
        initGoogleAnalytics: function () {
            if (typeof this.ga != 'undefined') {
                return;
            }
            this.ga = {};
            if (typeof ga === "function") {
                this.ga = function (metka) {
                    metka = '/' + metka.toLowerCase();
                    ga('send', 'pageview', metka);
                }
            } else {
                this.ga = function (opt) {}
            }
        },
        // Отправка метрики, при нажатии на кнопку отправки формы
        metrikaOnButtonClick: function () {
            var metka = $(this).data('click');
            if (metka) {
                this.yaCounter.reachGoal(metka);
                this.ga(metka);
            }
        },
        // Отправка метрики, при успешной отправке формы
        metrikaOnSuccessSend: function () {
            var metka = $(this).data('send');
            if (metka) {
                this.yaCounter.reachGoal(metka);
                this.ga(metka);
            }
        },
        // Добавление проверочного поля при нажатии на кнопку отправки формы
        locationOnButtonClick: function () {
            $(this).prepend('<input type="hidden" name="_location" value="' + window.location.href + '">');
        },
        // Отправка формы
        submit: function () {
            var $form = $(this);
            var data = $form.serialize();
            $.ajax({
                type: 'post',
                url: options.ajaxUrl,
                data: data,
                dataType: options.ajaxDataType,
                async:false,
                success: function (result) {
                    methods.successSend.apply($form, [result]);
                },
                error: function (result) {
                    methods.errorSend.apply($form, [result]);
                }
            });
            return false;
        },
        // Обработка успешной отправки формы
        successSend: function (result) {
            if (options.ajaxDataType == 'text') {
                alert(result);
            } else if (options.ajaxDataType == 'json' || options.ajaxDataType == 'jsonp') {
                alert(result[0], result[1]);
            }
            if (options.ajaxDataType == 'text' || result[1] != 'error') {
                $(this)[0].reset();
                $(this).trigger('form.successSend');
                return;
            }
            $(this).trigger('form.errorSend');
        },
        // Обработка неудачной отправки формы
        errorSend: function (result) {
            $(this).trigger('form.errorSend');
            alert(messages.ajaxError);
        },
        // Вывод сообщений
        alert: function ($message, $status) {
            window.alert($message);
        }
    }, methods);

    var make = function (form) {
        $(this)
            .submit(function () {
                methods.initYaCounter.apply(this);
                methods.initGoogleAnalytics.apply(this);
                if (this.defaultSubmit === true) {
                    return true;
                }
                if (this.disableSubmit == true) {
                    return false;
                }
                this.disableSubmit = true;
                $(this).trigger('form.buttonClick');
                if (!methods.validate.apply(this)) {
                    if (messages.errors.length > 1) {
                        $(this).find('.error-text').show();
                        alert(messages.notValid);
                    } else {
                        alert(messages.errors[0]);
                    }
                    messages.errors.length = 0;
                    return false;
                }

                if (typeof senderAjax == 'object') {
                    return senderAjax.send(this, options, methods.successSend);
                } else {
                    return methods.submit.apply(this);
                }
            })
            .on('form.buttonClick', function () {
                methods.metrikaOnButtonClick.apply(this);
                if (options.location) {
                    methods.locationOnButtonClick.apply(this);
                }
                this.disableSubmit = false;
            })
            .on('form.successSend', function () {
                methods.metrikaOnSuccessSend.apply(this);
            })
            .on('form.errorSend', function () {
                this.disableSubmit = false;
            });
        this.disableSubmit = false;
        this.defaultSubmit = false;
    };
    return this.each(make);
};

function ucfirst(str)
{
    var first = str.charAt(0).toUpperCase();
    return first + str.substr(1);
}
