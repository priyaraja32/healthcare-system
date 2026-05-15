<?php
// patients/edit.php
require_once '../config/db.php';
include '../includes/header.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch patient data using MySQLi
$sql = "SELECT * FROM patients WHERE patient_id = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$patient = $result->fetch_assoc();
$stmt->close();

if(!$patient) {
    header('Location: list.php');
    exit;
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $dob = $_POST['dob'];
    $join_date = $_POST['join_date'];
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    
    // Validation
    if (empty($name)) $errors[] = "Name is required";
    if (empty($dob)) $errors[] = "Date of birth is required";
    if (empty($join_date)) $errors[] = "Join date is required";
    if (empty($phone)) $errors[] = "Phone number is required";
    
    if ($dob > date('Y-m-d')) $errors[] = "Date of birth cannot be in the future";
    if ($join_date > date('Y-m-d')) $errors[] = "Join date cannot be in the future";
    
    // Phone number validation
    if (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
        $errors[] = "Phone number must be 10 digits";
    }
    
    if (empty($errors)) {
        // Update patient using MySQLi prepared statement
        $sql = "UPDATE patients SET name = ?, dob = ?, join_date = ?, 
                phone = ?, address = ? WHERE patient_id = ?";
        
        $update_stmt = $mysqli->prepare($sql);
        
        if ($update_stmt) {
            $update_stmt->bind_param("sssssi", $name, $dob, $join_date, $phone, $address, $id);
            
            if ($update_stmt->execute()) {
                $success = true;
                // Refresh patient data
                $patient['name'] = $name;
                $patient['dob'] = $dob;
                $patient['join_date'] = $join_date;
                $patient['phone'] = $phone;
                $patient['address'] = $address;
            } else {
                $errors[] = "Database error: " . $update_stmt->error;
            }
            $update_stmt->close();
        } else {
            $errors[] = "Database error: " . $mysqli->error;
        }
    }
}
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Edit Patient</h2>
        <div class="header-actions">
            <a href="view.php?id=<?= $id ?>" class="btn btn-info">Back to Profile</a>
            <a href="list.php" class="btn btn-primary">Patient List</a>
        </div>
    </div>
    
    <?php if($success): ?>
        <div class="alert alert-success alert-dismissible">
            <span class="close-btn" onclick="this.parentElement.style.display='none';">&times;</span>
            Patient updated successfully! 
            <a href="view.php?id=<?= $id ?>" class="alert-link">View updated profile</a>
        </div>
    <?php endif; ?>
    
    <?php if(!empty($errors)): ?>
        <div class="alert alert-danger">
            <strong>Please fix the following errors:</strong>
            <ul>
                <?php foreach($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="" id="editForm">
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Full Name *</label>
                <input type="text" name="name" class="form-control" 
                       value="<?= htmlspecialchars($patient['name']) ?>" 
                       required maxlength="100">
                <small class="form-text text-muted">Enter patient's full name</small>
            </div>
            
            <div class="form-group">
                <label class="form-label">Date of Birth *</label>
                <input type="date" name="dob" class="form-control" 
                       value="<?= $patient['dob'] ?>" 
                       required 
                       max="<?= date('Y-m-d') ?>"
                       min="1900-01-01">
                <small class="form-text text-muted">Patient's date of birth</small>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Join Date *</label>
                <input type="date" name="join_date" class="form-control" 
                       value="<?= $patient['join_date'] ?>" 
                       required 
                       max="<?= date('Y-m-d') ?>">
                <small class="form-text text-muted">When patient joined</small>
            </div>
            
            <div class="form-group">
                <label class="form-label">Phone Number *</label>
                <input type="tel" name="phone" class="form-control" 
                       value="<?= htmlspecialchars($patient['phone']) ?>" 
                       required 
                       pattern="[0-9]{10}" 
                       maxlength="10" 
                       minlength="10"
                       placeholder="9876543210">
                <small class="form-text text-muted">10 digit mobile number</small>
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">Address</label>
            <textarea name="address" class="form-control" rows="3" 
                      maxlength="500" placeholder="Enter complete address"><?= htmlspecialchars($patient['address']) ?></textarea>
            <small class="form-text text-muted">Full address (optional, max 500 chars)</small>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-success">
                <i class="icon-save"></i> Update Patient
            </button>
            <button type="button" class="btn btn-danger" onclick="confirmDelete(<?= $id ?>)">
                <i class="icon-delete"></i> Delete
            </button>
            <button type="reset" class="btn btn-secondary">Reset Form</button>
        </div>
    </form>
</div>

<script>
// Client-side validation
document.getElementById('editForm').addEventListener('submit', function(e) {
    let phone = document.querySelector('input[name="phone"]').value;
    let dob = document.querySelector('input[name="dob"]').value;
    let joinDate = document.querySelector('input[name="join_date"]').value;
    
    // Validate phone number
    if (phone && !/^\d{10}$/.test(phone)) {
        alert('Please enter a valid 10-digit phone number');
        e.preventDefault();
        return false;
    }
    
    // Validate date of birth
    if (dob && dob > new Date().toISOString().split('T')[0]) {
        alert('Date of birth cannot be in the future');
        e.preventDefault();
        return false;
    }
    
    // Validate join date
    if (joinDate && joinDate > new Date().toISOString().split('T')[0]) {
        alert('Join date cannot be in the future');
        e.preventDefault();
        return false;
    }
    
    // Confirm update
    if (!confirm('Are you sure you want to update this patient\'s information?')) {
        e.preventDefault();
    }
});

function confirmDelete(id) {
    if (confirm('Are you sure you want to delete this patient? This action cannot be undone!')) {
        window.location.href = 'delete.php?id=' + id;
    }
}

// Show character count for address
const addressField = document.querySelector('textarea[name="address"]');
if (addressField) {
    addressField.addEventListener('input', function() {
        const remaining = 500 - this.value.length;
        const counter = document.querySelector('.address-counter');
        if (counter) {
            counter.textContent = remaining + ' characters remaining';
        }
    });
}
</script>

<style>
.header-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}

.alert-dismissible {
    position: relative;
    padding-right: 40px;
}

.close-btn {
    position: absolute;
    right: 10px;
    top: 5px;
    font-size: 20px;
    font-weight: bold;
    cursor: pointer;
    color: #000;
}

.close-btn:hover {
    color: #f00;
}

.form-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.btn-success {
    background-color: #28a745;
    color: white;
    border: none;
    padding: 10px 20px;
    cursor: pointer;
}

.btn-danger {
    background-color: #dc3545;
    color: white;
    border: none;
    padding: 10px 20px;
    cursor: pointer;
}

.btn-info {
    background-color: #17a2b8;
    color: white;
    text-decoration: none;
    padding: 8px 15px;
    border-radius: 4px;
}

.form-text {
    font-size: 12px;
    color: #6c757d;
    margin-top: 5px;
}
</style>

<?php include '../includes/footer.php'; ?>