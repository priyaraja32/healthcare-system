<?php

include("../config/db.php");

$error = "";
$success = "";

if(isset($_POST['submit'])){

    $encoded_patient_id = trim($_POST['patient_id'] ?? '');

    $patient_id = base64_decode($encoded_patient_id);

    $visit_date = trim($_POST['visit_date'] ?? '');

    $consultation_fee = trim($_POST['consultation_fee'] ?? '');

    $lab_fee = trim($_POST['lab_fee'] ?? '');

    if(
        empty($patient_id) ||
        empty($visit_date)
    ){

        $error = "Required fields missing";

    }else{

        $stmt = $conn->prepare(
        "INSERT INTO visits
        (
        patient_id,
        visit_date,
        consultation_fee,
        lab_fee
        )
        VALUES(?,?,?,?)"
        );

        $stmt->bind_param(
        "isdd",
        $patient_id,
        $visit_date,
        $consultation_fee,
        $lab_fee
        );

        if($stmt->execute()){

            $new_visit_id = $conn->insert_id;

            $encoded_visit_id = base64_encode($new_visit_id);

            header(
            "Location:view.php?id=".$encoded_visit_id
            );

            exit;

        }else{

            $error = "Insert Failed";

        }

    }

}

$patients = mysqli_query(
$conn,
"SELECT * FROM patients ORDER BY name ASC"
);

include("../includes/header.php");

?>

<div class="card shadow p-4">

<h3 class="mb-4">
Add Visit
</h3>

<?php if($error){ ?>

<div class="alert alert-danger">
<?= $error ?>
</div>

<?php } ?>

<?php if($success){ ?>

<div class="alert alert-success">
<?= $success ?>
</div>

<?php } ?>

<form method="POST">

<div class="row">

<div class="col-md-6 mb-3">

<label class="form-label">
Patient
</label>

<select
name="patient_id"
class="form-control">

<option value="">
Select Patient
</option>

<?php while($p = mysqli_fetch_assoc($patients)){ ?>

<option
value="<?= base64_encode($p['patient_id']) ?>">

<?= htmlspecialchars($p['name']) ?>

</option>

<?php } ?>

</select>

</div>

<div class="col-md-6 mb-3">

<label class="form-label">
Visit Date
</label>

<input
type="date"
name="visit_date"
class="form-control">

</div>

<div class="col-md-6 mb-3">

<label class="form-label">
Consultation Fee
</label>

<input
type="number"
step="0.01"
name="consultation_fee"
class="form-control">

</div>

<div class="col-md-6 mb-3">

<label class="form-label">
Lab Fee
</label>

<input
type="number"
step="0.01"
name="lab_fee"
class="form-control">

</div>

</div>

<div class="d-flex gap-2">

<button
type="submit"
class="btn btn-primary"
name="submit">

Save Visit

</button>

<a
href="list.php"
class="btn btn-secondary">

Back

</a>

</div>

</form>

</div>

<?php include("../includes/footer.php"); ?>