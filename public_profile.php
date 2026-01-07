<?php
// public_profile.php - Public citizen profile for QR code scanning
include "config.php";

// Get citizen by eID or citizen_id
$citizen = null;
if (isset($_GET['eid']) && $_GET['eid']) {
    $eid = $_GET['eid'];
    $stmt = $conn->prepare("
        SELECT 
            CitizenID,
            Citizen_eID,
            FirstName,
            LastName,
            YEAR(DOB) as BirthYear,
            Gender
        FROM Citizens
        WHERE Citizen_eID = ?
    ");
    $stmt->bind_param("s", $eid);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $citizen = $result->fetch_assoc();
    }
} elseif (isset($_GET['citizen_id']) && intval($_GET['citizen_id']) > 0) {
    $citizenId = intval($_GET['citizen_id']);
    $stmt = $conn->prepare("
        SELECT 
            CitizenID,
            Citizen_eID,
            FirstName,
            LastName,
            YEAR(DOB) as BirthYear,
            Gender
        FROM Citizens
        WHERE CitizenID = ?
    ");
    $stmt->bind_param("i", $citizenId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $citizen = $result->fetch_assoc();
    }
}
if (!$citizen) {
    echo "Citizen not found.";
    exit;
}
$currentYear = date('Y');
$age = $currentYear - $citizen['BirthYear'];

// Get education separately
$eduStmt = $conn->prepare("SELECT GradeLevel, SchoolName FROM EducationRecords WHERE CitizenID = ? ORDER BY RecordDate DESC LIMIT 1");
$eduStmt->bind_param("i", $citizen['CitizenID']);
$eduStmt->execute();
$eduResult = $eduStmt->get_result();
$education = $eduResult->fetch_assoc();

// Get current employment separately
$empStmt = $conn->prepare("SELECT JobTitle, CompanyName FROM EmploymentRecords WHERE CitizenID = ? ORDER BY StartDate DESC LIMIT 1");
$empStmt->bind_param("i", $citizen['CitizenID']);
$empStmt->execute();
$empResult = $empStmt->get_result();
$employment = $empResult->fetch_assoc();

// Format education display
$educationDisplay = 'Not available';
if ($education) {
    $gradeLevel = $education['GradeLevel'];
    $institution = $education['SchoolName'];
    
    // Check if it's university level
    if (stripos($gradeLevel, 'university') !== false || stripos($gradeLevel, 'bachelor') !== false || 
        stripos($gradeLevel, 'master') !== false || stripos($gradeLevel, 'degree') !== false ||
        stripos($gradeLevel, 'diploma') !== false) {
        $educationDisplay = $gradeLevel; // Show as is for university
    } else if (is_numeric($gradeLevel)) {
        $educationDisplay = "Grade " . $gradeLevel; // Add "Grade" prefix for numbers
    } else {
        $educationDisplay = $gradeLevel; // Show as is for other text
    }
    
    // Add institution if available
    if ($institution) {
        $educationDisplay .= " - " . $institution;
    }
}

// Format employment display
$employmentDisplay = null;
if ($employment && $employment['JobTitle'] && $employment['CompanyName']) {
    $employmentDisplay = $employment['JobTitle'] . " at " . $employment['CompanyName'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NDMS Public Profile - <?php echo htmlspecialchars($citizen['FirstName'] . ' ' . $citizen['LastName']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            background: var(--gradient-bg);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 20%, rgba(59, 130, 246, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(16, 185, 129, 0.1) 0%, transparent 50%),
                url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="rgba(255,255,255,0.1)"/><circle cx="80" cy="20" r="2" fill="rgba(255,255,255,0.1)"/><circle cx="20" cy="80" r="2" fill="rgba(255,255,255,0.1)"/><circle cx="80" cy="80" r="2" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="50" r="1" fill="rgba(255,255,255,0.1)"/></svg>') repeat;
            opacity: 0.6;
            z-index: 0;
        }
        
        .profile-container {
            background: var(--card-bg);
            border-radius: 25px;
            padding: 0;
            max-width: 650px;
            width: 100%;
            box-shadow: 
                0 25px 50px rgba(0, 0, 0, 0.15),
                0 12px 24px rgba(0, 0, 0, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.6);
            text-align: center;
            position: relative;
            z-index: 1;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(20px);
        }
        
        .header {
            background: 
                linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 50%, var(--accent-color) 100%),
                url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="20" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/><circle cx="80" cy="80" r="25" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></svg>');
            color: white;
            padding: 35px 30px;
            margin-bottom: 0;
            position: relative;
            overflow: hidden;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -30%;
            width: 250px;
            height: 250px;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }
        
        .header::after {
            content: '';
            position: absolute;
            bottom: -20%;
            left: -20%;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
            border-radius: 50%;
            animation: float 8s ease-in-out infinite reverse;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            33% { transform: translateY(-10px) rotate(120deg); }
            66% { transform: translateY(5px) rotate(240deg); }
        }
        
        .header h1 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 8px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
            z-index: 1;
        }
        
        .header p {
            font-size: 16px;
            opacity: 0.9;
            position: relative;
            z-index: 1;
            font-weight: 500;
        }
        
        .profile-content {
            padding: 35px 30px;
        }
        
        .citizen-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: 
                linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 50%, var(--accent-color) 100%);
            margin: 0 auto 25px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 45px;
            color: white;
            font-weight: 700;
            box-shadow: 
                0 15px 35px rgba(0, 0, 0, 0.2),
                0 5px 15px rgba(0, 0, 0, 0.1),
                inset 0 2px 0 rgba(255, 255, 255, 0.3);
            border: 5px solid var(--card-bg);
            position: relative;
            z-index: 2;
            margin-top: -60px;
            animation: avatarPulse 3s ease-in-out infinite;
        }
        
        @keyframes avatarPulse {
            0%, 100% { box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2), 0 5px 15px rgba(0, 0, 0, 0.1), inset 0 2px 0 rgba(255, 255, 255, 0.3); }
            50% { box-shadow: 0 20px 45px rgba(59, 130, 246, 0.3), 0 8px 20px rgba(59, 130, 246, 0.2), inset 0 2px 0 rgba(255, 255, 255, 0.3); }
        }
        
        .citizen-name {
            font-size: 32px;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 15px;
            text-align: center;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .citizen-eid {
            background: var(--primary-color);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 25px;
            letter-spacing: 0.5px;
        }
        
        .profile-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .detail-item {
            background: var(--light-bg);
            padding: 20px;
            border-radius: 15px;
            text-align: left;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }
        
        .detail-item:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            border-color: var(--secondary-color);
        }
        
        .detail-label {
            font-size: 12px;
            font-weight: 700;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .detail-value {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
            line-height: 1.4;
        }
        
        .verification-badge {
            background: linear-gradient(135deg, var(--accent-color) 0%, #059669 100%);
            color: white;
            padding: 18px 25px;
            border-radius: 20px;
            margin-top: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            font-weight: 700;
            font-size: 15px;
            box-shadow: 
                0 8px 25px rgba(16, 185, 129, 0.3),
                0 3px 10px rgba(16, 185, 129, 0.2);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            animation: verifiedGlow 2s ease-in-out infinite alternate;
        }
        
        @keyframes verifiedGlow {
            from { box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3), 0 3px 10px rgba(16, 185, 129, 0.2); }
            to { box-shadow: 0 12px 35px rgba(16, 185, 129, 0.4), 0 5px 15px rgba(16, 185, 129, 0.3); }
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 25px;
            justify-content: center;
        }
        
        .back-btn {
            padding: 15px 30px;
            border-radius: 15px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
        }
        
        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(30, 58, 138, 0.3);
        }
        
        .verification-badge::before {
            content: '✅';
            font-size: 18px;
        }
        
        .footer-note {
            background: var(--light-bg);
            padding: 20px;
            margin: 25px -30px -35px -30px;
            border-top: 1px solid var(--border-color);
            font-size: 12px;
            color: var(--text-secondary);
            line-height: 1.5;
        }
        
        .status-badges {
            display: flex;
            gap: 8px;
            justify-content: center;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        
        .status-badge {
            background: var(--secondary-color);
            color: white;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-badge.education {
            background: var(--accent-color);
        }
        
        .status-badge.employment {
            background: var(--warning-color);
        }
        
        .status-badge.age {
            background: var(--primary-color);
        }
        
        /* Responsive Design */
        @media (max-width: 480px) {
            body {
                padding: 15px;
            }
            
            .profile-container {
                max-width: 100%;
            }
            
            .citizen-name {
                font-size: 26px;
            }
            
            .citizen-avatar {
                width: 100px;
                height: 100px;
                font-size: 38px;
                margin-top: -50px;
            }
            
            .profile-details {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .profile-content {
                padding: 25px 20px;
            }
            
            .footer-note {
                margin: 25px -20px -25px -20px;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 10px;
            }
            
            .back-btn {
                width: 100%;
                justify-content: center;
            }
        }
        
        /* Loading Animation */
        .profile-container {
            animation: slideUp 0.6s ease-out;
        }
        
        @keyframes slideUp {
            from { 
                opacity: 0; 
                transform: translateY(30px) scale(0.95); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0) scale(1); 
            }
        }
        
        .detail-item {
            animation: fadeInUp 0.5s ease-out;
            animation-fill-mode: both;
        }
        
        .detail-item:nth-child(1) { animation-delay: 0.1s; }
        .detail-item:nth-child(2) { animation-delay: 0.2s; }
        .detail-item:nth-child(3) { animation-delay: 0.3s; }
        .detail-item:nth-child(4) { animation-delay: 0.4s; }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        
    </style>
</head>
<body>

</head>
<body>
    <div class="profile-container">
        <div class="header">
            <h1>🇱🇰 NDMS Digital Identity</h1>
            <p>National Digital Management System</p>
        </div>
        
        <div class="profile-content">
            <div class="citizen-avatar">
                <?php echo strtoupper(substr($citizen['FirstName'], 0, 1) . substr($citizen['LastName'], 0, 1)); ?>
            </div>
            
            <div class="citizen-name">
                <?php echo htmlspecialchars($citizen['FirstName'] . ' ' . $citizen['LastName']); ?>
            </div>
            
            <div class="citizen-eid">
                ID: <?php echo htmlspecialchars($citizen['Citizen_eID'] ?? 'Not Set'); ?>
            </div>
            
            <div class="status-badges">
                <span class="status-badge age"><?php echo $age; ?> years old</span>
                <span class="status-badge"><?php echo htmlspecialchars($citizen['Gender'] ?? 'Not specified'); ?></span>
                <?php if ($education): ?>
                    <span class="status-badge education">Education ✓</span>
                <?php endif; ?>
                <?php if ($employment): ?>
                    <span class="status-badge employment">Employed ✓</span>
                <?php endif; ?>
            </div>
            
            <div class="profile-details">
                <div class="detail-item">
                    <div class="detail-label">
                        🎂 Birth Information
                    </div>
                    <div class="detail-value">
                        Born in <?php echo $citizen['BirthYear']; ?> • <?php echo $age; ?> years old
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">
                        👤 Gender Identity
                    </div>
                    <div class="detail-value">
                        <?php echo htmlspecialchars($citizen['Gender'] ?? 'Not specified'); ?>
                    </div>
                </div>
                
                <div class="detail-item">
                    <div class="detail-label">
                        🎓 Education Status
                    </div>
                    <div class="detail-value">
                        <?php echo htmlspecialchars($educationDisplay); ?>
                    </div>
                </div>
                
                <?php if ($employmentDisplay): ?>
                <div class="detail-item">
                    <div class="detail-label">
                        💼 Current Employment
                    </div>
                    <div class="detail-value">
                        <?php echo htmlspecialchars($employmentDisplay); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="verification-badge">
                Verified NDMS Profile
            </div>
            
            <div class="action-buttons">
                <a href="homepage.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                    Back to Homepage
                </a>
            </div>
        </div>
        
        <div class="footer-note">
            <strong>�️ Privacy Protected:</strong> This profile displays only public verification information. 
            Personal details, medical records, and sensitive data are protected and not accessible through this interface.
            <br><br>
            <strong>📱 QR Code Access:</strong> This profile can be accessed by scanning the citizen's QR code for instant verification purposes.
        </div>
    </div>
</body>
</html>