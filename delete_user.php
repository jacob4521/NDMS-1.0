<?php
include "config.php";

// Check if user is logged in and is Admin
if (!isset($_SESSION['UserID']) || $_SESSION['Role'] !== 'Admin') {
    header("Location: login.php");
    exit;
}

// Get user ID from query string
if (!isset($_GET['id'])) {
    header("Location: manage_users.php");
    exit;
}

$userId = intval($_GET['id']);
$success = "";
$error = "";

// Prevent admin from deleting their own account
if ($userId == $_SESSION['UserID']) {
    $error = "You cannot delete your own account.";
} else {
    // Fetch user details for confirmation
    $stmt = $conn->prepare("SELECT Username, Role FROM Users WHERE UserID = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        header("Location: manage_users.php");
        exit;
    }

    $user = $result->fetch_assoc();

    // Handle deletion confirmation
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm'])) {
        $deleteStmt = $conn->prepare("DELETE FROM Users WHERE UserID = ?");
        $deleteStmt->bind_param("i", $userId);
        
        if ($deleteStmt->execute()) {
            $success = "User deleted successfully!";
        } else {
            $error = "Error deleting user: " . $deleteStmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete User - NDMS Admin</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
            margin: 0; 
            padding: 20px; 
            background: #f8f9fa; 
        }
        
        .container { max-width: 600px; margin: 0 auto; }
        
        .header {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            text-align: center;
        }
        
        .nav-bar {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .nav-bar a {
            color: #007cba;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
            margin-right: 10px;
            transition: background 0.3s;
        }
        
        .nav-bar a:hover {
            background: #e3f2fd;
        }
        
        .confirmation-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
            text-align: center;
        }
        
        .user-details {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .user-details h4 {
            margin: 0 0 15px 0;
            color: #721c24;
        }
        
        .user-detail-item {
            margin-bottom: 10px;
        }
        
        .user-detail-item strong {
            color: #721c24;
        }
        
        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s;
            margin: 5px;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        
        .admin-badge {
            background: #dc3545;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .role-badge {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .role-admin { background: #dc3545; color: white; }
        .role-medical { background: #28a745; color: white; }
        .role-education { background: #17a2b8; color: white; }
        .role-employer { background: #ffc107; color: #212529; }
        
        .actions {
            text-align: center;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav-bar">
            <a href="dashboard.php">🏠 Dashboard</a>
            <a href="manage_users.php">👤 Manage Users</a>
            <a href="search_citizens.php">👥 Citizens</a>
            <span style="float: right;" class="admin-badge">Admin Panel</span>
        </div>

        <div class="header">
            <h1>🗑️ Delete User</h1>
            <p>Permanently remove user account</p>
        </div>

        <div class="confirmation-section">
            <?php if ($success): ?>
                <div class="alert alert-success">
                    ✅ <?= htmlspecialchars($success) ?>
                    <div style="margin-top: 15px;">
                        <a href="manage_users.php" class="btn btn-secondary">Back to User Management</a>
                    </div>
                </div>
            <?php elseif ($error): ?>
                <div class="alert alert-error">
                    ❌ <?= htmlspecialchars($error) ?>
                    <div style="margin-top: 15px;">
                        <a href="manage_users.php" class="btn btn-secondary">Back to User Management</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="warning-box">
                    <h3>⚠️ Warning: Permanent Action</h3>
                    <p>You are about to permanently delete this user account. This action cannot be undone.</p>
                </div>

                <div class="user-details">
                    <h4>🗂️ User to be deleted:</h4>
                    <div class="user-detail-item">
                        <strong>User ID:</strong> #<?= $user['UserID'] ?? $userId ?>
                    </div>
                    <div class="user-detail-item">
                        <strong>Username:</strong> <?= htmlspecialchars($user['Username'] ?? 'Unknown') ?>
                    </div>
                    <div class="user-detail-item">
                        <strong>Role:</strong> 
                        <span class="role-badge role-<?= strtolower(str_replace('Officer', '', $user['Role'] ?? '')) ?>">
                            <?= htmlspecialchars($user['Role'] ?? 'Unknown') ?>
                        </span>
                    </div>
                </div>

                <div style="background: #e2e3e5; padding: 15px; border-radius: 8px; margin-bottom: 25px;">
                    <h4 style="margin: 0 0 10px 0;">📋 What will happen:</h4>
                    <ul style="margin: 0; padding-left: 20px;">
                        <li>User account will be permanently deleted</li>
                        <li>User will no longer be able to login</li>
                        <li>Records created by this user will remain in the system</li>
                        <li>This action cannot be reversed</li>
                    </ul>
                </div>

                <form method="post" style="text-align: center;">
                    <p style="font-weight: 600; color: #721c24; margin-bottom: 20px;">
                        Are you absolutely sure you want to delete this user?
                    </p>
                    
                    <div class="actions">
                        <button type="submit" name="confirm" class="btn btn-danger" 
                                onclick="return confirm('This will permanently delete the user. Are you absolutely sure?')">
                            🗑️ Yes, Delete User
                        </button>
                        <a href="manage_users.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
