<?php
namespace Ideal\Structure\Service\ACL;

// Получаем всех пользователей системы для управления их провами
$users = Model::getAllUsers();
echo '<select id="selectUser">';
echo '<option value="0" disabled selected>Выберите пользователя</option>';
foreach ($users as $user) {
    echo '<option value="' . $user['ID'] . '">' . $user['email'] . '</option>';
}
echo '</select>';
?>
<div class="table-responsive" id="permission" style="display: none">
    <table class="table table-striped">
        <thead>
        <tr>
            <th>Название</th>
            <th>Скрыть</th>
            <th>Не менять</th>
            <th>Не удалять</th>
            <th>Не входить</th>
            <th>Не менять детей</th>
            <th>Не удалять детей</th>
            <th>Не входить в детей</th>
        </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>
<script type="text/javascript">
    $(function () {
        // Отлавливаем событие смены пользователя
        $('#selectUser').change(function () {
            $.ajax({
                type: "POST",
                data: {user_id: $(this).val()},
                dataType: 'json',
                url: '/?mode=ajax&controller=Ideal\\Structure\\Service\\ACL&action=mainUserPermission',
                success: function (data) {
                    var trs = '';
                    $('#permission:hidden').show();
                    $.each(data, function (index, value) {
                        var show = value.show == 0 ? 'checked="checked"' : '';
                        var edit = value.edit == 0 ? 'checked="checked"' : '';
                        var deletevar = value.delete == 0 ? 'checked="checked"' : '';
                        var enter = value.enter == 0 ? 'checked="checked"' : '';
                        var edit_children = value.edit_children == 0 ? 'checked="checked"' : '';
                        var delete_children = value.delete_children == 0 ? 'checked="checked"' : '';
                        var enter_children = value.enter_children == 0 ? 'checked="checked"' : '';
                        trs += ' \
                            <tr id="' + index + '">\
                            <td>' + value.name + '</td>\
                            <td><input type="checkbox" data-target="show" ' + show + '></td>\
                            <td><input type="checkbox" data-target="edit" ' + edit + '></td>\
                            <td><input type="checkbox" data-target="delete" ' + deletevar + '></td>\
                            <td><input type="checkbox" data-target="enter" ' + enter + '></td>\
                            <td><input type="checkbox" data-target="edit_children" ' + edit_children + '></td>\
                            <td><input type="checkbox" data-target="delete_children" ' + delete_children + '></td>\
                            <td><input type="checkbox" data-target="enter_children" ' + enter_children + '></td>\
                            </tr>';
                    });
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
                    structure: $(this).closest('tr').attr('id'),
                    is: $(this).is(':checked') ? 0 : 1,
                    user_id: $('#selectUser').val()
                },
                dataType: 'json',
                url: '/?mode=ajax&controller=Ideal\\Structure\\Service\\ACL&action=changePermission'
            });
        });
    });
</script>