<?php
// Test script to check subscribe.php
echo "Testing subscribe.php...\n";

// Test with POST data
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['email'] = 'test@example.com';

ob_start();
include 'subscribe.php';
$output = ob_get_clean();

echo "Output from subscribe.php:\n";
echo $output;
echo "\n";

// Check if it's valid JSON
$json = json_decode($output, true);
if ($json !== null) {
    echo "Valid JSON response: YES\n";
    echo "Success: " . ($json['success'] ? 'true' : 'false') . "\n";
    echo "Message: " . $json['message'] . "\n";
} else {
    echo "Valid JSON response: NO\n";
    echo "JSON Error: " . json_last_error_msg() . "\n";
}
?>
