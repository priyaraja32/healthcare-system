<?php
// includes/header.php
if (!isset($skip_auth_check)) {
    // Don't check login on login page
    if (basename($_SERVER['PHP_SELF']) != 'login.php') {
        requireLogin();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Healthcare Management System</title>
    <style>
        /* ALL CSS REMAINS HERE - NOTHING TO DELETE */
        :root {
            --primary-color: #2c7da0;
            --secondary-color: #61a5c2;
            --success-color: #2a9d8f;
            --danger-color: #e76f51;
            --warning-color: #f4a261;
            --info-color: #4895ef;
            --dark-color: #2b2d42;
            --light-color: #edf2f4;
            --gradient-start: #667eea;
            --gradient-end: #764ba2;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--gradient-start) 0%, var(--gradient-end) 100%);
            min-height: 100vh;
            color: var(--dark-color);
        }

        /* Login Page Styles */
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
            color: var(--primary-color);
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
            color: var(--dark-color);
        }

        .input-group input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid var(--light-color);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .input-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(44,125,160,0.1);
        }

        .login-btn {
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
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

        /* Navigation */
        .navbar {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            background: linear-gradient(135deg, var(--gradient-start), var(--gradient-end));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            text-decoration: none;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-name {
            font-weight: 500;
            color: var(--primary-color);
        }

        .logout-btn {
            background: var(--danger-color);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: background 0.3s;
        }

        .logout-btn:hover {
            background: #d35f3a;
        }

        .nav-menu {
            display: flex;
            gap: 2rem;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .nav-menu a {
            text-decoration: none;
            color: var(--dark-color);
            font-weight: 500;
            transition: color 0.3s;
            position: relative;
        }

        .nav-menu a:hover {
            color: var(--primary-color);
        }

        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-content {
            display: none;
            position: absolute;
            background-color: white;
            min-width: 200px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            z-index: 1;
            border-radius: 8px;
            margin-top: 10px;
        }

        .dropdown:hover .dropdown-content {
            display: block;
        }

        .dropdown-content a {
            color: var(--dark-color);
            padding: 12px 16px;
            text-decoration: none;
            display: block;
            transition: background 0.3s;
        }

        .dropdown-content a:hover {
            background-color: var(--light-color);
        }

        /* Main Container */
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        /* Cards */
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-bottom: 2rem;
            transition: transform 0.3s;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            border-bottom: 2px solid var(--light-color);
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }

        .card-title {
            font-size: 1.5rem;
            color: var(--primary-color);
        }

        /* Buttons */
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }

        .btn-success {
            background: var(--success-color);
            color: white;
        }

        .btn-danger {
            background: var(--danger-color);
            color: white;
        }

        .btn-warning {
            background: var(--warning-color);
            color: white;
        }

        .btn-info {
            background: var(--info-color);
            color: white;
        }

        /* Forms */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--dark-color);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid var(--light-color);
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
        }

        /* Tables */
        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--light-color);
        }

        tbody tr:hover {
            background-color: var(--light-color);
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }

        /* Alert */
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

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }

        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        .stat-label {
            color: var(--dark-color);
            margin-top: 0.5rem;
        }

        /* Grid Layout */
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }

        /* Footer */
        .footer {
            background: white;
            text-align: center;
            padding: 1rem;
            margin-top: 3rem;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
        }

        /* Animation */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .grid-2 {
                grid-template-columns: 1fr;
            }
            
            .nav-menu {
                flex-direction: column;
                gap: 0.5rem;
                width: 100%;
                margin-top: 1rem;
            }
            
            .container {
                padding: 0 1rem;
            }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php if(isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) != 'login.php'): ?>
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">🏥 Healthcare HMS</a>
            <ul class="nav-menu">
                <li><a href="/healthcare_db/index.php">Dashboard</a></li>
                <li class="dropdown">
                    <a href="#">Patients ▼</a>
                    <div class="dropdown-content">
                        <a href="/healthcare_db/patients/list.php">All Patients</a>
                        <a href="/healthcare_db/patients/add.php">Add Patient</a>
                    </div>
                </li>
                <li class="dropdown">
                    <a href="#">Visits ▼</a>
                    <div class="dropdown-content">
                        <a href="/healthcare_db/visits/list.php">All Visits</a>
                        <a href="/healthcare_db/visits/add.php">Add Visit</a>
                    </div>
                </li>
                <li class="dropdown">
                    <a href="#">Reports ▼</a>
                    <div class="dropdown-content">
                        <a href="/healthcare_db/reports/birthdays.php">Birthdays</a>
                        <a href="/healthcare_db/reports/followups.php">Follow-ups</a>
                        <a href="/healthcare_db/reports/monthly.php">Monthly Report</a>
                        <a href="/healthcare_db/reports/summary.php">Patient Summary</a>
                    </div>
                </li>
            </ul>
            <div class="user-info">
                <span class="user-name">👤 <?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?></span>
                <a href="/healthcare_db/logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </nav>
    <?php endif; ?>
    <div class="container fade-in">