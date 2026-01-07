<?php
// NDMS Configuration File
// Update these settings based on your server setup

// Include dynamic server configuration
require_once 'server_config.php';

// Get server URL from configuration file
$serverUrl = getServerUrl();
define('BASE_URL', $serverUrl . '/');

// QR Code Settings
define('QR_CODE_SIZE', 4);               // QR code size (1-10)
define('QR_CODE_LEVEL', 'L');            // Error correction level (L, M, Q, H)

// File Paths
define('QR_CODE_DIR', 'qr/');            // Directory to store QR code images

// INSTRUCTIONS FOR CHANGING SERVER URL:
// =====================================
// 1. Edit the file "server_config.txt" in the root directory
// 2. Change the SERVER_URL line to match your current network IP
// 3. Example: SERVER_URL=http://192.168.1.100:3000
// 4. Save the file and restart your PHP server
//
// The system will automatically use this URL for:
// - QR code generation
// - Public profile links  
// - Certificate verification URLs

?>
