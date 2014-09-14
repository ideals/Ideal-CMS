$(document).ready(function() {
    addonField = getAddonFieldName();
    // Получаем список подключенных к странице аддонов
    addons = $.parseJSON($('#' + addonField).val());

    // Получаем список доступных для добавления аддонов
    available = $.parseJSON($('#available_addons'));

    // Строим список подключённых аддонов в html-виде
    addonsHtml = '<ul>';
    for (i = 0; i < addons.length; i++) {
        addonsHtml += '<li>' + addons[i][2] + '</li>';
    }
    addonsHtml += '</ul>';

    // Отображаем список аддонов на странице
    $('#addonsList').html(addonsHtml);


    // todo ручная сортировка списка аддонов
    // и её отражение в поле ввода addonField и в списке вкладок

    // todo удаление любого аддона
    // выдача предупреждения об удалении данных и отражение этого события в поле ввода

    // todo редактирование названия аддона для этого элемента
    // отражение в поле ввода addonField и в списке вкладок
});

// Навешиваем событие на кнопку для отображения поля ввода для выбора аддона для добавления
$('#add-addon-button').click(function(){
    $(this).toggle();
    $('#add-addon').toggleClass('hide');
});

// Навешиваем событие на кнопку добавления аддона после выбора из select
$('#add-addon-add').click(function(){
    addonName = $('select#add-addon-select').val();
    addonField = getAddonFieldName();
    addons = $.parseJSON($('#' + addonField).val()); // список подключенных к странице аддонов

    // Ищем максимальный ID
    maxId = 0;
    for (i = 0; i < addons.length; i++) {
        maxId = (addons[i][0] > maxId) ? addons[i][0] : maxId;
    }
    newId = maxId - 0 + 1; // - 0 нужен для приведения типа maxId

    // Переданные параметры нужно записать в глобальную переменную idObject
    //window.idObject['action'] = action;
    //window.idObject['changeTemplate'] = 0;

    // Пытаемся получить заголовок и содержимое новой вкладки
    // В случае удачи — добавляем новую вкладку
    $.get(
        "index.php",
        {
            mode: 'ajax-model',
            controller: '\\Ideal\\Field\\Addon',
            action: 'add',
            par: window.idObject['par'],
            id: window.idObject['id'],
            addonName: addonName,
            addonField: addonField,
            groupName: 'general', // todo могут ли быть аддоны в аддонах или вложенных вкладках
            newId: newId
        },
        onAddNewTab,
        "json"
    );
});

// Навешиваем событие на кнопку отмены добавления аддона
$('#add-addon-hide').click(function(){
    $('#add-addon-button').toggle();
    $('#add-addon').toggleClass('hide');
});

// Добавление новой вкладки ко вкладкам редактирования элемента
function onAddNewTab(data) {
    // Скрываем поле добавления вкладки
    $('#add-addon-button').toggle();
    $('#add-addon').toggleClass('hide');

    // Добавляем в список вкладок для редактирования
    $('div#addonsList > ul').append('<li>' + data['name'] + '</li>');

    // Добавляем вкладку к списку вкладок
    $('#tabs').append(data['header']);

    // Добавляем собственно само содержимое вкладок
    $('#tabs-content').append(data['content']);
}
