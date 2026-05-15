<?php
// visits/list.php
require_once '../config/db.php';
include '../includes/header.php';

// Get all visits with patient information using MySQLi
$sql = "SELECT 
            v.*,
            p.name as patient_name,
            DATEDIFF(CURDATE(), v.visit_date) as days_since_visit,
            CASE 
                WHEN v.follow_up_due < CURDATE() THEN 'Overdue'
                WHEN v.follow_up_due BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'Due Soon'
                ELSE 'Scheduled'
            END as follow_up_status
        FROM visits v
        JOIN patients p ON v.patient_id = p.patient_id
        ORDER BY v.visit_date DESC";

$visitsResult = $mysqli->query($sql);
$visits = $visitsResult->fetch_all(MYSQLI_ASSOC);

// Get summary statistics
$statsSql = "SELECT 
                COUNT(*) as total_visits,
                SUM(consultation_fee + lab_fee) as total_revenue,
                AVG(consultation_fee + lab_fee) as avg_visit_cost,
                SUM(CASE WHEN follow_up_due < CURDATE() THEN 1 ELSE 0 END) as overdue_followups,
                SUM(CASE WHEN follow_up_due BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as upcoming_followups
             FROM visits";
$statsResult = $mysqli->query($statsSql);
$stats = $statsResult->fetch_assoc();
?>

<!-- Summary Statistics Cards - No Emojis -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
    <div style="background: white; padding: 1rem; border-radius: 10px; display: flex; align-items: center; gap: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <div style="font-size: 2rem; background: #e3f2fd; padding: 10px; border-radius: 10px;">📋</div>
        <div style="flex: 1;">
            <div style="font-size: 1.8rem; font-weight: bold; color: #2c7da0;"><?= $stats['total_visits'] ?? 0 ?></div>
            <div style="font-size: 0.85rem; color: #666;">Total Visits</div>
        </div>
    </div>
    <div style="background: white; padding: 1rem; border-radius: 10px; display: flex; align-items: center; gap: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <div style="font-size: 2rem; background: #e8f5e9; padding: 10px; border-radius: 10px;">💰</div>
        <div style="flex: 1;">
            <div style="font-size: 1.8rem; font-weight: bold; color: #28a745;">$<?= number_format($stats['total_revenue'] ?? 0, 2) ?></div>
            <div style="font-size: 0.85rem; color: #666;">Total Revenue</div>
        </div>
    </div>
    <div style="background: white; padding: 1rem; border-radius: 10px; display: flex; align-items: center; gap: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <div style="font-size: 2rem; background: #fff3e0; padding: 10px; border-radius: 10px;">📊</div>
        <div style="flex: 1;">
            <div style="font-size: 1.8rem; font-weight: bold; color: #ff9800;">$<?= number_format($stats['avg_visit_cost'] ?? 0, 2) ?></div>
            <div style="font-size: 0.85rem; color: #666;">Average per Visit</div>
        </div>
    </div>
    <div style="background: white; padding: 1rem; border-radius: 10px; display: flex; align-items: center; gap: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <div style="font-size: 2rem; background: #ffebee; padding: 10px; border-radius: 10px;">⚠️</div>
        <div style="flex: 1;">
            <div style="font-size: 1.8rem; font-weight: bold; color: #dc3545;"><?= $stats['overdue_followups'] ?? 0 ?></div>
            <div style="font-size: 0.85rem; color: #666;">Overdue Follow-ups</div>
        </div>
    </div>
    <div style="background: white; padding: 1rem; border-radius: 10px; display: flex; align-items: center; gap: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <div style="font-size: 2rem; background: #e8f5e9; padding: 10px; border-radius: 10px;">📅</div>
        <div style="flex: 1;">
            <div style="font-size: 1.8rem; font-weight: bold; color: #17a2b8;"><?= $stats['upcoming_followups'] ?? 0 ?></div>
            <div style="font-size: 0.85rem; color: #666;">Upcoming (7 days)</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">All Visits</h2>
        <div style="display: flex; gap: 0.5rem; align-items: center;">
            <a href="add.php" class="btn btn-success">+ Add New Visit</a>
            <button onclick="exportToCSV()" class="btn btn-secondary" style="padding: 0.3rem 0.8rem; font-size: 0.85rem;">Export to CSV</button>
            <button onclick="window.print()" class="btn btn-secondary" style="padding: 0.3rem 0.8rem; font-size: 0.85rem;">Print</button>
        </div>
    </div>
    
    <div class="table-container">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="padding: 12px; text-align: left; background: #2c7da0; color: white;">Patient Name</th>
                    <th style="padding: 12px; text-align: left; background: #2c7da0; color: white;">Visit Date</th>
                    <th style="padding: 12px; text-align: right; background: #2c7da0; color: white;">Consultation Fee</th>
                    <th style="padding: 12px; text-align: right; background: #2c7da0; color: white;">Lab Fee</th>
                    <th style="padding: 12px; text-align: right; background: #2c7da0; color: white;">Total Amount</th>
                    <th style="padding: 12px; text-align: left; background: #2c7da0; color: white;">Follow-up Due</th>
                    <th style="padding: 12px; text-align: center; background: #2c7da0; color: white;">Days Since</th>
                    <th style="padding: 12px; text-align: center; background: #2c7da0; color: white;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($visits) > 0): ?>
                    <?php foreach($visits as $visit): ?>
                    <tr style="border-bottom: 1px solid #ddd;">
                        <td style="padding: 10px;">
                            <a href="/healthcare_db/patients/view.php?id=<?= $visit['patient_id'] ?>" style="color: #2c7da0; text-decoration: none; font-weight: 500;">
                                <?= htmlspecialchars($visit['patient_name']) ?>
                            </a>
                        </td>
                        <td style="padding: 10px;">
                            <?= date('M d, Y', strtotime($visit['visit_date'])) ?>
                            <small style="color: #666;">(<?= date('D', strtotime($visit['visit_date'])) ?>)</small>
                        </td>
                        <td style="padding: 10px; text-align: right; font-family: monospace;">$<?= number_format($visit['consultation_fee'], 2) ?></td>
                        <td style="padding: 10px; text-align: right; font-family: monospace;">$<?= number_format($visit['lab_fee'], 2) ?></td>
                        <td style="padding: 10px; text-align: right; font-weight: bold; color: #28a745; font-family: monospace;">$<?= number_format($visit['consultation_fee'] + $visit['lab_fee'], 2) ?></td>
                        <td style="padding: 10px;">
                            <?= date('M d, Y', strtotime($visit['follow_up_due'])) ?>
                            <?php if($visit['follow_up_status'] == 'Overdue'): ?>
                                <span style="background: #dc3545; color: white; padding: 2px 6px; border-radius: 4px; font-size: 10px; margin-left: 5px;">Overdue</span>
                            <?php elseif($visit['follow_up_status'] == 'Due Soon'): ?>
                                <span style="background: #ffc107; color: #333; padding: 2px 6px; border-radius: 4px; font-size: 10px; margin-left: 5px;">Due Soon</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 10px; text-align: center;">
                            <?php 
                            $daysClass = '';
                            if($visit['days_since_visit'] > 180) $daysClass = 'style="color: #dc3545; font-weight: bold;"';
                            elseif($visit['days_since_visit'] > 90) $daysClass = 'style="color: #ffc107; font-weight: bold;"';
                            ?>
                            <span <?= $daysClass ?>><?= $visit['days_since_visit'] ?> days</span>
                        </td>
                        <td style="padding: 10px; text-align: center;">
                            <?php 
                            $statusColor = '';
                            $statusText = '';
                            switch($visit['follow_up_status']) {
                                case 'Overdue':
                                    $statusColor = '#dc3545';
                                    $statusText = 'Overdue';
                                    break;
                                case 'Due Soon':
                                    $statusColor = '#ffc107';
                                    $statusText = 'Due Soon';
                                    break;
                                default:
                                    $statusColor = '#17a2b8';
                                    $statusText = 'Scheduled';
                            }
                            ?>
                            <span style="background: <?= $statusColor ?>; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                <?= $statusText ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="padding: 40px; text-align: center;">
                            No visits recorded yet
                            <br><small style="color: #666;">Click "Add New Visit" to get started</small>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
            <?php if(count($visits) > 0): ?>
            <tfoot style="background: #f8f9fa; font-weight: bold; border-top: 2px solid #dee2e6;">
                <tr>
                    <td colspan="2" style="padding: 10px;"><strong>Totals:</strong></td>
                    <td style="padding: 10px; text-align: right;"><strong>$<?= number_format(array_sum(array_column($visits, 'consultation_fee')), 2) ?></strong></td>
                    <td style="padding: 10px; text-align: right;"><strong>$<?= number_format(array_sum(array_column($visits, 'lab_fee')), 2) ?></strong></td>
                    <td style="padding: 10px; text-align: right;"><strong>$<?= number_format(array_sum(array_column($visits, 'consultation_fee')) + array_sum(array_column($visits, 'lab_fee')), 2) ?></strong></td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<script>
// Export to CSV
function exportToCSV() {
    const rows = document.querySelectorAll('table tr');
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
    a.download = 'visits_' + new Date().toISOString().split('T')[0] + '.csv';
    a.click();
    URL.revokeObjectURL(url);
}
</script>

<?php include '../includes/footer.php'; ?>