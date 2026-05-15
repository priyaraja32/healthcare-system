<?php
// patients/list.php
require_once '../config/db.php';
include '../includes/header.php';

$search = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '%';
$search_term = isset($_GET['search']) ? $_GET['search'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;


$sql = "SELECT 
            p.patient_id,
            p.name,
            p.dob,
            p.join_date,
            p.phone,
            p.address,
            p.created_at,
            TIMESTAMPDIFF(YEAR, p.dob, CURDATE()) as age_years,
            CONCAT(
                TIMESTAMPDIFF(YEAR, p.dob, CURDATE()), ' years, ',
                MOD(TIMESTAMPDIFF(MONTH, p.dob, CURDATE()), 12), ' months'
            ) as age_full,
            COUNT(v.visit_id) as total_visits,
            MAX(v.visit_date) as last_visit_date,
            DATEDIFF(CURDATE(), MAX(v.visit_date)) as days_since_last_visit,
            MAX(v.follow_up_due) as last_follow_up_due,
            CASE 
                WHEN MAX(v.follow_up_due) IS NULL THEN 'No follow-up'
                WHEN MAX(v.follow_up_due) < CURDATE() THEN 'Overdue'
                WHEN MAX(v.follow_up_due) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'Upcoming'
                ELSE 'Active'
            END as follow_up_status
        FROM patients p
        LEFT JOIN visits v ON p.patient_id = v.patient_id
        WHERE p.name LIKE ? OR p.phone LIKE ?
        GROUP BY p.patient_id
        ORDER BY p.name
        LIMIT ? OFFSET ?";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("ssii", $search, $search, $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();
$patients = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get total count
$countSql = "SELECT COUNT(*) as total FROM patients WHERE name LIKE ? OR phone LIKE ?";
$countStmt = $mysqli->prepare($countSql);
$countStmt->bind_param("ss", $search, $search);
$countStmt->execute();
$countResult = $countStmt->get_result();
$totalPatients = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalPatients / $limit);
$countStmt->close();

// Get summary statistics
$summarySql = "SELECT 
                    COUNT(*) as total_patients,
                    SUM(CASE WHEN TIMESTAMPDIFF(YEAR, dob, CURDATE()) < 18 THEN 1 ELSE 0 END) as children,
                    SUM(CASE WHEN TIMESTAMPDIFF(YEAR, dob, CURDATE()) BETWEEN 18 AND 60 THEN 1 ELSE 0 END) as adults,
                    SUM(CASE WHEN TIMESTAMPDIFF(YEAR, dob, CURDATE()) > 60 THEN 1 ELSE 0 END) as seniors,
                    COUNT(DISTINCT v.patient_id) as active_patients
                FROM patients p
                LEFT JOIN visits v ON p.patient_id = v.patient_id 
                AND v.visit_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)";
$summaryResult = $mysqli->query($summarySql);
$summary = $summaryResult->fetch_assoc();
?>

<!-- Summary Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1rem; border-radius: 10px; text-align: center;">
        <div style="font-size: 2rem; font-weight: bold;"><?= $summary['total_patients'] ?? 0 ?></div>
        <div style="font-size: 0.9rem;">Total Patients</div>
    </div>
    <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 1rem; border-radius: 10px; text-align: center;">
        <div style="font-size: 2rem; font-weight: bold;"><?= $summary['children'] ?? 0 ?></div>
        <div style="font-size: 0.9rem;">Children (Below 18)</div>
    </div>
    <div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 1rem; border-radius: 10px; text-align: center;">
        <div style="font-size: 2rem; font-weight: bold;"><?= $summary['adults'] ?? 0 ?></div>
        <div style="font-size: 0.9rem;">Adults (18-60)</div>
    </div>
    <div style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; padding: 1rem; border-radius: 10px; text-align: center;">
        <div style="font-size: 2rem; font-weight: bold;"><?= $summary['seniors'] ?? 0 ?></div>
        <div style="font-size: 0.9rem;">Seniors (Above 60)</div>
    </div>
    <div style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; padding: 1rem; border-radius: 10px; text-align: center;">
        <div style="font-size: 2rem; font-weight: bold;"><?= $summary['active_patients'] ?? 0 ?></div>
        <div style="font-size: 0.9rem;">Active (Last 6 Months)</div>
    </div>
