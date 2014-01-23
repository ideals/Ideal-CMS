<?php
/**
 * Ideal CMS (http://idealcms.ru/)
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2013 Ideal CMS (http://idealcms.ru)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

/**
 * Экшэн отображения списка редиректов из файлов redirect.txt и .htaccess
 */

$file = new \Ideal\Structure\Service\Redirect\RewriteRule();
$file->loadRedirects();

// todo Переделать в ajax-вызовы

if (isset($_POST['add'])) {
    $file->addLine($_POST['from'], $_POST['to']);
    exit;
}
if (isset($_POST['edit'])) {
    $file->editLine($_POST['from'], $_POST['to'], $_POST['oldFrom'], $_POST['oldTo']);
    exit;
}
if (isset($_POST['delete'])) {
    $file->deleteLine($_POST['from'], $_POST['to']);
    exit;
}

$table = $file->getTable();

echo $file->getMsg();

// Если уровень ошибки больше 1, то список редиректов не отображается
if ($file->getError() > 1) return;
?>
<table id="redirect" class="table table-hover table-striped">
    <tr>
        <th style="width: 249px">Откуда</th>
        <th style="width: 249px">Куда</th>
        <th style="text-align: right"></th>
    </tr>
    <?php
    echo $table;
    ?>
</table>

<br/>

<button type="button" class="btn btn-primary pull-left" value="<?php echo $file->getCountParam(); ?>" onclick="addLine(this)">
    Добавить редирект
</button>

<div class="alert" style="margin-left: 190px;">
    <strong>Не забывайте</strong> экранировать специмволы в первой колонке в соответствии с правилами регулярных выражений!
</div>

<style>
    tr:hover .editGroup {
        display: inline;
    }

    tr {
        height: 42px;
    }

    tr td:last-child {
        text-align: right;
        vertical-align: middle;
    }

    .editLine td {
        padding-bottom: 0;
    }
</style>


