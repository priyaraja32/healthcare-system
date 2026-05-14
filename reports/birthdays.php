<?php

include("../config/db.php");
include("../includes/header.php");

$sql = "
SELECT

name,

dob,

TIMESTAMPDIFF(
YEAR,
dob,
CURDATE()
) + 1 AS turning_age,

DATE_FORMAT(
dob,
'%M %d'
) AS birthday

FROM patients

WHERE DATE_FORMAT(dob,'%m-%d')

BETWEEN

DATE_FORMAT(CURDATE(),'%m-%d')

AND

DATE_FORMAT(
DATE_ADD(CURDATE(),INTERVAL 30 DAY),
'%m-%d'
)

ORDER BY DATE_FORMAT(dob,'%m-%d')
";

$result = mysqli_query($conn,$sql);

?>

<div class="card shadow p-4">

<h3 class="mb-4">
Upcoming Birthdays
</h3>

<table class="table table-bordered table-hover">

<tr class="table-primary">

<th>Name</th>
<th>DOB</th>
<th>Birthday</th>
<th>Turning Age</th>

</tr>

<?php while($row = mysqli_fetch_assoc($result)){ ?>

<tr>

<td>
<?= $row['name'] ?>
</td>

<td>
<?= $row['dob'] ?>
</td>

<td>
<?= $row['birthday'] ?>
</td>

<td>
<?= $row['turning_age'] ?>
</td>

</tr>

<?php } ?>

</table>

</div>

<?php include("../includes/footer.php"); ?>