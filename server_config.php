<?php
/**
 * Server Configuration Helper
 * Reads the server configuration from server_config.txt
 */

function getServerConfig() {
    $configFile = __DIR__ . '/server_config.txt';
    $config = [];
    
    if (file_exists($configFile)) {
        $lines = file($configFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Skip comments and empty lines
            $line = trim($line);
            if (empty($line) || $line[0] === '#') {
                continue;
            }
            
            // Parse key=value pairs
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $config[trim($key)] = trim($value);
            }
        }
    }
    
    return $config;
}

function getServerUrl() {
    $config = getServerConfig();
    
    // Return configured URL or fallback to auto-detection
    if (isset($config['SERVER_URL']) && !empty($config['SERVER_URL'])) {
        return rtrim($config['SERVER_URL'], '/'); // Remove trailing slash
    }
    
    // Fallback: auto-detect (for backward compatibility)
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost:3000';
    return $protocol . '://' . $host;
}

function getPublicProfileUrl($citizenId) {
    return getServerUrl() . '/public_profile.php?citizen_id=' . urlencode($citizenId);
}
?>