</div>

<!-- Search Form -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Patient List</h2>
        <a href="add.php" class="btn btn-success">+ Add New Patient</a>
    </div>
    
    <form method="GET" action="" style="margin-bottom: 1.5rem;">
        <div style="display: flex; gap: 10px; align-items: center;">
            <input type="text" name="search" class="form-control" 
                   placeholder="Search by patient name or phone number..." 
                   value="<?= htmlspecialchars($search_term) ?>"
                   style="flex: 1;">
            <button type="submit" class="btn btn-primary">Search</button>
            <?php if($search_term): ?>
                <a href="list.php" class="btn btn-secondary">Clear</a>
            <?php endif; ?>
        </div>
    </form>
    
    <!-- Export Options -->
    <div style="margin-bottom: 1rem; text-align: right;">
        <button onclick="exportToCSV()" class="btn btn-secondary">Export to CSV</button>
        <button onclick="window.print()" class="btn btn-secondary">Print</button>
    </div>
    
    <!-- Patients Table - 8 COLUMNS (No Gender) -->
    <div class="table-container">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="padding: 12px; text-align: left; background: #2c7da0; color: white;">Name</th>
                    <th style="padding: 12px; text-align: left; background: #2c7da0; color: white;">Age</th>
                    <th style="padding: 12px; text-align: left; background: #2c7da0; color: white;">Date of Birth</th>
                    <th style="padding: 12px; text-align: left; background: #2c7da0; color: white;">Phone</th>
                    <th style="padding: 12px; text-align: left; background: #2c7da0; color: white;">Join Date</th>
                    <th style="padding: 12px; text-align: left; background: #2c7da0; color: white;">Last Visit</th>
                    <th style="padding: 12px; text-align: left; background: #2c7da0; color: white;">Status</th>
                    <th style="padding: 12px; text-align: left; background: #2c7da0; color: white;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($patients) > 0): ?>
                    <?php foreach($patients as $patient): ?>
                    <tr style="border-bottom: 1px solid #ddd;">
                        <td style="padding: 10px;">
                            <a href="view.php?id=<?= $patient['patient_id'] ?>" style="color: #2c7da0; text-decoration: none; font-weight: bold;">
                                <?= htmlspecialchars($patient['name']) ?>
                            </a>
                        </td>
                        <td style="padding: 10px;"><?= $patient['age_full'] ?></td>
                        <td style="padding: 10px;"><?= date('M d, Y', strtotime($patient['dob'])) ?></td>
                        <td style="padding: 10px;"><?= htmlspecialchars($patient['phone']) ?></td>
                        <td style="padding: 10px;"><?= date('M d, Y', strtotime($patient['join_date'])) ?></td>
                        <td style="padding: 10px;">
                            <?php if($patient['last_visit_date']): ?>
                                <?= date('M d, Y', strtotime($patient['last_visit_date'])) ?>
                                <small style="color: #666;">(<?= $patient['days_since_last_visit'] ?> days ago)</small>
                            <?php else: ?>
                                <span style="color: #999;">No visits</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 10px;">
                            <?php 
                            $statusColor = '';
                            $statusText = '';
                            switch($patient['follow_up_status']) {
                                case 'Overdue':
                                    $statusColor = '#dc3545';
                                    $statusText = 'Follow-up Overdue';
                                    break;
                                case 'Upcoming':
                                    $statusColor = '#ffc107';
                                    $statusText = 'Follow-up Soon';
                                    break;
                                case 'No follow-up':
                                    $statusColor = '#17a2b8';
                                    $statusText = 'No Follow-up';
                                    break;
                                default:
                                    $statusColor = '#28a745';
                                    $statusText = 'Active';
                            }
                            ?>
                            <span style="background: <?= $statusColor ?>; color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                <?= $statusText ?>
                            </span>
                        </td>
                        <td style="padding: 10px;">
                            <div style="display: flex; gap: 5px;">
                                <a href="view.php?id=<?= $patient['patient_id'] ?>" style="background: #17a2b8; color: white; padding: 5px 8px; border-radius: 4px; text-decoration: none; font-size: 12px;">View</a>
                                <a href="edit.php?id=<?= $patient['patient_id'] ?>" style="background: #ffc107; color: #333; padding: 5px 8px; border-radius: 4px; text-decoration: none; font-size: 12px;">Edit</a>
                                <button onclick="confirmDelete(<?= $patient['patient_id'] ?>, '<?= htmlspecialchars($patient['name']) ?>')" style="background: #dc3545; color: white; padding: 5px 8px; border-radius: 4px; border: none; cursor: pointer; font-size: 12px;">Delete</button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" style="padding: 40px; text-align: center;">
                            No patients found
                            <?php if($search_term): ?>
                                <br><small>Try different search terms or <a href="list.php">clear search</a></small>
                            <?php else: ?>
                                <br><small>Click "Add New Patient" to get started</small>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if($totalPages > 1): ?>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem; padding-top: 1rem; border-top: 1px solid #ddd;">
        <div style="color: #666;">
            Showing <?= (($page - 1) * $limit) + 1 ?> to 
            <?= min($page * $limit, $totalPatients) ?> of 
            <?= $totalPatients ?> patients
        </div>
        <div style="display: flex; gap: 5px;">
            <?php if($page > 1): ?>
                <a href="?page=1&search=<?= urlencode($search_term) ?>" style="padding: 8px 12px; border: 1px solid #ddd; color: #007bff; text-decoration: none; border-radius: 4px;">First</a>
                <a href="?page=<?= $page-1 ?>&search=<?= urlencode($search_term) ?>" style="padding: 8px 12px; border: 1px solid #ddd; color: #007bff; text-decoration: none; border-radius: 4px;">Previous</a>
            <?php endif; ?>
            
            <?php 
            $start = max(1, $page - 2);
            $end = min($totalPages, $page + 2);
            for($i = $start; $i <= $end; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search_term) ?>" 
                   style="padding: 8px 12px; border: 1px solid #ddd; <?= $i == $page ? 'background: #007bff; color: white;' : 'color: #007bff;' ?> text-decoration: none; border-radius: 4px;">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
            
            <?php if($page < $totalPages): ?>
                <a href="?page=<?= $page+1 ?>&search=<?= urlencode($search_term) ?>" style="padding: 8px 12px; border: 1px solid #ddd; color: #007bff; text-decoration: none; border-radius: 4px;">Next</a>
                <a href="?page=<?= $totalPages ?>&search=<?= urlencode($search_term) ?>" style="padding: 8px 12px; border: 1px solid #ddd; color: #007bff; text-decoration: none; border-radius: 4px;">Last</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function confirmDelete(id, name) {
    if(confirm("Are you sure you want to delete patient \"" + name + "\"?\n\nThis action cannot be undone!")) {
        window.location.href = "delete.php?id=" + id;
    }
}

function exportToCSV() {
    const rows = document.querySelectorAll('table tr');
    let csv = [];
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('th, td');
        const rowData = Array.from(cells).map(cell => {
            let text = cell.innerText;
            text = text.trim();
            return "\"" + text + "\"";
        });
        if(rowData.length > 1) csv.push(rowData.join(','));
    });
    
    const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = "patients_" + new Date().toISOString().split('T')[0] + ".csv";
    a.click();
    URL.revokeObjectURL(url);
}
</script>

<?php include '../includes/footer.php'; ?>