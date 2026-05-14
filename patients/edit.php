<?php

include("../config/db.php");

$encoded_id = $_POST['id'] ?? '';

$id = base64_decode($encoded_id);

if(empty($id)){

    header("Location:list.php");
    exit;

}

$stmt = $conn->prepare(
"SELECT * FROM patients WHERE patient_id=?"
);

$stmt->bind_param("i", $id);

$stmt->execute();

$result = $stmt->get_result();

$row = $result->fetch_assoc();

if(isset($_POST['update'])){

$name = trim($_POST['name'] ?? '');
$dob = trim($_POST['dob'] ?? '');
$join_date = trim($_POST['join_date'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');

$stmt = $conn->prepare(
"UPDATE patients
SET
name=?,
dob=?,
join_date=?,
phone=?,
address=?
WHERE patient_id=?"
);

$stmt->bind_param(
"sssssi",
$name,
$dob,
$join_date,
$phone,
$address,
$id
);

if($stmt->execute()){

    header("Location:list.php");
    exit;

}

}

include("../includes/header.php");

?>

<div class="card shadow p-4">

<h3 class="mb-4">
Edit Patient
</h3>

<form method="POST" action="">

<input
type="hidden"
name="id"
value="<?= base64_encode($id) ?>">

<div class="mb-3">

<label class="form-label">
Name
</label>

<input
type="text"
name="name"
class="form-control"
value="<?= htmlspecialchars($row['name']) ?>">

</div>

<div class="mb-3">

<label class="form-label">
DOB
</label>

<input
type="date"
name="dob"
class="form-control"
value="<?= $row['dob'] ?>">

</div>

<div class="mb-3">

<label class="form-label">
Join Date
</label>

<input
type="date"
name="join_date"
class="form-control"
value="<?= $row['join_date'] ?>">

</div>

<div class="mb-3">

<label class="form-label">
Phone
</label>

<input
type="text"
name="phone"
class="form-control"
value="<?= htmlspecialchars($row['phone']) ?>">

</div>

<div class="mb-3">

<label class="form-label">
Address
</label>

<textarea
name="address"
class="form-control"><?= htmlspecialchars($row['address']) ?></textarea>

</div>

<div class="d-flex gap-2">

<button
type="submit"
class="btn btn-primary"
name="update">

Update Patient

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