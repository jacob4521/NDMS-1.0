<?php
header('Content-Type: application/json');
include "config.php";

// Check if user is logged in and has access
if (!isset($_SESSION['UserID']) || !in_array($_SESSION['Role'], ['Admin', 'EducationOfficer'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['student_id'])) {
    echo json_encode(['success' => false, 'message' => 'Student ID is required']);
    exit();
}

$student_id = intval($input['student_id']);

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Verify this is a test student (created within last hour for safety)
    $student_query = $conn->prepare("SELECT CreatedAt FROM career_students WHERE StudentID = ?");
    $student_query->bind_param("i", $student_id);
    $student_query->execute();
    $student = $student_query->get_result()->fetch_assoc();
    
    if (!$student) {
        throw new Exception('Student not found');
    }
    
    $created_time = strtotime($student['CreatedAt']);
    $current_time = time();
    $time_diff = $current_time - $created_time;
    
    // Only allow cleanup of students created within the last hour (for safety)
    if ($time_diff > 3600) { // 1 hour = 3600 seconds
        throw new Exception('Cannot cleanup - student record is too old (not a test record)');
    }
    
    // Delete related records
    $conn->query("DELETE FROM career_suggestions WHERE StudentID = $student_id");
    $conn->query("DELETE FROM career_interests WHERE StudentID = $student_id");
    $conn->query("DELETE FROM ol_results WHERE StudentID = $student_id");
    $conn->query("DELETE FROM career_students WHERE StudentID = $student_id");
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Test data cleaned up successfully']);
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
?>
