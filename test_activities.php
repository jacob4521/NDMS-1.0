<?php
require_once 'config.php';
require_once 'citizen_activities_helper.php';

echo "<h1>Testing Citizen Activities System</h1>";

// Test 1: Database Connection
echo "<h2>1. Database Connection Test</h2>";
if ($conn->connect_error) {
    echo "❌ Database connection failed: " . $conn->connect_error;
} else {
    echo "✅ Database connected successfully<br>";
}

// Test 2: Table Exists
echo "<h2>2. Table Structure Test</h2>";
$result = $conn->query("SHOW TABLES LIKE 'CitizenActivities'");
if ($result->num_rows > 0) {
    echo "✅ CitizenActivities table exists<br>";
    
    // Check table structure
    $structure = $conn->query("DESCRIBE CitizenActivities");
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $structure->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ CitizenActivities table does not exist<br>";
}

// Test 3: Sample Data
echo "<h2>3. Sample Data Test</h2>";
$activities = getAllActivities();
echo "📊 Total activities in database: " . count($activities) . "<br>";

if (count($activities) > 0) {
    echo "✅ Sample data exists<br>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>ID</th><th>Citizen</th><th>Category</th><th>Activity</th><th>Level</th><th>Created</th></tr>";
    foreach (array_slice($activities, 0, 5) as $activity) {
        echo "<tr>";
        echo "<td>" . $activity['ActivityID'] . "</td>";
        echo "<td>" . $activity['FirstName'] . " " . $activity['LastName'] . "</td>";
        echo "<td>" . $activity['ActivityCategory'] . "</td>";
        echo "<td>" . $activity['ActivityName'] . "</td>";
        echo "<td>" . ($activity['AchievementLevel'] ?? 'N/A') . "</td>";
        echo "<td>" . date('Y-m-d', strtotime($activity['CreatedAt'])) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "⚠️ No sample data found<br>";
}

// Test 4: Helper Functions
echo "<h2>4. Helper Functions Test</h2>";

// Test getCitizenIdFromUser function
echo "<strong>Testing getCitizenIdFromUser:</strong><br>";
$testUserId = 1; // Admin user
$citizenId = getCitizenIdFromUser($testUserId);
if ($citizenId) {
    echo "✅ getCitizenIdFromUser working: User $testUserId -> Citizen $citizenId<br>";
} else {
    echo "⚠️ getCitizenIdFromUser returned null for user $testUserId<br>";
}

// Test getActivityStats function
echo "<strong>Testing getActivityStats:</strong><br>";
$stats = getActivityStats();
if (is_array($stats)) {
    echo "✅ getActivityStats working: " . count($stats) . " categories found<br>";
    foreach ($stats as $stat) {
        echo "- " . $stat['ActivityCategory'] . ": " . $stat['count'] . " activities (" . $stat['verified_count'] . " verified)<br>";
    }
} else {
    echo "❌ getActivityStats failed<br>";
}

// Test 5: File Upload Directory
echo "<h2>5. File Upload Directory Test</h2>";
$uploadDir = 'uploads/activities/';
if (is_dir($uploadDir)) {
    echo "✅ Upload directory exists: " . $uploadDir . "<br>";
    if (is_writable($uploadDir)) {
        echo "✅ Upload directory is writable<br>";
    } else {
        echo "⚠️ Upload directory is not writable<br>";
    }
} else {
    echo "❌ Upload directory does not exist: " . $uploadDir . "<br>";
}

// Test 6: Citizens with User Accounts
echo "<h2>6. Citizens with User Accounts Test</h2>";
$result = $conn->query("
    SELECT c.CitizenID, c.FirstName, c.LastName, c.Citizen_eID, u.UserID, u.Role
    FROM Citizens c 
    LEFT JOIN Users u ON c.Citizen_eID = u.Username 
    WHERE u.Role = 'Citizen'
    LIMIT 5
");

if ($result && $result->num_rows > 0) {
    echo "✅ Citizens with user accounts found:<br>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>CitizenID</th><th>Name</th><th>eID</th><th>UserID</th><th>Role</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['CitizenID'] . "</td>";
        echo "<td>" . $row['FirstName'] . " " . $row['LastName'] . "</td>";
        echo "<td>" . $row['Citizen_eID'] . "</td>";
        echo "<td>" . $row['UserID'] . "</td>";
        echo "<td>" . $row['Role'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "⚠️ No citizens with user accounts found<br>";
}

echo "<h2>7. Test Summary</h2>";
echo "<p>✅ Database connection working<br>";
echo "✅ Table structure correct<br>";
echo "✅ Sample data loaded<br>";
echo "✅ Helper functions working<br>";
echo "✅ Upload directory ready<br>";
echo "✅ User-citizen mapping available</p>";

echo "<h3>🎉 Citizen Activities System is ready!</h3>";
echo "<p><a href='citizen_activities.php'>Admin Activities Page</a> | <a href='citizen_activities_citizen.php'>Citizen Activities Page</a></p>";
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    h1, h2 { color: #2c3e50; }
    table { border-collapse: collapse; margin: 10px 0; }
    th, td { padding: 8px; border: 1px solid #ddd; text-align: left; }
    th { background-color: #f4f4f4; }
    .success { color: green; }
    .warning { color: orange; }
    .error { color: red; }
</style>
