<?php
// @codingStandardsIgnoreFile
$interval = 'day';
parse_str($_SERVER['QUERY_STRING'], $queryString);
if (isset($queryString['fromDate'])) {
    $fromTimestamp = strtotime(str_replace('.', '-', $queryString['fromDate']));
    unset($queryString['fromDate']);
} else {
    $fromTimestamp = time() - 2678400;
}
if (isset($queryString['toDate'])) {
    $toTimestamp = strtotime(str_replace('.', '-', $queryString['toDate']));
    unset($queryString['toDate']);
    if ($toTimestamp > time()) {
        $toTimestamp = time();
    }
} else {
    $toTimestamp = time();
}
if (isset($queryString['grouping'])) {
    $interval = $queryString['grouping'];
    unset($queryString['grouping']);
}

// Получаем дату с которой формировать графики. По умолчанию 30 дней назад
$fromDate = date('d.m.Y', $fromTimestamp);

// Получаем дату до которой формировать графики. По умолчанию текущий день.
$toDate = date('d.m.Y', $toTimestamp);

// Собираем строку/js-массив для настройки отображения первого графика
$conversion = new Ideal\Structure\Service\Conversion\Model();
$visualConfig = $conversion->getOrdersInfo($fromTimestamp, $toTimestamp, $interval);
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

            <?php if (isset($visualConfig['quantityOfOrders']) && !empty($visualConfig['quantityOfOrders'])) { ?>
            var quantityOfOrdersData = google.visualization.arrayToDataTable(<?php print $visualConfig['quantityOfOrders']; ?>);

            var quantityOfOrdersOptions = {
                title: "Общее кол-во заказов за определённый срок",
                isStacked: true,
                height: 300,
                legend: {position: 'top', maxLines: 3},
                vAxis: {minValue: 0}
            };

            var quantityOfOrdersChart = new google.visualization.ColumnChart(document.getElementById('quantityOfOrdersChart'));
            quantityOfOrdersChart.draw(quantityOfOrdersData, quantityOfOrdersOptions);
            <?php } ?>

            <?php if (isset($visualConfig['referer']) && !empty($visualConfig['referer'])) { ?>
            var refererChartData = google.visualization.arrayToDataTable(<?php print $visualConfig['referer']; ?>);
            var refererChartOptions = {
                title: 'Распределение заказов по источникам переходов'
            };

            $('#refererChartTab').addClass('active');
            var refererChart = new google.visualization.PieChart(document.getElementById('refererChart'));
            refererChart.draw(refererChartData, refererChartOptions);
            $('#refererChartTab').removeClass('active');
            <?php
            }?>

            <?php if (isset($visualConfig['orderType']) && !empty($visualConfig['orderType'])) { ?>
            var orderTypeChartData = google.visualization.arrayToDataTable(<?php print $visualConfig['orderType']; ?>);
            var orderTypeChartOptions = {
                title: 'Распределение заказов по видам'
            };

            $('#orderTypeChartTab').addClass('active');
            var orderTypeChart = new google.visualization.PieChart(document.getElementById('orderTypeChart'));
            orderTypeChart.draw(orderTypeChartData, orderTypeChartOptions);
            $('#orderTypeChartTab').removeClass('active');
            <?php
            }?>

            <?php if (isset($visualConfig['sumOfOrder']) && !empty($visualConfig['sumOfOrder'])) { ?>
            var sumOfOrdersData = google.visualization.arrayToDataTable(<?php print $visualConfig['sumOfOrder']; ?>);
            var sumOfOrdersDataOptions = {
                height: 300,
                title: 'Сумма заказов'
            };

            $('#sumOfOrdersChartTab').addClass('active');
            var sumOfOrdersDataChart = new google.visualization.ColumnChart(document.getElementById('sumOfOrdersChart'));
            sumOfOrdersDataChart.draw(sumOfOrdersData, sumOfOrdersDataOptions);
            $('#sumOfOrdersChartTab').removeClass('active');
            <?php
            }?>
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
        <label class="radio-inline"><input type="radio" name="grouping" value="day" <?php if ($interval == 'day') { echo 'checked'; } ?> />дням</label>
        <label class="radio-inline"><input type="radio" name="grouping" value="week" <?php if ($interval == 'week') { echo 'checked'; } ?>/>неделям</label>
        <label class="radio-inline"><input type="radio" name="grouping" value="month" <?php if ($interval == 'month') { echo 'checked'; } ?>/>месяцам</label>
    </div>
    <?php
     if (count($queryString) != 0) {
         foreach($queryString as $key => $value) {
             echo '<input type="hidden" value="'.$value.'" name="'.$key.'"/>';
         }
     }
     ?>
    <input type="submit" value="Перестроить графики" class="btn btn-primary btn-large"/>
</form>

<br/>
<br/>
<br/>

<ul class="nav nav-tabs">
    <li class="active"><a href="#quantityOfOrdersChartTab" data-toggle="tab">Общее кол-во</a></li>
    <li><a href="#refererChartTab" data-toggle="tab">Источники переходов</a></li>
    <li><a href="#orderTypeChartTab" data-toggle="tab">Виды заказов</a></li>
    <li><a href="#sumOfOrdersChartTab" data-toggle="tab">Сумма заказов</a></li>
</ul>

<div class="tab-content">
    <div class="tab-pane active" id="quantityOfOrdersChartTab">
        <div id="quantityOfOrdersChart"></div>
    </div>
    <div class="tab-pane" id="refererChartTab">
        <div id="refererChart"></div>
    </div>
    <div class="tab-pane" id="orderTypeChartTab">
        <div id="orderTypeChart"></div>
    </div>
    <div class="tab-pane" id="sumOfOrdersChartTab">
        <div id="sumOfOrdersChart" style="width: 600px;"></div>
    </div>
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