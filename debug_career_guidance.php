<?php
include "config.php";

echo "<h2>Career Guidance Database Debug</h2>";

// Check students
echo "<h3>Students in career_students table:</h3>";
$students_query = "SELECT StudentID, FullName, CreatedAt FROM career_students ORDER BY CreatedAt DESC LIMIT 10";
$students_result = $conn->query($students_query);

if ($students_result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Student ID</th><th>Name</th><th>Created At</th></tr>";
    while ($student = $students_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $student['StudentID'] . "</td>";
        echo "<td>" . htmlspecialchars($student['FullName']) . "</td>";
        echo "<td>" . $student['CreatedAt'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No students found in career_students table.</p>";
}

// Check interests for these students
echo "<h3>Interests in career_interests table:</h3>";
$interests_query = "
    SELECT ci.StudentID, cs.FullName, ci.InterestArea, ci.Priority, ci.CreatedAt 
    FROM career_interests ci 
    JOIN career_students cs ON ci.StudentID = cs.StudentID 
    ORDER BY ci.StudentID, ci.Priority 
    LIMIT 20
";
$interests_result = $conn->query($interests_query);

if ($interests_result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Student ID</th><th>Student Name</th><th>Interest Area</th><th>Priority</th><th>Created At</th></tr>";
    while ($interest = $interests_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $interest['StudentID'] . "</td>";
        echo "<td>" . htmlspecialchars($interest['FullName']) . "</td>";
        echo "<td>" . htmlspecialchars($interest['InterestArea']) . "</td>";
        echo "<td>" . $interest['Priority'] . "</td>";
        echo "<td>" . $interest['CreatedAt'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No interests found in career_interests table.</p>";
}

// Test the GROUP_CONCAT query that admin page uses
echo "<h3>Testing admin page query (GROUP_CONCAT interests):</h3>";
$admin_query = "
    SELECT 
        cs.StudentID,
        cs.FullName,
        GROUP_CONCAT(DISTINCT ci.InterestArea ORDER BY ci.Priority) as Interests
    FROM career_students cs
    LEFT JOIN career_interests ci ON cs.StudentID = ci.StudentID
    GROUP BY cs.StudentID
    ORDER BY cs.CreatedAt DESC
    LIMIT 10
";
$admin_result = $conn->query($admin_query);

if ($admin_result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Student ID</th><th>Student Name</th><th>Interests (GROUP_CONCAT)</th></tr>";
    while ($row = $admin_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['StudentID'] . "</td>";
        echo "<td>" . htmlspecialchars($row['FullName']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Interests'] ?: 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No results from admin query.</p>";
}

// Check database structure
echo "<h3>Database Table Structures:</h3>";
echo "<h4>career_students table:</h4>";
$result = $conn->query("DESCRIBE career_students");
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "<br>";
}

echo "<h4>career_interests table:</h4>";
$result = $conn->query("DESCRIBE career_interests");
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " - " . $row['Type'] . "<br>";
}

$conn->close();
?>
