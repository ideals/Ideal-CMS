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

    // Переданные параметры нужно записать в глобальную переменную idObject
    window.idObject['action'] = action;
    window.idObject['changeTemplate'] = 0;

    // Пытаемся получить заголовок и содержимое новой вкладки
    // В случае удачи — добавляем новую вкладку
    // todo разобраться в формате передачи данных и кто их обрабатывает, желательно перенести эту обработку в отдельный файл
    $.get(
        "index.php",
        {
            action: action,
            par: window.idObject['par'],
            id: window.idObject['id'],
            template: addonName,
            name: ''
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

    // Добавляем в список вкладок для редактрования
    $('#addonsList').append('<li>' + data['tabName'] + '</li>');

    // Добавляем вкладку к списку вкладок
    $('#tabs').append(data['tabHeader']);

    // Добавляем собственно само содержимое вкладок
    $('#tabs-content').append(data['tabContent']);
}