<?php
// notifications_helper.php
// Helper functions for the notification system

/**
 * Get unread notifications for a citizen
 */
function getUnreadNotifications($citizenID, $conn, $limit = 10) {
    $query = "
        SELECT NotificationID, Message, NotificationDate, CreatedAt
        FROM Notifications 
        WHERE CitizenID = ? AND IsSeen = FALSE 
        ORDER BY CreatedAt DESC 
        LIMIT ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $citizenID, $limit);
    $stmt->execute();
    
    return $stmt->get_result();
}

/**
 * Get count of unread notifications for a citizen
 */
function getUnreadNotificationCount($citizenID, $conn) {
    $query = "SELECT COUNT(*) as count FROM Notifications WHERE CitizenID = ? AND IsSeen = FALSE";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $citizenID);
    $stmt->execute();
    
    $result = $stmt->get_result()->fetch_assoc();
    return $result['count'];
}

/**
 * Mark a notification as seen
 */
function markNotificationAsSeen($notificationID, $conn) {
    $query = "UPDATE Notifications SET IsSeen = TRUE WHERE NotificationID = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $notificationID);
    
    return $stmt->execute();
}

/**
 * Mark all notifications as seen for a citizen
 */
function markAllNotificationsAsSeen($citizenID, $conn) {
    $query = "UPDATE Notifications SET IsSeen = TRUE WHERE CitizenID = ? AND IsSeen = FALSE";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $citizenID);
    
    return $stmt->execute();
}

/**
 * Create a custom notification for a citizen
 */
function createNotification($citizenID, $message, $conn, $notificationDate = null) {
    if ($notificationDate === null) {
        $notificationDate = date('Y-m-d');
    }
    
    $query = "INSERT INTO Notifications (CitizenID, Message, NotificationDate) VALUES (?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iss", $citizenID, $message, $notificationDate);
    
    return $stmt->execute();
}

/**
 * Get all notifications for admin/medical officer (all citizens)
 */
function getAllNotifications($conn, $limit = 50, $unseenOnly = false) {
    $whereClause = $unseenOnly ? "WHERE n.IsSeen = FALSE" : "";
    
    $query = "
        SELECT n.NotificationID, n.Message, n.NotificationDate, n.IsSeen, n.CreatedAt,
               c.FirstName, c.LastName, c.Citizen_eID, c.CitizenID
        FROM Notifications n
        JOIN Citizens c ON n.CitizenID = c.CitizenID
        $whereClause
        ORDER BY n.CreatedAt DESC
        LIMIT ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    
    return $stmt->get_result();
}

/**
 * Generate notification for upcoming vaccination (called manually or via cron)
 */
function generateUpcomingVaccinationNotifications($conn) {
    try {
        // Call the stored procedure
        $conn->query("CALL GenerateDailyVaccinationNotifications()");
        return true;
    } catch (Exception $e) {
        error_log("Error generating vaccination notifications: " . $e->getMessage());
        return false;
    }
}

/**
 * Get notification statistics for dashboard
 */
function getNotificationStats($conn) {
    $query = "
        SELECT 
            COUNT(*) as total_notifications,
            SUM(CASE WHEN IsSeen = FALSE THEN 1 ELSE 0 END) as unread_notifications,
            SUM(CASE WHEN NotificationDate = CURDATE() THEN 1 ELSE 0 END) as todays_notifications,
            COUNT(DISTINCT CitizenID) as citizens_with_notifications
        FROM Notifications
        WHERE NotificationDate >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ";
    
    return $conn->query($query)->fetch_assoc();
}

/**
 * Get citizens with the most unread notifications (for admin dashboard)
 */
function getCitizensWithMostNotifications($conn, $limit = 10) {
    $query = "
        SELECT c.CitizenID, c.FirstName, c.LastName, c.Citizen_eID,
               COUNT(n.NotificationID) as unread_count
        FROM Citizens c
        JOIN Notifications n ON c.CitizenID = n.CitizenID
        WHERE n.IsSeen = FALSE
        GROUP BY c.CitizenID
        ORDER BY unread_count DESC
        LIMIT ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    
    return $stmt->get_result();
}
?>
