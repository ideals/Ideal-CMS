$(document).ready(function () {
    // Обходим каждый элемент с класом "images-values" так как аддонов "Фотогалерея" может быть несколько.
    $('.images-values').each(function (index) {
        var currentData = $(this).val();
        if (currentData != '') {
            var currentDataArray = JSON.parse(currentData);
            var id = $(this).attr("id");
            var imageList = getImageList(currentDataArray, id);
            $('#' + id + '-list').html(imageList);
            startSortable('#' + id + '-list', '#' + id);
        }
    });

    // Вешаем событие на кнопку удаления изображения из списка
    $('.tab-pane').on('click', '.remove-image', function () {
        // Ищем поле содержащее адрес до изображения
        var imageUrl = $(this).closest('li').children('div').find('.gallery-item-url').val();

        // Ищем поле которое хранит всю информацию о изображениях в списке
        var imagesValues = $(this).closest('.tab-pane').children('.images-values');

        // Получаем всю информацию о изображениях в списке в виде текста
        var currentData = $(imagesValues).val();

        // Визуально удаляем изображение из списка
        $(this).closest('li').remove();

        // Получаем информацию о изображениях в списке ввиде массива
        var currentDataArray = JSON.parse(currentData);

        // Ищем ключ верхнего уровня удаляемого элемента
        var arrayKey = secondLevelFind(currentDataArray, imageUrl);

        // Удаляем информацию о изображении из массива
        currentDataArray.splice(arrayKey, 1);

        // Записываем обновлённую информацию о списке изображений в нужное поле
        $(imagesValues).val(JSON.stringify(currentDataArray));
    });

    // При смене описания картинки пересохраняем информацию для фотогалереи
    $(".tab-pane .gallery-item-description").change(function () {
        var id = $('.images-values').attr("id");
        var listSelector = '#' + id + '-list';
        var infoSelector = '#' + id;
        rescanPhotogalleryItems(listSelector, infoSelector)
    });
});

// Запускаем возможность сортировки списка
function startSortable(listSelector, infoSelector) {
    $(listSelector + " .sortable").sortable({
        stop: function (event, ui) {
            rescanPhotogalleryItems(listSelector, infoSelector)
        }
    });
    $(listSelector + " .sortable").disableSelection();
}

// Пересобираем информацию о фотогалерее
function rescanPhotogalleryItems(listSelector, infoSelector) {
    var urls = [];
    $(listSelector + " .sortable").find('li').each(function (index) {
        if ($(this).find('.gallery-item-url').val() != undefined) {
            var url = $(this).find('.gallery-item-url').val();
            var description = $(this).find('.gallery-item-description').val();
            urls.push([url, description]);
        }
    });
    $(infoSelector).val(JSON.stringify(urls));
}


// Открывает окно CKFinder для возможности выбора изображений
function imageGalleryShowFinder(fieldSelector) {
    var finder = new CKFinder();
    finder.selectActionData = {"fieldSelector": fieldSelector};
    finder.basePath = '{{ cmsFolder }}/Ideal/Library/ckfinder/';
    finder.selectActionFunction = imageGallerySetFileField;
    finder.popup();
}

// Производит работу над выбранными изображениями
function imageGallerySetFileField(fileUrl, data, allFiles) {
    var fieldSelector = '#' + data.selectActionData.fieldSelector;
    var urls = [];
    $.each(allFiles, function (index, value) {
        urls.push([value.url, '']);
    });
    var currentData = $(fieldSelector).val();
    // Если пока нет никаких данных по изображениям значит записываем только что выбранные
    if (currentData != '') {
        var currentDataArray = JSON.parse(currentData);
        urls = currentDataArray.concat(urls)
    }
    $(fieldSelector).val(JSON.stringify(urls));
    var imageList = getImageList(urls, data.selectActionData.fieldSelector);
    $(fieldSelector + '-list').html(imageList);
    startSortable(fieldSelector + '-list', fieldSelector);
}

// Генерирует html список изображений
function getImageList(imageList, fieldId) {
    var fieldList = '';
    fieldList += '<ul id="' + fieldId + '-sortable" class="sortable">';
    $.each(imageList, function (index, value) {
        fieldList += '<li class="ui-state-default">';
        fieldList += '<div class="col-xs-1">';
        fieldList += '<span class="glyphicon glyphicon-sort" style="top: 9px;"></span>';
        fieldList += '</div>';
        fieldList += '<div class="col-xs-1">';
        fieldList += '<span class="input-group-addon" style="padding: 0 5px">';
        fieldList += '<img src="' + value[0] + '" style="max-height:32px" class="form-control gallery-item-image"';
        fieldList += ' id="gallery-item-image' + index + '">';
        fieldList += '</span>';
        fieldList += '</div>';
        fieldList += '<div class="col-xs-5">';
        fieldList += '<input type="text" class="form-control gallery-item-url" name="gallery-item-url-' + index + '"';
        fieldList += ' id="gallery-item-url' + index + '" value="' + value[0] + '">';
        fieldList += '</div>';
        fieldList += '<div class="col-xs-4">'
        fieldList += '<input type="text" class="form-control gallery-item-description"';
        fieldList += ' name="gallery-item-description' + index + '"';
        fieldList += ' id="gallery-item-description' + index + '" value="' + value[1] + '"';
        fieldList += ' placeholder="Описание изображения">';
        fieldList += '</div>';
        fieldList += '<div class="col-xs-1">';
        fieldList += '<span class="glyphicon glyphicon-remove remove-image" style="color: #FF0000; top: 7px;"></span>';
        fieldList += '</div>';
        fieldList += '</li>';
    });
    fieldList += '</ul>';
    return fieldList;
}

// Ищет элемент на втором уровне двумерного массива и возвращает ключ первого уровня
function secondLevelFind(arr, value) {
    for (var i = 0; i < arr.length; i++) {
        if (arr[i][0] == value) {
            return i;
        }
    }
}
