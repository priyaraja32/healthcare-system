<?php

include("../config/db.php");
include("../includes/header.php");

$sql = "
SELECT

DATE_FORMAT(
visit_date,
'%Y-%m'
) AS visit_month,

COUNT(*) AS total_visits,

SUM(
consultation_fee + lab_fee
) AS total_amount

FROM visits

GROUP BY visit_month

ORDER BY visit_month ASC
";

$result = mysqli_query($conn,$sql);

$months = [];
$totals = [];
$data = [];

while($row = mysqli_fetch_assoc($result)){

    $months[] = $row['visit_month'];

    $totals[] = $row['total_visits'];

    $data[] = $row;

}

?>

<div class="card shadow p-4">

<h3 class="mb-4">
Monthly Report
</h3>

<table class="table table-bordered table-hover">

<tr class="table-primary">

<th>Month</th>
<th>Total Visits</th>
<th>Total Revenue</th>

</tr>

<?php foreach($data as $row){ ?>

<tr>

<td>
<?= $row['visit_month'] ?>
</td>

<td>
<?= $row['total_visits'] ?>
</td>

<td>
₹ <?= $row['total_amount'] ?>
</td>

</tr>

<?php } ?>

</table>

<hr>

<canvas id="visitChart"></canvas>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script src="../assets/js/chart.js"></script>

<script>

const months =
<?= json_encode($months) ?>;

const totals =
<?= json_encode($totals) ?>;

loadBarChart(
"visitChart",
months,
totals,
"Monthly Visits"
);

</script>

<?php include("../includes/footer.php"); ?>