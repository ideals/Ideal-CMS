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



jQuery('input, textarea').placeholder({customClass: 'form-placeholder'});



jQuery.fn.form = function(options, messages){
    options = $.extend({
        ajaxUrl : '/'
    }, options);
    messages = $.extend({
        ajaxError : 'К сожалению, на данный момент услуга обратного звонка не доступна. Приносим свои извинения.',
        notValid : 'Поля, выделенные красным, заполнены не верно!'
    }, messages);


    var methods = {
        //Валидация формы
        validate : function() {
            var $form = $(this);

            var values = $form.find('[name]');
            var check = $.parseJSON(values.filter('[name = "_validators"]').val());

            var isValid = true;
            for (var field in check) {
                var input = values.filter('[name = "' + field + '"]');
                for (var k in check[field]) {
                    var fn = 'validate' + ucfirst(check[field][k]);
                    var value = (typeof input.val() == 'undefined') ? '' : input.val();
                    if (eval(fn)(value, $form.attr('id'), input) == false) {
                        isValid = false;
                    }
                }
            }
            return isValid;
        },
        // Инициализация yaCounter
        initYaCounter: function() {
            if (typeof this.yaCounter != 'undefined') {
                return;
            }
            var yaCounterName = $(this).find('[name = "_yaCounter"]').val();
            this.yaCounter = {};
            if (typeof yaCounterName != 'undefined') {
                this.yaCounter.reachGoal = function(metka) {
                    eval("var yaCounter = " + yaCounterName);
                    yaCounter.reachGoal(metka);
                }
            } else {
                this.yaCounter.reachGoal = function(opt) {}
            }
        },
        //Отправка метрики, при нажатии на кнопку отправки формы
        metrikaOnButtonClick: function() {
            var metka = $(this).data('click');
            if (metka) {
                this.yaCounter.reachGoal(metka);
            }
        },
        //Отправка метрики, при успешной отправке формы
        metrikaOnSuccessSend: function() {
            var metka = $(this).data('send');
            if (metka) {
                this.yaCounter.reachGoal(metka);
            }
        },
        //Отправка формы
        submit : function() {
            var $form = $(this);
            $form.trigger('form.buttonClick');
            var data = $form.serialize();
            $.ajax({
                type: 'post',
                url: options.ajaxUrl,
                data: data,
                dataType: 'json',
                async:false,
                success: function(result){
                    alert(result);
                    $form[0].reset();
                    $form.trigger('form.successSend');

                },
                error: function(){
                    $form.trigger('form.errorSend');
                    alert(messages.ajaxError);
                }
            });
            return false;
        }

    };

    var make = function(form){
        $(this)
            .submit(function(){
                if ($(this).onSubmit == true) {
                    return false;
                }
                $(this).onSubmit = true;
                if (!methods.validate.apply(this)) {
                    alert(messages.notValid);
                    return false;
                }
                return methods.submit.apply(this);
            })
            .on('form.buttonClick', function() {
                methods.metrikaOnButtonClick.apply(this);
                $(this).onSubmit = false;
            })
            .on('form.successSend', function() {
                methods.metrikaOnSuccessSend.apply(this);
                $(this).onSubmit = false;
            })
            .onSubmit = false;
        methods.initYaCounter.apply(this);
    };

    return this.each(make);
};


function ucfirst(str)
{
    var first = str.charAt(0).toUpperCase();
    return first + str.substr(1);
}