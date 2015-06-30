$(document).ready(function () {
    $('#general_structure').change(function () {
        var templateID = $(this).val().toLowerCase();
        $('.general_template-controls input').each(function (index) {
            $(this).hide();
        });
        $('#general_template_' + templateID).show();
    });
});
