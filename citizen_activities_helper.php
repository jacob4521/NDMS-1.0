<?php
require_once 'config.php';

// ==========================
// CITIZEN ACTIVITIES HELPER FUNCTIONS
// ==========================

/**
 * Add a new citizen activity
 */
function addCitizenActivity($citizenId, $category, $name, $level, $details, $proofPath = null) {
    global $conn;
    
    $stmt = $conn->prepare("INSERT INTO CitizenActivities (CitizenID, ActivityCategory, ActivityName, AchievementLevel, Details, ProofPath) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssss", $citizenId, $category, $name, $level, $details, $proofPath);
    $result = $stmt->execute();
    $activityId = $conn->insert_id;
    $stmt->close();
    
    // Create notification for admins about new activity
    if ($result) {
        createActivityNotification($activityId, 'added');
    }
    
    return $result;
}

/**
 * Get activities for a specific citizen
 */
function getCitizenActivities($citizenId) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT a.*, c.FirstName, c.LastName, u.Username as VerifiedByName 
        FROM CitizenActivities a 
        JOIN Citizens c ON a.CitizenID = c.CitizenID 
        LEFT JOIN Users u ON a.VerifiedBy = u.UserID 
        WHERE a.CitizenID = ? 
        ORDER BY a.CreatedAt DESC
    ");
    $stmt->bind_param("i", $citizenId);
    $stmt->execute();
    $result = $stmt->get_result();
    $activities = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $activities;
}

/**
 * Get all activities (for admin)
 */
function getAllActivities($category = null, $verified = null) {
    global $conn;
    
    $sql = "
        SELECT a.*, c.FirstName, c.LastName, c.NIC, c.Citizen_eID, u.Username as VerifiedByName 
        FROM CitizenActivities a 
        JOIN Citizens c ON a.CitizenID = c.CitizenID 
        LEFT JOIN Users u ON a.VerifiedBy = u.UserID 
        WHERE 1=1
    ";
    
    $params = [];
    $types = "";
    
    if ($category) {
        $sql .= " AND a.ActivityCategory = ?";
        $params[] = $category;
        $types .= "s";
    }
    
    if ($verified !== null) {
        if ($verified) {
            $sql .= " AND a.VerifiedBy IS NOT NULL";
        } else {
            $sql .= " AND a.VerifiedBy IS NULL";
        }
    }
    
    $sql .= " ORDER BY a.CreatedAt DESC";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $activities = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $activities;
}

/**
 * Update an activity
 */
