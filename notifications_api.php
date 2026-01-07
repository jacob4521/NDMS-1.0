<?php
// notifications_api.php
// AJAX endpoint for notification operations

include "config.php";
include_once "notifications_helper.php";

// Check if user is logged in
if (!isset($_SESSION['UserID'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userID = $_SESSION['UserID'];
$userRole = $_SESSION['Role'];

// Get citizen ID based on user role
$citizenID = null;
if ($userRole === 'Citizen') {
    // For citizens, get their own CitizenID
    $citizenQuery = $conn->prepare("
        SELECT c.CitizenID 
        FROM Citizens c 
        JOIN Users u ON c.Citizen_eID = u.Username 
        WHERE u.UserID = ?
    ");
    $citizenQuery->bind_param("i", $userID);
    $citizenQuery->execute();
    $result = $citizenQuery->get_result();
    
    if ($result->num_rows > 0) {
        $citizenID = $result->fetch_assoc()['CitizenID'];
    }
}

// Handle different API actions
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_notifications':
        if ($userRole === 'Citizen' && $citizenID) {
            $notifications = getUnreadNotifications($citizenID, $conn);
            $notificationArray = [];
            
            while ($notification = $notifications->fetch_assoc()) {
                $notificationArray[] = $notification;
            }
            
            echo json_encode([
                'success' => true,
                'notifications' => $notificationArray,
                'count' => count($notificationArray)
            ]);
        } elseif ($userRole === 'Admin' || $userRole === 'MedicalOfficer') {
            // Admin/Medical officers see all notifications
            $notifications = getAllNotifications($conn, 20, true); // Only unseen
            $notificationArray = [];
            
            while ($notification = $notifications->fetch_assoc()) {
                $notificationArray[] = $notification;
            }
            
            echo json_encode([
                'success' => true,
                'notifications' => $notificationArray,
                'count' => count($notificationArray)
            ]);
        } else {
            echo json_encode(['error' => 'No access']);
        }
        break;
        
    case 'get_count':
        if ($userRole === 'Citizen' && $citizenID) {
            $count = getUnreadNotificationCount($citizenID, $conn);
            echo json_encode(['success' => true, 'count' => $count]);
        } elseif ($userRole === 'Admin' || $userRole === 'MedicalOfficer') {
            $stats = getNotificationStats($conn);
            echo json_encode(['success' => true, 'count' => $stats['unread_notifications']]);
        } else {
            echo json_encode(['error' => 'No access']);
        }
        break;
        
    case 'mark_seen':
        $notificationID = $_POST['notification_id'] ?? 0;
        
        if ($notificationID > 0) {
            $success = markNotificationAsSeen($notificationID, $conn);
            echo json_encode(['success' => $success]);
        } else {
            echo json_encode(['error' => 'Invalid notification ID']);
        }
        break;
        
    case 'mark_all_seen':
        if ($userRole === 'Citizen' && $citizenID) {
            $success = markAllNotificationsAsSeen($citizenID, $conn);
            echo json_encode(['success' => $success]);
        } else {
            echo json_encode(['error' => 'No access']);
        }
        break;
        
    case 'generate_notifications':
        // Only admins can manually trigger notification generation
        if ($userRole === 'Admin') {
            $success = generateUpcomingVaccinationNotifications($conn);
            echo json_encode(['success' => $success, 'message' => 'Notifications generated']);
        } else {
            echo json_encode(['error' => 'Admin access required']);
        }
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}
?>
