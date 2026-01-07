<?php
include 'config.php';

// Get a student's data to test scoring
$student_query = $conn->query("SELECT * FROM student_grades WHERE StudentID = 1 LIMIT 1");
$student = $student_query->fetch_assoc();

if (!$student) {
    echo "No student found with ID 1\n";
    exit;
}

echo "Testing career scoring for Student ID: " . $student['StudentID'] . "\n";
echo "Student Grades: Math=" . $student['Mathematics'] . ", Science=" . $student['Science'] . ", English=" . $student['English'] . "\n\n";

// Get both IT careers
$it_careers = $conn->query("SELECT * FROM career_paths WHERE PathName LIKE '%Information Technology%' ORDER BY PathID");

// Mock the CareerSuggestionEngine class behavior
$marketDemand = [
    'Information Technology' => 0.95,
    'Information Technology (Vocational)' => 0.95, // This might be the issue!
];

while($career = $it_careers->fetch_assoc()) {
    echo "Career: " . $career['PathName'] . " (ID: " . $career['PathID'] . ")\n";
    echo "Interest Area: " . $career['InterestArea'] . "\n";
    
    // Check market demand factor
    $demand = isset($marketDemand[$career['PathName']]) ? $marketDemand[$career['PathName']] : 0.75;
    echo "Market Demand Factor: " . $demand . " (" . ($demand * 100) . "%)\n";
    
    // Check if this career has any special advantages
    echo "Required Subjects: " . $career['RequiredSubjects'] . "\n";
    echo "Minimum Grades: " . $career['MinimumGrades'] . "\n";
    echo "---\n\n";
}
?>
