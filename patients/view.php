<?php
// patients/view.php
require_once '../config/db.php';
include '../includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch patient details with statistics
$sql = "SELECT 
            p.*,
            TIMESTAMPDIFF(YEAR, p.dob, CURDATE()) as age_years,
            CONCAT(
                TIMESTAMPDIFF(YEAR, p.dob, CURDATE()), ' years, ',
                MOD(TIMESTAMPDIFF(MONTH, p.dob, CURDATE()), 12), ' months'
            ) as age_full,
            COUNT(v.visit_id) as total_visits,
            MAX(v.visit_date) as last_visit_date,
            MIN(v.visit_date) as first_visit_date,
            MAX(v.follow_up_due) as next_follow_up,
            DATEDIFF(CURDATE(), MAX(v.visit_date)) as days_since_last_visit,
            DATEDIFF(MAX(v.visit_date), MIN(v.visit_date)) as days_between_visits,
            SUM(v.consultation_fee + v.lab_fee) as total_revenue,
            AVG(v.consultation_fee + v.lab_fee) as avg_visit_cost,
            CASE 
                WHEN MAX(v.follow_up_due) < CURDATE() THEN 'Overdue'
                WHEN MAX(v.follow_up_due) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'Upcoming'
                ELSE 'No follow-up scheduled'
            END as follow_up_status
        FROM patients p
        LEFT JOIN visits v ON p.patient_id = v.patient_id
        WHERE p.patient_id = ?
        GROUP BY p.patient_id";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$patient = $result->fetch_assoc();
$stmt->close();

if(!$patient) {
    $_SESSION['error'] = "Patient not found!";
    header('Location: list.php');
    exit;
}

// Fetch visit history
$visitsSql = "SELECT 
                v.*,
                DATEDIFF(CURDATE(), v.visit_date) as days_since_visit,
                CASE 
                    WHEN v.follow_up_due < CURDATE() THEN 'Missed'
                    WHEN v.follow_up_due BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'Due Soon'
                    ELSE 'Scheduled'
                END as follow_status
              FROM visits v
              WHERE v.patient_id = ?
              ORDER BY v.visit_date DESC";
$visitsStmt = $mysqli->prepare($visitsSql);
$visitsStmt->bind_param("i", $id);
$visitsStmt->execute();
$visitsResult = $visitsStmt->get_result();
$visits = $visitsResult->fetch_all(MYSQLI_ASSOC);
$visitsStmt->close();

// Calculate additional stats
$lastVisit = !empty($visits) ? $visits[0] : null;
$visitTrend = calculateVisitTrend($visits);
?>

