<?php
// vaccination_reminders_helper.php
// Helper functions for vaccination reminder system

/**
 * Generate vaccination reminders for a newborn based on existing schedules
 * Call this function after registering a new citizen
 */
function generateVaccinationReminders($citizenID, $dateOfBirth, $conn) {
    try {
        // Fetch all vaccination schedules
        $schedulesQuery = "SELECT VaccineID, DoseNumber, RecommendedAgeMonths FROM VaccinationSchedule ORDER BY RecommendedAgeMonths";
        $schedules = $conn->query($schedulesQuery);
        
        $reminderCount = 0;
        
        while ($schedule = $schedules->fetch_assoc()) {
            // Calculate scheduled date based on DOB + recommended age in months
            $scheduledDate = date('Y-m-d', strtotime("+{$schedule['RecommendedAgeMonths']} months", strtotime($dateOfBirth)));
            
            // Insert reminder
            $stmt = $conn->prepare("INSERT INTO VaccinationReminders (CitizenID, VaccineID, DoseNumber, ScheduledDate) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiis", $citizenID, $schedule['VaccineID'], $schedule['DoseNumber'], $scheduledDate);
            
            if ($stmt->execute()) {
                $reminderCount++;
            }
        }
        
        return $reminderCount;
        
    } catch (Exception $e) {
        error_log("Error generating vaccination reminders: " . $e->getMessage());
        return false;
    }
}

/**
 * Get upcoming vaccination reminders for a citizen
 */
function getUpcomingVaccinations($citizenID, $conn, $limit = null) {
    $limitClause = $limit ? "LIMIT $limit" : "";
    
        $query = "
            SELECT vr.ReminderID, v.VaccineName, vr.DoseNumber, vr.ScheduledDate, vr.IsCompleted, vr.CompletedDate, vr.Notes,
                   DATEDIFF(vr.ScheduledDate, CURDATE()) as DaysUntilDue,
               CASE 
                   WHEN vr.ScheduledDate < CURDATE() AND vr.IsCompleted = 0 THEN 'overdue'
                   WHEN vr.ScheduledDate = CURDATE() AND vr.IsCompleted = 0 THEN 'due_today'
                   WHEN vr.ScheduledDate > CURDATE() AND vr.IsCompleted = 0 THEN 'upcoming'
                   ELSE 'completed'
               END as Status
        FROM VaccinationReminders vr
        JOIN Vaccines v ON vr.VaccineID = v.VaccineID
        WHERE vr.CitizenID = ?
        ORDER BY vr.ScheduledDate ASC
        $limitClause
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $citizenID);
    $stmt->execute();
    
    return $stmt->get_result();
}

/**
 * Get vaccination reminder statistics for admin dashboard
 */
function getVaccinationStats($conn) {
    $query = "
        SELECT 
            COUNT(*) as total_reminders,
            SUM(CASE WHEN IsCompleted = 1 THEN 1 ELSE 0 END) as completed_reminders,
            SUM(CASE WHEN ScheduledDate < CURDATE() AND IsCompleted = 0 THEN 1 ELSE 0 END) as overdue_reminders,
            SUM(CASE WHEN ScheduledDate = CURDATE() AND IsCompleted = 0 THEN 1 ELSE 0 END) as due_today,
            SUM(CASE WHEN ScheduledDate BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND IsCompleted = 0 THEN 1 ELSE 0 END) as due_next_30_days
        FROM VaccinationReminders
    ";
    
    return $conn->query($query)->fetch_assoc();
}

/**
 * Mark vaccination as completed
 */
function markVaccinationCompleted($reminderID, $completedBy, $conn, $notes = null) {
    $stmt = $conn->prepare("UPDATE VaccinationReminders SET IsCompleted = 1, CompletedDate = CURDATE(), CompletedBy = ?, Notes = ? WHERE ReminderID = ?");
    $stmt->bind_param("isi", $completedBy, $notes, $reminderID);
    
    return $stmt->execute();
}

/**
 * Get citizens who have overdue vaccinations
 */
function getOverdueVaccinations($conn, $limit = 50) {
    $query = "
        SELECT c.CitizenID, c.FirstName, c.LastName, c.Citizen_eID,
               vr.ReminderID, v.VaccineName, vr.DoseNumber, vr.ScheduledDate,
               DATEDIFF(CURDATE(), vr.ScheduledDate) as DaysOverdue
        FROM VaccinationReminders vr
        JOIN Citizens c ON vr.CitizenID = c.CitizenID
        JOIN Vaccines v ON vr.VaccineID = v.VaccineID
        WHERE vr.ScheduledDate < CURDATE() AND vr.IsCompleted = 0
        ORDER BY vr.ScheduledDate ASC
        LIMIT ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    
    return $stmt->get_result();
}

/**
 * Get due vaccinations for today
 */
function getTodaysDueVaccinations($conn) {
    $query = "
        SELECT c.CitizenID, c.FirstName, c.LastName, c.Citizen_eID,
               vr.ReminderID, v.VaccineName, vr.DoseNumber, vr.ScheduledDate
        FROM VaccinationReminders vr
        JOIN Citizens c ON vr.CitizenID = c.CitizenID
        JOIN Vaccines v ON vr.VaccineID = v.VaccineID
        WHERE vr.ScheduledDate = CURDATE() AND vr.IsCompleted = 0
        ORDER BY c.FirstName, c.LastName
    ";
    
    return $conn->query($query);
}
?>
