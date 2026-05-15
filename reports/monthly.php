<?php
// reports/monthly.php
require_once '../config/db.php';
include '../includes/header.php';

// Get monthly visits report (last 6 months)
$visitsMonthlySql = "
    SELECT 
        DATE_FORMAT(visit_date, '%Y-%m') as month,
        DATE_FORMAT(visit_date, '%M %Y') as month_name,
        COUNT(*) as total_visits,
        SUM(consultation_fee + lab_fee) as total_revenue,
        AVG(consultation_fee + lab_fee) as avg_visit_cost
    FROM visits
    WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(visit_date, '%Y-%m')
    ORDER BY month ASC
";
$monthlyVisitsResult = $mysqli->query($visitsMonthlySql);
$monthlyVisits = $monthlyVisitsResult->fetch_all(MYSQLI_ASSOC);

// Get patients joined per month (last 12 months)
$joinedMonthlySql = "
    SELECT 
        DATE_FORMAT(join_date, '%Y-%m') as month,
        DATE_FORMAT(join_date, '%M %Y') as month_name,
        COUNT(*) as patients_joined
    FROM patients
    WHERE join_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(join_date, '%Y-%m')
    ORDER BY month ASC
";
$monthlyJoinsResult = $mysqli->query($joinedMonthlySql);
$monthlyJoins = $monthlyJoinsResult->fetch_all(MYSQLI_ASSOC);

// Get patients grouped by join month
$joinMonthGroupsSql = "
    SELECT 
        DATE_FORMAT(join_date, '%M') as join_month,
        MONTH(join_date) as month_num,
        COUNT(*) as patient_count,
        GROUP_CONCAT(name SEPARATOR ', ') as patient_names
    FROM patients
    GROUP BY DATE_FORMAT(join_date, '%M'), MONTH(join_date)
    ORDER BY month_num ASC
";
$joinMonthGroupsResult = $mysqli->query($joinMonthGroupsSql);
$joinMonthGroups = $joinMonthGroupsResult->fetch_all(MYSQLI_ASSOC);

// Get summary statistics
$summaryStatsSql = "
    SELECT 
        (SELECT COUNT(*) FROM visits WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)) as total_visits_6months,
        (SELECT SUM(consultation_fee + lab_fee) FROM visits WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)) as total_revenue_6months,
        (SELECT COUNT(*) FROM patients WHERE join_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)) as new_patients_12months,
        (SELECT ROUND(AVG(consultation_fee + lab_fee), 2) FROM visits WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)) as avg_visit_cost
";
$summaryResult = $mysqli->query($summaryStatsSql);
$summary = $summaryResult->fetch_assoc();
?>

<!-- Summary Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1.5rem; border-radius: 10px; text-align: center;">
        <div style="font-size: 2rem; font-weight: bold;"><?= $summary['total_visits_6months'] ?? 0 ?></div>
        <div style="font-size: 0.9rem; margin-top: 0.5rem;">Total Visits</div>
        <div style="font-size: 0.75rem; opacity: 0.9;">Last 6 Months</div>
    </div>
    <div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 1.5rem; border-radius: 10px; text-align: center;">
        <div style="font-size: 2rem; font-weight: bold;">$<?= number_format($summary['total_revenue_6months'] ?? 0, 2) ?></div>
        <div style="font-size: 0.9rem; margin-top: 0.5rem;">Total Revenue</div>
        <div style="font-size: 0.75rem; opacity: 0.9;">Last 6 Months</div>
    </div>
    <div style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); color: white; padding: 1.5rem; border-radius: 10px; text-align: center;">
        <div style="font-size: 2rem; font-weight: bold;"><?= $summary['new_patients_12months'] ?? 0 ?></div>
        <div style="font-size: 0.9rem; margin-top: 0.5rem;">New Patients</div>
        <div style="font-size: 0.75rem; opacity: 0.9;">Last 12 Months</div>
    </div>
    <div style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; padding: 1.5rem; border-radius: 10px; text-align: center;">
        <div style="font-size: 2rem; font-weight: bold;">$<?= number_format($summary['avg_visit_cost'] ?? 0, 2) ?></div>
        <div style="font-size: 0.9rem; margin-top: 0.5rem;">Average Cost</div>
        <div style="font-size: 0.75rem; opacity: 0.9;">Per Visit</div>
    </div>
</div>

