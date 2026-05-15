<?php
// reports/followups.php
require_once '../config/db.php';
include '../includes/header.php';

// Get upcoming follow-ups (next 7 days)
$upcomingSql = "
    SELECT 
        v.*,
        p.name as patient_name,
        p.phone,
        DATEDIFF(v.follow_up_due, CURDATE()) as days_until_due
    FROM visits v
    JOIN patients p ON v.patient_id = p.patient_id
    WHERE v.follow_up_due BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ORDER BY v.follow_up_due ASC
";
$upcomingResult = $mysqli->query($upcomingSql);
$upcoming = $upcomingResult->fetch_all(MYSQLI_ASSOC);

// Get overdue follow-ups
$overdueSql = "
    SELECT 
        v.*,
        p.name as patient_name,
        p.phone,
        DATEDIFF(CURDATE(), v.follow_up_due) as days_overdue
    FROM visits v
    JOIN patients p ON v.patient_id = p.patient_id
    WHERE v.follow_up_due < CURDATE()
    ORDER BY v.follow_up_due ASC
";
$overdueResult = $mysqli->query($overdueSql);
$overdue = $overdueResult->fetch_all(MYSQLI_ASSOC);

// Get missed follow-ups (no follow-up visit)
$missedSql = "
    SELECT 
        v.*,
        p.name as patient_name,
        p.phone,
        DATEDIFF(CURDATE(), v.follow_up_due) as days_missed
    FROM visits v
    JOIN patients p ON v.patient_id = p.patient_id
    WHERE v.follow_up_due < CURDATE()
    AND NOT EXISTS (
        SELECT 1 FROM visits v2 
        WHERE v2.patient_id = v.patient_id 
        AND v2.visit_date > v.follow_up_due
    )
    ORDER BY v.follow_up_due ASC
";
$missedResult = $mysqli->query($missedSql);
$missed = $missedResult->fetch_all(MYSQLI_ASSOC);

// Get summary counts
$summarySql = "
    SELECT 
        (SELECT COUNT(*) FROM visits WHERE follow_up_due BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)) as upcoming_count,
        (SELECT COUNT(*) FROM visits WHERE follow_up_due < CURDATE()) as overdue_count,
        (SELECT COUNT(*) FROM visits v WHERE follow_up_due < CURDATE() 
         AND NOT EXISTS (SELECT 1 FROM visits v2 WHERE v2.patient_id = v.patient_id AND v2.visit_date > v.follow_up_due)) as missed_count
";
$summaryResult = $mysqli->query($summarySql);
$summary = $summaryResult->fetch_assoc();
?>

<!-- Summary Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
    <div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 1.5rem; border-radius: 10px; text-align: center;">
        <div style="font-size: 2rem; font-weight: bold;"><?= $summary['upcoming_count'] ?? 0 ?></div>
        <div style="font-size: 0.9rem; margin-top: 0.5rem;">Upcoming Follow-ups</div>
        <div style="font-size: 0.75rem; opacity: 0.9;">Next 7 Days</div>
    </div>
    <div style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; padding: 1.5rem; border-radius: 10px; text-align: center;">
        <div style="font-size: 2rem; font-weight: bold;"><?= $summary['overdue_count'] ?? 0 ?></div>
        <div style="font-size: 0.9rem; margin-top: 0.5rem;">Overdue Follow-ups</div>
        <div style="font-size: 0.75rem; opacity: 0.9;">Past Due Date</div>
    </div>
    <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 1.5rem; border-radius: 10px; text-align: center;">
        <div style="font-size: 2rem; font-weight: bold;"><?= $summary['missed_count'] ?? 0 ?></div>
        <div style="font-size: 0.9rem; margin-top: 0.5rem;">Missed Follow-ups</div>
        <div style="font-size: 0.75rem; opacity: 0.9;">No Visit After Due Date</div>
    </div>
</div>

