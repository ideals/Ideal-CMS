$('input, textarea').placeholder({customClass: 'form-placeholder'});

fc = function()
{
    var $form = $(this);

    var values = $form.find('[name]');

    var check = $.parseJSON(values.filter('[name = "_validators"]').val());

    var isValid = true;
    for (var k in check) {
        var input = values.filter('[name = "' + k + '"]');
        var fn = 'validate' + ucfirst(check[k][0]);
        if (eval(fn)(input.val(), $form.attr('id'), input) == false) {
            isValid = false;
        }
    }

    if (!isValid) {
        alert('Поля, выделенные красным, заполнены не верно!');
        return false;
    }

    send(values);
};

function ucfirst(str)
{
    var first = str.charAt(0).toUpperCase();
    return first + str.substr(1);
}
