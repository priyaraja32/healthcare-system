<?php
// fix_password.php - Run this file once to fix passwords
require_once 'config/db.php';

echo "<h2>Password Fix Utility</h2>";

// First, let's see what users exist
$stmt = $pdo->query("SELECT user_id, username, email, full_name, role FROM users");
$users = $stmt->fetchAll();

if (count($users) > 0) {
    echo "<h3>Current Users:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Full Name</th><th>Role</th></tr>";
    foreach($users as $user) {
        echo "<tr>";
        echo "<td>{$user['user_id']}</td>";
        echo "<td>{$user['username']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "<td>{$user['full_name']}</td>";
        echo "<td>{$user['role']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Update passwords
echo "<h3>Updating passwords...</h3>";

// Password: admin123
$hashed_password = password_hash('admin123', PASSWORD_DEFAULT);

// Update all users to use the same password for testing
$updateStmt = $pdo->prepare("UPDATE users SET password = :password");
$updateStmt->execute([':password' => $hashed_password]);

// Also ensure we have at least these users
$users_to_insert = [
    ['admin', $hashed_password, 'admin@healthcare.com', 'Administrator', 'admin'],
    ['doctor1', $hashed_password, 'doctor@healthcare.com', 'Dr. Smith', 'doctor'],
    ['staff1', $hashed_password, 'staff@healthcare.com', 'John Staff', 'staff']
];

foreach($users_to_insert as $user) {
    // Check if user exists
    $checkStmt = $pdo->prepare("SELECT user_id FROM users WHERE username = :username");
    $checkStmt->execute([':username' => $user[0]]);
    
    if($checkStmt->rowCount() == 0) {
        $insertStmt = $pdo->prepare("INSERT INTO users (username, password, email, full_name, role) VALUES (?, ?, ?, ?, ?)");
        $insertStmt->execute($user);
        echo "Added user: {$user[0]}<br>";
    } else {
        echo "User {$user[0]} already exists, password updated<br>";
    }
}

echo "<h3 style='color: green;'>✅ Passwords have been reset to: admin123</h3>";
echo "<p><a href='login.php'>Go to Login Page</a></p>";

// Test the password
echo "<h3>Testing password verification:</h3>";
$testStmt = $pdo->prepare("SELECT * FROM users WHERE username = 'admin'");
$testStmt->execute();
$admin = $testStmt->fetch();

if($admin) {
    if(password_verify('admin123', $admin['password'])) {
        echo "<p style='color: green;'>✓ Password verification successful for admin</p>";
    } else {
        echo "<p style='color: red;'>✗ Password verification failed</p>";
    }
}
?>