<div class="patient-profile">
    <!-- Header Section -->
    <div class="profile-header">
        <div class="profile-avatar">
            <div class="avatar-initials">
                <?= strtoupper(substr($patient['name'], 0, 2)) ?>
            </div>
        </div>
        <div class="profile-info">
            <h1><?= htmlspecialchars($patient['name']) ?></h1>
            <div class="profile-meta">
                <span class="meta-item">🆔 Patient ID: #<?= str_pad($id, 5, '0', STR_PAD_LEFT) ?></span>
                <span class="meta-item">📅 Member since: <?= date('M Y', strtotime($patient['join_date'])) ?></span>
                <span class="meta-item">🎂 Age: <?= $patient['age_full'] ?></span>
            </div>
        </div>
        <div class="profile-actions">
            <a href="edit.php?id=<?= $id ?>" class="btn btn-primary">✏️ Edit Patient</a>
            <a href="list.php" class="btn btn-info">📋 Back to List</a>
            <button onclick="window.print()" class="btn btn-secondary">🖨️ Print Profile</button>
        </div>
    </div>

    <div class="grid-2">
        <!-- Personal Information Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">👤 Personal Information</h3>
            </div>
            <div class="info-table">
                <div class="info-row">
                    <div class="info-label">Full Name:</div>
                    <div class="info-value"><strong><?= htmlspecialchars($patient['name']) ?></strong></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Date of Birth:</div>
                    <div class="info-value"><?= date('F d, Y', strtotime($patient['dob'])) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Age:</div>
                    <div class="info-value"><span class="age-badge-large"><?= $patient['age_full'] ?></span></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Phone Number:</div>
                    <div class="info-value">
                        <a href="tel:<?= htmlspecialchars($patient['phone']) ?>"><?= htmlspecialchars($patient['phone']) ?></a>
                        <button onclick="copyToClipboard('<?= htmlspecialchars($patient['phone']) ?>')" class="copy-btn">📋</button>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Address:</div>
                    <div class="info-value">
                        <?= !empty($patient['address']) ? nl2br(htmlspecialchars($patient['address'])) : '<em class="text-muted">No address provided</em>' ?>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Join Date:</div>
                    <div class="info-value"><?= date('F d, Y', strtotime($patient['join_date'])) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Patient Since:</div>
                    <div class="info-value">
                        <?php 
                        $joinDays = (strtotime(date('Y-m-d')) - strtotime($patient['join_date'])) / (60*60*24);
                        echo floor($joinDays) . " days";
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Medical Statistics Card -->
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">📊 Medical Statistics</h3>
            </div>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-value"><?= $patient['total_visits'] ?></div>
                    <div class="stat-label">Total Visits</div>
                </div>
                <?php if($patient['first_visit_date']): ?>
                <div class="stat-item">
                    <div class="stat-value"><?= date('M d, Y', strtotime($patient['first_visit_date'])) ?></div>
                    <div class="stat-label">First Visit</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?= date('M d, Y', strtotime($patient['last_visit_date'])) ?></div>
                    <div class="stat-label">Last Visit</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value <?= $patient['days_since_last_visit'] > 30 ? 'text-warning' : '' ?>">
                        <?= $patient['days_since_last_visit'] ?> days
                    </div>
                    <div class="stat-label">Since Last Visit</div>
                </div>
                <?php if($patient['total_revenue']): ?>
                <div class="stat-item">
                    <div class="stat-value">$<?= number_format($patient['total_revenue'], 2) ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">$<?= number_format($patient['avg_visit_cost'], 2) ?></div>
                    <div class="stat-label">Average per Visit</div>
                </div>
                <?php endif; ?>
                <div class="stat-item">
                    <div class="stat-value">
                        <?php if($patient['next_follow_up']): ?>
                            <?= date('M d, Y', strtotime($patient['next_follow_up'])) ?>
                            <br>
                            <span class="badge badge-<?= $patient['follow_up_status'] == 'Overdue' ? 'danger' : ($patient['follow_up_status'] == 'Upcoming' ? 'warning' : 'success') ?>">
                                <?= $patient['follow_up_status'] ?>
                            </span>
                        <?php else: ?>
                            No follow-up
                        <?php endif; ?>
                    </div>
                    <div class="stat-label">Next Follow-up</div>
                </div>
                <?php else: ?>
                <div class="stat-item full-width">
                    <div class="alert alert-info">No visit history available yet.</div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Visit History Section -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">📝 Visit History</h3>
            <div class="header-actions">
                <a href="/healthcare_db/visits/add.php?patient_id=<?= $id ?>" class="btn btn-success">+ Add New Visit</a>
                <?php if(!empty($visits)): ?>
                    <button onclick="exportVisitsToCSV()" class="btn btn-secondary">📊 Export Visits</button>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if(!empty($visits)): ?>
            <div class="table-container">
                <table class="data-table" id="visitsTable">
                    <thead>
                        <tr>
                            <th onclick="sortVisits(0)">Visit Date ⬍</th>
                            <th>Consultation Fee</th>
                            <th>Lab Fee</th>
                            <th>Total Amount</th>
                            <th>Follow-up Due</th>
                            <th>Status</th>
                            <th>Days Since</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $totalConsultation = 0;
                        $totalLabFee = 0;
                        foreach($visits as $visit):
                            $totalConsultation += $visit['consultation_fee'];
                            $totalLabFee += $visit['lab_fee'];
                            $totalAmount = $visit['consultation_fee'] + $visit['lab_fee'];
                        ?>
                        <tr>
                            <td>
                                <strong><?= date('M d, Y', strtotime($visit['visit_date'])) ?></strong><br>
                                <small><?= date('l', strtotime($visit['visit_date'])) ?></small>
                            </td>
                            <td>$<?= number_format($visit['consultation_fee'], 2) ?></td>
                            <td>$<?= number_format($visit['lab_fee'], 2) ?></td>
                            <td class="total-amount">$<?= number_format($totalAmount, 2) ?></td>
                            <td>
                                <?php if($visit['follow_up_due']): ?>
                                    <?= date('M d, Y', strtotime($visit['follow_up_due'])) ?>
                                <?php else: ?>
                                    <em>Not scheduled</em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                $statusClass = '';
                                $statusIcon = '';
                                switch($visit['follow_status']) {
                                    case 'Missed':
                                        $statusClass = 'status-badge status-danger';
                                        $statusIcon = '❌';
                                        break;
                                    case 'Due Soon':
                                        $statusClass = 'status-badge status-warning';
                                        $statusIcon = '⚠️';
                                        break;
                                    default:
                                        $statusClass = 'status-badge status-success';
                                        $statusIcon = '✅';
                                }
                                ?>
                                <span class="<?= $statusClass ?>"><?= $statusIcon ?> <?= $visit['follow_status'] ?></span>
                            </td>
                            <td>
                                <?php if($visit['days_since_visit'] <= 7): ?>
                                    <span class="recent-badge"><?= $visit['days_since_visit'] ?> days ago</span>
                                <?php else: ?>
                                    <?= $visit['days_since_visit'] ?> days
                                <?php endif; ?>
                            </td>
                            <td>
                                <button onclick="viewVisitDetails(<?= $visit['visit_id'] ?>)" class="btn-icon btn-view" title="View Details">👁️</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="summary-row">
                            <td><strong>Totals:</strong></td>
                            <td><strong>$<?= number_format($totalConsultation, 2) ?></strong></td>
                            <td><strong>$<?= number_format($totalLabFee, 2) ?></strong></td>
                            <td><strong>$<?= number_format($totalConsultation + $totalLabFee, 2) ?></strong></td>
                            <td colspan="4"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <!-- Visit Trend Chart -->
            <div class="visit-trend">
                <h4>Visit Frequency Trend</h4>
                <div class="trend-indicator">
                    <?php 
                    $trendClass = $visitTrend > 0 ? 'trend-up' : ($visitTrend < 0 ? 'trend-down' : 'trend-stable');
                    $trendIcon = $visitTrend > 0 ? '📈' : ($visitTrend < 0 ? '📉' : '➡️');
                    ?>
                    <span class="<?= $trendClass ?>">
                        <?= $trendIcon ?> Visit frequency is <?= abs($visitTrend) ?>% 
                        <?= $visitTrend > 0 ? 'higher' : ($visitTrend < 0 ? 'lower' : 'stable') ?> than average
                    </span>
                </div>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">📋</div>
                <h3>No Visit History</h3>
                <p>This patient hasn't had any visits yet.</p>
                <a href="/healthcare_db/visits/add.php?patient_id=<?= $id ?>" class="btn btn-primary">➕ Add First Visit</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Copy to clipboard function
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showNotification('Phone number copied to clipboard!', 'success');
    });
}

