<?php

include("../config/db.php");
include("../includes/header.php");

$totalPatientsQuery =
mysqli_query(
$conn,
"SELECT COUNT(*) AS total_patients
FROM patients"
);

$totalPatients =
mysqli_fetch_assoc(
$totalPatientsQuery
);

$totalVisitsQuery =
mysqli_query(
$conn,
"SELECT COUNT(*) AS total_visits
FROM visits"
);

$totalVisits =
mysqli_fetch_assoc(
$totalVisitsQuery
);

$overdueQuery =
mysqli_query(
$conn,
"
SELECT COUNT(*) AS overdue

FROM visits

WHERE follow_up_due < CURDATE()
"
);

$overdue =
mysqli_fetch_assoc(
$overdueQuery
);

$summarySql = "
SELECT

p.name,

TIMESTAMPDIFF(
YEAR,
p.dob,
CURDATE()
) AS age,

COUNT(v.visit_id)
AS total_visits,

MAX(v.visit_date)
AS last_visit,

DATEDIFF(
CURDATE(),
MAX(v.visit_date)
) AS days_since_last_visit,

MAX(v.follow_up_due)
AS next_followup

FROM patients p

LEFT JOIN visits v
ON p.patient_id=v.patient_id

GROUP BY p.patient_id

ORDER BY p.patient_id DESC
";

$summaryResult =
mysqli_query($conn,$summarySql);

?>

<div class="row mb-4">

<div class="col-md-4">

<div class="dashboard-card bg-blue shadow">

<h5>Total Patients</h5>

<h2>
<?= $totalPatients['total_patients'] ?>
</h2>

</div>

</div>

<div class="col-md-4">

<div class="dashboard-card bg-green shadow">

<h5>Total Visits</h5>

<h2>
<?= $totalVisits['total_visits'] ?>
</h2>

</div>

</div>

<div class="col-md-4">

<div class="dashboard-card bg-red shadow">

<h5>Overdue Followups</h5>

<h2>
<?= $overdue['overdue'] ?>
</h2>

</div>

</div>

</div>

<div class="card shadow p-4">

<h3 class="mb-4">
Patient Summary Report
</h3>

<table class="table table-bordered table-hover">

<tr class="table-primary">

<th>Name</th>
<th>Age</th>
<th>Total Visits</th>
<th>Last Visit</th>
<th>Days Since Visit</th>
<th>Next Follow-Up</th>

</tr>

<?php while($row =
mysqli_fetch_assoc($summaryResult)){ ?>

<tr>

<td>
<?= $row['name'] ?>
</td>

<td>
<?= $row['age'] ?>
</td>

<td>
<?= $row['total_visits'] ?>
</td>

<td>
<?= $row['last_visit'] ?>
</td>

<td>
<?= $row['days_since_last_visit'] ?>
</td>

<td>
<?= $row['next_followup'] ?>
</td>

</tr>

<?php } ?>

</table>

</div>

<div class="mt-4">

<a
href="followups.php"
class="btn btn-primary">

Follow-Up Report

</a>

<a
href="monthly.php"
class="btn btn-success">

Monthly Report

</a>

<a
href="birthdays.php"
class="btn btn-warning">

Birthday Report

</a>

</div>

<?php include("../includes/footer.php"); ?>