<div class="grid-2" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; margin-bottom: 1.5rem;">
    <!-- Upcoming Follow-ups Card -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Upcoming Follow-ups (Next 7 Days)</h3>
        </div>
        <div class="table-container">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #2c7da0; color: white;">
                        <th style="padding: 12px; text-align: left;">Patient Name</th>
                        <th style="padding: 12px; text-align: left;">Phone Number</th>
                        <th style="padding: 12px; text-align: left;">Due Date</th>
                        <th style="padding: 12px; text-align: center;">Days Until</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($upcoming) > 0): ?>
                        <?php foreach($upcoming as $followup): ?>
                        <tr style="border-bottom: 1px solid #ddd;">
                            <td style="padding: 10px;">
                                <a href="/healthcare_db/patients/view.php?id=<?= $followup['patient_id'] ?>" style="color: #2c7da0; text-decoration: none; font-weight: 500;">
                                    <?= htmlspecialchars($followup['patient_name']) ?>
                                </a>
                            </td>
                            <td style="padding: 10px;"><?= htmlspecialchars($followup['phone']) ?></td>
                            <td style="padding: 10px;"><?= date('M d, Y', strtotime($followup['follow_up_due'])) ?></td>
                            <td style="padding: 10px; text-align: center;">
                                <?php if($followup['days_until_due'] == 0): ?>
                                    <span style="background: #ffc107; color: #333; padding: 4px 8px; border-radius: 4px; font-size: 12px;">Today</span>
                                <?php else: ?>
                                    <span style="background: #17a2b8; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;"><?= $followup['days_until_due'] ?> days</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="padding: 40px; text-align: center; color: #666;">
                                No upcoming follow-ups
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Overdue Follow-ups Card -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Overdue Follow-ups</h3>
        </div>
        <div class="table-container">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #2c7da0; color: white;">
                        <th style="padding: 12px; text-align: left;">Patient Name</th>
                        <th style="padding: 12px; text-align: left;">Phone Number</th>
                        <th style="padding: 12px; text-align: left;">Due Date</th>
                        <th style="padding: 12px; text-align: center;">Days Overdue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($overdue) > 0): ?>
                        <?php foreach($overdue as $followup): ?>
                        <tr style="border-bottom: 1px solid #ddd;">
                            <td style="padding: 10px;">
                                <a href="/healthcare_db/patients/view.php?id=<?= $followup['patient_id'] ?>" style="color: #2c7da0; text-decoration: none; font-weight: 500;">
                                    <?= htmlspecialchars($followup['patient_name']) ?>
                                </a>
                            </td>
                            <td style="padding: 10px;"><?= htmlspecialchars($followup['phone']) ?></td>
                            <td style="padding: 10px;"><?= date('M d, Y', strtotime($followup['follow_up_due'])) ?></td>
                            <td style="padding: 10px; text-align: center;">
                                <span style="background: #dc3545; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;"><?= $followup['days_overdue'] ?> days</span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="padding: 40px; text-align: center; color: #666;">
                                No overdue follow-ups
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Missed Follow-ups Card - Full Width -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Missed Follow-ups (No Follow-up Visit After Due Date)</h3>
    </div>
    <div class="table-container">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #2c7da0; color: white;">
                    <th style="padding: 12px; text-align: left;">Patient Name</th>
                    <th style="padding: 12px; text-align: left;">Phone Number</th>
                    <th style="padding: 12px; text-align: left;">Due Date</th>
                    <th style="padding: 12px; text-align: center;">Days Missed</th>
                    <th style="padding: 12px; text-align: center;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($missed) > 0): ?>
                    <?php foreach($missed as $followup): ?>
                    <tr style="border-bottom: 1px solid #ddd;">
                        <td style="padding: 10px;">
                            <a href="/healthcare_db/patients/view.php?id=<?= $followup['patient_id'] ?>" style="color: #2c7da0; text-decoration: none; font-weight: 500;">
                                <?= htmlspecialchars($followup['patient_name']) ?>
                            </a>
                        </td>
                        <td style="padding: 10px;"><?= htmlspecialchars($followup['phone']) ?></td>
                        <td style="padding: 10px;"><?= date('M d, Y', strtotime($followup['follow_up_due'])) ?></td>
                        <td style="padding: 10px; text-align: center;">
                            <span style="background: #dc3545; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;"><?= $followup['days_missed'] ?> days</span>
                        </td>
                        <td style="padding: 10px; text-align: center;">
                            <a href="/healthcare_db/visits/add.php?patient_id=<?= $followup['patient_id'] ?>" 
                               style="background: #28a745; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 12px;">
                                Schedule Visit
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="padding: 40px; text-align: center; color: #666;">
                            No missed follow-ups
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
/* Additional styles */
.card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
}

.card-header {
    padding: 1rem 1.5rem;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-title {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: #2c7da0;
}

.table-container {
    overflow-x: auto;
    padding: 0;
}

/* Responsive */
@media (max-width: 768px) {
    .grid-2 {
        grid-template-columns: 1fr !important;
        gap: 1rem !important;
    }
    
    .table-container {
        overflow-x: auto;
    }
    
    table {
        min-width: 500px;
    }
}

/* Hover effect */
tbody tr:hover {
    background-color: #f8f9fa;
}
</style>

<?php include '../includes/footer.php'; ?>