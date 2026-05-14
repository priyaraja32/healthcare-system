<?php

include("../config/db.php");
include("../includes/header.php");

$sql = "
SELECT

p.name,

v.visit_date,

v.follow_up_due,

DATEDIFF(
v.follow_up_due,
CURDATE()
) AS days_remaining,

CASE

WHEN v.follow_up_due < CURDATE()
THEN 'Overdue'

WHEN v.follow_up_due
BETWEEN CURDATE()
AND DATE_ADD(CURDATE(),INTERVAL 7 DAY)
THEN 'Upcoming'

ELSE 'Normal'

END AS followup_status

FROM visits v

JOIN patients p
ON p.patient_id=v.patient_id

ORDER BY v.follow_up_due ASC
";

$result = mysqli_query($conn,$sql);

?>

<div class="card shadow p-4">

<h3 class="mb-4">
Follow-Up Report
</h3>

<table class="table table-bordered table-hover">

<tr class="table-primary">

<th>Patient</th>
<th>Visit Date</th>
<th>Follow-Up Due</th>
<th>Days Remaining</th>
<th>Status</th>

</tr>

<?php while($row = mysqli_fetch_assoc($result)){ ?>

<tr>

<td><?= $row['name'] ?></td>

<td><?= $row['visit_date'] ?></td>

<td><?= $row['follow_up_due'] ?></td>

<td><?= $row['days_remaining'] ?></td>

<td>

<?php if($row['followup_status']=="Overdue"){ ?>

<span class="badge bg-danger">
Overdue
</span>

<?php }elseif($row['followup_status']=="Upcoming"){ ?>

<span class="badge bg-warning text-dark">
Upcoming
</span>

<?php }else{ ?>

<span class="badge bg-success">
Normal
</span>

<?php } ?>

</td>

</tr>

<?php } ?>

</table>

</div>

<?php include("../includes/footer.php"); ?>