// Открывает окно CKFinder для возможности выбора изображений
function imageGalleryShowFinder(fieldSelector) {
    var finder = new CKFinder();
    finder.selectActionData = {"fieldSelector" : fieldSelector};
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
    var currentData = $('#' + data.selectActionData.fieldSelector).val();
    // Если пока нет никаких данных по изображениям значит записываем только что выбранные
    if (currentData == '') {
        $('#' + data.selectActionData.fieldSelector).val(JSON.stringify(urls));
    } else {
        var currentDataArray = JSON.parse(currentData);
        $('#' + data.selectActionData.fieldSelector).val(JSON.stringify(currentDataArray.concat(urls)));
    }
}