<script type="text/javascript">
    function addLine(e)
    {
        $(e).attr("disabled", "disabled");
        var i = parseInt($(e).val()) + 1;
        var from = $('#line' + (i - 1) + ' > .from').children().val();
        var to = $('#line' + (i - 1) + ' > .to').children().val();
        if (from !== '' && to !== '') {
            $('#redirect > tbody:last').append('<tr id="line' + i + '">'
                + '<td class="from"><input type="text" name="from"></td>'
                + '<td class="to"><input type="text" name="to"></td>'
                + '<td><div class="hide editGroup"> '
                + '<span class="input-prepend">'
                + '<button type="button" style="width: 47px;" onclick="saveLine(' + i + ')" title="Сохранить" class="btn btn-success btn-mini">'
                + '<i class="icon-ok icon-white"></i></button></span>'
                + '<span class="input-append"><button onclick="delLine(' + i + ')" title="Удалить" class="btn btn-danger btn-mini">'
                + '<i class="icon-remove icon-white"></i></button></span></div>'
                + '</td></tr>')
            $(e).val(i + 1);
        }
        $(e).removeAttr('disabled');
    }

    function delLine(e)
    {
        var line = $('#line' + e);
        var from = line.find('.from');
        var to = line.find('.to');
        var oldFrom = from.attr('data-from') || false;
        var oldTo = to.attr('data-to') || false;

        if (oldFrom === false && oldTo === false) {
            // Если удаление вызвано для свежесозданного редиректа, ещё не записанного в файл
            $('#line' + e).remove();
            return true;
        }

        if (!confirm('Удалить редирект ' + oldFrom + ' >> ' + oldTo + ' ?')) {
            return false;
        }

        $.ajax({
            type: "POST",
            data: "delete=1&from=" + oldFrom + "&to=" + oldTo,
            success: function (data) {
                if (data.error) {

                } else {
                    $('#line' + e).remove();
                }
            }

        });
    }

    function editLine(e)
    {
        var line = $('#line' + e);
        line.addClass('editLine');
        // Заменяем кнопку «Редактировать» на кнопку «Сохранить»
        var editBtn = line.find('.btn-info').removeClass('btn-info').addClass('btn-success');
        editBtn.attr('onclick', 'saveLine(' + e + ')');
        editBtn.attr('title', 'Сохранить');
        editBtn.children().removeClass('icon-pencil').addClass('icon-ok');
        // Заменяем кнопку «Удалить» на кнопку «Отмена»
        var cancelBtn = line.find('.btn-danger');
        cancelBtn.attr('onclick', 'cancelLine(' + e + ')');
        cancelBtn.attr('title', 'Отменить');
        // Создаём поля ввода
        var from = line.find('.from');
        var to = line.find('.to');
        from.html('<input type="text" name="from" value="' + from.html() + '">');
        to.html('<input type="text" name="to" value="' + to.html() + '">');
    }

    function cancelLine(e)
    {
        var line = $('#line' + e);
        var from = line.find('.from');
        var to = line.find('.to');
        var oldFrom = from.attr('data-from') || false;
        var oldTo = to.attr('data-to') || false;

        line.removeClass('editLine');
        from.html(oldFrom);
        to.html(oldTo);

        var editBtn = line.find('.btn-success').removeClass('btn-success').addClass('btn-info');
        editBtn.attr('onclick', 'editLine(' + e + ')');
        editBtn.attr('title', 'Изменить');
        editBtn.children().removeClass('icon-ok').addClass('icon-pencil');

        var cancelBtn = line.find('.btn-danger');
        cancelBtn.attr('onclick', 'delLine(' + e + ')');
        cancelBtn.attr('title', 'Удалить');
        cancelBtn.val(oldFrom);

        return true;

    }

    function saveLine(e)
    {
        var type = 'add';
        var line = $('#line' + e);
        var from = line.find('.from');
        var to = line.find('.to');
        var fromVal = from.children().val();
        var toVal = to.children().val();
        var oldFrom, oldTo;
        var data;
        oldFrom = from.attr('data-from') || false;
        oldTo = to.attr('data-to') || false;
        if (toVal == '' || fromVal == '') {
            alert('Заполнены не все поля!');
            return false;
        }
        if (fromVal == toVal) {
            alert('Бесконечный редирект самого на себя!');
            return false;
        }
        if (oldFrom && oldTo) {
            type = 'edit';
            data = type + '=1&from=' + fromVal + '&to=' + toVal + '&oldFrom=' + oldFrom + '&oldTo=' + oldTo
        } else {
            data = type + "=1&from=" + fromVal + "&to=" + toVal;

        }
        if (fromVal == from.attr('data-from') && toVal == to.attr('data-to')) {
            line.removeClass('editLine');
            from.html(fromVal);
            to.html(toVal);

            var butedit = line.find('.btn-success').removeClass('btn-success').addClass('btn-info');
            butedit.attr('onclick', 'editLine(' + e + ')');
            butedit.attr('title', 'Изменить');
            butedit.children().removeClass('icon-ok').addClass('icon-pencil');

            var cancelBtn = line.find('.btn-danger');
            cancelBtn.attr('onclick', 'delLine(' + e + ')');
            cancelBtn.attr('title', 'Удалить');
            cancelBtn.val(oldFrom);

            return true;
        }
        $.ajax({
            dataType: 'json',
            type: "POST",
            data: data,
            success: function (data) {
                if (data.error) {
                    $('#line' + data.line).css('background', 'lightcyan');
                    alert(data.text);
                    return false;
                } else {
                    line.removeClass('editLine');
                    from.html(fromVal);
                    from.attr('data-from', fromVal);
                    to.html(toVal);
                    to.attr('data-to', toVal);

                    var butedit = line.find('.btn-success').removeClass('btn-success').addClass('btn-info');
                    butedit.attr('onclick', 'editLine(' + e + ')');
                    butedit.attr('title', 'Изменить');
                    butedit.children().removeClass('icon-ok').addClass('icon-pencil');
                    line.removeClass();
                }
            }
        });
    }
</script>
