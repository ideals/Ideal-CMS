<form action="" method=post enctype="multipart/form-data">

    <table id="redirect" class="table table-hover">
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
    <button type="submit" class="btn btn-info" name="edit" value="1">Сохранить настройки</button>
</form>

<script>
    function addLine(e) {
        $(e).attr("disabled", "disabled");
        var i = parseInt($(e).val());
        var from = $('[name=from' + (i - 1) + ']').val();
        var on = $('[name=on' + (i - 1) + ']').val();
        if (from !== '' && on !== '') {
            $('#redirect > tbody:last').append('<tr>'
                + '<td><input class="input span3" type="text" name="rule[' + i + '][from]"></td>'
                + '<td><input class="input span3" type="text" name="rule[' + i + '][on]"></td>'
                + '<td><input class="input span2" type="text" name="rule[' + i + '][rule]" value="[R=301,L]"></td>'
                + '<td><button onclick="delLine(this)" type="button" class="btn btn-danger" style="font-size:22px">&times</button></td>'
                + '</tr>');
            $(e).val(i + 1);
        }
        $(e).removeAttr('disabled');
    }

    function delLine(e) {
        $(e).parent().parent().remove();

    }
</script>
