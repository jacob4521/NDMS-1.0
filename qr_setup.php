<?php
include "config.php";
include "qr_config.php";

// Check if user is logged in
if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit;
}

// Get server information
$serverName = $_SERVER['SERVER_NAME'];
$serverPort = $_SERVER['SERVER_PORT'];
$currentUrl = "http://$serverName" . ($serverPort != '80' ? ":$serverPort" : '') . "/ndms/";

// Test citizen for QR demo
$testCitizen = $conn->query("SELECT CitizenID, FirstName, LastName FROM Citizens ORDER BY CitizenID DESC LIMIT 1")->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>QR Code Configuration - NDMS</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .header { background: #007cba; color: white; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .section { background: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 5px; border-left: 4px solid #007cba; }
        .config-box { background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 10px 0; font-family: monospace; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .nav { margin-bottom: 20px; }
        .nav a { padding: 8px 15px; background: #007cba; color: white; text-decoration: none; border-radius: 5px; margin-right: 10px; }
        .test-qr { text-align: center; padding: 20px; }
        .step { margin: 15px 0; padding: 10px; background: white; border-radius: 5px; }
        .step h4 { margin: 0 0 10px 0; color: #007cba; }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav">
            <a href="dashboard.php">← Back to Dashboard</a>
            <a href="register.php">Register Citizen</a>
            <a href="search_citizens.php">Citizen Directory</a>
        </div>

        <div class="header">
            <h1>📱 QR Code Configuration & Testing</h1>
            <p>Setup mobile access for NDMS citizen profiles</p>
        </div>

        <div class="section">
            <h2>🔧 Current Configuration</h2>
            
            <div class="config-box">
                <strong>Server IP:</strong> <?= SERVER_IP ?><br>
                <strong>Server Port:</strong> <?= SERVER_PORT ?><br>
                <strong>Base URL:</strong> <?= BASE_URL ?><br>
                <strong>Current Access URL:</strong> <?= $currentUrl ?>
            </div>

            <div class="warning">
                <h4>⚠️ Important Notes:</h4>
                <ul>
                    <li>If you're testing locally, the current configuration may not work on mobile devices</li>
                    <li>Mobile devices need to be on the same WiFi network as your computer</li>
                    <li>You may need to update the IP address in <code>qr_config.php</code></li>
                </ul>
            </div>
        </div>

        <div class="section">
            <h2>📋 Setup Instructions</h2>
            
            <div class="step">
                <h4>Step 1: Find Your Computer's IP Address</h4>
                <p><strong>Windows:</strong> Open Command Prompt and type: <code>ipconfig</code></p>
                <p>Look for "IPv4 Address" under your active network adapter (usually WiFi or Ethernet)</p>
                <p><strong>Common formats:</strong> 192.168.1.xxx, 192.168.0.xxx, 10.0.0.xxx</p>
            </div>

            <div class="step">
                <h4>Step 2: Update Configuration</h4>
                <p>Edit <code>qr_config.php</code> and change:</p>
                <div class="config-box">
                    define('SERVER_IP', 'YOUR_ACTUAL_IP');<br>
                    define('SERVER_PORT', 'YOUR_PORT'); // Usually 80 or 3000
                </div>
            </div>

            <div class="step">
                <h4>Step 3: Test Mobile Access</h4>
                <p>On your mobile device, try opening: <code><?= BASE_URL ?></code></p>
                <p>If it doesn't work, check firewall settings and WAMP configuration</p>
            </div>

            <div class="step">
                <h4>Step 4: Test QR Code</h4>
                <p>Register a new citizen and scan the generated QR code with your mobile device</p>
            </div>
        </div>

        <?php if ($testCitizen): ?>
        <div class="section">
            <h2>🧪 Test QR Code</h2>
            
            <div class="test-qr">
                <p>Test URL for Citizen: <strong><?= htmlspecialchars($testCitizen['FirstName'] . ' ' . $testCitizen['LastName']) ?></strong></p>
                <div class="config-box">
                    <?= BASE_URL ?>view_citizen.php?citizen_id=<?= $testCitizen['CitizenID'] ?>
                </div>
                
                <p><a href="<?= BASE_URL ?>view_citizen.php?citizen_id=<?= $testCitizen['CitizenID'] ?>" target="_blank">
                    Click to test this URL in browser
                </a></p>
                
                <?php
                $qrPath = QR_CODE_DIR . "citizen_" . $testCitizen['CitizenID'] . ".png";
                if (file_exists($qrPath)):
                ?>
                    <p><strong>QR Code for this citizen:</strong></p>
                    <img src="<?= $qrPath ?>" alt="Test QR Code" style="border: 2px solid #ccc; padding: 10px;">
                    <p><small>Scan this with your mobile device to test</small></p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="section">
            <h2>🔍 Troubleshooting</h2>
            
            <div class="step">
                <h4>QR Code shows "NDMS-EID-X" instead of opening profile?</h4>
                <p>✅ Fixed! New QR codes will contain full URLs to citizen profiles</p>
            </div>

            <div class="step">
                <h4>Mobile can't access the URL?</h4>
                <ul>
                    <li>Check if mobile is on same WiFi network</li>
                    <li>Verify IP address in qr_config.php is correct</li>
                    <li>Try disabling Windows Firewall temporarily</li>
                    <li>Check WAMP settings for network access</li>
                </ul>
            </div>

            <div class="step">
                <h4>QR Code not generating?</h4>
                <ul>
                    <li>Ensure PHP QR Code library is installed in includes/phpqrcode/</li>
                    <li>Check that qr/ folder has write permissions</li>
                    <li>Verify no PHP errors in the log</li>
                </ul>
            </div>
        </div>

        <div class="success">
            <h4>✅ What's New:</h4>
            <ul>
                <li><strong>QR codes now contain full URLs</strong> to citizen profiles</li>
                <li><strong>Mobile scanning</strong> opens the complete profile page</li>
                <li><strong>Network access</strong> configured for mobile devices</li>
                <li><strong>Configuration file</strong> for easy setup changes</li>
            </ul>
        </div>
    </div>
</body>
</html>
