<?php
// fix_password.php - Reset passwords using MySQLi
require_once 'config/db.php';

echo "<h1>Password Reset Tool</h1>";
echo "<hr>";

// Check if $mysqli connection exists
if (!isset($mysqli) || $mysqli->connect_error) {
    echo "<p style='color: red;'>Database connection failed!</p>";
    echo "<p>Please check your config/db.php file.</p>";
    exit;
}

echo "<p style='color: green;'>Database connected successfully!</p>";

// New password
$new_password = 'admin123';
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

echo "<p>New password hash: <code>" . htmlspecialchars($hashed_password) . "</code></p>";

// Check if users table exists
$tableCheck = $mysqli->query("SHOW TABLES LIKE 'users'");
if($tableCheck->num_rows == 0) {
    echo "<p style='color: red;'> Users table does not exist! Creating...</p>";
    
    // Create users table
    $createTable = "CREATE TABLE IF NOT EXISTS users (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        email VARCHAR(100),
        full_name VARCHAR(100),
        role ENUM('admin', 'doctor', 'staff') DEFAULT 'staff',
        last_login DATETIME,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    if($mysqli->query($createTable)) {
        echo "<p style='color: green;'> Users table created successfully!</p>";
    } else {
        echo "<p style='color: red;'> Error creating table: " . $mysqli->error . "</p>";
    }
}

// Update all users password
$updateStmt = $mysqli->prepare("UPDATE users SET password = ?");
if($updateStmt) {
    $updateStmt->bind_param("s", $hashed_password);
    
    if($updateStmt->execute()) {
        $affected = $mysqli->affected_rows;
        if($affected > 0) {
            echo "<p style='color: green; font-weight: bold;'> Successfully updated $affected user(s) password to: <strong>$new_password</strong></p>";
        } else {
            echo "<p style='color: orange;'> No users found to update. Inserting default users...</p>";
            
            // Insert default users
            $insertStmt = $mysqli->prepare("INSERT INTO users (username, password, email, full_name, role) VALUES 
                ('admin', ?, 'admin@healthcare.com', 'Administrator', 'admin'),
                ('doctor1', ?, 'doctor@healthcare.com', 'Dr. Smith', 'doctor'),
                ('staff1', ?, 'staff@healthcare.com', 'John Staff', 'staff')");
            
            if($insertStmt) {
                $insertStmt->bind_param("sss", $hashed_password, $hashed_password, $hashed_password);
                if($insertStmt->execute()) {
                    echo "<p style='color: green;'>Default users created successfully!</p>";
                } else {
                    echo "<p style='color: red;'>Error inserting users: " . $insertStmt->error . "</p>";
                }
                $insertStmt->close();
            }
        }
    } else {
        echo "<p style='color: red;'> Error updating passwords: " . $updateStmt->error . "</p>";
    }
    $updateStmt->close();
} else {
    echo "<p style='color: red;'> Error preparing statement: " . $mysqli->error . "</p>";
}

// Show current users
echo "<h2> Current Users in Database:</h2>";
$result = $mysqli->query("SELECT user_id, username, role, email, full_name FROM users");
if($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
    echo "<tr style='background: #2c7da0; color: white;'>
            <th>ID</th>
            <th>Username</th>
            <th>Role</th>
            <th>Email</th>
            <th>Full Name</th>
          </tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['user_id'] . "</td>";
        echo "<td><strong>" . htmlspecialchars($row['username']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($row['role']) . "</td>";
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'> No users found in database.</p>";
}

echo "<hr>";
echo "<h3> Login Credentials:</h3>";
echo "<ul>";
echo "<li><strong>Admin:</strong> username: <code>admin</code> | password: <code>admin123</code></li>";
echo "<li><strong>Doctor:</strong> username: <code>doctor1</code> | password: <code>admin123</code></li>";
echo "<li><strong>Staff:</strong> username: <code>staff1</code> | password: <code>admin123</code></li>";
echo "</ul>";

echo "<br>";
echo "<a href='login.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🔐 Go to Login Page</a>";
echo "&nbsp;&nbsp;";
echo "<a href='index.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🏠 Go to Dashboard</a>";
?>