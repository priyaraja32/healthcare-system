<?php
// reports/summary.php
require_once '../config/db.php';
include '../includes/header.php';

$summarySql = "
    SELECT 
        p.patient_id,
        p.name,
        p.phone,
        CONCAT(
            TIMESTAMPDIFF(YEAR, p.dob, CURDATE()), ' years, ',
            MOD(TIMESTAMPDIFF(MONTH, p.dob, CURDATE()), 12), ' months'
        ) as age,
        COUNT(v.visit_id) as total_visits,
        MAX(v.visit_date) as last_visit_date,
        DATEDIFF(CURDATE(), MAX(v.visit_date)) as days_since_last_visit,
        MAX(v.follow_up_due) as next_follow_up,
        CASE 
            WHEN MAX(v.follow_up_due) < CURDATE() THEN 'Overdue'
            WHEN MAX(v.follow_up_due) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'Upcoming'
            WHEN MAX(v.follow_up_due) IS NULL THEN 'No visits'
            ELSE 'Scheduled'
        END as follow_up_status,
        CASE 
            WHEN COUNT(v.visit_id) = 0 THEN 'Never visited'
            WHEN DATEDIFF(CURDATE(), MAX(v.visit_date)) > 180 THEN 'Inactive'
            ELSE 'Active'
        END as patient_status
    FROM patients p
    LEFT JOIN visits v ON p.patient_id = v.patient_id
    GROUP BY p.patient_id
    ORDER BY patient_status, p.name
";

$summaryResult = $mysqli->query($summarySql);
$summary = $summaryResult->fetch_all(MYSQLI_ASSOC);

// Get summary statistics
$statsSql = "
    SELECT 
        COUNT(*) as total_patients,
        SUM(CASE WHEN patient_status = 'Active' THEN 1 ELSE 0 END) as active_patients,
        SUM(CASE WHEN patient_status = 'Inactive' THEN 1 ELSE 0 END) as inactive_patients,
        SUM(CASE WHEN patient_status = 'Never visited' THEN 1 ELSE 0 END) as never_visited,
        SUM(CASE WHEN follow_up_status = 'Overdue' THEN 1 ELSE 0 END) as overdue_followups,
        SUM(CASE WHEN follow_up_status = 'Upcoming' THEN 1 ELSE 0 END) as upcoming_followups,
        ROUND(AVG(total_visits), 2) as avg_visits_per_patient
    FROM (
        SELECT 
            p.patient_id,
            CASE 
                WHEN COUNT(v.visit_id) = 0 THEN 'Never visited'
                WHEN DATEDIFF(CURDATE(), MAX(v.visit_date)) > 180 THEN 'Inactive'
                ELSE 'Active'
            END as patient_status,
            CASE 
                WHEN MAX(v.follow_up_due) < CURDATE() THEN 'Overdue'
                WHEN MAX(v.follow_up_due) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'Upcoming'
                ELSE 'No follow-up'
            END as follow_up_status,
            COUNT(v.visit_id) as total_visits
        FROM patients p
        LEFT JOIN visits v ON p.patient_id = v.patient_id
        GROUP BY p.patient_id
    ) as stats
";

$statsResult = $mysqli->query($statsSql);
$stats = $statsResult->fetch_assoc();
?>

