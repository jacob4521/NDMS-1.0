<?php
include "config.php";

// Check if user is logged in and is a citizen
if (!isset($_SESSION['UserID']) || $_SESSION['Role'] !== 'Citizen') {
    header("Location: login.php");
    exit;
}

$userID = $_SESSION['UserID'];
$message = '';
$messageType = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $oldPassword = $_POST['old_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Validate input
    if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
        $message = "All fields are required.";
        $messageType = "error";
    } elseif ($newPassword !== $confirmPassword) {
        $message = "New passwords do not match.";
        $messageType = "error";
    } elseif (strlen($newPassword) < 6) {
        $message = "New password must be at least 6 characters long.";
        $messageType = "error";
    } else {
        // Verify old password
        $stmt = $conn->prepare("SELECT PasswordHash FROM Users WHERE UserID = ?");
        $stmt->bind_param("i", $userID);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        if (password_verify($oldPassword, $user['PasswordHash'])) {
            // Update password
            $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateStmt = $conn->prepare("UPDATE Users SET PasswordHash = ? WHERE UserID = ?");
            $updateStmt->bind_param("si", $newHash, $userID);
            
            if ($updateStmt->execute()) {
                $message = "Password updated successfully!";
                $messageType = "success";
            } else {
                $message = "Error updating password. Please try again.";
                $messageType = "error";
            }
        } else {
            $message = "Current password is incorrect.";
            $messageType = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - NDMS</title>
    <!-- No external sidebar CSS needed as citizen sidebar has inline styles -->
    <style>
        /* NDMS Modern Theme - National Digital Management System */
        :root {
            --primary-color: #1e3a8a;      /* Deep Blue - Government/Authority */
            --secondary-color: #3b82f6;    /* Bright Blue - Modern Tech */
            --accent-color: #10b981;       /* Emerald - Success/Progress */
            --warning-color: #f59e0b;      /* Amber - Attention */
            --danger-color: #ef4444;       /* Red - Critical */
            --light-bg: #f8fafc;          /* Light Gray Background */
            --card-bg: #ffffff;           /* Pure White Cards */
            --text-primary: #1f2937;      /* Dark Gray Text */
            --text-secondary: #6b7280;    /* Medium Gray Text */
            --border-color: #e5e7eb;      /* Light Border */
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --gradient-bg: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--light-bg);
            color: var(--text-primary);
            line-height: 1.6;
        }

        /* Sidebar Integration Styles */
        body.has-citizen-sidebar {
            padding-left: 280px;
            transition: padding-left 0.3s ease;
        }
        
        body.citizen-sidebar-collapsed {
            padding-left: 60px;
        }

        .main-content {
            min-height: 100vh;
            padding: 30px;
            background: var(--light-bg);
        }

        .container {
            max-width: 700px;
            margin: 0 auto;
            padding: 0;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
            padding: 40px 20px;
            background: var(--gradient-bg);
            color: white;
            border-radius: 15px;
            box-shadow: var(--shadow);
        }

        .header h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 16px;
            opacity: 0.9;
        }

        .content {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 40px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-primary);
            font-size: 15px;
        }

        .form-group input {
            width: 100%;
            padding: 15px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: var(--card-bg);
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
        }

        .btn {
            display: inline-block;
            padding: 15px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 150px;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: #1e40af;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(30, 58, 138, 0.3);
        }

        .btn-secondary {
            background: var(--text-secondary);
            color: white;
        }

        .btn-secondary:hover {
            background: #4b5563;
            transform: translateY(-2px);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-weight: 500;
            border-left: 4px solid;
        }

        .alert.success {
            background: #f0fdf4;
            color: #166534;
            border-left-color: var(--accent-color);
        }

        .alert.error {
            background: #fef2f2;
            color: #dc2626;
            border-left-color: var(--danger-color);
        }

        .form-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
        }

        .password-requirements {
            background: #f8fafc;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
        }

        .password-requirements h4 {
            color: var(--primary-color);
            margin-bottom: 10px;
            font-size: 16px;
        }

        .password-requirements ul {
            list-style: none;
            padding: 0;
        }

        .password-requirements li {
            padding: 5px 0;
            color: var(--text-secondary);
            position: relative;
            padding-left: 20px;
        }

        .password-requirements li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: var(--accent-color);
            font-weight: bold;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 20px;
            padding: 10px 0;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            gap: 12px;
            padding-left: 20px;
            color: #1976d2;
        }

        /* Mobile adjustments */
        @media (max-width: 768px) {
            body.has-citizen-sidebar {
                padding-left: 0 !important;
                padding-top: 80px; /* Space for mobile menu button */
            }
            
            .main-content {
                padding: 15px;
            }
            
            .content {
                padding: 25px;
            }

            .header {
                padding: 30px 15px;
            }

            .header h1 {
                font-size: 24px;
            }

            .form-buttons {
                flex-direction: column;
                align-items: center;
            }

            .btn {
                width: 100%;
                max-width: 300px;
            }
        }
    </style>

</head>
<body>
    <?php include 'includes/citizen_sidebar.php'; ?>
    
    <div class="main-content">
        <div class="container">
        <div class="header">
            <h1>🔐 Change Password</h1>
            <p>Update your account password</p>
        </div>
        
        <a href="citizen_dashboard.php" class="back-link">← Back to Dashboard</a>

        <?php if ($message): ?>
            <div class="alert <?= $messageType ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="content">
            <div class="password-requirements">
                <h4>Password Requirements:</h4>
                <ul>
                    <li>Minimum 6 characters</li>
                    <li>Must not match your current password</li>
                    <li>Should be unique and secure</li>
                </ul>
            </div>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="old_password">Current Password</label>
                    <input type="password" id="old_password" name="old_password" required>
                </div>

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" required minlength="6">
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="6">
                </div>

                <div class="form-buttons">
                    <button type="submit" class="btn btn-primary">Update Password</button>
                    <a href="citizen_dashboard.php" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
        </div>
    </div>

    <script>
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if (newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
    <!-- Citizen sidebar includes its own JavaScript -->
</body>
</html>