function updateActivity($activityId, $category, $name, $level, $details, $proofPath = null) {
    global $conn;
    
    if ($proofPath) {
        $stmt = $conn->prepare("UPDATE CitizenActivities SET ActivityCategory = ?, ActivityName = ?, AchievementLevel = ?, Details = ?, ProofPath = ? WHERE ActivityID = ?");
        $stmt->bind_param("sssssi", $category, $name, $level, $details, $proofPath, $activityId);
    } else {
        $stmt = $conn->prepare("UPDATE CitizenActivities SET ActivityCategory = ?, ActivityName = ?, AchievementLevel = ?, Details = ? WHERE ActivityID = ?");
        $stmt->bind_param("ssssi", $category, $name, $level, $details, $activityId);
    }
    
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

/**
 * Delete an activity
 */
function deleteActivity($activityId) {
    global $conn;
    
    // Get the proof file path before deleting
    $stmt = $conn->prepare("SELECT ProofPath FROM CitizenActivities WHERE ActivityID = ?");
    $stmt->bind_param("i", $activityId);
    $stmt->execute();
    $result = $stmt->get_result();
    $activity = $result->fetch_assoc();
    $stmt->close();
    
    // Delete the record
    $stmt = $conn->prepare("DELETE FROM CitizenActivities WHERE ActivityID = ?");
    $stmt->bind_param("i", $activityId);
    $success = $stmt->execute();
    $stmt->close();
    
    // Delete the proof file if it exists
    if ($success && $activity && $activity['ProofPath'] && file_exists($activity['ProofPath'])) {
        unlink($activity['ProofPath']);
    }
    
    return $success;
}

/**
 * Verify an activity (admin only)
 */
function verifyActivity($activityId, $verifiedBy) {
    global $conn;
    
    $stmt = $conn->prepare("UPDATE CitizenActivities SET VerifiedBy = ?, VerifiedAt = NOW() WHERE ActivityID = ?");
    $stmt->bind_param("ii", $verifiedBy, $activityId);
    $result = $stmt->execute();
    $stmt->close();
    
    if ($result) {
        createActivityNotification($activityId, 'verified');
    }
    
    return $result;
}

/**
 * Check if user can edit activity
 */
function canEditActivity($activityId, $userId, $userRole) {
    global $conn;
    
    // Admins can edit any activity
    if ($userRole === 'Admin') {
        return true;
    }
    
    // Citizens can only edit their own activities
    if ($userRole === 'Citizen') {
        $stmt = $conn->prepare("
            SELECT a.CitizenID, c.Citizen_eID 
            FROM CitizenActivities a 
            JOIN Citizens c ON a.CitizenID = c.CitizenID 
            JOIN Users u ON c.Citizen_eID = u.Username 
            WHERE a.ActivityID = ? AND u.UserID = ?
        ");
        $stmt->bind_param("ii", $activityId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $canEdit = $result->num_rows > 0;
        $stmt->close();
        
        return $canEdit;
    }
    
    return false;
}

/**
 * Get activity statistics
 */
function getActivityStats($citizenId = null) {
    global $conn;
    
    $sql = "
        SELECT 
            ActivityCategory,
            COUNT(*) as count,
            SUM(CASE WHEN VerifiedBy IS NOT NULL THEN 1 ELSE 0 END) as verified_count
        FROM CitizenActivities 
    ";
    
    if ($citizenId) {
        $sql .= " WHERE CitizenID = ?";
    }
    
    $sql .= " GROUP BY ActivityCategory";
    
    $stmt = $conn->prepare($sql);
    if ($citizenId) {
        $stmt->bind_param("i", $citizenId);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $stats = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    return $stats;
}

/**
 * Handle file upload for activity proof
 */
function uploadActivityProof($file) {
    $uploadDir = 'uploads/activities/';
    
    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'error' => 'Invalid file type. Only JPG, PNG, GIF, and PDF files are allowed.'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'File too large. Maximum size is 5MB.'];
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'activity_' . time() . '_' . uniqid() . '.' . $extension;
    $filePath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        return ['success' => true, 'path' => $filePath];
    } else {
        return ['success' => false, 'error' => 'Failed to upload file.'];
    }
}

/**
 * Create notification for activity events
 */
function createActivityNotification($activityId, $action) {
    global $conn;
    
    // Check if notifications table exists (check both possible names)
    $result = $conn->query("SHOW TABLES LIKE 'notifications'");
    if ($result->num_rows == 0) {
        $result = $conn->query("SHOW TABLES LIKE 'Notifications'");
        if ($result->num_rows == 0) {
            return; // Notifications table doesn't exist
        }
    }
    
    // Get activity details
    $stmt = $conn->prepare("
        SELECT a.*, c.FirstName, c.LastName 
        FROM CitizenActivities a 
        JOIN Citizens c ON a.CitizenID = c.CitizenID 
        WHERE a.ActivityID = ?
    ");
    $stmt->bind_param("i", $activityId);
    $stmt->execute();
    $result = $stmt->get_result();
    $activity = $result->fetch_assoc();
    $stmt->close();
    
    if (!$activity) return;
    
    try {
        $message = '';
        
        switch ($action) {
            case 'added':
                $message = "New {$activity['ActivityCategory']} activity added by {$activity['FirstName']} {$activity['LastName']}: {$activity['ActivityName']}";
                
                // Get admin CitizenIDs to send notifications
                $stmt = $conn->prepare("
                    SELECT c.CitizenID 
                    FROM Citizens c 
                    JOIN Users u ON c.Citizen_eID = u.Username 
                    WHERE u.Role = 'Admin'
                ");
                $stmt->execute();
                $adminResult = $stmt->get_result();
                
                while ($admin = $adminResult->fetch_assoc()) {
                    $insertStmt = $conn->prepare("
                        INSERT INTO notifications (CitizenID, Message, NotificationDate, IsSeen) 
                        VALUES (?, ?, CURDATE(), 0)
                    ");
                    $insertStmt->bind_param("is", $admin['CitizenID'], $message);
                    $insertStmt->execute();
                    $insertStmt->close();
                }
                $stmt->close();
                break;
                
            case 'verified':
                $message = "Your {$activity['ActivityCategory']} activity '{$activity['ActivityName']}' has been verified";
                
                // Send to the citizen who owns the activity
                $stmt = $conn->prepare("
                    INSERT INTO notifications (CitizenID, Message, NotificationDate, IsSeen) 
                    VALUES (?, ?, CURDATE(), 0)
                ");
                $stmt->bind_param("is", $activity['CitizenID'], $message);
                $stmt->execute();
                $stmt->close();
                break;
        }
    } catch (Exception $e) {
        // Silently fail if notification creation fails - don't break the main functionality
        error_log("Activity notification creation failed: " . $e->getMessage());
    }
}

/**
 * Get citizens for dropdown (admin use)
 */
function getAllCitizensForDropdown() {
    global $conn;
    
    $result = $conn->query("
        SELECT CitizenID, FirstName, LastName, NIC, Citizen_eID 
        FROM Citizens 
        ORDER BY FirstName, LastName
    ");
    
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get citizen ID from user session
 */
function getCitizenIdFromUser($userId) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT c.CitizenID 
        FROM Citizens c 
        JOIN Users u ON c.Citizen_eID = u.Username 
        WHERE u.UserID = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $citizen = $result->fetch_assoc();
    $stmt->close();
    
    return $citizen ? $citizen['CitizenID'] : null;
}
?>