<?php
// patients/add.php
require_once '../config/db.php';
include '../includes/header.php';

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
        // Using MySQLi with prepared statement
        $sql = "INSERT INTO patients (name, dob, join_date, phone, address, created_by) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $mysqli->prepare($sql);
        
        if ($stmt) {
            // Bind parameters: s = string, d = date (string), i = integer
            $stmt->bind_param("sssssi", 
                $name, 
                $dob, 
                $join_date, 
                $phone, 
                $address, 
                $_SESSION['user_id']
            );
            
            if ($stmt->execute()) {
                $success = true;
                // Clear form data on success
                $name = $dob = $join_date = $phone = $address = "";
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
        <h2 class="card-title">Add New Patient</h2>
        <a href="list.php" class="btn btn-primary">Back to List</a>
    </div>
    
    <?php if($success): ?>
        <div class="alert alert-success">
            Patient added successfully! 
            <a href="list.php" class="alert-link">View all patients</a>
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
    
    <form method="POST" action="">
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Full Name *</label>
                <input type="text" name="name" class="form-control" 
                       value="<?= htmlspecialchars($name ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Date of Birth *</label>
                <input type="date" name="dob" class="form-control" 
                       value="<?= htmlspecialchars($dob ?? '') ?>" 
                       required max="<?= date('Y-m-d') ?>">
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label class="form-label">Join Date *</label>
                <input type="date" name="join_date" class="form-control" 
                       value="<?= htmlspecialchars($join_date ?? '') ?>" 
                       required max="<?= date('Y-m-d') ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">Phone Number *</label>
                <input type="tel" name="phone" class="form-control" 
                       value="<?= htmlspecialchars($phone ?? '') ?>" 
                       required pattern="[0-9]{10}" 
                       maxlength="10" 
                       placeholder="9876543210">
                <small class="form-text text-muted">Enter 10 digit mobile number</small>
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">Address</label>
            <textarea name="address" class="form-control" rows="3"><?= htmlspecialchars($address ?? '') ?></textarea>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Add Patient</button>
            <button type="reset" class="btn btn-secondary">Reset Form</button>
        </div>
    </form>
</div>

<script>
// Client-side validation for phone number
document.querySelector('form').addEventListener('submit', function(e) {
    let phone = document.querySelector('input[name="phone"]').value;
    if (phone && !/^\d{10}$/.test(phone)) {
        alert('Please enter a valid 10-digit phone number');
        e.preventDefault();
    }
});
</script>

<?php include '../includes/footer.php'; ?>