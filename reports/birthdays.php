<?php
// reports/birthdays.php
require_once '../config/db.php';
include '../includes/header.php';

// Get birthdays in next 30 days
$birthdaysNext30Sql = "
    SELECT 
        name,
        dob,
        phone,
        TIMESTAMPDIFF(YEAR, dob, CURDATE()) as age,
        DATE_FORMAT(dob, '%M %d') as birthday_date,
        DATEDIFF(
            STR_TO_DATE(CONCAT(YEAR(CURDATE()), '-', MONTH(dob), '-', DAY(dob)), '%Y-%m-%d'),
            CURDATE()
        ) as days_until_birthday,
        CASE 
            WHEN TIMESTAMPDIFF(YEAR, dob, CURDATE()) BETWEEN 0 AND 12 THEN 'Child'
            WHEN TIMESTAMPDIFF(YEAR, dob, CURDATE()) BETWEEN 13 AND 19 THEN 'Teen'
            WHEN TIMESTAMPDIFF(YEAR, dob, CURDATE()) BETWEEN 20 AND 35 THEN 'Young Adult'
            WHEN TIMESTAMPDIFF(YEAR, dob, CURDATE()) BETWEEN 36 AND 50 THEN 'Adult'
            ELSE 'Senior'
        END as age_group
    FROM patients
    WHERE 
        DATE_FORMAT(dob, '%m-%d') BETWEEN DATE_FORMAT(CURDATE(), '%m-%d') 
        AND DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 30 DAY), '%m-%d')
    ORDER BY days_until_birthday ASC
";

$birthdaysResult = $mysqli->query($birthdaysNext30Sql);
$birthdaysNext30 = $birthdaysResult->fetch_all(MYSQLI_ASSOC);

// Get milestone birthdays
$turningAgesSql = "
    SELECT 
        name,
        dob,
        phone,
        TIMESTAMPDIFF(YEAR, dob, CURDATE()) as current_age,
        TIMESTAMPDIFF(YEAR, dob, DATE_ADD(CURDATE(), INTERVAL 1 YEAR)) as turning_age,
        CASE 
            WHEN TIMESTAMPDIFF(YEAR, dob, DATE_ADD(CURDATE(), INTERVAL 1 YEAR)) = 18 THEN 'Coming of Age (18)'
            WHEN TIMESTAMPDIFF(YEAR, dob, DATE_ADD(CURDATE(), INTERVAL 1 YEAR)) = 21 THEN 'Legal Adult (21)'
            WHEN TIMESTAMPDIFF(YEAR, dob, DATE_ADD(CURDATE(), INTERVAL 1 YEAR)) = 30 THEN 'Dirty Thirty (30)'
            WHEN TIMESTAMPDIFF(YEAR, dob, DATE_ADD(CURDATE(), INTERVAL 1 YEAR)) = 40 THEN 'Over the Hill (40)'
            WHEN TIMESTAMPDIFF(YEAR, dob, DATE_ADD(CURDATE(), INTERVAL 1 YEAR)) = 50 THEN 'Golden Jubilee (50)'
            WHEN TIMESTAMPDIFF(YEAR, dob, DATE_ADD(CURDATE(), INTERVAL 1 YEAR)) = 60 THEN 'Diamond Year (60)'
            WHEN TIMESTAMPDIFF(YEAR, dob, DATE_ADD(CURDATE(), INTERVAL 1 YEAR)) = 70 THEN 'Platinum (70)'
            WHEN TIMESTAMPDIFF(YEAR, dob, DATE_ADD(CURDATE(), INTERVAL 1 YEAR)) = 80 THEN 'Octogenarian (80)'
            WHEN TIMESTAMPDIFF(YEAR, dob, DATE_ADD(CURDATE(), INTERVAL 1 YEAR)) = 90 THEN 'Nonagenarian (90)'
            WHEN TIMESTAMPDIFF(YEAR, dob, DATE_ADD(CURDATE(), INTERVAL 1 YEAR)) = 100 THEN 'Centenarian (100)'
            ELSE NULL
        END as milestone
    FROM patients
    WHERE 
        TIMESTAMPDIFF(YEAR, dob, DATE_ADD(CURDATE(), INTERVAL 1 YEAR)) IN (18, 21, 30, 40, 50, 60, 70, 80, 90, 100)
    ORDER BY turning_age ASC
";

$turningResult = $mysqli->query($turningAgesSql);
$turningAges = $turningResult->fetch_all(MYSQLI_ASSOC);