// Show notification
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = message;
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 3000);
}

// Export visits to CSV
function exportVisitsToCSV() {
    const table = document.getElementById('visitsTable');
    const rows = table.querySelectorAll('tr');
    let csv = [];
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('th, td');
        const rowData = Array.from(cells).map(cell => {
            let text = cell.innerText;
            // Remove emojis and extra spaces
            text = text.replace(/[📊📈📉✅❌⚠️👁️📋🖨️✏️]/g, '');
            text = text.trim();
            return `"${text}"`;
        });
        if(rowData.length > 1) csv.push(rowData.join(','));
    });
    
    const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `patient_<?= $id ?>_visits_${new Date().toISOString().split('T')[0]}.csv`;
    a.click();
    URL.revokeObjectURL(url);
    
    showNotification('Visits exported successfully!', 'success');
}

// View visit details
function viewVisitDetails(visitId) {
    window.location.href = `/healthcare_db/visits/view.php?id=${visitId}&patient_id=<?= $id ?>`;
}

// Sort visits table
let sortDirection = true;
function sortVisits(columnIndex) {
    const table = document.getElementById('visitsTable');
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    rows.sort((a, b) => {
        let aVal = a.cells[columnIndex].innerText;
        let bVal = b.cells[columnIndex].innerText;
        
        if(columnIndex === 0) { // Date column
            aVal = new Date(aVal);
            bVal = new Date(bVal);
        } else if(columnIndex >= 1 && columnIndex <= 3) { // Amount columns
            aVal = parseFloat(aVal.replace(/[^0-9.-]/g, ''));
            bVal = parseFloat(bVal.replace(/[^0-9.-]/g, ''));
        }
        
        if(sortDirection) {
            return aVal > bVal ? 1 : -1;
        } else {
            return aVal < bVal ? 1 : -1;
        }
    });
    
    rows.forEach(row => tbody.appendChild(row));
    sortDirection = !sortDirection;
}

