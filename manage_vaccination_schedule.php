<?php
include "config.php";

// Check if user is logged in and has permission
if (!isset($_SESSION['UserID']) || !in_array($_SESSION['Role'], ['Admin', 'MedicalOfficer'])) {
    header("Location: login.php");
    exit;
}

$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_schedule'])) {
        $vaccineID = $_POST['vaccine_id'];
        $doseNumber = $_POST['dose_number'];
        $ageMonths = $_POST['recommended_age'];
        $notes = $_POST['notes'];
        $createdBy = $_SESSION['UserID'];
        
        $stmt = $conn->prepare("INSERT INTO VaccinationSchedule (VaccineID, DoseNumber, RecommendedAgeMonths, Notes, CreatedBy) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiisi", $vaccineID, $doseNumber, $ageMonths, $notes, $createdBy);
        
        if ($stmt->execute()) {
            $message = "Vaccination schedule added successfully!";
            $messageType = "success";
        } else {
            $message = "Error adding vaccination schedule.";
            $messageType = "error";
        }
    }
    
    if (isset($_POST['delete_schedule'])) {
        $scheduleID = $_POST['schedule_id'];
        
        // Check if this schedule has any reminders
        $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM VaccinationReminders WHERE VaccineID = (SELECT VaccineID FROM VaccinationSchedule WHERE ScheduleID = ?) AND DoseNumber = (SELECT DoseNumber FROM VaccinationSchedule WHERE ScheduleID = ?)");
        $checkStmt->bind_param("ii", $scheduleID, $scheduleID);
        $checkStmt->execute();
        $reminderCount = $checkStmt->get_result()->fetch_assoc()['count'];
        
        if ($reminderCount > 0) {
            $message = "Cannot delete schedule: There are existing reminders for this vaccine dose.";
            $messageType = "error";
        } else {
            $stmt = $conn->prepare("DELETE FROM VaccinationSchedule WHERE ScheduleID = ?");
            $stmt->bind_param("i", $scheduleID);
            
            if ($stmt->execute()) {
                $message = "Vaccination schedule deleted successfully!";
                $messageType = "success";
            } else {
                $message = "Error deleting vaccination schedule.";
                $messageType = "error";
            }
        }
    }
}

// Fetch all schedules
$schedulesQuery = "
    SELECT s.ScheduleID, v.VaccineName, s.DoseNumber, s.RecommendedAgeMonths, s.Notes, s.CreatedAt,
           u.Username as CreatedByName
    FROM VaccinationSchedule s
    JOIN Vaccines v ON s.VaccineID = v.VaccineID
    LEFT JOIN Users u ON s.CreatedBy = u.UserID
    ORDER BY v.VaccineName, s.DoseNumber
";
$schedules = $conn->query($schedulesQuery);

// Fetch all vaccines for dropdown
$vaccines = $conn->query("SELECT VaccineID, VaccineName FROM Vaccines ORDER BY VaccineName");

// Get statistics
$statsQuery = "SELECT 
    COUNT(*) as total_schedules,
    COUNT(DISTINCT VaccineID) as unique_vaccines,
    MAX(RecommendedAgeMonths) as max_age_months
    FROM VaccinationSchedule";
