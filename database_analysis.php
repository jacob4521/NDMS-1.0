<?php
include 'config.php';

echo "=== OL_SUBJECTS TABLE STRUCTURE ===\n";
$result = $conn->query('DESCRIBE ol_subjects');
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . ' - ' . ($row['Default'] ?? 'NULL') . "\n";
}

echo "\n=== OL_SUBJECTS TABLE DATA WITH CATEGORIES ===\n";
$result = $conn->query('SELECT SubjectName, Category, IsCompulsory FROM ol_subjects ORDER BY IsCompulsory DESC, Category, SubjectName');
while($row = $result->fetch_assoc()) {
    $type = $row['IsCompulsory'] ? 'COMPULSORY' : 'OPTIONAL';
    $category = $row['Category'] ?? 'No Category';
    echo $type . " | " . $category . " | " . $row['SubjectName'] . "\n";
}

echo "\n=== CHECKING CAREER INTEREST AREAS IN DATABASE ===\n";
$result = $conn->query('SELECT DISTINCT InterestArea FROM career_interests ORDER BY InterestArea');
$db_interests = [];
echo "INTERESTS FOUND IN DATABASE:\n";
while($row = $result->fetch_assoc()) {
    $db_interests[] = $row['InterestArea'];
    echo "- " . $row['InterestArea'] . "\n";
}

echo "\n=== COMPARING INTEREST AREAS ===\n";
$form_interests = [
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

echo "STANDARDIZED INTEREST AREAS (used in both pages):\n";
foreach ($form_interests as $interest) {
    echo "- " . $interest . "\n";
}

if (empty($db_interests)) {
    echo "\n⚠️ NO INTEREST DATA IN DATABASE YET\n";
    echo "This is normal if no students have submitted forms yet.\n";
} else {
    $db_not_standardized = array_diff($db_interests, $form_interests);
    if (!empty($db_not_standardized)) {
        echo "\n❌ NON-STANDARDIZED INTERESTS IN DATABASE:\n";
        foreach ($db_not_standardized as $interest) {
            echo "- " . $interest . "\n";
        }
    } else {
        echo "\n✅ All database interests match standardized format\n";
    }
}
?>
