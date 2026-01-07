<?php
include "config.php";
include_once "vaccination_reminders_helper.php";

// Check if user is logged in and is a citizen
if (!isset($_SESSION['UserID']) || $_SESSION['Role'] !== 'Citizen') {
    header("Location: login.php");
    exit;
}

$userID = $_SESSION['UserID'];

// Get citizen information using username (which is the eID)
$citizenQuery = $conn->prepare("
    SELECT c.*, u.Username as LoginUsername
    FROM Citizens c 
    JOIN Users u ON c.Citizen_eID = u.Username 
    WHERE u.UserID = ?
");
$citizenQuery->bind_param("i", $userID);
$citizenQuery->execute();
$citizenResult = $citizenQuery->get_result();

if ($citizenResult->num_rows === 0) {
    die("Citizen profile not found.");
}

$citizen = $citizenResult->fetch_assoc();
$citizenID = $citizen['CitizenID'];

// Handle marking vaccination as completed
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_completed'])) {
    $reminderID = $_POST['reminder_id'];
    $notes = $_POST['notes'] ?? '';
    
    if (markVaccinationCompleted($reminderID, $userID, $conn, $notes)) {
        $message = "Vaccination marked as completed successfully!";
        $messageType = "success";
    } else {
        $message = "Error marking vaccination as completed.";
        $messageType = "error";
    }
}

// Get all vaccination reminders for this citizen
$allReminders = getUpcomingVaccinations($citizenID, $conn);

// Separate reminders by status
$overdueReminders = [];
$dueTodayReminders = [];
$upcomingReminders = [];
$completedReminders = [];

while ($reminder = $allReminders->fetch_assoc()) {
    switch ($reminder['Status']) {
        case 'overdue':
            $overdueReminders[] = $reminder;
            break;
        case 'due_today':
            $dueTodayReminders[] = $reminder;
            break;
        case 'upcoming':
            $upcomingReminders[] = $reminder;
            break;
        case 'completed':
            $completedReminders[] = $reminder;
            break;
    }
}

