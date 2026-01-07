<?php
include "config.php";

// Only Education Officers and Admins can manage subjects
if (!isset($_SESSION['UserID']) || !in_array($_SESSION['Role'], ['Admin', 'EducationOfficer'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit;
}

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

switch($method) {
    case 'GET':
        // Fetch all active subjects
        $query = "SELECT SubjectID, SubjectName, SubjectCode, Category FROM Subjects ORDER BY Category, SubjectName";
        $result = $conn->query($query);
        
        $subjects = [];
        while($row = $result->fetch_assoc()) {
            $subjects[] = $row;
        }
        
        echo json_encode(['success' => true, 'subjects' => $subjects]);
        break;
        
    case 'POST':
        // Add new subject
        $input = json_decode(file_get_contents('php://input'), true);
        
        if(!$input || !isset($input['subjectName']) || !isset($input['subjectCode']) || !isset($input['category'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            exit;
        }
        
        $subjectName = trim($input['subjectName']);
        $subjectCode = trim($input['subjectCode']);
        $category = trim($input['category']);
        $createdBy = $_SESSION['UserID'];
        
        // Check if subject already exists
        $checkStmt = $conn->prepare("SELECT SubjectID FROM Subjects WHERE SubjectName = ? OR SubjectCode = ?");
        $checkStmt->bind_param("ss", $subjectName, $subjectCode);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if($checkResult->num_rows > 0) {
            http_response_code(409);
            echo json_encode(['error' => 'Subject name or code already exists']);
            exit;
        }
        
        $stmt = $conn->prepare("INSERT INTO Subjects (SubjectName, SubjectCode, Category, CreatedBy) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $subjectName, $subjectCode, $category, $createdBy);
        
        if($stmt->execute()) {
            $subjectId = $stmt->insert_id;
            echo json_encode([
                'success' => true, 
                'message' => 'Subject added successfully',
                'subject' => [
                    'SubjectID' => $subjectId,
                    'SubjectName' => $subjectName,
                    'SubjectCode' => $subjectCode,
                    'Category' => $category
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to add subject: ' . $stmt->error]);
        }
        break;
        
    case 'DELETE':
        // Deactivate subject (we don't actually delete to preserve data integrity)
        $input = json_decode(file_get_contents('php://input'), true);
        
        if(!$input || !isset($input['subjectId'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Subject ID required']);
            exit;
        }
        
        $subjectId = intval($input['subjectId']);
        
        // Check if subject is used in any education records
        $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM EducationRecords WHERE SubjectID = ?");
        $checkStmt->bind_param("i", $subjectId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $usage = $checkResult->fetch_assoc();
        
        if($usage['count'] > 0) {
            http_response_code(409);
            echo json_encode(['error' => 'Cannot delete subject: it is used in ' . $usage['count'] . ' education records']);
            exit;
        }
        
        $stmt = $conn->prepare("DELETE FROM Subjects WHERE SubjectID = ?");
        $stmt->bind_param("i", $subjectId);
        
        if($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Subject deleted successfully']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to delete subject']);
        }
        break;
        
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
?>
