<?php
// @codingStandardsIgnoreFile
$fromTimestamp = time() - 2678400;
$toTimestamp = time();

// Получаем дату с которой формировать графики. По умолчанию 30 дней назад
$fromDate = date('d.m.Y', $fromTimestamp);

// Получаем дату до которой формировать графики. По умолчанию текущий день.
$toDate = date('d.m.Y');

// Собираем строку/js-массив для настройки отображения первого графика
$conversion = new Ideal\Structure\Service\Conversion\Model();
$visualConfig = $conversion->getOrdersInfo($fromTimestamp, $toTimestamp);
?>

<style>
    .no-border-radius {
        border-radius: 0;
    }

    .grouping {
        margin: 10px 0;
    }

    .first-label {
        margin: 0 5px 0 0;
    }
</style>
<link href="Ideal/Library/datetimepicker/build/css/bootstrap-datetimepicker.min.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="Ideal/Library/moment/moment.js"></script>
<script type="text/javascript" src="Ideal/Library/moment/locale/ru.js"></script>
<script type="text/javascript" src="Ideal/Library/datetimepicker/src/js/bootstrap-datetimepicker.js"></script>

<!--Load the AJAX API-->
<?php if (!empty($visualConfig)) { ?>
    <script type="text/javascript" src="https://www.google.com/jsapi"></script>
    <script type="text/javascript">
        google.load('visualization', '1.0', {'packages': ['corechart']});
        google.setOnLoadCallback(drawChart);

        function drawChart() {

            var data = google.visualization.arrayToDataTable(<?php print $visualConfig; ?>);

            var options_stacked = {
                title: "Общее кол-во заказов за определённый срок",
                isStacked: true,
                height: 300,
                legend: {position: 'top', maxLines: 3},
                vAxis: {minValue: 0}
            };

            var chart = new google.visualization.ColumnChart(document.getElementById('graphContent'));
            chart.draw(data, options_stacked);
        }
    </script>
<?php } ?>

<form action="" id="graphSettings">
    <div>
        <div class="input-group date">
            <label>Выберите период c:</label>
            <span id="fromWrapper">
                <span class="btn btn-default btn-sm">
                    <span class="glyphicon glyphicon-calendar"></span>
                </span>
                <input id="fromDate" type="text" name="fromDate" value="<?php echo $fromDate ?>"
                       class="input-sm no-border-radius"/>
            </span>
            <label>по:</label>
            <span id="toWrapper">
                <span class="btn btn-default btn-sm">
                    <span class="glyphicon glyphicon-calendar"></span>
                </span>
                <input id="toDate" type="text" name="toDate" value="<?php echo $toDate; ?>"
                       class="input-sm no-border-radius"/>
            </span>
        </div>
    </div>

    <div class="input-group grouping">
        <label class="first-label">Сгруппировать по:</label>
        <label class="radio-inline"><input type="radio" name="grouping" value="day" checked/>дням</label>
        <label class="radio-inline"><input type="radio" name="grouping" value="week"/>неделям</label>
        <label class="radio-inline"><input type="radio" name="grouping" value="month"/>месяцам</label>
    </div>

    <input type="button" value="Перестроить графики" class="btn btn-primary btn-large"/>
</form>

<div id="graphContent">
</div>

<script type="text/javascript">
    $(function () {
        $('#fromWrapper').datetimepicker({
            pickTime: false
        });
        $('#toWrapper').datetimepicker({
            pickTime: false
        });
    });
</script>