<!-- Summary Statistics Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
    <div style="background: white; padding: 1rem; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center;">
        <div style="font-size: 2rem; font-weight: bold; color: #2c7da0;"><?= $stats['total_patients'] ?? 0 ?></div>
        <div style="font-size: 0.85rem; color: #666; margin-top: 0.25rem;">Total Patients</div>
    </div>
    <div style="background: white; padding: 1rem; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center;">
        <div style="font-size: 2rem; font-weight: bold; color: #28a745;"><?= $stats['active_patients'] ?? 0 ?></div>
        <div style="font-size: 0.85rem; color: #666; margin-top: 0.25rem;">Active Patients</div>
    </div>
    <div style="background: white; padding: 1rem; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center;">
        <div style="font-size: 2rem; font-weight: bold; color: #dc3545;"><?= $stats['inactive_patients'] ?? 0 ?></div>
        <div style="font-size: 0.85rem; color: #666; margin-top: 0.25rem;">Inactive Patients</div>
    </div>
    <div style="background: white; padding: 1rem; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center;">
        <div style="font-size: 2rem; font-weight: bold; color: #ffc107;"><?= $stats['never_visited'] ?? 0 ?></div>
        <div style="font-size: 0.85rem; color: #666; margin-top: 0.25rem;">Never Visited</div>
    </div>
    <div style="background: white; padding: 1rem; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center;">
        <div style="font-size: 2rem; font-weight: bold; color: #dc3545;"><?= $stats['overdue_followups'] ?? 0 ?></div>
        <div style="font-size: 0.85rem; color: #666; margin-top: 0.25rem;">Overdue Follow-ups</div>
    </div>
    <div style="background: white; padding: 1rem; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center;">
        <div style="font-size: 2rem; font-weight: bold; color: #17a2b8;"><?= $stats['upcoming_followups'] ?? 0 ?></div>
        <div style="font-size: 0.85rem; color: #666; margin-top: 0.25rem;">Upcoming Follow-ups</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Complete Patient Summary Report</h2>
        <div style="display: flex; gap: 0.5rem;">
            <button onclick="exportToCSV()" class="btn btn-secondary" style="padding: 0.3rem 0.8rem; font-size: 0.85rem;">Export to CSV</button>
            <button onclick="window.print()" class="btn btn-secondary" style="padding: 0.3rem 0.8rem; font-size: 0.85rem;">Print Report</button>
        </div>
    </div>
    <div class="table-container">
        <table style="width: 100%; border-collapse: collapse;" id="summaryTable">
            <thead>
                <tr style="background: #2c7da0; color: white;">
                    <th style="padding: 12px; text-align: left;">Patient Name</th>
                    <th style="padding: 12px; text-align: left;">Phone Number</th>
                    <th style="padding: 12px; text-align: left;">Age</th>
                    <th style="padding: 12px; text-align: center;">Total Visits</th>
                    <th style="padding: 12px; text-align: left;">Last Visit Date</th>
                    <th style="padding: 12px; text-align: center;">Days Since</th>
                    <th style="padding: 12px; text-align: left;">Next Follow-up</th>
                    <th style="padding: 12px; text-align: center;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($summary) > 0): ?>
                    <?php foreach($summary as $patient): ?>
                    <tr style="border-bottom: 1px solid #ddd;">
                        <td style="padding: 10px;">
                            <a href="/healthcare_db/patients/view.php?id=<?= $patient['patient_id'] ?>" style="color: #2c7da0; text-decoration: none; font-weight: 500;">
                                <?= htmlspecialchars($patient['name']) ?>
                            </a>
                         </a>
                        <td style="padding: 10px;"><?= htmlspecialchars($patient['phone']) ?> </a>
                        <td style="padding: 10px;"><?= $patient['age'] ?> </a>
                        <td style="padding: 10px; text-align: center;">
                            <span style="background: #17a2b8; color: white; padding: 4px 10px; border-radius: 20px; font-size: 0.85rem;">
                                <?= $patient['total_visits'] ?>
                            </span>
                         </a>
                        <td style="padding: 10px;">
                            <?php if($patient['last_visit_date']): ?>
                                <?= date('M d, Y', strtotime($patient['last_visit_date'])) ?>
                            <?php else: ?>
                                <span style="color: #999;">No visits</span>
                            <?php endif; ?>
                         </a>
                        <td style="padding: 10px; text-align: center;">
                            <?php if($patient['days_since_last_visit']): ?>
                                <?php 
                                $daysColor = '';
                                if($patient['days_since_last_visit'] > 180) $daysColor = '#dc3545';
                                elseif($patient['days_since_last_visit'] > 90) $daysColor = '#ffc107';
                                else $daysColor = '#28a745';
                                ?>
                                <span style="color: <?= $daysColor ?>; font-weight: bold;"><?= $patient['days_since_last_visit'] ?> days</span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                         </a>
                        <td style="padding: 10px;">
                            <?php if($patient['next_follow_up']): ?>
                                <?= date('M d, Y', strtotime($patient['next_follow_up'])) ?>
                                <?php if($patient['follow_up_status'] == 'Overdue'): ?>
                                    <span style="background: #dc3545; color: white; padding: 2px 6px; border-radius: 4px; font-size: 10px; margin-left: 5px;">Overdue</span>
                                <?php elseif($patient['follow_up_status'] == 'Upcoming'): ?>
                                    <span style="background: #ffc107; color: #333; padding: 2px 6px; border-radius: 4px; font-size: 10px; margin-left: 5px;">Soon</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color: #999;">No follow-up</span>
                            <?php endif; ?>
                         </a>
                        <td style="padding: 10px; text-align: center;">
                            <div style="display: flex; flex-wrap: wrap; gap: 4px; justify-content: center;">
                                <?php if($patient['patient_status'] == 'Active'): ?>
                                    <span style="background: #28a745; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px;">Active</span>
                                <?php elseif($patient['patient_status'] == 'Inactive'): ?>
                                    <span style="background: #dc3545; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px;">Inactive</span>
                                <?php else: ?>
                                    <span style="background: #ffc107; color: #333; padding: 4px 8px; border-radius: 4px; font-size: 11px;">No visits</span>
                                <?php endif; ?>
                                
                                <?php if($patient['follow_up_status'] == 'Overdue'): ?>
                                    <span style="background: #dc3545; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px;">Follow-up Overdue</span>
                                <?php elseif($patient['follow_up_status'] == 'Upcoming'): ?>
                                    <span style="background: #17a2b8; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px;">Follow-up Soon</span>
                                <?php endif; ?>
                            </div>
                         </a>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="padding: 40px; text-align: center; color: #666;">No patient data available</a>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Export to CSV
