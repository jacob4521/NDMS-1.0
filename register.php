<?php
include "config.php";
include "qr_config.php";
require "includes/phpqrcode/qrlib.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $dob   = $_POST['dob'];
    $gender = $_POST['gender'];
    $address = $_POST['address'];

    // Generate unique CitizenEID (LKYYYYXXXXX)
    function generateCitizenEID($dob, $conn) {
        $birthYear = date('Y', strtotime($dob));
        $count = 0;
        do {
            $randomNumber = str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);
            $citizenEID = "LK{$birthYear}{$randomNumber}";
            $stmt = $conn->prepare("SELECT COUNT(*) FROM Citizens WHERE Citizen_eID = ?");
            $stmt->bind_param("s", $citizenEID);
            $stmt->execute();
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();
        } while ($count > 0);
        return $citizenEID;
    }

    $citizenEID = generateCitizenEID($dob, $conn);

    // Insert Citizen with Citizen_eID
    $stmt = $conn->prepare("INSERT INTO Citizens (Citizen_eID, FirstName, LastName, DOB, Gender, Address, QRCodePath) VALUES (?, ?, ?, ?, ?, ?, '')");
    $stmt->bind_param("ssssss", $citizenEID, $fname, $lname, $dob, $gender, $address);
    $stmt->execute();
    $citizenId = $stmt->insert_id;

    // Generate QR with full URL to public citizen profile using eID
    $profileUrl = BASE_URL . "public_profile.php?eid=$citizenEID";
    $qrFile = QR_CODE_DIR . "citizen_$citizenId.png";

    // Convert error correction level string to constant
    $errorCorrectionLevel = QR_ECLEVEL_L; // Default
    switch (QR_CODE_LEVEL) {
        case 'L': $errorCorrectionLevel = QR_ECLEVEL_L; break;
        case 'M': $errorCorrectionLevel = QR_ECLEVEL_M; break;
        case 'Q': $errorCorrectionLevel = QR_ECLEVEL_Q; break;
        case 'H': $errorCorrectionLevel = QR_ECLEVEL_H; break;
    }

    QRcode::png($profileUrl, $qrFile, $errorCorrectionLevel, QR_CODE_SIZE);

    // Update path in database using prepared statement
    $stmt = $conn->prepare("UPDATE Citizens SET QRCodePath = ? WHERE CitizenID = ?");
    $stmt->bind_param("si", $qrFile, $citizenId);
    $stmt->execute();

    // Auto-create citizen account
    $randomPassword = bin2hex(random_bytes(4)); // 8-character random password
    $hashedPassword = password_hash($randomPassword, PASSWORD_DEFAULT);
    
    $userStmt = $conn->prepare("INSERT INTO Users (Username, PasswordHash, Role) VALUES (?, ?, 'Citizen')");
    $userStmt->bind_param("ss", $citizenEID, $hashedPassword);
    $userStmt->execute();
    $userID = $userStmt->insert_id;

    // Link the user account to the citizen record
    $linkStmt = $conn->prepare("UPDATE Citizens SET UserID = ? WHERE CitizenID = ?");
    $linkStmt->bind_param("ii", $userID, $citizenId);
    $linkStmt->execute();

    // Auto-generate vaccination reminders for newborn
    include_once "vaccination_reminders_helper.php";
    include_once "notifications_helper.php";
    $reminderCount = generateVaccinationReminders($citizenId, $dob, $conn);
    
    // Create welcome notification for the new citizen
    if ($reminderCount > 0) {
        $welcomeMessage = "Welcome to NDMS! Your vaccination schedule has been created with $reminderCount reminders. Your first vaccination may be due soon.";
        createNotification($citizenId, $welcomeMessage, $conn);
    }

    $msg = "Citizen registered successfully! QR code generated with URL: $profileUrl<br>";
    $msg .= "<strong>🏠 Citizen Account Created:</strong><br>";
    $msg .= "Username: <strong>$citizenEID</strong><br>";
    $msg .= "Password: <strong>$randomPassword</strong><br>";
    $msg .= "💉 Vaccination reminders: <strong>$reminderCount reminders generated</strong><br>";
    $msg .= "<em>Please provide these credentials to the parents for online access.</em>";
    $showQR = true; // Flag to display QR code
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Citizen - NDMS</title>
    <link rel="stylesheet" href="includes/sidebar.css">
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
            color: var(--text-primary);
            line-height: 1.6;
            padding: 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: var(--card-bg);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow-xl);
            border: 1px solid var(--border-color);
        }
        
        .header {
            background: var(--gradient-bg);
            color: white;
            padding: 40px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="2"/><circle cx="50" cy="50" r="30" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/><circle cx="50" cy="50" r="20" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></svg>') no-repeat center;
            opacity: 0.3;
        }
        
        .header h1 {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
            z-index: 1;
        }
        
        .header p {
            font-size: 18px;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }
        
        .nav {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .nav a {
            color: var(--primary-color);
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            border: 1px solid transparent;
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--gradient-bg);
            color: white;
        }
        
        .nav a:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            filter: brightness(1.1);
        }
        
        .content {
            padding: 40px;
        }
        
        .form-section {
            margin-bottom: 40px;
        }
        
        .section-title {
            color: var(--primary-color);
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 25px;
            padding-bottom: 10px;
            border-bottom: 3px solid var(--secondary-color);
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 25px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 700;
            color: var(--text-primary);
            font-size: 14px;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: white;
            font-family: inherit;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .btn {
            padding: 16px 32px;
            background: var(--gradient-bg);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: var(--shadow-md);
            text-align: center;
            justify-content: center;
            min-width: 150px;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
            filter: brightness(1.1);
        }
        
        .btn-secondary {
            background: var(--text-secondary);
        }
        
        .btn-secondary:hover {
            background: var(--text-primary);
        }
        
        .btn-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .success-message {
            background: linear-gradient(135deg, var(--accent-color), #059669);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin: 25px 0;
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
        }
        
        .success-message::before {
            content: '✅';
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 30px;
            opacity: 0.7;
        }
        
        .success-message h3 {
            font-size: 20px;
            margin-bottom: 15px;
            font-weight: 700;
        }
        
        .qr-section {
            background: var(--light-bg);
            padding: 30px;
            border-radius: 15px;
            text-align: center;
            margin: 25px 0;
            border: 2px dashed var(--border-color);
        }
        
        .qr-section h4 {
            color: var(--primary-color);
            margin-bottom: 20px;
            font-size: 18px;
            font-weight: 700;
        }
        
        .qr-section img {
            border: 3px solid var(--border-color);
            border-radius: 10px;
            margin: 15px 0;
            max-width: 200px;
            height: auto;
        }
        
        .citizen-credentials {
            background: var(--warning-color);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
            text-align: center;
        }
        
        .citizen-credentials h4 {
            font-size: 18px;
            margin-bottom: 15px;
        }
        
        .credentials-box {
            background: rgba(255,255,255,0.2);
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
            font-size: 16px;
            font-weight: bold;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }
            
            .header h1 {
                font-size: 28px;
            }
            
            .content {
                padding: 25px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .btn-actions {
                flex-direction: column;
                align-items: stretch;
            }
        }
        
        /* Loading Animation */
        .form-group {
            animation: slideUp 0.3s ease-out;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="nav">
            <a href="dashboard.php">← Back to Dashboard</a>
            <a href="search_citizens.php">👥 View All Citizens</a>
        </div>
        <div class="container">
            <div class="header">
                <h1>🇱🇰 NDMS - Register New Citizen</h1>
                <p>National Digital Management System</p>
            </div>
            
            <div class="content">
                <?php if(isset($showQR) && $showQR): ?>
                    <!-- Success and QR Code Display -->
                    <div class="success-message">
                        <h3>✅ Registration Successful!</h3>
                        <p>New citizen has been registered in the NDMS system</p>
                    </div>
                    
                    <div class="form-section">
                        <div class="section-title">
                            👤 Citizen Information
                        </div>
                        <div class="form-row">
                            <div><strong>Name:</strong> <?= htmlspecialchars($fname . ' ' . $lname) ?></div>
                            <div><strong>Date of Birth:</strong> <?= $dob ?></div>
                            <div><strong>Gender:</strong> <?= htmlspecialchars($gender) ?></div>
                            <div><strong>Citizen eID:</strong> <span style="background: var(--primary-color); color: white; padding: 6px 12px; border-radius: 15px; font-weight: bold;"><?= htmlspecialchars($citizenEID) ?></span></div>
                        </div>
                    </div>
                    
                    <!-- Login Credentials Display -->
                    <div class="citizen-credentials">
                        <h4>🔑 Login Credentials Created</h4>
                        <p>A secure login account has been automatically created for this citizen:</p>
                        <div class="credentials-box">
                            <strong>Username:</strong> <?= htmlspecialchars($citizenEID) ?>
                        </div>
                        <div class="credentials-box">
                            <strong>Temporary Password:</strong> <?= htmlspecialchars($randomPassword) ?>
                        </div>
                        <p style="font-size: 14px; margin-top: 15px;">
                            <strong>⚠️ Important:</strong> Please provide these credentials to the citizen/guardian. 
                            They should change the password upon first login for security.
                        </p>
                    </div>
                    
                    <div class="qr-section">
                        <h4>📱 Scan QR Code for Public Profile</h4>
                        <p>This QR code provides instant access to the citizen's public profile for verification purposes.</p>
                        
                        <?php if(file_exists($qrFile)): ?>
                            <a href="<?= $profileUrl ?>" target="_blank">
                                <img src="<?= $qrFile ?>" alt="QR Code for <?= htmlspecialchars($fname . ' ' . $lname) ?>">
                            </a>
                        <?php else: ?>
                            <p style="color: var(--danger-color);">QR Code generation failed. Please try again.</p>
                        <?php endif; ?>
                        
                        <div style="background: var(--light-bg); padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 4px solid var(--secondary-color);">
                            <p><strong>Public Profile URL:</strong></p>
                            <p style="background: white; padding: 12px; border-radius: 8px; word-break: break-all; font-family: monospace; font-size: 14px; margin: 10px 0;">
                                <a href="<?= $profileUrl ?>" target="_blank" style="color: var(--secondary-color); text-decoration: none;"><?= $profileUrl ?></a>
                            </p>
                        </div>
                        
                        <p style="color: var(--text-secondary); font-size: 14px; text-align: center; margin-top: 20px;">
                            <strong>Note:</strong> This QR code shows only public information (name, birth year, gender, education status) and protects sensitive data.
                        </p>
                    </div>
                    
                    <div class="btn-actions">
                        <a href="register.php" class="btn">➕ Register Another</a>
                        <a href="dashboard.php" class="btn btn-secondary">🏠 Dashboard</a>
                        <a href="<?= $profileUrl ?>" target="_blank" class="btn btn-secondary">👁️ View Profile</a>
                        <?php if ($_SESSION['Role'] === 'Admin' || $_SESSION['Role'] === 'MedicalOfficer'): ?>
                            <a href="view_citizen.php?citizen_id=<?= $citizenId ?>&tab=vaccination" class="btn" style="background: var(--accent-color);">💉 Add First Vaccination</a>
                        <?php endif; ?>
                    </div>
                    
                <?php else: ?>
                    <!-- Registration Form -->
                    <div class="form-section">
                        <div class="section-title">
                            📝 Basic Information
                        </div>
                        
                        <div style="background: var(--light-bg); padding: 20px; border-radius: 12px; margin-bottom: 25px; border-left: 4px solid var(--secondary-color);">
                            <h4 style="color: var(--primary-color); margin-bottom: 10px; font-size: 16px;">🆔 Citizen eID Generation</h4>
                            <p style="color: var(--text-secondary); margin: 0; font-size: 14px;">
                                A unique Citizen eID will be automatically generated based on the birth year and random numbers (Format: LK2025XXXXX).
                                This eID will serve as the citizen's permanent identification number in the NDMS system.
                            </p>
                        </div>
                        
                        <form method="post">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="fname">First Name:</label>
                                    <input type="text" id="fname" name="fname" required placeholder="Enter first name">
                                </div>
                                <div class="form-group">
                                    <label for="lname">Last Name:</label>
                                    <input type="text" id="lname" name="lname" required placeholder="Enter last name">
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="dob">Date of Birth:</label>
                                    <input type="date" id="dob" name="dob" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="gender">Gender:</label>
                                    <select id="gender" name="gender" required>
                                        <option value="">-- Select Gender --</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="address">Residential Address:</label>
                                <textarea id="address" name="address" required placeholder="Enter complete residential address including city and postal code"></textarea>
                            </div>
                            
                            <div class="btn-actions">
                                <button type="submit" class="btn">🚀 Register Citizen</button>
                            </div>
                        </form>
                    </div>
                    
                    <div class="btn-actions">
                        <a href="dashboard.php" class="btn btn-secondary">🏠 Back to Dashboard</a>
                        <a href="search_citizens.php" class="btn btn-secondary">👥 View All Citizens</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="includes/sidebar.js"></script>
</body>
</html>
