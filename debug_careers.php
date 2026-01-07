<?php
include 'config.php';

// Check for duplicate career names
echo "Duplicate career paths:\n";
$query = $conn->query('SELECT PathName, COUNT(*) as count FROM career_paths GROUP BY PathName HAVING COUNT(*) > 1');
while($row = $query->fetch_assoc()) {
    echo $row['PathName'] . ': ' . $row['count'] . " entries\n";
}

echo "\n\nInformation Technology related entries:\n";
$it_query = $conn->query('SELECT * FROM career_paths WHERE PathName LIKE "%Information Technology%" OR PathName LIKE "%IT%" OR PathName LIKE "%Technology%" ORDER BY PathName');
while($row = $it_query->fetch_assoc()) {
    echo 'ID: ' . $row['PathID'] . ', Name: ' . $row['PathName'] . ', Active: ' . $row['IsActive'] . ', Interest: ' . $row['InterestArea'] . "\n";
}

echo "\n\nFirst 10 careers ordered by PathName:\n";
$all_query = $conn->query('SELECT PathID, PathName, InterestArea FROM career_paths WHERE IsActive = TRUE ORDER BY PathName LIMIT 10');
while($row = $all_query->fetch_assoc()) {
    echo $row['PathID'] . ': ' . $row['PathName'] . ' (' . $row['InterestArea'] . ")\n";
}
?>
