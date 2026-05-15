<?php
// visits/add.php
require_once '../config/db.php';
include '../includes/header.php';

$patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : 0;
$errors = [];
$success = false;

// Fetch patients using MySQLi
$patientsQuery = "SELECT patient_id, name FROM patients ORDER BY name";
$patientsResult = $mysqli->query($patientsQuery);
$patients = $patientsResult->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $patient_id = $_POST['patient_id'];
    $visit_date = $_POST['visit_date'];
    $consultation_fee = $_POST['consultation_fee'];
    $lab_fee = $_POST['lab_fee'];
    $symptoms = $_POST['symptoms'];
    $diagnosis = $_POST['diagnosis'];
    $prescription = $_POST['prescription'];
    
    // Validation
    if (empty($patient_id)) $errors[] = "Please select a patient";
    if (empty($visit_date)) $errors[] = "Visit date is required";
    if ($visit_date > date('Y-m-d')) $errors[] = "Visit date cannot be in the future";
    if ($consultation_fee < 0) $errors[] = "Consultation fee cannot be negative";
    if ($lab_fee < 0) $errors[] = "Lab fee cannot be negative";
    
    if (empty($errors)) {
        // Calculate follow_up_due (7 days after visit date)
        $follow_up_due = date('Y-m-d', strtotime($visit_date . ' + 7 days'));
        
        // Insert using MySQLi prepared statement
        $sql = "INSERT INTO visits (patient_id, visit_date, consultation_fee, lab_fee, follow_up_due, symptoms, diagnosis, prescription, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $mysqli->prepare($sql);
        
        if ($stmt) {
            // Bind parameters: i, s, d, d, s, s, s, s, i
            $stmt->bind_param("isddssssi", 
                $patient_id, 
                $visit_date, 
                $consultation_fee, 
                $lab_fee, 
                $follow_up_due, 
                $symptoms, 
                $diagnosis, 
                $prescription, 
                $_SESSION['user_id']
            );
            
            if ($stmt->execute()) {
                $success = true;
                // Clear form data on success (optional)
                $patient_id = $_GET['patient_id'] ?? '';
                $visit_date = '';
                $consultation_fee = '';
                $lab_fee = '0';
                $symptoms = '';
                $diagnosis = '';
                $prescription = '';
            } else {
                $errors[] = "Database error: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $errors[] = "Database error: " . $mysqli->error;
        }
    }
}
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Add New Visit</h2>
        <a href="list.php" class="btn btn-primary">View All Visits</a>
    </div>
    
    <?php if($success): ?>
        <div class="alert alert-success">
            ✅ Visit added successfully! Follow-up is set for 7 days later.
            <?php if($patient_id): ?>
                <br><small><a href="/healthcare_db/patients/view.php?id=<?= $patient_id ?>">View patient profile</a></small>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <?php if(!empty($errors)): ?>
        <?php foreach($errors as $error): ?>
            <div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <form method="POST" action="">
        <div class="form-group">
            <label class="form-label">Select Patient *</label>
            <select name="patient_id" class="form-control" required>
                <option value="">-- Select Patient --</option>
                <?php foreach($patients as $patient): ?>
                    <option value="<?= $patient['patient_id'] ?>" <?= $patient_id == $patient['patient_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($patient['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Visit Date *</label>
                <input type="date" name="visit_date" class="form-control" 
                       value="<?= htmlspecialchars($visit_date ?? '') ?>" 
                       required max="<?= date('Y-m-d') ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">Consultation Fee ($) *</label>
                <input type="number" name="consultation_fee" class="form-control" 
                       step="0.01" min="0" 
                       value="<?= htmlspecialchars($consultation_fee ?? '') ?>" 
                       required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Lab Fee ($)</label>
                <input type="number" name="lab_fee" class="form-control" 
                       step="0.01" min="0" 
                       value="<?= htmlspecialchars($lab_fee ?? '0') ?>">
                <small class="form-text text-muted">Optional - leave as 0 if not applicable</small>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Symptoms</label>
                <textarea name="symptoms" class="form-control" rows="2" 
                          placeholder="Describe patient's symptoms..."><?= htmlspecialchars($symptoms ?? '') ?></textarea>
                <small class="form-text text-muted">Example: Fever, cough, headache, etc.</small>
            </div>
            
            <div class="form-group">
                <label class="form-label">Diagnosis</label>
                <textarea name="diagnosis" class="form-control" rows="2" 
                          placeholder="Doctor's diagnosis..."><?= htmlspecialchars($diagnosis ?? '') ?></textarea>
                <small class="form-text text-muted">Medical diagnosis based on symptoms</small>
            </div>
            
            <div class="form-group">
                <label class="form-label">Prescription</label>
                <textarea name="prescription" class="form-control" rows="2" 
                          placeholder="Medicines prescribed..."><?= htmlspecialchars($prescription ?? '') ?></textarea>
                <small class="form-text text-muted">List medicines with dosage</small>
            </div>
        </div>
        
        <div class="alert alert-info">
            <strong>ℹ️ Note:</strong> Follow-up date will be automatically set to 7 days after the visit date.
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">➕ Add Visit</button>
            <button type="reset" class="btn btn-secondary">🔄 Reset Form</button>
            <?php if($patient_id): ?>
                <a href="/healthcare_db/patients/view.php?id=<?= $patient_id ?>" class="btn btn-info">👁️ View Patient</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<style>
/* Additional styles to maintain UI consistency */
.form-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
    flex-wrap: wrap;
}

