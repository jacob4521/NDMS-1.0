<?php
include "config.php";

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Use prepared statement for security
    $stmt = $conn->prepare("SELECT * FROM Users WHERE Username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // Check if password uses new hash format or old MD5
        $isValidPassword = false;
        if (password_verify($password, $user['PasswordHash'])) {
            // New password hash format
            $isValidPassword = true;
        } elseif ($user['PasswordHash'] === md5($password)) {
            // Old MD5 format - verify and update to new format
            $isValidPassword = true;
            
            // Update to new password hash format
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $updateStmt = $conn->prepare("UPDATE Users SET PasswordHash = ? WHERE UserID = ?");
            $updateStmt->bind_param("si", $newHash, $user['UserID']);
            $updateStmt->execute();
        }
        
        if ($isValidPassword) {
            // For citizen accounts, check if the citizen profile is active
            if ($user['Role'] === 'Citizen') {
                // Check if citizen account is active
                $citizenStmt = $conn->prepare("SELECT IsActive, FirstName, LastName FROM Citizens WHERE UserID = ?");
                $citizenStmt->bind_param("i", $user['UserID']);
                $citizenStmt->execute();
                $citizenResult = $citizenStmt->get_result();
                
                if ($citizenResult->num_rows > 0) {
                    $citizen = $citizenResult->fetch_assoc();
                    if (!$citizen['IsActive']) {
                        $error = "Your account has been deactivated. Please contact the administrator for assistance.";
                    } else {
                        // Account is active, proceed with login
                        $_SESSION['UserID'] = $user['UserID'];
                        $_SESSION['Role'] = $user['Role'];
                        $_SESSION['CitizenName'] = $citizen['FirstName'] . ' ' . $citizen['LastName'];
                        header("Location: citizen_dashboard.php");
                        exit;
                    }
                } else {
                    $error = "Citizen profile not found. Please contact the administrator.";
                }
            } else {
                // For non-citizen accounts (Admin, MedicalOfficer, etc.), proceed normally
                $_SESSION['UserID'] = $user['UserID'];
                $_SESSION['Role'] = $user['Role'];
                header("Location: dashboard.php");
                exit;
            }
        } else {
            $error = "Invalid login credentials!";
        }
    } else {
        $error = "Invalid login credentials!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NDMS - National Digital Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
            --gradient-bg: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--light-bg);
            background-image: 
                radial-gradient(circle at 20% 80%, rgba(30, 58, 138, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(59, 130, 246, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(16, 185, 129, 0.05) 0%, transparent 50%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: var(--card-bg);
            border-radius: 24px;
            box-shadow: var(--shadow-xl);
            overflow: hidden;
            max-width: 1000px;
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 600px;
        }

        .login-visual {
            background: var(--gradient-bg);
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            color: white;
            overflow: hidden;
        }

        .login-visual::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/></pattern></defs><rect width="100" height="100" fill="url(%23grid)"/></svg>') repeat;
            opacity: 0.3;
        }

        .visual-content {
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .national-emblem {
            width: 120px;
            height: 120px;
            background: rgba(255,255,255,0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            font-size: 48px;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255,255,255,0.2);
        }

        .visual-content h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 15px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .visual-content p {
            font-size: 18px;
            opacity: 0.9;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .features {
            list-style: none;
            text-align: left;
            max-width: 300px;
            margin: 0 auto;
        }

        .features li {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
            font-size: 16px;
            opacity: 0.9;
        }

        .features li i {
            width: 20px;
            color: var(--accent-color);
            filter: brightness(1.5);
        }

        .login-form {
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .form-header h2 {
            color: var(--primary-color);
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .form-header p {
            color: var(--text-secondary);
            font-size: 16px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper input {
            width: 100%;
            padding: 16px 20px 16px 50px;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: white;
            color: var(--text-primary);
        }

        .input-wrapper input:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .input-wrapper i {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
            font-size: 16px;
        }

        .login-btn {
            background: var(--gradient-bg);
            color: white;
            border: none;
            padding: 16px 32px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            filter: brightness(1.1);
        }

        .error-message {
            background: #fef2f2;
            color: var(--danger-color);
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            border: 1px solid #fecaca;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .forgot-password {
            text-align: center;
            margin-top: 25px;
        }

        .forgot-password a {
            color: var(--secondary-color);
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
        }

        .forgot-password a:hover {
            text-decoration: underline;
        }

        .divider {
            margin: 30px 0;
            text-align: center;
            position: relative;
        }

        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: var(--border-color);
        }

        .divider span {
            background: white;
            padding: 0 20px;
            color: var(--text-secondary);
            font-size: 14px;
        }

        .public-access {
            text-align: center;
            padding: 20px;
            background: var(--light-bg);
            border-radius: 12px;
            margin-top: 20px;
        }

        .public-access h4 {
            color: var(--text-primary);
            margin-bottom: 15px;
            font-size: 16px;
        }

        .public-btn {
            background: white;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
            padding: 12px 24px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .public-btn:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-1px);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .login-container {
                grid-template-columns: 1fr;
                max-width: 400px;
            }

            .login-visual {
                order: 2;
                padding: 30px 20px;
                min-height: auto;
            }

            .login-form {
                order: 1;
                padding: 40px 30px;
            }

            .national-emblem {
                width: 80px;
                height: 80px;
                font-size: 32px;
                margin-bottom: 20px;
            }

            .visual-content h1 {
                font-size: 24px;
            }

            .visual-content p {
                font-size: 16px;
            }

            .features {
                display: none;
            }
        }

        /* Loading Animation */
        .login-container {
            animation: slideUp 0.6s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Accessibility */
        .login-btn:focus, .public-btn:focus {
            outline: 3px solid var(--accent-color);
            outline-offset: 2px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Visual Side -->
        <div class="login-visual">
            <div class="visual-content">
                <div class="national-emblem">🇱🇰</div>
                <h1>NDMS</h1>
                <p>National Digital Management System</p>
                <ul class="features">
                    <li><i class="fas fa-shield-alt"></i> Secure Government Portal</li>
                    <li><i class="fas fa-database"></i> Centralized Citizen Records</li>
                    <li><i class="fas fa-certificate"></i> Digital Certificates</li>
                    <li><i class="fas fa-users"></i> Multi-Role Access</li>
                    <li><i class="fas fa-mobile-alt"></i> Modern Interface</li>
                </ul>
            </div>
        </div>

        <!-- Login Form Side -->
        <div class="login-form">
            <div class="form-header">
                <h2>System Login</h2>
                <p>Please enter your credentials to access the portal</p>
            </div>

            <?php if (isset($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" name="username" id="username" required 
                               placeholder="Enter your username">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" id="password" required 
                               placeholder="Enter your password">
                    </div>
                </div>

                <button type="submit" class="login-btn">
                    <i class="fas fa-sign-in-alt"></i>
                    Login to System
                </button>
            </form>

            <?php if(strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false): ?>
            <div class="public-access" style="background: #fef3c7; margin-top: 15px;">
                <h4 style="color: #92400e;">Development Test Accounts</h4>
                <div style="font-size: 12px; color: #92400e; text-align: left;">
                    <strong>Admin:</strong> admin / admin123<br>
                    <strong>Medical:</strong> doctor1 / doc123<br>
                    <strong>Education:</strong> teacher1 / edu123<br>
                    <strong>Employer:</strong> employer1 / emp123
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
