<?php
include "config.php";

// Check if user is logged in and is Admin
if (!isset($_SESSION['UserID']) || $_SESSION['Role'] !== 'Admin') {
    header("Location: login.php");
    exit;
}

$success = "";
$error = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    // Validate input
    if (empty($username) || empty($password) || empty($role)) {
        $error = "All fields are required.";
    } else if (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        // Check if username already exists
        $checkStmt = $conn->prepare("SELECT UserID FROM Users WHERE Username = ?");
        $checkStmt->bind_param("s", $username);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            $error = "Username already exists. Please choose a different username.";
        } else {
            // Hash password securely
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $conn->prepare("INSERT INTO Users (Username, PasswordHash, Role) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $passwordHash, $role);
            
            if ($stmt->execute()) {
                $success = "User created successfully!";
                // Clear form data
                $username = $password = $role = "";
            } else {
                $error = "Error creating user: " . $stmt->error;
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
    <title>Add New User - NDMS Admin</title>
    <link rel="stylesheet" href="includes/sidebar.css">
    <style>
        /* NDMS Modern Theme - National Digital Management System */
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
        }
        
        .container { max-width: 800px; margin: 0 auto; }
        
        .header {
            background: linear-gradient(135deg, #28a745, #20c997);
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
        
        .form-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
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
            background: #28a745;
            color: white;
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
            background: #218838;
        }
        
        .btn-secondary {
            background: #6c757d;
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
        
        .role-info {
            background: #e3f2fd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }
        
        .role-info h4 {
            margin: 0 0 15px 0;
            color: #007cba;
        }
        
        .role-item {
            margin-bottom: 10px;
        }
        
        .role-item strong {
            color: #495057;
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
    </style>
</head>
<body>
    <!-- Include Sidebar -->
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
        <div class="header">
            <h1>➕ Add New User</h1>
            <p>Create a new system user account</p>
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

            <div class="role-info">
                <h4>👥 User Roles Information</h4>
                <div class="role-item"><strong>Admin:</strong> Full system access, user management, all modules</div>
                <div class="role-item"><strong>Medical Officer:</strong> Birth certificates, medical records</div>
                <div class="role-item"><strong>Education Officer:</strong> Education records, academic management</div>
                <div class="role-item"><strong>Employer:</strong> Employment records, job history</div>
            </div>

            <form method="post">
                <div class="form-group">
                    <label for="username">👤 Username</label>
                    <input type="text" id="username" name="username" 
                           value="<?= htmlspecialchars($username ?? '') ?>" 
                           required placeholder="Enter unique username">
                </div>

                <div class="form-group">
                    <label for="password">🔒 Password</label>
                    <input type="password" id="password" name="password" 
                           required placeholder="Enter secure password">
                    <div class="password-help">Password must be at least 6 characters long</div>
                </div>

                <div class="form-group">
                    <label for="role">🎭 User Role</label>
                    <select id="role" name="role" required>
                        <option value="">-- Select Role --</option>
                        <option value="Admin" <?= ($role ?? '') === 'Admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="MedicalOfficer" <?= ($role ?? '') === 'MedicalOfficer' ? 'selected' : '' ?>>Medical Officer</option>
                        <option value="EducationOfficer" <?= ($role ?? '') === 'EducationOfficer' ? 'selected' : '' ?>>Education Officer</option>
                        <option value="Employer" <?= ($role ?? '') === 'Employer' ? 'selected' : '' ?>>Employer</option>
                    </select>
                </div>

                <div style="text-align: center; margin-top: 30px;">
                    <button type="submit" class="btn">🚀 Create User</button>
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
