<?php
session_start();
// Test file to debug dashboard navigation
$_SESSION['Role'] = 'Admin'; // Force admin role for testing
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Dashboard Navigation</title>
    <style>
        .nav-section {
            margin-bottom: 20px;
            border: 1px solid #ccc;
            padding: 10px;
        }
        .nav-section-title {
            font-weight: bold;
            color: #333;
            background: #f0f0f0;
            padding: 5px;
            margin-bottom: 10px;
        }
        .nav-item {
            margin: 5px 0;
            padding: 5px;
            background: #f9f9f9;
        }
    </style>
</head>
<body>
    <h1>Dashboard Navigation Test</h1>
    
    <!-- User Management Section -->
    <div class="nav-section">
        <div class="nav-section-title">User Management</div>
        <div class="nav-item">Register Citizen</div>
        <div class="nav-item">Citizen Directory</div>
        <div class="nav-item">Manage Users</div>
        <div class="nav-item">Citizen Accounts</div>
    </div>
    
    <!-- Academic & Career Section -->
    <div class="nav-section">
        <div class="nav-section-title">Academic & Career</div>
        <div class="nav-item">Manage Subjects</div>
    </div>
    
    <!-- Communication Section -->
    <div class="nav-section">
        <div class="nav-section-title">Communication</div>
        <div class="nav-item">Notifications</div>
        <div class="nav-item">Newsletter Subscribers</div>
        <div class="nav-item">Contact Messages</div>
    </div>
    
</body>
</html>
