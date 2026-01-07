<?php
include "config.php";

// Check if user is logged in and has access
if (!isset($_SESSION['UserID']) || !in_array($_SESSION['Role'], ['Admin', 'EducationOfficer'])) {
    header("Location: login.php");
    exit();
}

// Get student ID from parameter
$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;

if ($student_id <= 0) {
    $_SESSION['error'] = "Invalid student ID";
    header("Location: admin_career_guidance.php");
    exit();
}

// Check if student exists and doesn't already have suggestions
$check_query = "
    SELECT cs.*, csg.StudentID as has_suggestion 
    FROM career_students cs 
    LEFT JOIN career_suggestions csg ON cs.StudentID = csg.StudentID 
    WHERE cs.StudentID = ?
";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param("i", $student_id);
$check_stmt->execute();
$student = $check_stmt->get_result()->fetch_assoc();

if (!$student) {
    $_SESSION['error'] = "Student not found";
    header("Location: admin_career_guidance.php");
    exit();
}

if ($student['has_suggestion']) {
    $_SESSION['info'] = "Career suggestions already exist for this student";
    header("Location: career_suggestions.php?student_id=" . $student_id);
    exit();
}

// Include the career suggestion engine
require_once 'career_suggestions.php';

try {
    // Generate suggestions
    $suggestion_engine = new CareerSuggestionEngine($conn);
    $suggestions = $suggestion_engine->generateSuggestions($student_id, true);
    
    if (!empty($suggestions)) {
        // Insert suggestions into database
        $insert_query = "
            INSERT INTO career_suggestions (StudentID, PrimarySuggestion, SecondarySuggestion, OtherSuggestions, MatchScore, CreatedBy, GeneratedAt) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ";
        
        $primary = $suggestions[0]['career_path'];
        $secondary = isset($suggestions[1]) ? $suggestions[1]['career_path'] : null;
        $other_suggestions = array_slice($suggestions, 2);
        $other_json = !empty($other_suggestions) ? json_encode($other_suggestions) : null;
        $match_score = $suggestions[0]['match_score'];
        
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("isssdi", 
            $student_id, 
            $primary, 
            $secondary, 
            $other_json, 
            $match_score, 
            $_SESSION['UserID']
        );
        
        if ($insert_stmt->execute()) {
            $_SESSION['success'] = "Career suggestions generated successfully for " . htmlspecialchars($student['FullName']);
            header("Location: career_suggestions.php?student_id=" . $student_id);
        } else {
            $_SESSION['error'] = "Failed to save career suggestions: " . $conn->error;
            header("Location: admin_career_guidance.php");
        }
    } else {
        $_SESSION['warning'] = "No suitable career suggestions could be generated for this student";
        header("Location: admin_career_guidance.php");
    }
    
} catch (Exception $e) {
    $_SESSION['error'] = "Error generating suggestions: " . $e->getMessage();
    header("Location: admin_career_guidance.php");
}

exit();
?>
