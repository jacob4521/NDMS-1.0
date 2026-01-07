<?php
require_once 'config.php';

// Simple test page to check view_citizen.php functionality
if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit;
}

echo "<h2>View Citizen Test Page</h2>";

// Get some sample citizens
$citizensQuery = "SELECT CitizenID, FirstName, LastName, DOB FROM Citizens ORDER BY CitizenID LIMIT 10";
$result = $conn->query($citizensQuery);

if ($result->num_rows > 0) {
    echo "<h3>Available Citizens:</h3>";
    echo "<ul>";
    while ($citizen = $result->fetch_assoc()) {
        $citizenId = $citizen['CitizenID'];
        $name = htmlspecialchars($citizen['FirstName'] . ' ' . $citizen['LastName']);
        $dob = $citizen['DOB'];
        
        echo "<li>";
        echo "<strong>ID: $citizenId</strong> - $name (DOB: $dob) ";
        echo "<a href='view_citizen.php?id=$citizenId' target='_blank' style='color: blue; text-decoration: underline;'>";
        echo "View Profile</a>";
        echo "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No citizens found in the database.</p>";
}

echo "<br><br>";
echo "<a href='dashboard.php'>← Back to Dashboard</a> | ";
echo "<a href='birth_certificate.php'>Birth Certificates</a> | ";
echo "<a href='view_certificates.php'>View All Certificates</a>";
?>