// Calculate visit trend
function calculateVisitTrend(visits) {
    // This would normally be calculated server-side
    // For demo purposes, returning a random value
    return Math.floor(Math.random() * 30) - 15;
}

// Auto-refresh every 60 seconds (optional)
setInterval(() => {
    location.reload();
}, 60000);
</script>

<style>
/* Profile Header */
.patient-profile {
    max-width: 1400px;
    margin: 0 auto;
}

.profile-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 15px;
    margin-bottom: 2rem;
    display: flex;
    align-items: center;
    gap: 2rem;
    flex-wrap: wrap;
}

.profile-avatar {
    width: 100px;
    height: 100px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.avatar-initials {
    font-size: 2.5rem;
    font-weight: bold;
}

.profile-info {
    flex: 1;
}

.profile-info h1 {
    margin: 0 0 0.5rem 0;
    font-size: 2rem;
}

.profile-meta {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.meta-item {
    background: rgba(255,255,255,0.2);
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
    font-size: 0.9rem;
}

.profile-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

/* Info Table */
.info-table {
    padding: 0;
}

.info-row {
    display: flex;
    padding: 0.75rem;
    border-bottom: 1px solid #eee;
}

.info-row:last-child {
    border-bottom: none;
}

.info-label {
    width: 120px;
    font-weight: 600;
    color: #555;
}

.info-value {
    flex: 1;
}

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
    padding: 1rem;
}

.stat-item {
    text-align: center;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 10px;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: bold;
    color: #007bff;
}

.stat-label {
    font-size: 0.85rem;
    color: #666;
    margin-top: 0.5rem;
}

.full-width {
    grid-column: 1 / -1;
}

/* Visit Trend */
.visit-trend {
    margin-top: 1.5rem;
    padding: 1rem;
    background: #f8f9fa;
    border-radius: 10px;
}

.trend-indicator {
    margin-top: 0.5rem;
}

.trend-up { color: #28a745; }
.trend-down { color: #dc3545; }
.trend-stable { color: #ffc107; }

/* Table Styles */
.data-table tfoot tr {
    background: #f8f9fa;
    font-weight: bold;
}

.total-amount {
    font-weight: bold;
    color: #28a745;
}

.recent-badge {
    background: #28a745;
    color: white;
    padding: 0.2rem 0.5rem;
    border-radius: 12px;
    font-size: 0.8rem;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 3rem;
}

.empty-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
}

/* Notifications */
.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    padding: 1rem 1.5rem;
    border-radius: 5px;
    color: white;
    z-index: 9999;
    animation: slideIn 0.3s ease-out;
}

.notification-success { background: #28a745; }
.notification-info { background: #17a2b8; }
.notification-error { background: #dc3545; }

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

/* Responsive */
@media (max-width: 768px) {
    .profile-header {
        flex-direction: column;
        text-align: center;
    }
    
    .info-row {
        flex-direction: column;
    }
    
    .info-label {
        width: 100%;
        margin-bottom: 0.25rem;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
}

.copy-btn {
    background: none;
    border: none;
    cursor: pointer;
    margin-left: 0.5rem;
    font-size: 1rem;
}
</style>

<?php
// Helper function to calculate visit trend
function calculateVisitTrend($visits) {
    if(count($visits) < 2) return 0;
    
    $dates = array_column($visits, 'visit_date');
    $recentCount = 0;
    $olderCount = 0;
    $cutoff = date('Y-m-d', strtotime('-3 months'));
    
    foreach($visits as $visit) {
        if($visit['visit_date'] >= $cutoff) {
            $recentCount++;
        } else {
            $olderCount++;
        }
    }
    
    if($olderCount == 0) return 100;
    return round((($recentCount - $olderCount) / $olderCount) * 100);
}

include '../includes/footer.php'; 
?>