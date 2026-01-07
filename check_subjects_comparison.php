<?php
include 'config.php';

echo "=== DATABASE OPTIONAL SUBJECTS ===\n";
$result = $conn->query('SELECT SubjectName FROM ol_subjects WHERE IsCompulsory = FALSE ORDER BY SubjectName');
$db_optional = [];
while($row = $result->fetch_assoc()) {
    $db_optional[] = $row['SubjectName'];
    echo $row['SubjectName'] . "\n";
}

echo "\n=== DATABASE COMPULSORY SUBJECTS ===\n";
$result = $conn->query('SELECT SubjectName FROM ol_subjects WHERE IsCompulsory = TRUE ORDER BY SubjectName');
$db_compulsory = [];
while($row = $result->fetch_assoc()) {
    $db_compulsory[] = $row['SubjectName'];
    echo $row['SubjectName'] . "\n";
}

echo "\n=== TESTING PAGE OPTIONAL SUBJECTS ===\n";
$testing_optional = [
    'Agriculture & Food Technology', 'Aquatic Bioresources Technology', 'Arabic',
    'Art', 'Business & Accounting Studies', 'Chinese', 'Civic Education',
    'Dancing', 'Drama & Theatre', 'Economics', 'Engineering Technology',
    'English Literature', 'Entrepreneurship Studies', 'French', 'Geography',
    'German', 'Health & Physical Education', 'Hindi', 'Home Economics',
    'Information & Communication Technology', 'Japanese', 'Korean', 'Music',
    'Pali', 'Sanskrit', 'Sinhala Literature', 'Tamil Literature'
];

foreach ($testing_optional as $subject) {
    echo $subject . "\n";
}

echo "\n=== TESTING PAGE INTEREST AREAS ===\n";
$testing_interests = [
    'Science & Medical',
    'Engineering & Technology', 
    'ICT & Computing',
    'Commerce & Business',
    'Arts & Humanities',
    'Law & Social Sciences',
    'Creative Arts & Design',
    'Vocational / Skilled Trades',
    'Sports & Physical Education'
];

foreach ($testing_interests as $interest) {
    echo $interest . "\n";
}

echo "\n=== DIFFERENCES ANALYSIS ===\n";

// Find subjects in testing page but not in database
$testing_not_in_db = array_diff($testing_optional, $db_optional);
if (!empty($testing_not_in_db)) {
    echo "SUBJECTS IN TESTING PAGE BUT NOT IN DATABASE:\n";
    foreach ($testing_not_in_db as $subject) {
        echo "- " . $subject . "\n";
    }
} else {
    echo "✅ All testing page subjects are in the database\n";
}

// Find subjects in database but not in testing page
$db_not_in_testing = array_diff($db_optional, $testing_optional);
if (!empty($db_not_in_testing)) {
    echo "\nSUBJECTS IN DATABASE BUT NOT IN TESTING PAGE:\n";
    foreach ($db_not_in_testing as $subject) {
        echo "- " . $subject . "\n";
    }
} else {
    echo "\n✅ All database subjects are in the testing page\n";
}

echo "\n=== FORM PAGE USES DATABASE DIRECTLY ===\n";
echo "The career_guidance_form.php pulls subjects directly from the database using:\n";
echo "\$optional_subjects = \$conn->query(\"SELECT * FROM ol_subjects WHERE IsCompulsory = FALSE ORDER BY Category, SubjectName\");\n";
echo "So form page always shows what's in the database.\n";

echo "\n=== RECOMMENDATIONS ===\n";
if (!empty($testing_not_in_db) || !empty($db_not_in_testing)) {
    echo "❌ INCONSISTENCY DETECTED - Testing page and database don't match!\n";
    echo "SOLUTION: Update testing page to use database subjects or sync database with testing page.\n";
} else {
    echo "✅ PERFECT SYNC - Testing page matches database exactly!\n";
}
?>