$stats = $conn->query($statsQuery)->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Vaccination Schedule - NDMS</title>
    <link rel="stylesheet" href="includes/sidebar.css">
    <style>
        /* NDMS Modern Theme - National Digital Management System */
        :root {
            --primary-color: #1e3a8a;      /* Deep Blue - Government/Authority */
            --secondary-color: #3b82f6;    /* Bright Blue - Modern Tech */
            --accent-color: #10b981;       /* Emerald - Success/Progress */
            --warning-color: #f59e0b;      /* Amber - Attention */
            --danger-color: #ef4444;       /* Red - Critical */
            --light-bg: #f8fafc;          /* Light Gray Background */
            --card-bg: #ffffff;           /* Pure White Cards */
            --text-primary: #1f2937;      /* Dark Gray Text */
            --text-secondary: #6b7280;    /* Medium Gray Text */
            --border-color: #e5e7eb;      /* Light Border */
            --gradient-bg: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
            background: var(--light-bg);
            color: var(--text-primary);
            line-height: 1.6;
            padding: 20px;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header {
            background: var(--gradient-bg);
            color: white;
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-xl);
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 200px;
            height: 200px;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="2"/><circle cx="50" cy="50" r="30" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/><circle cx="50" cy="50" r="20" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></svg>') no-repeat center;
            opacity: 0.3;
        }
        
        .header h1 {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 12px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
            z-index: 1;
        }
        
        .header p {
            font-size: 18px;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: var(--card-bg);
            padding: 30px;
            border-radius: 20px;
            text-align: center;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-bg);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-number {
            font-size: 42px;
            font-weight: 800;
            color: var(--primary-color);
            margin-bottom: 8px;
            background: var(--gradient-bg);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .stat-label {
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .form-section {
            background: var(--card-bg);
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            position: relative;
        }
        
        .form-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-bg);
            border-radius: 20px 20px 0 0;
        }
        
        .form-section h3 {
            color: var(--primary-color);
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 700;
            color: var(--text-primary);
            font-size: 14px;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: inherit;
            background: white;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-group small {
            color: var(--text-secondary);
            font-size: 12px;
            margin-top: 5px;
            display: block;
        }
        
        .btn {
            background: var(--gradient-bg);
            color: white;
            padding: 14px 28px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: var(--shadow-sm);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            filter: brightness(1.1);
        }
        
        .btn-danger {
            background: var(--danger-color);
            padding: 8px 16px;
            font-size: 12px;
        }
        
        .btn-danger:hover {
            background: #dc2626;
        }
        
        .table-container {
            overflow-x: auto;
            margin-top: 20px;
            background: var(--card-bg);
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
        }
        
        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            background: var(--card-bg);
            border-radius: 20px;
            overflow: hidden;
        }
        
        .schedule-table th,
        .schedule-table td {
            padding: 15px 12px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        .schedule-table th {
            background: var(--primary-color);
            color: white;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .schedule-table tr:hover {
            background: var(--light-bg);
        }
        
        .dose-badge {
            background: var(--secondary-color);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .age-badge {
            background: var(--warning-color);
            color: white;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .nav-buttons {
            text-align: center;
            margin-top: 30px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            justify-content: center;
        }
        
        .nav-buttons a {
            background: var(--gradient-bg);
            color: white;
            padding: 12px 20px;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .nav-buttons a:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            filter: brightness(1.1);
        }
        
        .message {
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 25px;
            font-weight: 600;
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
        }
        
        .message.success {
            background: var(--accent-color);
            color: white;
        }
        
        .message.success::before {
            content: '✅';
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 24px;
            opacity: 0.7;
        }
        
        .message.error {
            background: var(--danger-color);
            color: white;
        }
        
        .message.error::before {
            content: '❌';
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 24px;
            opacity: 0.7;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
        }
        
        .empty-state h4 {
            color: var(--primary-color);
            margin-bottom: 15px;
            font-size: 24px;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }
            
            .header h1 {
                font-size: 28px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .nav-buttons {
                flex-direction: column;
            }
            
            .schedule-table {
                font-size: 14px;
            }
            
            .schedule-table th,
            .schedule-table td {
                padding: 10px 8px;
            }
        }
        
        /* Loading Animation */
        .form-section, .stat-card, .table-container {
            animation: slideUp 0.3s ease-out;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

                .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #28a745;
        }
        
        .btn {
            background: #28a745;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #218838;
        }
        
        .btn-danger {
            background: #dc3545;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .table-container {
            overflow-x: auto;
            margin-top: 20px;
        }
        
        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .schedule-table th,
        .schedule-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        
        .schedule-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }
        
        .schedule-table tr:hover {
            background: #f8f9fa;
        }
        
        .dose-badge {
            background: #17a2b8;
            color: white;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .age-badge {
            background: #ffc107;
            color: #212529;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .nav-buttons {
            text-align: center;
            margin-top: 20px;
        }
        
        .nav-buttons a {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin: 0 5px;
            display: inline-block;
        }
        
        .nav-buttons a:hover {
            background: #5a6268;
        }
        
        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
        
<body>
    <!-- Include Sidebar -->
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
        <div class="header">
            <h1>💉 Vaccination Schedule Management</h1>
            <p>National Digital Management System - Comprehensive Immunization Planning</p>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?= $messageType ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>
            
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['total_schedules'] ?></div>
                    <div class="stat-label">Total Schedules</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['unique_vaccines'] ?></div>
                    <div class="stat-label">Vaccines Scheduled</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['max_age_months'] ?></div>
                    <div class="stat-label">Max Age (Months)</div>
                </div>
            </div>
            
            <div class="form-section">
                <h3>➕ Add New Vaccination Schedule</h3>
                <form method="post">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="vaccine_id">Vaccine:</label>
                            <select name="vaccine_id" id="vaccine_id" required>
                                <option value="">Select Vaccine</option>
                                <?php while ($vaccine = $vaccines->fetch_assoc()): ?>
                                    <option value="<?= $vaccine['VaccineID'] ?>"><?= htmlspecialchars($vaccine['VaccineName']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="dose_number">Dose Number:</label>
                            <input type="number" name="dose_number" id="dose_number" min="1" max="10" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="recommended_age">Recommended Age (Months):</label>
                            <input type="number" name="recommended_age" id="recommended_age" min="0" max="240" required>
                            <small>0 = At birth, 2 = 2 months old, etc.</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="notes">Notes:</label>
                            <input type="text" name="notes" id="notes" placeholder="e.g., At birth, Booster dose">
                        </div>
                    </div>
                    
                    <button type="submit" name="add_schedule" class="btn">💉 Add Schedule</button>
                </form>
            </div>
            
            <h3>📅 Current Vaccination Schedules</h3>
            <div class="table-container">
                <table class="schedule-table">
                    <thead>
                        <tr>
                            <th>Vaccine</th>
                            <th>Dose</th>
                            <th>Age (Months)</th>
                            <th>Age Description</th>
                            <th>Notes</th>
                            <th>Created By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($schedules->num_rows > 0): ?>
                            <?php while ($schedule = $schedules->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($schedule['VaccineName']) ?></strong></td>
                                    <td><span class="dose-badge">Dose <?= $schedule['DoseNumber'] ?></span></td>
                                    <td><span class="age-badge"><?= $schedule['RecommendedAgeMonths'] ?> months</span></td>
                                    <td>
                                        <?php 
                                        $months = $schedule['RecommendedAgeMonths'];
                                        if ($months == 0) echo "At birth";
                                        elseif ($months < 12) echo "$months months old";
                                        elseif ($months == 12) echo "1 year old";
                                        else echo floor($months/12) . " years " . ($months%12) . " months old";
                                        ?>
                                    </td>
                                    <td><?= htmlspecialchars($schedule['Notes'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($schedule['CreatedByName'] ?? 'Unknown') ?></td>
                                    <td>
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="schedule_id" value="<?= $schedule['ScheduleID'] ?>">
                                            <button type="submit" name="delete_schedule" class="btn btn-danger" 
                                                    onclick="return confirm('Delete this vaccination schedule?')">
                                                🗑️ Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="empty-state">
                                    <h4>💉 No Vaccination Schedules Found</h4>
                                    <p>Add your first vaccination schedule using the form above.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="nav-buttons">
                <a href="dashboard.php">🏠 Back to Dashboard</a>
                <a href="manage_vaccines.php">💉 Manage Vaccines</a>
                <a href="vaccination_reminders_overview.php">🔔 View Reminders</a>
                <a href="login.php?logout=1">🚪 Logout</a>
            </div>
        </div>
    </div>

    <script>
        // Enhanced form validation and interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Add smooth transitions for form elements
            document.querySelectorAll('input, select, textarea').forEach(element => {
                element.addEventListener('focus', function() {
                    this.style.transform = 'scale(1.02)';
                    this.style.boxShadow = '0 0 0 3px rgba(59, 130, 246, 0.1)';
                });
                
                element.addEventListener('blur', function() {
                    this.style.transform = 'scale(1)';
                    this.style.boxShadow = 'none';
                });
            });

            // Auto-hide success messages
            setTimeout(function() {
                const successMessages = document.querySelectorAll('.message.success');
                successMessages.forEach(msg => {
                    msg.style.transition = 'opacity 0.5s, transform 0.5s';
                    msg.style.opacity = '0';
                    msg.style.transform = 'translateY(-10px)';
                    setTimeout(() => msg.remove(), 500);
                });
            }, 5000);

            // Form validation
            document.querySelector('form').addEventListener('submit', function(e) {
                const requiredFields = this.querySelectorAll('[required]');
                let isValid = true;
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        field.style.borderColor = '#ef4444';
                        field.style.backgroundColor = '#fef2f2';
                        field.style.animation = 'shake 0.5s ease-in-out';
                        isValid = false;
                    } else {
                        field.style.borderColor = '#10b981';
                        field.style.backgroundColor = '#f0fdf4';
                        setTimeout(() => {
                            field.style.borderColor = '#e5e7eb';
                            field.style.backgroundColor = 'white';
                        }, 1000);
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    alert('🚨 Please fill in all required fields.');
                }
            });

            // Age helper function
            const ageInput = document.getElementById('recommended_age');
            if (ageInput) {
                ageInput.addEventListener('input', function() {
                    const months = parseInt(this.value);
                    let ageText = '';
                    if (months === 0) ageText = 'At birth';
                    else if (months < 12) ageText = `${months} months old`;
                    else if (months === 12) ageText = '1 year old';
                    else ageText = `${Math.floor(months/12)} years ${months%12} months old`;
                    
                    // Show age preview
                    let preview = this.nextElementSibling;
                    if (!preview || !preview.classList.contains('age-preview')) {
                        preview = document.createElement('small');
                        preview.className = 'age-preview';
                        preview.style.color = '#10b981';
                        preview.style.fontWeight = '600';
                        this.parentNode.appendChild(preview);
                    }
                    preview.textContent = `Preview: ${ageText}`;
                });
            }
        });

        // Add shake animation for validation errors
        const style = document.createElement('style');
        style.textContent = `
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-5px); }
                75% { transform: translateX(5px); }
            }
        `;
        document.head.appendChild(style);
    </script>
        </div> <!-- End container -->
    </div> <!-- End main-content -->
    
    <!-- Include Sidebar JavaScript -->
    <script src="includes/sidebar.js"></script>
</body>
</html>
