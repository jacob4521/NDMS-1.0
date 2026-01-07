<?php
include 'config.php';

echo "=== OPTIONAL SUBJECTS FROM DATABASE ===\n";
$result = $conn->query('SELECT SubjectName FROM ol_subjects WHERE IsCompulsory = FALSE ORDER BY SubjectName');
while($row = $result->fetch_assoc()) {
    echo $row['SubjectName'] . "\n";
}

echo "\n=== COMPULSORY SUBJECTS FROM DATABASE ===\n";
$result = $conn->query('SELECT SubjectName FROM ol_subjects WHERE IsCompulsory = TRUE ORDER BY SubjectName');
while($row = $result->fetch_assoc()) {
    echo $row['SubjectName'] . "\n";
}
?>
