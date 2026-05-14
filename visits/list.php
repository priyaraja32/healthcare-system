<?php

include("../config/db.php");
include("../includes/header.php");

$search = $_GET['search'] ?? '';

$page = isset($_GET['page'])
? (int)$_GET['page']
: 1;

if($page < 1){
    $page = 1;
}

$limit = 5;

$offset = ($page - 1) * $limit;

$searchTerm = "%$search%";

/*
-----------------------------------
TOTAL RECORD COUNT
-----------------------------------
*/

$totalSql = "
SELECT COUNT(*) AS total

FROM visits v

JOIN patients p
ON p.patient_id = v.patient_id

WHERE p.name LIKE ?
";

$stmtTotal = $conn->prepare($totalSql);

$stmtTotal->bind_param(
"s",
$searchTerm
);

$stmtTotal->execute();

$totalResult = $stmtTotal->get_result();

$totalRow = $totalResult->fetch_assoc();

$totalRecords = $totalRow['total'];

$totalPages = ceil($totalRecords / $limit);

/*
-----------------------------------
MAIN QUERY
-----------------------------------
*/

$sql = "
SELECT

v.*,

p.name,

DATEDIFF(
CURDATE(),
v.visit_date
) AS days_since_visit,

CASE

WHEN v.follow_up_due < CURDATE()
THEN 'Overdue'

WHEN v.follow_up_due
BETWEEN CURDATE()
AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
THEN 'Upcoming'

ELSE 'Normal'

END AS followup_status

FROM visits v

JOIN patients p
ON p.patient_id = v.patient_id

WHERE p.name LIKE ?

ORDER BY v.visit_id ASC

LIMIT ?, ?
";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
"sii",
$searchTerm,
$offset,
$limit
);

$stmt->execute();

$result = $stmt->get_result();

?>

<div class="d-flex justify-content-between mb-3">

<h3>
Visit List
</h3>

<a
href="add.php"
class="btn btn-success">

Add Visit

</a>

</div>

<!-- SEARCH -->

<form method="GET" class="mb-3">

<input
type="text"
name="search"
class="form-control"
placeholder="Search Patient"
value="<?= htmlspecialchars($search) ?>">

</form>

<!-- TABLE -->

<div class="table-responsive">

<table class="table table-bordered table-hover shadow">

<tr class="table-primary">

<th>S.No</th>
<th>Visit ID</th>
<th>Patient</th>
<th>Visit Date</th>
<th>Follow-Up Due</th>
<th>Days Since Visit</th>
<th>Status</th>
<th>Fees</th>

</tr>

<?php

$serial = $offset + 1;

if($result->num_rows > 0){

while($row = $result->fetch_assoc()){

?>

<tr>

<td>
<?= $serial++ ?>
</td>

<td>
<?= $row['visit_id'] ?>
</td>

<td>
<?= htmlspecialchars($row['name']) ?>
</td>

<td>
<?= $row['visit_date'] ?>
</td>

<td>
<?= $row['follow_up_due'] ?>
</td>

<td>
<?= $row['days_since_visit'] ?> Days
</td>

<td>

<?php
if($row['followup_status'] == "Overdue"){
?>

<span class="badge bg-danger">
Overdue
</span>

<?php
}
elseif($row['followup_status'] == "Upcoming"){
?>

<span class="badge bg-warning text-dark">
Upcoming
</span>

<?php
}
else{
?>

<span class="badge bg-success">
Normal
</span>

<?php } ?>

</td>

<td>

₹
<?= $row['consultation_fee'] + $row['lab_fee'] ?>

</td>

</tr>

<?php

}

}else{

?>

<tr>

<td colspan="8" class="text-center text-danger">

No Visits Found

</td>

</tr>

<?php } ?>

</table>

</div>

<!-- PAGINATION -->

<nav>

<ul class="pagination">

<!-- PREVIOUS -->

<li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">

<a
class="page-link"
href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">

Previous

</a>

</li>

<!-- PAGE NUMBERS -->

<?php for($i = 1; $i <= $totalPages; $i++){ ?>

<li class="page-item <?= ($page == $i) ? 'active' : '' ?>">

<a
class="page-link"
href="?page=<?= $i ?>&search=<?= urlencode($search) ?>">

<?= $i ?>

</a>

</li>

<?php } ?>

<!-- NEXT -->

<li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">

<a
class="page-link"
href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">

Next

</a>

</li>

</ul>

</nav>

<?php include("../includes/footer.php"); ?>