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

// Fetch user details
$stmt = $conn->prepare("SELECT * FROM Users WHERE UserID = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: manage_users.php");
    exit;
}

$user = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $role = $_POST['role'];
    $password = $_POST['password'];
    
    // Validate input
    if (empty($username) || empty($role)) {
        $error = "Username and role are required.";
    } else {
        // Check if username already exists (excluding current user)
        $checkStmt = $conn->prepare("SELECT UserID FROM Users WHERE Username = ? AND UserID != ?");
        $checkStmt->bind_param("si", $username, $userId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            $error = "Username already exists. Please choose a different username.";
        } else {
            if (!empty($password)) {
                // Update with new password
                if (strlen($password) < 6) {
                    $error = "Password must be at least 6 characters long.";
                } else {
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                    $updateStmt = $conn->prepare("UPDATE Users SET Username=?, Role=?, PasswordHash=? WHERE UserID=?");
                    $updateStmt->bind_param("sssi", $username, $role, $passwordHash, $userId);
                }
            } else {
                // Update without changing password
                $updateStmt = $conn->prepare("UPDATE Users SET Username=?, Role=? WHERE UserID=?");
                $updateStmt->bind_param("ssi", $username, $role, $userId);
            }
            
            if (!$error && $updateStmt->execute()) {
                $success = "User updated successfully!";
                // Refresh user data from database
                $refreshStmt = $conn->prepare("SELECT * FROM Users WHERE UserID = ?");
                $refreshStmt->bind_param("i", $userId);
                $refreshStmt->execute();
                $refreshResult = $refreshStmt->get_result();
                if ($refreshResult->num_rows > 0) {
                    $user = $refreshResult->fetch_assoc();
                }
            } else if (!$error) {
                $error = "Error updating user: " . $updateStmt->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - NDMS Admin</title>
    <link rel="stylesheet" href="includes/sidebar.css">
    <style>
        :root {
            --primary-color: #1e3a8a;
            --secondary-color: #3b82f6;
            --accent-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --light-bg: #f8fafc;
            --card-bg: #ffffff;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --border-color: #e5e7eb;
            --gradient-bg: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }
        
        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
            background: var(--light-bg);
            color: var(--text-primary);
            line-height: 1.6;
            margin: 0;
            display: flex;
            min-height: 100vh; 
            margin: 0; 
            padding: 20px; 
            background: #f8f9fa; 
        }
        
        .container { max-width: 800px; margin: 0 auto; }
        
        .header {
            background: linear-gradient(135deg, #ffc107, #e0a800);
            color: #212529;
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
        
        .form-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .user-info {
            background: #e3f2fd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .user-info h4 {
            margin: 0 0 10px 0;
            color: #007cba;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #495057;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #007cba;
        }
        
        .btn {
            background: #ffc107;
            color: #212529;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #e0a800;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
            margin-left: 10px;
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
        
        .password-help {
            font-size: 14px;
            color: #6c757d;
            margin-top: 5px;
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
    </style>
</head>
<body>
    <!-- Include Sidebar -->
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
        <div class="header">
            <h1>✏️ Edit User</h1>
            <p>Update user account information</p>
        </div>

        <div class="form-section">
            <?php if ($success): ?>
                <div class="alert alert-success">
                    ✅ <?= htmlspecialchars($success) ?>
                    <a href="manage_users.php" style="float: right; color: #155724;">View All Users →</a>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    ❌ <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <div class="form-group">
                    <label for="username">👤 Username</label>
                    <input type="text" id="username" name="username" 
                           value="<?= isset($user['Username']) ? htmlspecialchars($user['Username']) : '' ?>" 
                           required placeholder="Enter username">
                </div>

                <div class="form-group">
                    <label for="role">🎭 User Role</label>
                    <select id="role" name="role" required>
                        <option value="Admin" <?= (isset($user['Role']) && $user['Role'] === 'Admin') ? 'selected' : '' ?>>Admin</option>
                        <option value="MedicalOfficer" <?= (isset($user['Role']) && $user['Role'] === 'MedicalOfficer') ? 'selected' : '' ?>>Medical Officer</option>
                        <option value="EducationOfficer" <?= (isset($user['Role']) && $user['Role'] === 'EducationOfficer') ? 'selected' : '' ?>>Education Officer</option>
                        <option value="Employer" <?= (isset($user['Role']) && $user['Role'] === 'Employer') ? 'selected' : '' ?>>Employer</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="password">🔒 New Password (Optional)</label>
                    <input type="password" id="password" name="password" 
                           placeholder="Leave blank to keep current password">
                    <div class="password-help">Only enter a new password if you want to change it. Must be at least 6 characters long.</div>
                </div>

                <div style="text-align: center; margin-top: 30px;">
                    <button type="submit" class="btn">💾 Update User</button>
                    <a href="manage_users.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
        </div> <!-- End container -->
    </div> <!-- End main-content -->
    
    <!-- Include Sidebar JavaScript -->
    <script src="includes/sidebar.js"></script>
</body>
</html>
