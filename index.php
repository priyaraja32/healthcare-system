<?php
// index.php
require_once 'config/db.php';
include 'includes/header.php';

// Get statistics using MySQLi
$statsQuery = "
    SELECT 
        (SELECT COUNT(*) FROM patients) as total_patients,
        (SELECT COUNT(*) FROM visits) as total_visits,
        (SELECT COUNT(*) FROM visits WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as visits_30days,
        (SELECT COUNT(*) FROM visits WHERE follow_up_due BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)) as upcoming_followups,
        (SELECT COUNT(*) FROM visits WHERE follow_up_due < CURDATE()) as overdue_followups,
        (SELECT ROUND(COALESCE(AVG(consultation_fee + lab_fee), 0), 2) FROM visits) as avg_visit_cost
";

$statsResult = $mysqli->query($statsQuery);
$stats = $statsResult->fetch_assoc();

// Get recent visits
$recentVisitsQuery = "
    SELECT 
        v.*,
        p.name,
        p.patient_id,
        DATEDIFF(CURDATE(), v.visit_date) as days_since_visit,
        CASE 
            WHEN v.follow_up_due < CURDATE() THEN 'Overdue'
            WHEN v.follow_up_due BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'Upcoming'
            ELSE 'Future'
        END as follow_up_status
    FROM visits v
    JOIN patients p ON v.patient_id = p.patient_id
    ORDER BY v.visit_date DESC
    LIMIT 5
";
$recentVisitsResult = $mysqli->query($recentVisitsQuery);
$recentVisits = $recentVisitsResult->fetch_all(MYSQLI_ASSOC);

// Get chart data
$chartQuery = "
    SELECT 
        DATE_FORMAT(visit_date, '%Y-%m') as month,
        DATE_FORMAT(visit_date, '%M %Y') as month_name,
        COUNT(*) as visit_count,
        SUM(consultation_fee + lab_fee) as total_revenue
    FROM visits
    WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(visit_date, '%Y-%m')
    ORDER BY month ASC
";
$chartResult = $mysqli->query($chartQuery);
$chartData = $chartResult->fetch_all(MYSQLI_ASSOC);
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-number"><?= $stats['total_patients'] ?></div>
        <div class="stat-label">Total Patients</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= $stats['total_visits'] ?></div>
        <div class="stat-label">Total Visits</div>
    </div>
    <div class="stat-card">
        <div class="stat-number"><?= $stats['visits_30days'] ?></div>
        <div class="stat-label">Last 30 Days Visits</div>
    </div>
    <div class="stat-card">
        <div class="stat-number">$<?= number_format($stats['avg_visit_cost'], 2) ?></div>
        <div class="stat-label">Average Visit Cost</div>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Follow-up Status</h3>
        </div>
        <div class="stats-grid" style="grid-template-columns: repeat(2, 1fr);">
            <div class="stat-card">
                <div class="stat-number" style="color: var(--warning-color);"><?= $stats['upcoming_followups'] ?></div>
                <div class="stat-label">Upcoming (7 days)</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: var(--danger-color);"><?= $stats['overdue_followups'] ?></div>
                <div class="stat-label">Overdue</div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Monthly Visit Trend (Last 6 Months)</h3>
        </div>
        <canvas id="visitChart" height="200"></canvas>
    </div>
</div>

<!-- FIXED Recent Visits Table -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Recent Visits</h3>
        <a href="/healthcare_db/visits/list.php" class="btn btn-primary">View All</a>
    </div>
    <div class="table-container">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="padding: 12px; text-align: left; background: #2c7da0; color: white;">Patient</th>
                    <th style="padding: 12px; text-align: left; background: #2c7da0; color: white;">Visit Date</th>
                    <th style="padding: 12px; text-align: left; background: #2c7da0; color: white;">Consultation</th>
                    <th style="padding: 12px; text-align: left; background: #2c7da0; color: white;">Lab Fee</th>
                    <th style="padding: 12px; text-align: left; background: #2c7da0; color: white;">Total</th>
                    <th style="padding: 12px; text-align: left; background: #2c7da0; color: white;">Days Since</th>
                    <th style="padding: 12px; text-align: left; background: #2c7da0; color: white;">Follow-up Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if(!empty($recentVisits)): ?>
                    <?php foreach($recentVisits as $visit): ?>
                    <tr style="border-bottom: 1px solid #ddd;">
                        <td style="padding: 10px;">
                            <a href="/healthcare_db/patients/view.php?id=<?= $visit['patient_id'] ?>" style="color: #2c7da0; text-decoration: none;">
                                <?= htmlspecialchars($visit['name']) ?>
                            </a>
                        </td>
                        <td style="padding: 10px;"><?= date('M d, Y', strtotime($visit['visit_date'])) ?></td>
                        <td style="padding: 10px;">$<?= number_format($visit['consultation_fee'], 2) ?></td>
                        <td style="padding: 10px;">$<?= number_format($visit['lab_fee'], 2) ?></td>
                        <td style="padding: 10px; font-weight: bold; color: #28a745;">
                            $<?= number_format($visit['consultation_fee'] + $visit['lab_fee'], 2) ?>
                        </td>
                        <td style="padding: 10px;"><?= $visit['days_since_visit'] ?> days</td>
                        <td style="padding: 10px;">
                            <?php if($visit['follow_up_status'] == 'Overdue'): ?>
                                <span style="background: #dc3545; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">⚠️ Overdue</span>
                            <?php elseif($visit['follow_up_status'] == 'Upcoming'): ?>
                                <span style="background: #ffc107; color: #333; padding: 4px 8px; border-radius: 4px; font-size: 12px;">📅 Upcoming</span>
                            <?php else: ?>
                                <span style="background: #28a745; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">✅ Future</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="padding: 40px; text-align: center;">No visits recorded yet</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Chart data
const chartData = <?= json_encode($chartData) ?>;

if(chartData.length > 0) {
    const ctx = document.getElementById('visitChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.map(d => d.month_name || d.month),
            datasets: [{
                label: 'Number of Visits',
                data: chartData.map(d => d.visit_count),
                borderColor: '#2c7da0',
                backgroundColor: 'rgba(44, 125, 160, 0.1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>