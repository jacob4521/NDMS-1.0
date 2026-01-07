<?php
// simple_profile.php - Simple version without QR code generation issues
include "config.php";

// Get citizen ID from URL
$citizenId = isset($_GET['citizen_id']) ? intval($_GET['citizen_id']) : 9;

if ($citizenId <= 0) {
    echo "Invalid citizen ID.";
    exit;
}

// Fetch minimal public info only
$stmt = $conn->prepare("
    SELECT 
        CitizenID,
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

if ($result->num_rows == 0) {
    echo "Citizen not found.";
    exit;
}

$citizen = $result->fetch_assoc();
$currentYear = date('Y');
$age = $currentYear - $citizen['BirthYear'];

// Get education separately
$eduStmt = $conn->prepare("SELECT CONCAT('Grade ', GradeLevel) as Grade FROM EducationRecords WHERE CitizenID = ? ORDER BY RecordDate DESC LIMIT 1");
$eduStmt->bind_param("i", $citizenId);
$eduStmt->execute();
$eduResult = $eduStmt->get_result();
$education = $eduResult->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NDMS Public Profile - <?php echo htmlspecialchars($citizen['FirstName'] . ' ' . $citizen['LastName']); ?></title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .profile-container {
            background: white;
            border-radius: 20px;
            padding: 30px;
            max-width: 400px;
            width: 100%;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            text-align: center;
            position: relative;
        }
        
        .header {
            background: #007cba;
            color: white;
            padding: 15px;
            border-radius: 15px;
            margin-bottom: 25px;
        }
        
        .header h1 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }
        
        .eid-badge {
            background: #f8f9fa;
            border: 2px solid #007cba;
            color: #007cba;
            padding: 8px 15px;
            border-radius: 25px;
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 20px;
            display: inline-block;
        }
        
        .citizen-name {
            font-size: 24px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        
        .info-grid {
            display: grid;
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .info-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #007cba;
            text-align: left;
        }
        
        .info-label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 16px;
            color: #2c3e50;
            font-weight: 500;
        }
        
        .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #6c757d;
        }
        
        .verified-badge {
            position: absolute;
            top: -10px;
            right: -10px;
            background: #28a745;
            color: white;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }
        
        @media (max-width: 480px) {
            .profile-container {
                margin: 10px;
                padding: 20px;
            }
            
            .citizen-name {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <div class="verified-badge">✓</div>
        
        <div class="header">
            <h1>🇱🇰 National Digital Identity</h1>
        </div>
        
        <div class="eid-badge">
            NDMS-EID-<?php echo $citizen['CitizenID']; ?>
        </div>
        
        <div class="citizen-name">
            <?php echo htmlspecialchars($citizen['FirstName'] . ' ' . $citizen['LastName']); ?>
        </div>
        
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Birth Year</div>
                <div class="info-value"><?php echo $citizen['BirthYear']; ?> (Age: <?php echo $age; ?>)</div>
            </div>
            
            <div class="info-item">
                <div class="info-label">Gender</div>
                <div class="info-value"><?php echo htmlspecialchars($citizen['Gender'] ?? 'Not specified'); ?></div>
            </div>
            
            <div class="info-item">
                <div class="info-label">Education Status</div>
                <div class="info-value"><?php echo htmlspecialchars($education['Grade'] ?? 'Not available'); ?></div>
            </div>
        </div>
        
        <div class="footer">
            <p>📱 Scan to verify • 🛡️ Public profile only</p>
            <p><small>This profile shows limited public information for verification purposes.</small></p>
        </div>
    </div>
</body>
</html>
