<?php
include "config.php";

// Simple login test for admin dashboard
session_start();

if (isset($_POST['test_login'])) {
    // Set admin session for testing
    $_SESSION['UserID'] = 1;
    $_SESSION['Role'] = 'Admin';
    $_SESSION['Username'] = 'TestAdmin';
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Admin Login</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .login-form { max-width: 300px; margin: 50px auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        input, button { width: 100%; padding: 10px; margin: 5px 0; }
        button { background: #007cba; color: white; border: none; cursor: pointer; }
    </style>
</head>
<body>
    <div class="login-form">
        <h2>Test Admin Login</h2>
        <form method="post">
            <button type="submit" name="test_login">Login as Admin</button>
        </form>
        <p><small>This will set admin session for testing the dashboard navigation groups.</small></p>
    </div>
</body>
</html>
