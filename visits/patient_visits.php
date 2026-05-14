<?php

include("../config/db.php");

$encoded_id = $_POST['id'] ?? '';

$id = base64_decode($encoded_id);

if(empty($id)){

    header("Location:../patients/list.php");
    exit;

}

$sql = "
SELECT

p.name,

COUNT(v.visit_id) AS total_visits,

MIN(v.visit_date) AS first_visit,

MAX(v.visit_date) AS last_visit,

DATEDIFF(
MAX(v.visit_date),
MIN(v.visit_date)
) AS total_days_between

FROM patients p

LEFT JOIN visits v
ON p.patient_id = v.patient_id

WHERE p.patient_id = ?

GROUP BY p.patient_id
";

$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $id);

$stmt->execute();

$summary = $stmt->get_result();

$summaryRow = $summary->fetch_assoc();

$visitSql = "
SELECT

visit_id,

visit_date,

consultation_fee,

lab_fee,

follow_up_due,

DATEDIFF(
CURDATE(),
visit_date
) AS days_since_visit

FROM visits

WHERE patient_id = ?

ORDER BY visit_date DESC
";

$stmt2 = $conn->prepare($visitSql);

$stmt2->bind_param("i", $id);

$stmt2->execute();

$visits = $stmt2->get_result();

include("../includes/header.php");

?>

<div class="card shadow p-4 mb-4">

<h3>
<?= htmlspecialchars($summaryRow['name']) ?> Visit History
</h3>

<hr>

<div class="row">

<div class="col-md-3">

<div class="alert alert-primary">

<h6>Total Visits</h6>

<h4>
<?= $summaryRow['total_visits'] ?>
</h4>

</div>

</div>

<div class="col-md-3">

<div class="alert alert-success">

<h6>First Visit</h6>

<h5>
<?= $summaryRow['first_visit'] ?>
</h5>

</div>

</div>

<div class="col-md-3">

<div class="alert alert-warning">

<h6>Last Visit</h6>

<h5>
<?= $summaryRow['last_visit'] ?>
</h5>

</div>

</div>

<div class="col-md-3">

<div class="alert alert-danger">

<h6>Days Between</h6>

<h4>
<?= $summaryRow['total_days_between'] ?>
</h4>

</div>

</div>

</div>

</div>

<div class="card shadow p-4">

<h4 class="mb-3">
Visit Records
</h4>

<div class="table-responsive">

<table class="table table-bordered table-hover">

<tr class="table-primary">

<th>Visit Date</th>
<th>Consultation Fee</th>
<th>Lab Fee</th>
<th>Follow-Up Date</th>
<th>Days Since Visit</th>
<th>Action</th>

</tr>

<?php if($visits->num_rows > 0){ ?>

<?php while($row = $visits->fetch_assoc()){ ?>

<?php
$encoded_visit_id =
base64_encode($row['visit_id']);
?>

<tr>

<td>
<?= $row['visit_date'] ?>
</td>

<td>
₹ <?= $row['consultation_fee'] ?>
</td>

<td>
₹ <?= $row['lab_fee'] ?>
</td>

<td>
<?= $row['follow_up_due'] ?>
</td>

<td>
<?= $row['days_since_visit'] ?>
</td>

<td>

<div class="d-flex gap-2 flex-wrap">

<!-- VIEW -->

<form
action="view.php"
method="POST">

<input
type="hidden"
name="id"
value="<?= $encoded_visit_id ?>">

<button
type="submit"
class="btn btn-primary btn-sm">

View

</button>

</form>

<!-- EDIT -->

<form
action="edit.php"
method="POST">

<input
type="hidden"
name="id"
value="<?= $encoded_visit_id ?>">

<button
type="submit"
class="btn btn-warning btn-sm">

Edit

</button>

</form>

<!-- DELETE -->

<form
action="delete.php"
method="POST"
onsubmit="return confirm('Delete this visit?')">

<input
type="hidden"
name="id"
value="<?= $encoded_visit_id ?>">

<button
type="submit"
class="btn btn-danger btn-sm">

Delete

</button>

</form>

</div>

</td>

</tr>

<?php } ?>

<?php } else { ?>

<tr>

<td colspan="6" class="text-center text-danger">

No Visit Records Found

</td>

</tr>

<?php } ?>

</table>

</div>

<!-- BACK BUTTON -->

<form
action="../patients/view.php"
method="POST">

<input
type="hidden"
name="id"
value="<?= base64_encode($id) ?>">

<button
type="submit"
class="btn btn-secondary">

Back

</button>

</form>

</div>

<?php include("../includes/footer.php"); ?>