fc = function()
{
    var $form = $(this);

    var values = $form.find('[name]');

    var check = $.parseJSON(values.filter('[name = "_validators"]').val());

    for (var k in check) {
        alert(k + check);
    }

};
