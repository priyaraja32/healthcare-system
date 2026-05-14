<?php

include("../config/db.php");

$encoded_id = $_POST['id'] ?? '';

$id = base64_decode($encoded_id);

if(empty($id)){

    header("Location:list.php");
    exit;

}

$sql = "
SELECT

p.*,

TIMESTAMPDIFF(
YEAR,
p.dob,
CURDATE()
) AS age,

COUNT(v.visit_id) AS total_visits,

MAX(v.visit_date) AS last_visit,

DATEDIFF(
CURDATE(),
MAX(v.visit_date)
) AS days_since_last_visit,

MAX(v.follow_up_due) AS next_followup,

CASE
WHEN MAX(v.follow_up_due) < CURDATE()
THEN 'Overdue'
ELSE 'Upcoming'
END AS followup_status

FROM patients p

LEFT JOIN visits v
ON p.patient_id = v.patient_id

WHERE p.patient_id = ?

GROUP BY p.patient_id
";

$stmt = $conn->prepare($sql);

$stmt->bind_param("i", $id);

$stmt->execute();

$result = $stmt->get_result();

$row = $result->fetch_assoc();

include("../includes/header.php");

?>

<div class="card shadow p-4">

<h3>
<?= htmlspecialchars($row['name']) ?>
</h3>

<hr>

<p>

<b>Age:</b>

<?= $row['age'] ?>

</p>

<p>

<b>Total Visits:</b>

<?= $row['total_visits'] ?>

</p>

<p>

<b>Last Visit:</b>

<?= $row['last_visit'] ?>

</p>

<p>

<b>Days Since Last Visit:</b>

<?= $row['days_since_last_visit'] ?>

</p>

<p>

<b>Next Follow-Up:</b>

<?= $row['next_followup'] ?>

</p>

<p>

<b>Status:</b>

<?php if($row['followup_status'] == "Overdue"){ ?>

<span class="badge bg-danger">

<?= $row['followup_status'] ?>

</span>

<?php } else { ?>

<span class="badge bg-success">

<?= $row['followup_status'] ?>

</span>

<?php } ?>

</p>

<!-- VISIT HISTORY -->

<form
action="../visits/patient_visits.php"
method="POST">

<input
type="hidden"
name="id"
value="<?= base64_encode($row['patient_id']) ?>">

<button
type="submit"
class="btn btn-primary w-100">

Visit History

</button>

</form>

<br>

<!-- BACK -->

<a
href="list.php"
class="btn btn-secondary w-100">

Back

</a>

</div>

<?php include("../includes/footer.php"); ?>