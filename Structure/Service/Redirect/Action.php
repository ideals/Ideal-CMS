<table id="redirect" class="table table-hover table-striped">
    <tr>
        <th style="width: 249px">Откуда</th>
        <th style="width: 249px">Куда</th>
        <th style="text-align: right"></th>
    </tr>
    <?php
    $config = \Ideal\Core\Config::getInstance();
    $file = new \Ideal\Structure\Service\Redirect\RewriteRile();

    $file->loadFile($config->cmsFolder . '/redirect.txt');

    if (isset($_POST['edit'])) {
        $file->saveFile();
    }

    echo $file->showEdit();
    ?>
</table>

<br/>
<button type="button" class="btn btn-primary" value="<?php echo $file->getCountParam(); ?>" onclick="addLine(this)">
    Добавить редирект
</button>


<script>
    $('tr').on('onfocus','.editGroup', function(){
        alert('1');
        $(this).show();
        console.log(this);
    })
    function addLine(e) {
        $(e).attr("disabled", "disabled");
        var i = parseInt($(e).val());
        var from = $('[name=from' + (i - 1) + ']').val();
        var on = $('[name=on' + (i - 1) + ']').val();
        if (from !== '' && on !== '') {
            $('#redirect > tbody:last').append('<tr onfocus="aloha()" style="height: 41px" id="line' + i + '">'
                + '<td class="from"></td><td class="on"></td>'
                + '<td style="text-align: right;"><div class="hide editGroup"> '
                + '<span class="input-prepend">'
                + '<button style="width: 47px;" onclick="editLine(' + i + ')" title="Изменить" class="btn btn-info btn-mini">'
                + '<i class="icon-pencil icon-white"></i></button></span>'
                + '<span class="input-append"><button onclick="delLine(' + i + ')" title="Удалить" class="btn btn-danger btn-mini">'
                + '<i class="icon-remove icon-white"></i></button></span></div>'
                + '</td></tr>')
            $(e).val(i + 1);
        }
        $(e).removeAttr('disabled');
    }
    function aloha(){
        alert('aloha');
    }

    function delLine(e) {
        $('#line' + e).remove();
    }

    function editLine(e) {
        var line = $('#line' + e);
        var butedit = line.find('.btn-info').removeClass('btn-info').addClass('btn-success');
        butedit.attr('onclick', 'saveLine(' + e + ')');
        butedit.children().removeClass('icon-pencil').addClass('icon-ok');
        var from = line.find('.from');
        var on = line.find('.on');
        from.html('<input type="text" name="from" value="' + from.html() + '">');
        on.html('<input type="text" name="on" value="' + on.html() + '">');
    }

    function saveLine(e){
        var line = $('#line' + e);

        var from = line.find('.from');
        var on = line.find('.on');
        var fromVal = from.children().val();
        var onVal = on.children().val();
        if(onVal == '' || fromVal == ''){
            alert('Заполнены не все поля!');
            return false;
        }
        if(fromVal == onVal){
            alert('Бесцонечный редирект самого на себя!');
            return false;
        }
        $.ajax({
            type: "POST",
            data: "edit=1&from=" + fromVal + "&on=" + onVal
        });
        from.html(fromVal);
        on.html(onVal);

        var butedit = line.find('.btn-success').removeClass('btn-success').addClass('btn-info');
        butedit.attr('onclick', 'editLine(' + e + ')');
        butedit.children().removeClass('icon-ok').addClass('icon-pencil');
    }

</script>