// Get upcoming birthdays summary statistics
$statsSql = "
    SELECT 
        COUNT(*) as total_upcoming,
        SUM(CASE WHEN days_until_birthday = 0 THEN 1 ELSE 0 END) as today_birthdays,
        SUM(CASE WHEN days_until_birthday BETWEEN 1 AND 7 THEN 1 ELSE 0 END) as this_week,
        SUM(CASE WHEN days_until_birthday BETWEEN 8 AND 30 THEN 1 ELSE 0 END) as next_three_weeks,
        AVG(age) as avg_age
    FROM (
        SELECT 
            TIMESTAMPDIFF(YEAR, dob, CURDATE()) as age,
            DATEDIFF(
                STR_TO_DATE(CONCAT(YEAR(CURDATE()), '-', MONTH(dob), '-', DAY(dob)), '%Y-%m-%d'),
                CURDATE()
            ) as days_until_birthday
        FROM patients
        WHERE 
            DATE_FORMAT(dob, '%m-%d') BETWEEN DATE_FORMAT(CURDATE(), '%m-%d') 
            AND DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 30 DAY), '%m-%d')
    ) as upcoming
";

$statsResult = $mysqli->query($statsSql);
$stats = $statsResult->fetch_assoc();

// Get birthdays by month for current year
$monthlyBirthdaysSql = "
    SELECT 
        MONTHNAME(dob) as month_name,
        MONTH(dob) as month_num,
        COUNT(*) as birthday_count
    FROM patients
    WHERE YEAR(dob) <= YEAR(CURDATE())
    GROUP BY MONTH(dob), MONTHNAME(dob)
    ORDER BY month_num
";

$monthlyResult = $mysqli->query($monthlyBirthdaysSql);
$monthlyBirthdays = $monthlyResult->fetch_all(MYSQLI_ASSOC);
?>

<!-- Birthday Summary Cards -->
<div class="summary-cards">
    <div class="summary-card birthday-card">
        <div class="summary-icon"></div>
        <div class="summary-value"><?= $stats['total_upcoming'] ?? 0 ?></div>
        <div class="summary-label">Upcoming Birthdays</div>
    </div>
    <div class="summary-card today-card">
        <div class="summary-icon"></div>
        <div class="summary-value"><?= $stats['today_birthdays'] ?? 0 ?></div>
        <div class="summary-label">Today's Birthdays</div>
    </div>
    <div class="summary-card week-card">
        <div class="summary-icon"></div>
        <div class="summary-value"><?= $stats['this_week'] ?? 0 ?></div>
        <div class="summary-label">This Week</div>
    </div>
    <div class="summary-card month-card">
        <div class="summary-icon"></div>
        <div class="summary-value"><?= $stats['next_three_weeks'] ?? 0 ?></div>
        <div class="summary-label">Next 3 Weeks</div>
    </div>
    <div class="summary-card age-card">
        <div class="summary-icon"></div>
        <div class="summary-value"><?= round($stats['avg_age'] ?? 0) ?></div>
        <div class="summary-label">Average Age</div>
    </div>
</div>

