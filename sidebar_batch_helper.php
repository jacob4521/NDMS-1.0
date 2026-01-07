<?php
// Batch Apply Sidebar Script
// This script helps apply sidebar to multiple pages efficiently

$pages_to_update = [
    'edit_user.php',
    'manage_vaccination_schedule.php', 
    'add_education.php',
    'view_education.php',
    'view_certificates.php',
    'citizen_activities.php',
    'citizen_dashboard.php',
    'change_password.php',
    'view_citizen.php',
    'register.php',
    'add_vaccination.php',
    'add_medical.php',
    'homepage.php'
];

// Instructions for each file type
$instructions = [
    'title_line' => 'Add: <link rel="stylesheet" href="includes/sidebar.css"> after title',
    'body_replace' => 'Replace <body><div class="container"> with sidebar include',
    'end_replace' => 'Add closing divs and sidebar.js include before </body>'
];

echo "Pages that need sidebar integration:\n";
foreach ($pages_to_update as $page) {
    echo "- $page\n";
}

echo "\nInstructions for each page:\n";
foreach ($instructions as $step => $instruction) {
    echo "1. $instruction\n";
}
?>