// Calculate statistics
$totalReminders = count($overdueReminders) + count($dueTodayReminders) + count($upcomingReminders) + count($completedReminders);
$completionRate = $totalReminders > 0 ? round((count($completedReminders) / $totalReminders) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Vaccination Schedule - NDMS</title>
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
            --citizen-gradient: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }

        body { 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
            margin: 0; 
            padding: 10px 20px 20px 0; 
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 25%, #f8fafc 50%, #ecfdf5 75%, #f0fdf4 100%);
            background-attachment: fixed;
            min-height: auto;
            color: var(--text-primary);
            line-height: 1.6;
            position: relative;
            transition: padding-left 0.3s ease;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 20%, rgba(16, 185, 129, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(59, 130, 246, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 40% 70%, rgba(30, 58, 138, 0.03) 0%, transparent 50%);
            pointer-events: none;
            z-index: -1;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
            min-height: auto;
            height: auto;
        }

        .container::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 200px;
            height: 200px;
            background: linear-gradient(45deg, rgba(16, 185, 129, 0.1), rgba(59, 130, 246, 0.1));
            border-radius: 50%;
            z-index: -1;
            animation: float 6s ease-in-out infinite;
        }

        .container::after {
            content: '';
            position: absolute;
            bottom: -50px;
            left: -50px;
            width: 150px;
            height: 150px;
            background: linear-gradient(-45deg, rgba(30, 58, 138, 0.08), rgba(16, 185, 129, 0.08));
            border-radius: 50%;
            z-index: -1;
            animation: float 8s ease-in-out infinite reverse;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
        
        .header {
            background: var(--citizen-gradient);
            color: white;
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 20px;
            box-shadow: var(--shadow-xl);
            position: relative;
            backdrop-filter: blur(10px);
        }

        .header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="2"/><circle cx="50" cy="50" r="30" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></svg>') no-repeat center;
            opacity: 0.3;
        }

        .header::after {
            content: '💉';
            position: absolute;
            top: 20px;
            right: 30px;
            font-size: 60px;
            opacity: 0.2;
            animation: pulse 3s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.2; }
            50% { transform: scale(1.1); opacity: 0.3; }
        }

        .header h1 {
            margin: 0 0 10px 0;
            font-size: 28px;
            font-weight: 700;
            position: relative;
            z-index: 1;
        }

        .header p {
            margin: 0;
            opacity: 0.9;
            font-size: 16px;
            position: relative;
            z-index: 1;
        }
        
        .content {
            padding: 0;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: var(--card-bg);
            padding: 25px;
            border-radius: 20px;
            text-align: center;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(255, 255, 255, 0.7) 100%);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--accent-color);
        }

        .stat-card::after {
            content: '';
            position: absolute;
            top: 10px;
            right: 10px;
            width: 30px;
            height: 30px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.3) 0%, transparent 70%);
            border-radius: 50%;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }
        
        .stat-card.overdue::before {
            background: var(--danger-color);
        }
        
        .stat-card.due-today::before {
            background: var(--warning-color);
        }

        .stat-card.upcoming::before {
            background: var(--secondary-color);
        }

        .stat-card.completed::before {
            background: var(--accent-color);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .stat-label {
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .vaccination-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(255, 255, 255, 0.8) 100%);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(5px);
        }

        .vaccination-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--accent-color);
        }

        .vaccination-card::after {
            content: '';
            position: absolute;
            top: -50%;
            right: -10px;
            width: 100px;
            height: 200%;
            background: linear-gradient(45deg, transparent 40%, rgba(255, 255, 255, 0.1) 50%, transparent 60%);
            transform: rotate(15deg);
            transition: all 0.5s ease;
            opacity: 0;
        }

        .vaccination-card:hover::after {
            opacity: 1;
            right: 100%;
        }

        .vaccination-card.overdue::before {
            background: var(--danger-color);
        }

        .vaccination-card.due-today::before {
            background: var(--warning-color);
        }

        .vaccination-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }

        .vaccine-status {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
        }

        .status-completed {
            background: var(--citizen-gradient);
            color: white;
        }

        .status-overdue {
            background: linear-gradient(135deg, var(--danger-color) 0%, #dc2626 100%);
            color: white;
        }

        .status-due-today {
            background: linear-gradient(135deg, var(--warning-color) 0%, #d97706 100%);
            color: white;
        }

        .status-upcoming {
            background: linear-gradient(135deg, var(--secondary-color) 0%, #2563eb 100%);
            color: white;
        }

        .nav-buttons {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(255, 255, 255, 0.7) 100%);
            padding: 25px;
            border-radius: 20px;
            margin-top: 30px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            text-align: center;
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
        }

        .nav-buttons::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: var(--citizen-gradient);
        }

        .nav-buttons a {
            display: inline-block;
            margin: 10px;
            padding: 12px 24px;
            background: var(--gradient-bg);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
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
            border: 1px solid;
        }
        
        .message.success {
            background: #d1fae5;
            color: #065f46;
            border-color: #a7f3d0;
        }
        
        .message.error {
            background: #fee2e2;
            color: #991b1b;
            border-color: #fecaca;
        }
        
        .no-data {
            text-align: center;
            padding: 60px 40px;
            color: var(--text-secondary);
            background: var(--card-bg);
            border-radius: 20px;
            border: 2px dashed var(--border-color);
            box-shadow: var(--shadow-md);
        }

        .no-data h4 {
            color: var(--text-primary);
            margin-bottom: 15px;
            font-size: 20px;
        }

        .no-data p {
            font-size: 16px;
            opacity: 0.8;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }
            
            .header {
                padding: 20px;
                text-align: center;
            }
            
            .header h1 {
                font-size: 24px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .nav-buttons a {
                display: block;
                margin: 10px 0;
            }
        }

        /* Loading Animation */
        .vaccination-card, .stat-card {
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .stat-card.upcoming {
            border-left: 4px solid #17a2b8;
        }
        
        .stat-card.completed {
            border-left: 4px solid #28a745;
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
        }
        
        .stat-number.overdue { color: #dc3545; }
        .stat-number.due-today { color: #ffc107; }
        .stat-number.upcoming { color: #17a2b8; }
        .stat-number.completed { color: #28a745; }
        
        .stat-label {
            color: #6c757d;
            margin-top: 5px;
        }
        
        .section {
            margin-bottom: 30px;
        }
        
        .section h3 {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .reminder-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid #ddd;
        }
        
        .reminder-card.overdue {
            border-left-color: #dc3545;
            background: #f8d7da;
        }
        
        .reminder-card.due-today {
            border-left-color: #ffc107;
            background: #fff3cd;
        }
        
        .reminder-card.upcoming {
            border-left-color: #17a2b8;
            background: #d1ecf1;
        }
        
        .reminder-card.completed {
            border-left-color: #28a745;
            background: #d4edda;
        }
        
        .reminder-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .vaccine-name {
            font-size: 18px;
            font-weight: bold;
        }
        
        .dose-badge {
            background: #17a2b8;
            color: white;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .date-info {
            margin-bottom: 10px;
        }
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-overdue {
            background: #dc3545;
            color: white;
        }
        
        .status-due-today {
            background: #ffc107;
            color: #212529;
        }
        
        .status-upcoming {
            background: #17a2b8;
            color: white;
        }
        
        .status-completed {
            background: #28a745;
            color: white;
        }
        
        .action-form {
            margin-top: 15px;
        }
        
        .btn {
            background: #28a745;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        
        .btn:hover {
            background: #218838;
        }
        
        .btn-small {
            padding: 4px 8px;
            font-size: 12px;
        }
        
        .nav-buttons {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
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
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }

        /* Sidebar Integration Styles */
        body.has-citizen-sidebar {
            padding-left: 290px;
            transition: padding-left 0.3s ease;
        }
        
        body.citizen-sidebar-collapsed {
            padding-left: 70px;
        }

        body.has-citizen-sidebar .container {
            margin: 0;
            max-width: none;
            min-height: auto;
            height: auto;
        }

        /* Mobile adjustments */
        @media (max-width: 768px) {
            body.has-citizen-sidebar {
                padding-left: 0 !important;
                padding-top: 80px; /* Space for mobile menu button */
            }
            
            body.has-citizen-sidebar .container {
                margin: 0;
                height: auto;
                min-height: auto;
            }
        }
    </style>

</head>
<body>
    <?php include 'includes/citizen_sidebar.php'; ?>
    
    <div class="container">
        <div class="header">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h1>💉 My Vaccination Schedule</h1>
                    <p>Hello, <?= htmlspecialchars($citizen['FirstName'] . ' ' . $citizen['LastName']) ?>!</p>
                    <p>Stay up to date with your vaccination schedule</p>
                </div>
                <div>
                    <?php include 'notification_component.php'; ?>
                </div>
            </div>
        </div>
        
        <div class="content">
            <?php if (isset($message)): ?>
                <div class="message <?= $messageType ?>">
                    <?= $message ?>
                </div>
            <?php endif; ?>
            
            <div class="stats-grid">
                <div class="stat-card overdue">
                    <div class="stat-number overdue"><?= count($overdueReminders) ?></div>
                    <div class="stat-label">Overdue Vaccinations</div>
                </div>
                <div class="stat-card due-today">
                    <div class="stat-number due-today"><?= count($dueTodayReminders) ?></div>
                    <div class="stat-label">Due Today</div>
                </div>
                <div class="stat-card upcoming">
                    <div class="stat-number upcoming"><?= count($upcomingReminders) ?></div>
                    <div class="stat-label">Upcoming</div>
                </div>
                <div class="stat-card completed">
                    <div class="stat-number completed"><?= $completionRate ?>%</div>
                    <div class="stat-label">Completion Rate</div>
                </div>
            </div>
            
            <?php if (count($overdueReminders) > 0): ?>
                <div class="section">
                    <h3>🚨 Overdue Vaccinations</h3>
                    <?php foreach ($overdueReminders as $reminder): ?>
                        <div class="reminder-card overdue">
                            <div class="reminder-header">
                                <div class="vaccine-name"><?= htmlspecialchars($reminder['VaccineName']) ?></div>
                                <span class="dose-badge">Dose <?= $reminder['DoseNumber'] ?></span>
                            </div>
                            <div class="date-info">
                                <strong>Scheduled:</strong> <?= date('F j, Y', strtotime($reminder['ScheduledDate'])) ?>
                                <span class="status-badge status-overdue">
                                    <?= abs($reminder['DaysUntilDue']) ?> days overdue
                                </span>
                            </div>
                            <div class="action-form">
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="reminder_id" value="<?= $reminder['ReminderID'] ?>">
                                    <input type="text" name="notes" placeholder="Notes (optional)" style="margin-right: 10px;">
                                    <button type="submit" name="mark_completed" class="btn" 
                                            onclick="return confirm('Mark this vaccination as completed?')">
                                        ✅ Mark Completed
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (count($dueTodayReminders) > 0): ?>
                <div class="section">
                    <h3>⏰ Due Today</h3>
                    <?php foreach ($dueTodayReminders as $reminder): ?>
                        <div class="reminder-card due-today">
                            <div class="reminder-header">
                                <div class="vaccine-name"><?= htmlspecialchars($reminder['VaccineName']) ?></div>
                                <span class="dose-badge">Dose <?= $reminder['DoseNumber'] ?></span>
                            </div>
                            <div class="date-info">
                                <strong>Scheduled:</strong> <?= date('F j, Y', strtotime($reminder['ScheduledDate'])) ?>
                                <span class="status-badge status-due-today">Due Today</span>
                            </div>
                            <div class="action-form">
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="reminder_id" value="<?= $reminder['ReminderID'] ?>">
                                    <input type="text" name="notes" placeholder="Notes (optional)" style="margin-right: 10px;">
                                    <button type="submit" name="mark_completed" class="btn" 
                                            onclick="return confirm('Mark this vaccination as completed?')">
                                        ✅ Mark Completed
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php if (count($upcomingReminders) > 0): ?>
                <div class="section">
                    <h3>📅 Upcoming Vaccinations</h3>
                    <?php foreach (array_slice($upcomingReminders, 0, 5) as $reminder): ?>
                        <div class="reminder-card upcoming">
                            <div class="reminder-header">
                                <div class="vaccine-name"><?= htmlspecialchars($reminder['VaccineName']) ?></div>
                                <span class="dose-badge">Dose <?= $reminder['DoseNumber'] ?></span>
                            </div>
                            <div class="date-info">
                                <strong>Scheduled:</strong> <?= date('F j, Y', strtotime($reminder['ScheduledDate'])) ?>
                                <span class="status-badge status-upcoming">
                                    In <?= $reminder['DaysUntilDue'] ?> days
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (count($upcomingReminders) > 5): ?>
                        <p><em>... and <?= count($upcomingReminders) - 5 ?> more upcoming vaccinations</em></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if (count($completedReminders) > 0): ?>
                <div class="section">
                    <h3>✅ Completed Vaccinations</h3>
                    <?php foreach (array_slice($completedReminders, 0, 5) as $reminder): ?>
                        <div class="reminder-card completed">
                            <div class="reminder-header">
                                <div class="vaccine-name"><?= htmlspecialchars($reminder['VaccineName']) ?></div>
                                <span class="dose-badge">Dose <?= $reminder['DoseNumber'] ?></span>
                            </div>
                            <div class="date-info">
                                <strong>Completed:</strong> <?= date('F j, Y', strtotime($reminder['CompletedDate'])) ?>
                                <span class="status-badge status-completed">Completed</span>
                            </div>
                            <?php if ($reminder['Notes']): ?>
                                <p><small><strong>Notes:</strong> <?= htmlspecialchars($reminder['Notes']) ?></small></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <?php if (count($completedReminders) > 5): ?>
                        <p><em>... and <?= count($completedReminders) - 5 ?> more completed vaccinations</em></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($totalReminders == 0): ?>
                <div class="no-data">
                    <h4>No Vaccination Schedule Found</h4>
                    <p>No vaccination reminders have been generated for your profile yet.</p>
                    <p>This may happen if you were registered before the vaccination schedule system was implemented.</p>
                </div>
            <?php endif; ?>
            
            <div class="nav-buttons">
                <a href="citizen_dashboard.php">🏠 My Dashboard</a>
                <a href="view_citizen.php?citizen_id=<?= $citizenID ?>">📋 View All Records</a>
                <a href="career_guidance_form.php">🎯 Career Guidance</a>
                <a href="change_password.php">🔐 Change Password</a>
                <a href="login.php?logout=1">🚪 Logout</a>
            </div>
        </div>
    </div>
</body>
</html>