<div class="grid-2">
    <!-- Upcoming Birthdays -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Upcoming Birthdays (Next 30 Days)</h3>
            <button onclick="exportBirthdays()" class="btn btn-secondary btn-sm">📊 Export List</button>
        </div>
        
        <?php if(!empty($birthdaysNext30)): ?>
            <div class="birthday-list">
                <?php 
                $current_date = date('Y-m-d');
                foreach($birthdaysNext30 as $patient): 
                    $isToday = $patient['days_until_birthday'] == 0;
                    $urgentClass = $isToday ? 'urgent-birthday' : ($patient['days_until_birthday'] <= 7 ? 'week-birthday' : '');
                ?>
                <div class="birthday-item <?= $urgentClass ?>">
                    <div class="birthday-avatar">
                        <?= strtoupper(substr($patient['name'], 0, 2)) ?>
                    </div>
                    <div class="birthday-info">
                        <div class="birthday-name">
                            <strong><?= htmlspecialchars($patient['name']) ?></strong>
                            <span class="age-group"><?= $patient['age_group'] ?></span>
                        </div>
                        <div class="birthday-details">
                            <span class="birthday-date"><?= $patient['birthday_date'] ?></span>
                            <span class="birthday-age">Turning <?= $patient['age'] + 1 ?> years</span>
                            <?php if($patient['phone']): ?>
                                <span class="birthday-phone">📞 <?= htmlspecialchars($patient['phone']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="birthday-countdown">
                        <?php if($isToday): ?>
                            <span class="badge badge-success pulse">🎉 TODAY!</span>
                        <?php else: ?>
                            <div class="countdown-timer">
                                <span class="countdown-days"><?= $patient['days_until_birthday'] ?></span>
                                <span class="countdown-label">days</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="birthday-actions">
                        <button onclick="sendBirthdayWish(<?= $patient['phone'] ?>, '<?= htmlspecialchars($patient['name']) ?>')" 
                                class="btn-icon btn-wish" title="Send Wish">💝</button>
                        <a href="/healthcare_db/patients/view.php?id=<?= $patient['patient_id'] ?>" 
                           class="btn-icon btn-view" title="View Profile">👁️</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon"></div>
                <p>No birthdays in the next 30 days</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Milestone Birthdays -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"> Milestone Birthdays This Year</h3>
            <button onclick="sendMilestoneWishes()" class="btn btn-primary btn-sm">💝 Send Wishes</button>
        </div>
        
        <?php if(!empty($turningAges)): ?>
            <div class="milestone-list">
                <?php foreach($turningAges as $patient): ?>
                <div class="milestone-item">
                    <div class="milestone-icon">
                        <?php 
                        if(strpos($patient['milestone'], '18') !== false) echo '🎓';
                        elseif(strpos($patient['milestone'], '21') !== false) echo '🍾';
                        elseif(strpos($patient['milestone'], '30') !== false) echo '🎈';
                        elseif(strpos($patient['milestone'], '40') !== false) echo '🎯';
                        elseif(strpos($patient['milestone'], '50') !== false) echo '👑';
                        elseif(strpos($patient['milestone'], '60') !== false) echo '💎';
                        elseif(strpos($patient['milestone'], '70') !== false) echo '🌟';
                        elseif(strpos($patient['milestone'], '80') !== false) echo '🏆';
                        elseif(strpos($patient['milestone'], '90') !== false) echo '🏅';
                        else echo '';
                        ?>
                    </div>
                    <div class="milestone-info">
                        <div class="milestone-name"><?= htmlspecialchars($patient['name']) ?></div>
                        <div class="milestone-details">
                            <?= $patient['milestone'] ?> • Current: <?= $patient['current_age'] ?> years
                        </div>
                    </div>
                    <div class="milestone-badge">
                        <span class="badge badge-warning">Special!</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon"></div>
                <p>No milestone birthdays this year</p>
            </div>
        <?php endif; ?>
        
        <!-- Birthday Tips -->
        <div class="birthday-tips">
            <h4>Birthday Reminder Tips</h4>
            <ul>
                <li> Call patients a day before their birthday</li>
                <li> Send personalized birthday messages</li>
                <li> Offer special health checkup discounts for milestone birthdays</li>
                <li> Use email templates for birthday wishes</li>
            </ul>
        </div>
    </div>
</div>

<!-- Monthly Birthday Distribution -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title"> Birthday Distribution by Month</h3>
        <button onclick="showMonthlyChart()" class="btn btn-secondary btn-sm">Show Chart</button>
    </div>
    <div class="monthly-distribution">
        <?php 
        $maxBirthdays = !empty($monthlyBirthdays) ? max(array_column($monthlyBirthdays, 'birthday_count')) : 1;
        foreach($monthlyBirthdays as $month): 
            $percentage = ($month['birthday_count'] / $maxBirthdays) * 100;
        ?>
        <div class="month-bar">
            <div class="month-label"><?= substr($month['month_name'], 0, 3) ?></div>
            <div class="bar-container">
                <div class="bar-fill" style="width: <?= $percentage ?>%">
                    <span class="bar-count"><?= $month['birthday_count'] ?></span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
// Export birthdays to CSV
function exportBirthdays() {
    const birthdays = <?= json_encode($birthdaysNext30) ?>;
    let csv = [["Name", "Birthday Date", "Age", "Days Until", "Phone"]];
    
    birthdays.forEach(patient => {
        csv.push([
            patient.name,
            patient.birthday_date,
            patient.age,
            patient.days_until_birthday,
            patient.phone || ''
        ]);
    });
    
    let csvContent = csv.map(row => row.join(",")).join("\n");
    let blob = new Blob([csvContent], { type: 'text/csv' });
    let url = URL.createObjectURL(blob);
    let a = document.createElement('a');
    a.href = url;
    a.download = `birthdays_${new Date().toISOString().split('T')[0]}.csv`;
    a.click();
    URL.revokeObjectURL(url);
}

// Send birthday wish (simulated)
function sendBirthdayWish(phone, name) {
    if(phone) {
        showNotification(`📱 Opening messaging app to wish ${name}...`, 'success');
        // In real implementation, you would integrate with SMS API
        // window.location.href = `sms:${phone}?body=Happy Birthday ${name}!`;
    } else {
        showNotification(`No phone number available for ${name}`, 'error');
    }
}

// Send milestone wishes
function sendMilestoneWishes() {
    const milestones = <?= json_encode($turningAges) ?>;
    if(milestones.length > 0) {
        showNotification(` Preparing to send wishes to ${milestones.length} patients celebrating milestones!`, 'success');
    } else {
        showNotification('No milestone birthdays to celebrate', 'info');
    }
}

// Show monthly chart
function showMonthlyChart() {
    const months = <?= json_encode($monthlyBirthdays) ?>;
    let message = "Birthday Distribution:\n\n";
    months.forEach(month => {
        message += `${month.month_name}: ${'⭐'.repeat(Math.ceil(month.birthday_count/2))} (${month.birthday_count})\n`;
    });
    alert(message);
}

// Notification function
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = message;
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 3000);
}

