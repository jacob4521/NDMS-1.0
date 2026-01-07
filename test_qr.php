<?php
// Test QR code generation with the proper library
require_once 'includes/phpqrcode/phpqrcode.php';
require_once 'server_config.php'; // Include server configuration

// Test data with dynamic URL
$serverUrl = getServerUrl();
$testUrl = $serverUrl . "/verify_certificate.php?cert=BC-LK-2025-000001&hash=test123";
$testFile = __DIR__ . '/uploads/qrcodes/test_qr.png';

// Ensure directory exists
$qrDir = __DIR__ . '/uploads/qrcodes/';
if (!is_dir($qrDir)) {
    mkdir($qrDir, 0755, true);
}

echo "<h2>QR Code Generation Test</h2>";

try {
    // Generate QR code
    QRcode::png($testUrl, $testFile, QR_ECLEVEL_M, 8, 2);
    
    if (file_exists($testFile)) {
        echo "<p style='color: green;'>✅ QR Code generated successfully!</p>";
        echo "<p><strong>File:</strong> " . $testFile . "</p>";
        echo "<p><strong>File size:</strong> " . filesize($testFile) . " bytes</p>";
        echo "<p><strong>Test URL encoded:</strong> " . htmlspecialchars($testUrl) . "</p>";
        echo "<div style='border: 2px solid #ccc; padding: 20px; display: inline-block; margin-top: 20px;'>";
        echo "<img src='uploads/qrcodes/test_qr.png' alt='Test QR Code' style='max-width: 200px;'>";
        echo "<br><small>Test QR Code</small>";
        echo "</div>";
    } else {
        echo "<p style='color: red;'>❌ QR Code file was not created</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error generating QR Code: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<br><br><a href='birth_certificate.php'>← Back to Birth Certificate Registration</a>";
?>
