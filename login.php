<?php
// login.php - Improved version with better error handling
require_once 'config/db.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';
$success_msg = '';

// Check if this is a first-time setup using MySQLi
$checkResult = $mysqli->query("SELECT COUNT(*) as count FROM users");
$checkUsers = $checkResult->fetch_assoc();

if($checkUsers['count'] == 0) {
    // Create default admin user
    $default_password = password_hash('admin123', PASSWORD_DEFAULT);
    
    // Insert default users using MySQLi prepared statement
    $insert = $mysqli->prepare("INSERT INTO users (username, password, email, full_name, role) VALUES 
        ('admin', ?, 'admin@healthcare.com', 'Administrator', 'admin'),
        ('doctor1', ?, 'doctor@healthcare.com', 'Dr. Smith', 'doctor'),
        ('staff1', ?, 'staff@healthcare.com', 'John Staff', 'staff')");
    
    if($insert) {
        $insert->bind_param("sss", $default_password, $default_password, $default_password);
        $insert->execute();
        $insert->close();
        $success_msg = "Default users created! Use admin/admin123 to login.";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    if(empty($username) || empty($password)) {
        $error = 'Please enter both username and password!';
    } else {
        // Prepare SQL statement to prevent SQL injection using MySQLi
        $stmt = $mysqli->prepare("SELECT * FROM users WHERE username = ?");
        if($stmt) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
            
            // Debug information (remove in production)
            if($user) {
                // Verify password
                if(password_verify($password, $user['password'])) {
                    // Password is correct
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role'] = $user['role'];
                    
                    // Update last login time using MySQLi
                    $updateStmt = $mysqli->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
                    if($updateStmt) {
                        $updateStmt->bind_param("i", $user['user_id']);
                        $updateStmt->execute();
                        $updateStmt->close();
                    }
                    
                    // Redirect to dashboard
                    header('Location: index.php');
                    exit;
                } else {
                    $error = 'Invalid password! Please use "admin123" as password.';
                    // For debugging - remove in production
                    error_log("Password verification failed for user: $username");
                }
            } else {
                $error = 'Username not found! Please check your username.';
            }
        } else {
            $error = 'Database error: ' . $mysqli->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Healthcare System Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .debug-info {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            padding: 10px;
            margin-top: 15px;
            font-size: 12px;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="login-logo">🏥</div>
                <h2>Healthcare Management System</h2>
                <p>Please login to continue</p>
            </div>
            
            <?php if($success_msg): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success_msg) ?></div>
            <?php endif; ?>
            
            <?php if($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="input-group">
                    <label>Username</label>
                    <input type="text" name="username" required placeholder="Enter username" value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">
                </div>
                
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" required placeholder="Enter password">
                </div>
                
                <button type="submit" class="login-btn">Login</button>
            </form>
            
            <div style="margin-top: 1.5rem; text-align: center; font-size: 0.85rem; color: #666;">
                <p><strong>Demo Accounts:</strong></p>
                <p>👨‍💼 Admin: <strong>admin</strong> / <strong>admin123</strong></p>
                <p>👨‍⚕️ Doctor: <strong>doctor1</strong> / <strong>admin123</strong></p>
                <p>👨‍💻 Staff: <strong>staff1</strong> / <strong>admin123</strong></p>
            </div>
            
            <!-- Debug Info - Remove in production -->
            <div class="debug-info">
                <strong>🔧 Debug Information:</strong><br>
                <?php
                // Check if users table has data using MySQLi
                $userCountResult = $mysqli->query("SELECT COUNT(*) as count FROM users");
                $userCount = $userCountResult->fetch_assoc();
                echo "Users in database: " . $userCount['count'] . "<br>";
                
                if($userCount['count'] > 0) {
                    $sampleResult = $mysqli->query("SELECT username, role FROM users LIMIT 1");
                    $sampleUser = $sampleResult->fetch_assoc();
                    echo "Sample user: " . $sampleUser['username'] . " (Role: " . $sampleUser['role'] . ")<br>";
                }
                ?>
                <small>If login fails, run <strong>fix_password.php</strong> to reset passwords.</small>
            </div>
        </div>
    </div>
</body>
</html>