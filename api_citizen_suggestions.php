<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['UserID'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get the search query
$query = $_GET['q'] ?? '';

if (strlen($query) < 1) {
    echo json_encode([]);
    exit;
}

// Search for citizens by eID or name
$stmt = $conn->prepare("
    SELECT Citizen_eID, FirstName, LastName, CitizenID 
    FROM Citizens 
    WHERE Citizen_eID LIKE ? OR FirstName LIKE ? OR LastName LIKE ?
    ORDER BY Citizen_eID ASC
    LIMIT 10
");

$searchTerm = "%$query%";
$stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
$stmt->execute();
$result = $stmt->get_result();

$suggestions = [];
while ($row = $result->fetch_assoc()) {
    $suggestions[] = [
        'eid' => $row['Citizen_eID'],
        'name' => $row['FirstName'] . ' ' . $row['LastName'],
        'citizen_id' => $row['CitizenID'],
        'display' => $row['Citizen_eID'] . ' - ' . $row['FirstName'] . ' ' . $row['LastName']
    ];
}

$stmt->close();

header('Content-Type: application/json');
echo json_encode($suggestions);
?>
