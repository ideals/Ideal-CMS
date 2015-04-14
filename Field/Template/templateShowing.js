$(document).ready(function () {
    $('#general_structure').change(function () {
        var templateID = $(this).val().toLowerCase();
        $('.general_template-controls select').each(function (index) {
            $(this).hide();
            $(this).next('div').hide();
        });
        $('#general_template_' + templateID).show();
        $('#general_template_' + templateID).next('div').show();
    });


});
