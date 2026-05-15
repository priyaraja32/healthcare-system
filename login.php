<?php
// login.php 
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
if($checkResult) {
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
                    error_log("Password verification failed for user: $username");
                    error_log("Stored hash: " . $user['password']);
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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #2b2d42;
        }

        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .login-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 2.5rem;
            width: 100%;
            max-width: 450px;
            animation: fadeInUp 0.5s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-logo {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .login-header h2 {
            color: #2c7da0;
            margin-bottom: 0.5rem;
        }

        .login-header p {
            color: #666;
        }

        .input-group {
            margin-bottom: 1.5rem;
        }

        .input-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #2b2d42;
        }

        .input-group input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #edf2f4;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .input-group input:focus {
            outline: none;
            border-color: #2c7da0;
            box-shadow: 0 0 0 3px rgba(44,125,160,0.1);
        }

        .login-btn {
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s;
        }

        .login-btn:hover {
            transform: translateY(-2px);
        }

        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .debug-info {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 5px;
            padding: 10px;
            margin-top: 15px;
            font-size: 12px;
            color: #721c24;
        }

        .demo-accounts {
            margin-top: 1.5rem;
            text-align: center;
            font-size: 0.85rem;
            color: #666;
        }

        .demo-accounts p {
            margin: 5px 0;
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
            
            <div class="demo-accounts">
                <p><strong>Demo Accounts:</strong></p>
                <p>Admin: <strong>admin</strong> / <strong>admin123</strong></p>
                <p>Doctor: <strong>doctor1</strong> / <strong>admin123</strong></p>
                <p>Staff: <strong>staff1</strong> / <strong>admin123</strong></p>
            </div>
            
            <div class="debug-info">
                <strong>Debug Information:</strong><br>
                <?php
                // Check if users table has data using MySQLi
                $userCountResult = $mysqli->query("SELECT COUNT(*) as count FROM users");
                if($userCountResult) {
                    $userCount = $userCountResult->fetch_assoc();
                    echo "Users in database: " . $userCount['count'] . "<br>";
                    
                    if($userCount['count'] > 0) {
                        $sampleResult = $mysqli->query("SELECT username, role FROM users LIMIT 1");
                        if($sampleResult) {
                            $sampleUser = $sampleResult->fetch_assoc();
                            echo "Sample user: " . $sampleUser['username'] . " (Role: " . $sampleUser['role'] . ")<br>";
                        }
                    }
                } else {
                    echo "Error checking users table: " . $mysqli->error . "<br>";
                }
                ?>
                <small>If login fails, use: admin / admin123</small>
            </div>
        </div>
    </div>
</body>
</html>