<?php
namespace Ideal\Structure\Service\Redirect;
$file = new RewriteRile();
$file->LoadFile();

if (isset($_POST['add'])) {
    $file->addLine();
    exit;
}
if (isset($_POST['edit'])) {
    $file->editLine();
    exit;
}
if (isset($_POST['delete'])) {
    $file->deleteLine();
    exit;
}
$table = $file->getTable();
echo $file->getMsg();
if ($file->getError() < 1) {
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
    <button type="button" class="btn btn-primary" value="<?php echo $file->getCountParam(); ?>" onclick="addLine(this)">
        Добавить редирект
    </button>

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


    <script>
        function addLine(e) {
            $(e).attr("disabled", "disabled");
            var i = parseInt($(e).val()) + 1;
            var from = $('#line' + (i - 1) + ' > .from').children().val();
            var on = $('#line' + (i - 1) + ' > .on').children().val();
            if (from !== '' && on !== '') {
                $('#redirect > tbody:last').append('<tr id="line' + i + '">'
                    + '<td class="from"><input type="text" name="from"></td>'
                    + '<td class="on"><input type="text" name="on"></td>'
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

        function delLine(e) {
            var line = $('#line' + e);
            var from = line.find('.from');
            var on = line.find('.on');
            var fromVal = from.children().val();
            if (fromVal === undefined) {
                fromVal = from.html();
            }
            var onVal = on.children().val();
            if (onVal === undefined) {
                onVal = on.html();
            }

            if (fromVal === '' && onVal === '') {
                $('#line' + e).remove();
                return true;
            }
            if (!confirm('Удалить?')) {
                return false;
            }
            $.ajax({
                type: "POST",
                data: "delete=1&from=" + fromVal + "&on=" + onVal,
                success: function (data) {
                    if (data.error) {

                    } else {
                        $('#line' + e).remove();
                    }
                }

            });
        }

        function editLine(e) {
            var line = $('#line' + e);
            line.addClass('editLine');
            var butedit = line.find('.btn-info').removeClass('btn-info').addClass('btn-success');
            butedit.attr('onclick', 'saveLine(' + e + ')');
            butedit.attr('title', 'Сохранить');
            butedit.children().removeClass('icon-pencil').addClass('icon-ok');
            line.find('.btn-danger').val(line.children('.from').text());
            var from = line.find('.from');
            var on = line.find('.on');
            from.html('<input type="text" name="from" value="' + from.html() + '">');
            on.html('<input type="text" name="on" value="' + on.html() + '">');
        }

        function saveLine(e) {
            var type = 'add';
            var line = $('#line' + e);
            var from = line.find('.from');
            var on = line.find('.on');
            var fromVal = from.children().val();
            var onVal = on.children().val();
            var oldFrom, oldOn;
            var data;
            oldFrom = from.attr('data-from') || false;
            oldOn = on.attr('data-on') || false;
            if (onVal == '' || fromVal == '') {
                alert('Заполнены не все поля!');
                return false;
            }
            if (fromVal == onVal) {
                alert('Бесконечный редирект самого на себя!');
                return false;
            }
            if (oldFrom && oldOn) {
                type = 'edit';
                data = type + '=1&from=' + fromVal + '&on=' + onVal + '&oldFrom=' + oldFrom + '&oldOn=' + onVal
            } else {
                data = type + "=1&from=" + fromVal + "&on=" + onVal;

            }
            if (fromVal == from.attr('data-from') && onVal == on.attr('data-on')) {
                line.removeClass('editLine');
                from.html(fromVal);
                on.html(onVal);

                var butedit = line.find('.btn-success').removeClass('btn-success').addClass('btn-info');
                butedit.attr('onclick', 'editLine(' + e + ')');
                butedit.attr('title', 'Изменить');
                butedit.children().removeClass('icon-ok').addClass('icon-pencil');
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
                        on.html(onVal);
                        on.attr('data-on', fromVal);

                        var butedit = line.find('.btn-success').removeClass('btn-success').addClass('btn-info');
                        butedit.attr('onclick', 'editLine(' + e + ')');
                        butedit.attr('title', 'Изменить');
                        butedit.children().removeClass('icon-ok').addClass('icon-pencil');
                        line.removeClass();
                    }
                }
            });
        }

        function ckeck(data) {
        }

        function scrollToElement(theElement) {
            if (typeof theElement === "string") theElement = document.getElementById(theElement);

            var selectedPosX = 0;
            var selectedPosY = 0;

            while (theElement != null) {
                selectedPosX += theElement.offsetLeft;
                selectedPosY += theElement.offsetTop;
                theElement = theElement.offsetParent;
            }

            window.scrollTo(selectedPosX, selectedPosY);
        }


    </script>
<?php
}