.btn-secondary {
    background-color: #6c757d;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 0.25rem;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
}

.btn-secondary:hover {
    background-color: #5a6268;
}

.btn-info {
    background-color: #17a2b8;
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 0.25rem;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
}

.btn-info:hover {
    background-color: #138496;
}

.form-text {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.875rem;
    color: #6c757d;
}

/* Maintain original form styles */
.form-group {
    margin-bottom: 1rem;
}

.form-label {
    display: inline-block;
    margin-bottom: 0.5rem;
    font-weight: 600;
}

.form-control {
    display: block;
    width: 100%;
    padding: 0.375rem 0.75rem;
    font-size: 1rem;
    line-height: 1.5;
    color: #495057;
    background-color: #fff;
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
}

.form-row {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
}

.form-row .form-group {
    flex: 1;
    margin-bottom: 0;
}

.alert {
    padding: 0.75rem 1.25rem;
    margin-bottom: 1rem;
    border: 1px solid transparent;
    border-radius: 0.25rem;
}

.alert-success {
    color: #155724;
    background-color: #d4edda;
    border-color: #c3e6cb;
}

.alert-danger {
    color: #721c24;
    background-color: #f8d7da;
    border-color: #f5c6cb;
}

.alert-info {
    color: #0c5460;
    background-color: #d1ecf1;
    border-color: #bee5eb;
}

.card {
    background: white;
    border-radius: 0.25rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 1rem;
}

.card-header {
    padding: 0.75rem 1.25rem;
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-title {
    margin: 0;
    font-size: 1.25rem;
}

.btn-primary {
    color: #fff;
    background-color: #007bff;
    border-color: #007bff;
    padding: 0.5rem 1rem;
    border-radius: 0.25rem;
    text-decoration: none;
    display: inline-block;
    cursor: pointer;
}

.btn-primary:hover {
    background-color: #0069d9;
    border-color: #0062cc;
}

/* Responsive */
@media (max-width: 768px) {
    .form-row {
        flex-direction: column;
        gap: 1rem;
    }
    
    .form-row .form-group {
        margin-bottom: 1rem;
    }
    
    .card-header {
        flex-direction: column;
        gap: 0.5rem;
        text-align: center;
    }
    
    .form-actions {
        justify-content: center;
    }
}
</style>

<?php include '../includes/footer.php'; ?>