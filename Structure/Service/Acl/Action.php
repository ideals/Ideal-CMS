<?php
namespace Ideal\Structure\Service\Acl;

// Получаем все группы пользователей системы для которых есть возможность менять права
$userGroups = \Ideal\Structure\Acl\Admin\Model::getAllUserGroups();
?>
<form class="form-horizontal">
    <div class="form-group">
        <label for="selectUser" class="col-sm-2 control-label">Группа пользователя</label>
        <div class="col-sm-10">
            <select id="selectUserGroup" class="form-control">
                <option value="0" disabled selected>Выберите группу пользователя</option>
                <?php
                // Формируем список пользователей для выбора
                foreach ($userGroups as $userGroup) {
                    echo '<option value="' . $userGroup['ID'] . '">' . $userGroup['name'] . '</option>';
                }
                ?>
            </select>
        </div>
    </div>
</form>
<div class="table-responsive" id="permission" style="display: none">
    <table class="table table-striped">
        <thead>
        <tr>
            <th>Название</th>
            <th>Скрыть</th>
            <th>Не менять</th>
            <th>Не удалять</th>
            <th>Не входить</th>
        </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
<script type="text/javascript">
    $(function () {
        // Отлавливаем событие смены группы пользователя
        $('#selectUserGroup').change(function () {
            $.ajax({
                type: "POST",
                data: {user_group_id: $(this).val()},
                dataType: 'json',
                url: '/?mode=ajax&controller=Ideal\\Structure\\Service\\Acl&action=mainUserGroupPermission',
                success: function (data) {
                    $('#permission:hidden').show();
                    var trs = getTableRows(data);
                    $('#permission tbody').html(trs);
                }
            })
        });

        // Ловим клики на чекбоксах и заносим данные в таблицу
        $('#permission tbody').on('change', 'input:checkbox', function () {
            $.ajax({
                type: "POST",
                data: {
                    target: $(this).data('target'),
                    structure: $(this).closest('tr').data('seid'),
                    is: $(this).is(':checked') ? 0 : 1,
                    user_group_id: $('#selectUserGroup').val()
                },
                dataType: 'json',
                url: '/?mode=ajax&controller=Ideal\\Structure\\Service\\Acl&action=changePermission'
            });
        });

        // Ловим клики на основных пунктах, чтобы показать/скрыть вложенные
        $('#permission tbody').on('click', 'a', function () {
            var closestTr = $(this).closest('tr');
            var startId = $(closestTr).attr('id');

            // Ищем количество элементов удовлетворяющих маске
            var searchTr = $('[id^="' + startId + '-"]');

            // Если дочерние элементы пункта открыты, то закрываем их
            if (searchTr.length > 0) {
                $.each(searchTr, function () {
                    $(this).remove();
                });
            } else { // Иначе открываем дочерние элементы
                // Ищем пробелы, для подсчёта уровня вложенности и его отрисовки
                var lvl = $(this).closest('td').children('span').length;
                $.ajax({
                    type: "POST",
                    data: {
                        structure: $(closestTr).data('seid'),
                        user_group_id: $('#selectUserGroup').val(),
                        prev_structure: $(closestTr).data('prev_structure')
                    },
                    dataType: 'json',
                    url: '/?mode=ajax&controller=Ideal\\Structure\\Service\\Acl&action=showChildren',
                    success: function (data) {
                        if (!$.isEmptyObject(data)) {
                            // Формируем дополнительныен пробелы
                            var spaces = '<span class="space">&nbsp;&nbsp;</span>'.repeat(lvl);
                            var trs = getTableRows(data, spaces, startId);
                            $(closestTr).after(trs);
                        } else {
                            alert('Данный пункт не имееет дочерних элементов');
                        }
                    }
                });
            }
            return false;
        });

        // Возвращает строки таблицы для отрисовки на странице
        function getTableRows(data, spaces, startId) {
            spaces = typeof spaces !== 'undefined' ? spaces + '<span>|-</span>' : '';
            startId = typeof startId !== 'undefined' ? startId + '-' : '';
            var trs = '';
            $.each(data, function (index, value) {
                var additionalId = index.split('-');
                if (additionalId[1] == 0) {
                    additionalId = additionalId[0]
                } else {
                    additionalId = additionalId[1]
                }
                var show = value.show == 0 ? 'checked="checked"' : '';
                var edit = value.edit == 0 ? 'checked="checked"' : '';
                var deletevar = value.delete == 0 ? 'checked="checked"' : '';
                var enter = value.enter == 0 ? 'checked="checked"' : '';
                trs += ' \
                            <tr id = "' + startId + additionalId + '" ' +
                    '               data-prev_structure="' + value.prev_structure + '" data-seid="' + index + '">\
                            <td>' + spaces + '<a href="">' + value.name + '</a></td>\
                            <td><input type="checkbox" data-target="show" ' + show + '></td>\
                            <td><input type="checkbox" data-target="edit" ' + edit + '></td>\
                            <td><input type="checkbox" data-target="delete" ' + deletevar + '></td>\
                            <td><input type="checkbox" data-target="enter" ' + enter + '></td>\
                            </tr>';
            });
            return trs;
        }

        String.prototype.repeat = function (num) {
            return new Array(num + 1).join(this);
        }
    });
</script>
