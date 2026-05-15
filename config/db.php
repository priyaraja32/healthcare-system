<?php
// config/db.php
session_start();

$host = 'localhost';
$dbname = 'healthcare_db';
$username = 'root';
$password = '';

// Create MySQLi connection
$mysqli = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Set charset
$mysqli->set_charset("utf8mb4");

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] == $role;
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireRole($role) {
    requireLogin();
    if (!hasRole($role) && !hasRole('admin')) {
        header('Location: index.php');
        exit;
    }
}
?>