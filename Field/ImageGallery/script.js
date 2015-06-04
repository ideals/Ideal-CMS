$(document).ready(function () {
    $('.imagesValues').each(function (index) {
        var currentData = $(this).val();
        if (currentData != '') {
            var currentDataArray = JSON.parse(currentData);
            var id = $(this).attr("id");
            var imageList = getImageList(currentDataArray);
            $('#' + id + '-list').html(imageList);
        }
    });
});

// Открывает окно CKFinder для возможности выбора изображений
function imageGalleryShowFinder(fieldSelector) {
    var finder = new CKFinder();
    finder.selectActionData = {"fieldSelector": '#' + fieldSelector};
    finder.basePath = '{{ cmsFolder }}/Ideal/Library/ckfinder/';
    finder.selectActionFunction = imageGallerySetFileField;
    finder.popup();
}

// Производит работу над выбранными изображениями
function imageGallerySetFileField(fileUrl, data, allFiles) {

    var urls = [];
    $.each(allFiles, function (index, value) {
        urls.push([value.url, '']);
    });
    var currentData = $(data.selectActionData.fieldSelector).val();
    // Если пока нет никаких данных по изображениям значит записываем только что выбранные
    if (currentData != '') {
        var currentDataArray = JSON.parse(currentData);
        urls = currentDataArray.concat(urls)
    }
    $(data.selectActionData.fieldSelector).val(JSON.stringify(urls));
    var imageList = getImageList(urls);
    $(data.selectActionData.fieldSelector + '-list').html(imageList);
}

// Генерирует html список изображений
function getImageList(imageList) {
    var fieldList = '';
    fieldList += '<ul id="sortable">';
    $.each(imageList, function (index, value) {
        fieldList += '<li class="ui-state-default">';
        fieldList += '<div class="col-xs-1">';
        fieldList += '<span class="glyphicon glyphicon-sort" style="top: 7px;"></span>';
        fieldList += '</div>';
        fieldList += '<div class="col-xs-1">';
        fieldList += '<span class="input-group-addon" style="padding: 0 5px">';
        fieldList += '<img src="' + value[0] + '" style="max-height:32px" class="form-control galleryItemImage"';
        fieldList += ' id="galleryItemImage' + index + '">';
        fieldList += '</span>';
        fieldList += '</div>';
        fieldList += '<div class="col-xs-5">';
        fieldList += '<input type="text" class="form-control galleryItemUrl" name="galleryItemUrl' + index + '"';
        fieldList += ' id="galleryItemUrl' + index + '" value="' + value[0] + '">';
        fieldList += '</div>';
        fieldList += '<div class="col-xs-5">'
        fieldList += '<input type="text" class="form-control galleryItemDescription"';
        fieldList += ' name="galleryItemDescription' + index + '"';
        fieldList += ' id="galleryItemDescription' + index + '" value="' + value[1] + '" placeholder="Описание изображения">';
        fieldList += '</div>';
        fieldList += '</li>';
    });
    fieldList += '</ul>';
    return fieldList;
}