<!-- Monthly Visit Report -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Monthly Visit Report</h2>
        <div style="display: flex; gap: 0.5rem;">
            <button onclick="exportToCSV('visits')" class="btn btn-secondary" style="padding: 0.3rem 0.8rem; font-size: 0.85rem;">Export to CSV</button>
            <button onclick="window.print()" class="btn btn-secondary" style="padding: 0.3rem 0.8rem; font-size: 0.85rem;">Print</button>
        </div>
    </div>
    <div class="table-container">
        <table style="width: 100%; border-collapse: collapse;" id="visitsTable">
            <thead>
                <tr style="background: #2c7da0; color: white;">
                    <th style="padding: 12px; text-align: left;">Month</th>
                    <th style="padding: 12px; text-align: center;">Total Visits</th>
                    <th style="padding: 12px; text-align: right;">Total Revenue</th>
                    <th style="padding: 12px; text-align: right;">Average Visit Cost</th>
                </tr>
            </thead>
            <tbody>
                <?php if(count($monthlyVisits) > 0): ?>
                    <?php foreach($monthlyVisits as $report): ?>
                    <tr style="border-bottom: 1px solid #ddd;">
                        <td style="padding: 10px;"><strong><?= htmlspecialchars($report['month_name']) ?></strong></td>
                        <td style="padding: 10px; text-align: center;"><?= $report['total_visits'] ?></td>
                        <td style="padding: 10px; text-align: right; color: #28a745; font-weight: 500;">$<?= number_format($report['total_revenue'], 2) ?></td>
                        <td style="padding: 10px; text-align: right;">$<?= number_format($report['avg_visit_cost'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="padding: 40px; text-align: center; color: #666;">No visit data available</a>
                    </tr>
                <?php endif; ?>
            </tbody>
            <?php if(!empty($monthlyVisits)): ?>
            <tfoot style="background: #f8f9fa; font-weight: bold; border-top: 2px solid #dee2e6;">
                <tr>
                    <td style="padding: 10px;"><strong>Total / Average</strong></td>
                    <td style="padding: 10px; text-align: center;"><strong><?= array_sum(array_column($monthlyVisits, 'total_visits')) ?></strong></td>
                    <td style="padding: 10px; text-align: right;"><strong>$<?= number_format(array_sum(array_column($monthlyVisits, 'total_revenue')), 2) ?></strong></td>
                    <td style="padding: 10px; text-align: right;"><strong>$<?= number_format(array_sum(array_column($monthlyVisits, 'total_revenue')) / count($monthlyVisits), 2) ?></strong></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; margin-bottom: 1.5rem;">
    <!-- Patients Joined Per Month -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Patients Joined Per Month</h3>
        </div>
        <div class="table-container">
            <table style="width: 100%; border-collapse: collapse;" id="joinsTable">
                <thead>
                    <tr style="background: #2c7da0; color: white;">
                        <th style="padding: 12px; text-align: left;">Month</th>
                        <th style="padding: 12px; text-align: center;">New Patients</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($monthlyJoins) > 0): ?>
                        <?php foreach($monthlyJoins as $join): ?>
                        <tr style="border-bottom: 1px solid #ddd;">
                            <td style="padding: 10px;"><?= htmlspecialchars($join['month_name']) ?></td>
                            <td style="padding: 10px; text-align: center;">
                                <span style="background: #28a745; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.85rem;">
                                    <?= $join['patients_joined'] ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="2" style="padding: 40px; text-align: center; color: #666;">No patient join data available</a>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <?php if(!empty($monthlyJoins)): ?>
                <tfoot style="background: #f8f9fa; font-weight: bold; border-top: 2px solid #dee2e6;">
                    <tr>
                        <td style="padding: 10px;"><strong>Total New Patients:</strong></a>
                        <td style="padding: 10px; text-align: center;"><strong><?= array_sum(array_column($monthlyJoins, 'patients_joined')) ?></strong></a>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>
    
    <!-- Patients Grouped by Join Month -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Patients Grouped by Join Month</h3>
        </div>
        <div class="table-container">
            <table style="width: 100%; border-collapse: collapse;" id="groupsTable">
                <thead>
                    <tr style="background: #2c7da0; color: white;">
                        <th style="padding: 12px; text-align: left;">Join Month</th>
                        <th style="padding: 12px; text-align: center;">Patients Count</th>
                        <th style="padding: 12px; text-align: left;">Patient Names</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(count($joinMonthGroups) > 0): ?>
                        <?php foreach($joinMonthGroups as $group): ?>
                        <tr style="border-bottom: 1px solid #ddd;">
                            <td style="padding: 10px;"><strong><?= htmlspecialchars($group['join_month']) ?></strong></td>
                            <td style="padding: 10px; text-align: center;">
                                <span style="background: #17a2b8; color: white; padding: 4px 12px; border-radius: 20px; font-size: 0.85rem;">
                                    <?= $group['patient_count'] ?>
                                </span>
                            </td>
                            <td style="padding: 10px; font-size: 0.85rem; color: #555;">
                                <?php 
                                $names = explode(', ', $group['patient_names']);
                                $displayNames = array_slice($names, 0, 3);
                                echo htmlspecialchars(implode(', ', $displayNames));
                                if(count($names) > 3) {
                                    $remaining = count($names) - 3;
                                    echo ' <span style="color: #007bff; cursor: help; border-bottom: 1px dotted #007bff;" title="' . htmlspecialchars($group['patient_names']) . '">+' . $remaining . ' more</span>';
                                }
                                ?>
                             </a>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" style="padding: 40px; text-align: center; color: #666;">No patient data available</a>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Export to CSV function
function exportToCSV(tableType) {
    let tableId = '';
    let filename = '';
    
    if(tableType === 'visits') {
        tableId = 'visitsTable';
        filename = 'monthly_visits_report';
    } else if(tableType === 'joins') {
        tableId = 'joinsTable';
        filename = 'patients_joined_report';
    } else {
        tableId = 'groupsTable';
        filename = 'patients_grouped_report';
    }
    
    const table = document.getElementById(tableId);
    if(!table) return;
    
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
    a.download = filename + '_' + new Date().toISOString().split('T')[0] + '.csv';
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

/* Responsive */
@media (max-width: 768px) {
    div[style*="grid-template-columns: repeat(2, 1fr)"] {
        grid-template-columns: 1fr !important;
        gap: 1rem !important;
    }
    
    .table-container {
        overflow-x: auto;
    }
    
    table {
        min-width: 500px;
    }
    
    .card-header {
        flex-direction: column;
        text-align: center;
    }
}

/* Hover effect */
tbody tr:hover {
    background-color: #f8f9fa;
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