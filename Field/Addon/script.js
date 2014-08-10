$(document).ready(function() {
    addonField = getAddonFieldName();
    // Получаем список подключенных к странице аддонов
    addons = $.parseJSON($('#' + addonField).val());

    // Получаем список доступных для добавления аддонов
    available = $.parseJSON($('#available_addons'));

    // Строим список подключенных аддонов в html-виде
    addonsHtml = '<ul>';
    for (i = 0; i < addons.length; i++) {
        addonsHtml += '<li>' + addons[i][2] + '</li>';
    }
    addonsHtml += '</ul>';

    // Отображаем список аддонов на странице
    $('#addonsList').html(addonsHtml);


    // todo ручная сортировка списка аддонов
    // и её отражение в поле ввода addonField и в списке вкладок

    // todo добавление аддона к списку
    // отражение этого события в поле ввода addonField, в списке вкладок и подтягивание содержимого новой вкладки

    // todo удаление любого аддона
    // выдача предупреждения об удалении данных и отражение этого события в поле ввода
});

// Навешиваем событие на кнопку для отображения поля ввода для выбора аддона для добавления
$('#add-addon-button').click(function(){
    $(this).toggle();
    $('#add-addon').toggleClass('hide');
});

// Навешиваем событие на кнопку добавления аддона после выбора из select
$('#add-addon-hide').click(function(){
    $('#add-addon-button').toggle();
    $('#add-addon').toggleClass('hide');
});