// Auto-refresh birthdays every hour
setInterval(() => {
    location.reload();
}, 3600000);
</script>

<style>
/* Summary Cards */
.summary-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.summary-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1rem;
    border-radius: 10px;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.summary-icon {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.summary-value {
    font-size: 2rem;
    font-weight: bold;
}

.summary-label {
    font-size: 0.9rem;
    opacity: 0.9;
}

.birthday-card { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
.today-card { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
.week-card { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
.month-card { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
.age-card { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }

/* Birthday List */
.birthday-list, .milestone-list {
    max-height: 500px;
    overflow-y: auto;
}

.birthday-item, .milestone-item {
    display: flex;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid #eee;
    transition: background 0.3s;
}

.birthday-item:hover, .milestone-item:hover {
    background: #f8f9fa;
}

.urgent-birthday {
    background: linear-gradient(90deg, #ffe6e6 0%, #ffffff 100%);
    border-left: 4px solid #dc3545;
}

.week-birthday {
    background: linear-gradient(90deg, #fff3e6 0%, #ffffff 100%);
    border-left: 4px solid #ffc107;
}

.birthday-avatar, .milestone-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 1.2rem;
    margin-right: 1rem;
}

.birthday-info, .milestone-info {
    flex: 1;
}

.birthday-name {
    font-size: 1.1rem;
    margin-bottom: 0.25rem;
}

.age-group {
    display: inline-block;
    background: #e9ecef;
    padding: 0.2rem 0.5rem;
    border-radius: 12px;
    font-size: 0.7rem;
    margin-left: 0.5rem;
}

.birthday-details {
    display: flex;
    gap: 1rem;
    font-size: 0.85rem;
    color: #666;
}

.birthday-date, .birthday-age, .birthday-phone {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
}

.birthday-countdown {
    text-align: center;
    margin: 0 1rem;
}

.countdown-timer {
    background: #007bff;
    color: white;
    padding: 0.3rem 0.8rem;
    border-radius: 20px;
}

.countdown-days {
    font-size: 1.2rem;
    font-weight: bold;
}

.countdown-label {
    font-size: 0.7rem;
}

.birthday-actions {
    display: flex;
    gap: 0.5rem;
}

/* Milestone Items */
.milestone-icon {
    background: linear-gradient(135deg, #ffd89b 0%, #19547b 100%);
}

.milestone-name {
    font-weight: bold;
    margin-bottom: 0.25rem;
}

.milestone-details {
    font-size: 0.85rem;
    color: #666;
}

/* Birthday Tips */
.birthday-tips {
    margin-top: 1rem;
    padding: 1rem;
    background: #e7f3ff;
    border-radius: 8px;
    border-left: 4px solid #007bff;
}

.birthday-tips h4 {
    margin: 0 0 0.5rem 0;
}

.birthday-tips ul {
    margin: 0;
    padding-left: 1.5rem;
}

.birthday-tips li {
    margin: 0.25rem 0;
}

/* Monthly Distribution */
.monthly-distribution {
    padding: 1rem;
}

.month-bar {
    display: flex;
    align-items: center;
    margin-bottom: 0.75rem;
}

.month-label {
    width: 40px;
    font-weight: bold;
}

.bar-container {
    flex: 1;
    background: #e9ecef;
    border-radius: 10px;
    overflow: hidden;
    margin-left: 0.5rem;
}

.bar-fill {
    background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 0.3rem;
    text-align: right;
    border-radius: 10px;
    transition: width 1s ease;
}

.bar-count {
    display: inline-block;
    padding-right: 0.5rem;
    font-size: 0.8rem;
}

/* Animations */
@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.pulse {
    animation: pulse 1s infinite;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 3rem;
}

.empty-icon {
    font-size: 3rem;
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
    .summary-cards {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .birthday-item, .milestone-item {
        flex-wrap: wrap;
    }
    
    .birthday-details {
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .birthday-countdown {
        margin: 0.5rem 0;
    }
}

/* Buttons */
.btn-sm {
    padding: 0.3rem 0.8rem;
    font-size: 0.85rem;
}

.btn-icon {
    padding: 0.3rem 0.5rem;
    background: none;
    border: none;
    cursor: pointer;
    font-size: 1.2rem;
}

.btn-wish:hover { transform: scale(1.1); }
.btn-view:hover { transform: scale(1.1); }
</style>

<?php include '../includes/footer.php'; ?>