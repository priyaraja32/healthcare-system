<?php

include("../config/db.php");
include("../includes/header.php");

$search = $_GET['search'] ?? '';

$page = $_GET['page'] ?? 1;

if($page < 1){
    $page = 1;
}

$limit = 5;

$offset = ($page - 1) * $limit;

/*
-----------------------------------
TOTAL RECORDS
-----------------------------------
*/

$totalQuery = "
SELECT COUNT(*) AS total
FROM patients
WHERE name LIKE ?
";

$stmtTotal = $conn->prepare($totalQuery);

$searchTerm = "%$search%";

$stmtTotal->bind_param(
"s",
$searchTerm
);

$stmtTotal->execute();

$totalResult = $stmtTotal->get_result();

$totalRow = $totalResult->fetch_assoc();

$totalPages = ceil(
$totalRow['total'] / $limit
);

/*
-----------------------------------
MAIN QUERY
-----------------------------------
*/

$sql = "
SELECT

p.*,

TIMESTAMPDIFF(
YEAR,
p.dob,
CURDATE()
) AS age,

CONCAT(

TIMESTAMPDIFF(
YEAR,
p.dob,
CURDATE()
),

' Years ',

TIMESTAMPDIFF(

MONTH,

DATE_ADD(
p.dob,

INTERVAL
TIMESTAMPDIFF(
YEAR,
p.dob,
CURDATE()
) YEAR

),

CURDATE()

),

' Months'

) AS full_age,

COUNT(v.visit_id)
AS total_visits,

YEAR(p.join_date)
AS join_year,

MONTH(p.join_date)
AS join_month,

DAY(p.join_date)
AS join_day

FROM patients p

LEFT JOIN visits v
ON p.patient_id = v.patient_id

WHERE p.name LIKE ?

GROUP BY p.patient_id

ORDER BY p.patient_id ASC

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

<div class="d-flex justify-content-between align-items-center mb-4">

<h3 class="fw-bold">
Patients List
</h3>

<a href="add.php"
class="btn btn-success">

Add Patient

</a>

</div>

<!-- SEARCH -->

<form method="GET"
class="row mb-4">

<div class="col-md-10">

<input
type="text"
name="search"
class="form-control"
placeholder="Search Patient Name..."
value="<?= htmlspecialchars($search) ?>">

</div>

<div class="col-md-2">

<button
class="btn btn-primary w-100">

Search

</button>

</div>

</form>

<!-- TABLE -->

<div class="table-responsive">

<table class="table table-bordered table-hover shadow bg-white">

<thead class="table-primary">

<tr>

<th>ID</th>
<th>Name</th>
<th>Age</th>
<th>Full Age</th>
<th>Total Visits</th>
<th>Join Date Parts</th>
<th width="300">
Action
</th>

</tr>

</thead>

<tbody>

<?php

if($result->num_rows > 0){

while($row = $result->fetch_assoc()){

$encoded_id = base64_encode($row['patient_id']);

?>

<tr>

<td>
<?= $row['patient_id'] ?>
</td>

<td>
<?= htmlspecialchars($row['name']) ?>
</td>

<td>
<?= $row['age'] ?>
</td>

<td>
<?= $row['full_age'] ?>
</td>

<td>
<?= $row['total_visits'] ?>
</td>

<td>

<?= $row['join_year'] ?>
/

<?= $row['join_month'] ?>
/

<?= $row['join_day'] ?>

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
value="<?= $encoded_id ?>">

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
value="<?= $encoded_id ?>">

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

onsubmit="return confirm('Delete this patient?')">

<input
type="hidden"
name="id"
value="<?= $encoded_id ?>">

<button
type="submit"
class="btn btn-danger btn-sm">

Delete

</button>

</form>

</div>

</td>

</tr>

<?php

}

}else{

?>

<tr>

<td colspan="7"
class="text-center text-danger">

No Patients Found

</td>

</tr>

<?php } ?>

</tbody>

</table>

</div>

<!-- PAGINATION -->

<nav class="mt-4">

<ul class="pagination justify-content-center">

<!-- PREVIOUS -->

<li class="page-item
<?= ($page <= 1) ? 'disabled' : '' ?>">

<a class="page-link"

href="?page=<?= $page-1 ?>
&search=<?= urlencode($search) ?>">

Previous

</a>

</li>

<!-- PAGE NUMBERS -->

<?php

for($i=1; $i<=$totalPages; $i++){

?>

<li class="page-item
<?= ($page == $i) ? 'active' : '' ?>">

<a class="page-link"

href="?page=<?= $i ?>
&search=<?= urlencode($search) ?>">

<?= $i ?>

</a>

</li>

<?php } ?>

<!-- NEXT -->

<li class="page-item
<?= ($page >= $totalPages) ? 'disabled' : '' ?>">

<a class="page-link"

href="?page=<?= $page+1 ?>
&search=<?= urlencode($search) ?>">

Next

</a>

</li>

</ul>

</nav>

<?php include("../includes/footer.php"); ?>