<?php
include "config.php";

echo "EducationRecords table structure:\n";
$result = $conn->query('DESCRIBE EducationRecords');
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}
?>