function exportToCSV() {
    const table = document.getElementById('summaryTable');
    const rows = table.querySelectorAll('tr');
    let csv = [];
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('th, td');
        const rowData = Array.from(cells).map(cell => {
            let text = cell.innerText;
            text = text.trim();
            return '"' + text + '"';
        });
        if(rowData.length > 1) csv.push(rowData.join(','));
    });
    
    const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'patient_summary_' + new Date().toISOString().split('T')[0] + '.csv';
    a.click();
    URL.revokeObjectURL(url);
}
</script>

<style>
/* Card styling */
.card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 1.5rem;
    overflow: hidden;
}

.card-header {
    padding: 1rem 1.5rem;
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.card-title {
    margin: 0;
    font-size: 1.25rem;
    color: #2c7da0;
}

.table-container {
    overflow-x: auto;
    padding: 0;
}

/* Button styles */
.btn-secondary {
    background-color: #6c757d;
    color: white;
    border: none;
    cursor: pointer;
    border-radius: 4px;
}

.btn-secondary:hover {
    background-color: #5a6268;
}

/* Hover effect */
tbody tr:hover {
    background-color: #f8f9fa;
}

/* Responsive */
@media (max-width: 768px) {
    div[style*="grid-template-columns: repeat(auto-fit, minmax(180px, 1fr))"] {
        grid-template-columns: repeat(2, 1fr) !important;
    }
    
    .table-container {
        overflow-x: auto;
    }
    
    table {
        min-width: 800px;
    }
    
    .card-header {
        flex-direction: column;
        text-align: center;
    }
    
    .status-badges {
        flex-direction: column;
        align-items: center;
    }
}

/* Print styles */
@media print {
    .btn-secondary {
        display: none;
    }
    
    .card {
        box-shadow: none;
        border: 1px solid #ddd;
    }
    
    thead tr {
        background: #ddd !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
</style>

<?php include '../includes/footer.php'; ?>