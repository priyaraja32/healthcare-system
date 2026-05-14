<?php

include("../config/db.php");

$error = "";
$success = "";

if(isset($_POST['submit'])){

$name = trim($_POST['name'] ?? '');
$dob = trim($_POST['dob'] ?? '');
$join_date = trim($_POST['join_date'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');

if(
empty($name) ||
empty($dob) ||
empty($join_date)
){

    $error = "Required fields missing";

}else{

$sql = "
SELECT
CASE
WHEN ? > CURDATE()
THEN 'invalid'
ELSE 'valid'
END AS dob_status
";

$stmt = $conn->prepare($sql);

$stmt->bind_param("s", $dob);

$stmt->execute();

$result = $stmt->get_result();

$check = $result->fetch_assoc();

if($check['dob_status'] == 'invalid'){

    $error = "DOB cannot be future date";

}else{

$stmt = $conn->prepare(
"INSERT INTO patients
(name,dob,join_date,phone,address)
VALUES(?,?,?,?,?)"
);

$stmt->bind_param(
"sssss",
$name,
$dob,
$join_date,
$phone,
$address
);

if($stmt->execute()){

    $new_id = $conn->insert_id;

    $encoded_id = base64_encode($new_id);

    header(
    "Location:view.php?id=".$encoded_id
    );

    exit;

}else{

    $error = "Insert Failed";

}

}

}

}

include("../includes/header.php");

?>

<div class="card shadow p-4">

<h3 class="mb-4">
Add Patient
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

<form method="POST" action="">

<div class="row">

<div class="col-md-6 mb-3">

<label class="form-label">
Name
</label>

<input
type="text"
name="name"
class="form-control"
value="<?= htmlspecialchars($name ?? '') ?>">

</div>

<div class="col-md-3 mb-3">

<label class="form-label">
DOB
</label>

<input
type="date"
name="dob"
class="form-control"
value="<?= htmlspecialchars($dob ?? '') ?>">

</div>

<div class="col-md-3 mb-3">

<label class="form-label">
Join Date
</label>

<input
type="date"
name="join_date"
class="form-control"
value="<?= htmlspecialchars($join_date ?? '') ?>">

</div>

<div class="col-md-6 mb-3">

<label class="form-label">
Phone
</label>

<input
type="text"
name="phone"
class="form-control"
value="<?= htmlspecialchars($phone ?? '') ?>">

</div>

<div class="col-md-6 mb-3">

<label class="form-label">
Address
</label>

<textarea
name="address"
class="form-control"><?= htmlspecialchars($address ?? '') ?></textarea>

</div>

</div>

<div class="d-flex gap-2">

<button
type="submit"
class="btn btn-primary"
name="submit">

Save Patient

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