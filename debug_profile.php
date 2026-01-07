<?php
// debug_profile.php - Debug version to see what's wrong
include "config.php";

// Get citizen ID from URL
$citizenId = isset($_GET['citizen_id']) ? intval($_GET['citizen_id']) : 9; // Default to citizen 9

echo "<h2>Debug: Citizen ID = $citizenId</h2>";

// Fetch minimal public info only
$stmt = $conn->prepare("
    SELECT 
        CitizenID,
        FirstName,
        LastName,
        DOB,
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

echo "<h3>Raw Data from Database:</h3>";
echo "<pre>";
print_r($citizen);
echo "</pre>";

// Check education records separately
$eduStmt = $conn->prepare("SELECT GradeLevel FROM EducationRecords WHERE CitizenID = ? ORDER BY RecordDate DESC LIMIT 1");
$eduStmt->bind_param("i", $citizenId);
$eduStmt->execute();
$eduResult = $eduStmt->get_result();
$education = $eduResult->fetch_assoc();

echo "<h3>Education Data:</h3>";
echo "<pre>";
print_r($education);
echo "</pre>";

$currentYear = date('Y');
$age = $currentYear - $citizen['BirthYear'];

echo "<h3>Calculated Values:</h3>";
echo "Current Year: $currentYear<br>";
echo "Birth Year: " . $citizen['BirthYear'] . "<br>";
echo "Age: $age<br>";
echo "Full Name: " . htmlspecialchars($citizen['FirstName'] . ' ' . $citizen['LastName']) . "<br>";
echo "Gender: " . htmlspecialchars($citizen['Gender'] ?? 'Not specified') . "<br>";
?>
