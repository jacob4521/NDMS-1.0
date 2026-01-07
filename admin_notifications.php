<?php
include "config.php";
include_once "notifications_helper.php";

// Check if user is logged in and is admin
if (!isset($_SESSION['UserID']) || $_SESSION['Role'] !== 'Admin') {
    header("Location: login.php");
    exit;
}

// Handle manual notification generation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['generate_notifications'])) {
    $success = generateUpcomingVaccinationNotifications($conn);
    $message = $success ? "Vaccination notifications generated successfully!" : "Error generating notifications.";
    $messageType = $success ? "success" : "error";
}

// Get notification statistics
$stats = getNotificationStats($conn);
$allNotifications = getAllNotifications($conn, 50);
$criticalCitizens = getCitizensWithMostNotifications($conn, 10);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification Management - NDMS</title>
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
        
        .nav-bar {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .nav-links {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .nav-bar a {
            color: white;
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            background: var(--gradient-bg);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .nav-bar a:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            filter: brightness(1.1);
        }
        
        .header {
            background: var(--gradient-bg);
            color: white;
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-xl);
            flex-wrap: wrap;
            gap: 20px;
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
            margin: 0 0 8px 0;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
            z-index: 1;
        }
        
        .header p {
            font-size: 18px;
            opacity: 0.9;
            margin: 0;
            position: relative;
            z-index: 1;
        }
        
        .stats-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-box {
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
        
        .stat-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-bg);
        }
        
        .stat-box:hover {
            transform: translateY(-5px);
        }
        
        .stat-box h3 {
            font-size: 42px;
            font-weight: 800;
            margin: 0 0 8px 0;
            background: var(--gradient-bg);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .stat-box p {
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
        }
        
        .content-section {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            position: relative;
            margin-bottom: 30px;
        }
        
        .content-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-bg);
            border-radius: 20px 20px 0 0;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .section-header h2 {
            margin: 0;
            color: var(--primary-color);
            font-size: 24px;
            font-weight: 700;
        }
        
        .btn {
            background: var(--gradient-bg);
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: var(--shadow-sm);
            cursor: pointer;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            filter: brightness(1.1);
            color: white;
        }
        
        .btn-success {
            background: var(--accent-color);
        }
        
        .btn-success:hover {
            background: #059669;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: var(--card-bg);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
        }
        
        .table th,
        .table td {
            padding: 15px 12px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        .table th {
            background: var(--primary-color);
            color: white;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .table tr:hover {
            background: var(--light-bg);
        }
        
        .notification-item {
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            transition: background 0.3s ease;
        }
        
        .notification-item:last-child {
            border-bottom: none;
        }
        
        .notification-item:hover {
            background: var(--light-bg);
        }
        
        .notification-content {
            flex: 1;
        }
        
        .notification-citizen {
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 8px;
            font-size: 16px;
        }
        
        .notification-message {
            color: var(--text-primary);
            margin-bottom: 8px;
            line-height: 1.5;
        }
        
        .notification-date {
            font-size: 12px;
            color: var(--text-secondary);
            font-weight: 500;
        }
        
        .notification-status {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-unseen {
            background: #fef3c7;
            color: #92400e;
        }
        
        .status-seen {
            background: #dcfce7;
            color: #166534;
        }
        
        .message {
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            border: 1px solid transparent;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
        }
        
        .message.success {
            background: #dcfce7;
            color: #166534;
            border-color: #bbf7d0;
        }
        
        .message.error {
            background: #fef2f2;
            color: #991b1b;
            border-color: #fecaca;
        }
        
        .role-badge {
            background: var(--secondary-color);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-muted {
            color: var(--text-secondary);
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }
            
            .header {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }
            
            .header h1 {
                font-size: 28px;
            }
            
            .nav-bar {
                flex-direction: column;
                gap: 15px;
            }
            
            .nav-links {
                width: 100%;
                justify-content: center;
            }
            
            .stats-section {
                grid-template-columns: 1fr;
            }
            
            .section-header {
                flex-direction: column;
                text-align: center;
            }
            
            .notification-item {
                flex-direction: column;
                gap: 15px;
            }
            
            .table {
                font-size: 14px;
            }
            
            .table th,
            .table td {
                padding: 10px 8px;
            }
        }
        
        /* Loading Animation */
        .stat-box, .content-section, .nav-bar {
            animation: slideUp 0.3s ease-out;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
<?php include 'includes/sidebar.php'; ?>
<div class="main-content">
    <div class="container">
        <div class="nav-bar">
            <div class="nav-links">
                <a href="dashboard.php">🏠 Dashboard</a>
                <a href="manage_vaccination_schedule.php">💉 Vaccination Schedule</a>
                <a href="search_citizens.php">👥 Citizens</a>
                <a href="citizen_activities.php">🏆 Activities</a>
            </div>
            <div style="display: flex; align-items: center; gap: 15px;">
                <?php include 'notification_component.php'; ?>
                <span class="role-badge">Admin</span>
            </div>
        </div>

        <div class="header">
            <div>
                <h1>🔔 Notification Management</h1>
                <p>National Digital Management System - Monitor and manage vaccination reminders and notifications</p>
            </div>
        </div>

        <?php if (isset($message)): ?>
            <div class="message <?= $messageType ?>">
                <?= $messageType === 'success' ? '✅' : '❌' ?> <?= $message ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-section">
            <div class="stat-box">
                <h3 style="color: var(--danger-color);"><?= $stats['unread_notifications'] ?></h3>
                <p>Unread Notifications</p>
            </div>
            <div class="stat-box">
                <h3 style="color: var(--primary-color);"><?= $stats['total_notifications'] ?></h3>
                <p>Total Notifications (Last 7 Days)</p>
            </div>
            <div class="stat-box">
                <h3 style="color: var(--accent-color);"><?= $stats['todays_notifications'] ?></h3>
                <p>Today's Notifications</p>
            </div>
            <div class="stat-box">
                <h3 style="color: var(--warning-color);"><?= $stats['citizens_with_notifications'] ?></h3>
                <p>Citizens with Notifications</p>
            </div>
        </div>

        <!-- Manual Generation -->
        <div class="content-section">
            <div class="section-header">
                <h2>🔄 Generate Notifications</h2>
                <p class="text-muted">Manually trigger notification generation</p>
            </div>
            <p style="margin-bottom: 20px;">Manually trigger the generation of vaccination reminder notifications for all citizens.</p>
            <form method="POST">
                <button type="submit" name="generate_notifications" class="btn btn-success">
                    🔄 Generate Daily Notifications
                </button>
            </form>
            <p style="margin-top: 15px;"><small><em>Note: This process normally runs automatically via cron job. Use this only for testing or manual updates.</em></small></p>
        </div>

        <!-- Citizens with Most Notifications -->
        <div class="content-section">
            <div class="section-header">
                <h2>⚠️ Citizens Requiring Attention</h2>
                <p class="text-muted">Citizens with the most unread notifications (may need follow-up)</p>
            </div>
            
            <?php if ($criticalCitizens && $criticalCitizens->num_rows > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Citizen</th>
                            <th>eID</th>
                            <th>Unread Notifications</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($citizen = $criticalCitizens->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($citizen['FirstName'] . ' ' . $citizen['LastName']) ?></strong></td>
                                <td><?= htmlspecialchars($citizen['Citizen_eID']) ?></td>
                                <td>
                                    <span style="font-size: 18px; color: var(--danger-color); font-weight: 700;">
                                        <?= $citizen['unread_count'] ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="view_citizen_role_based.php?citizen_id=<?= $citizen['CitizenID'] ?>" 
                                       class="btn" style="padding: 8px 16px; font-size: 12px;">
                                        👁️ View Profile
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="text-center" style="padding: 40px 20px;">
                    <h3 style="color: var(--accent-color); margin-bottom: 10px;">🎉 Great Job!</h3>
                    <p class="text-muted">No citizens with unread notifications at this time.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Notifications -->
        <div class="content-section">
            <div class="section-header">
                <h2>📋 Recent Notifications</h2>
                <p class="text-muted">All notifications from the last 7 days</p>
            </div>
            
            <div style="max-height: 600px; overflow-y: auto; border-radius: 10px;">
                <?php if ($allNotifications && $allNotifications->num_rows > 0): ?>
                    <?php while ($notification = $allNotifications->fetch_assoc()): ?>
                        <div class="notification-item">
                            <div class="notification-content">
                                <div class="notification-citizen">
                                    👤 <?= htmlspecialchars($notification['FirstName'] . ' ' . $notification['LastName']) ?>
                                    (<?= htmlspecialchars($notification['Citizen_eID']) ?>)
                                </div>
                                <div class="notification-message">📧 <?= htmlspecialchars($notification['Message']) ?></div>
                                <div class="notification-date">📅 <?= date('M j, Y g:i A', strtotime($notification['CreatedAt'])) ?></div>
                            </div>
                            <div>
                                <span class="notification-status <?= $notification['IsSeen'] ? 'status-seen' : 'status-unseen' ?>">
                                    <?= $notification['IsSeen'] ? '✅ Seen' : '👁️ Unseen' ?>
                                </span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center" style="padding: 60px 20px;">
                        <h3 style="color: var(--text-secondary); margin-bottom: 15px;">📭 No notifications found</h3>
                        <p class="text-muted">No notifications have been generated in the last 7 days.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script src="includes/sidebar.js"></script>
<script>
    // Enhanced interactions and animations
    document.addEventListener('DOMContentLoaded', function() {
        // Add hover effects to notification items
        const notificationItems = document.querySelectorAll('.notification-item');
        notificationItems.forEach(item => {
            item.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.01)';
                this.style.transition = 'transform 0.2s ease';
            });
            
            item.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1)';
            });
        });

        // Add hover effects to table rows
        const tableRows = document.querySelectorAll('.table tbody tr');
        tableRows.forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.01)';
                this.style.transition = 'transform 0.2s ease';
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1)';
            });
        });

        // Add smooth transitions for buttons
        document.querySelectorAll('.btn').forEach(button => {
            button.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.transition = 'all 0.3s ease';
            });
            
            button.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });

        // Confirmation for notification generation
        document.querySelector('button[name="generate_notifications"]')?.addEventListener('click', function(e) {
            if (!confirm('🔄 Generate vaccination notifications for all citizens?\n\nThis will create reminder notifications based on vaccination schedules.')) {
                e.preventDefault();
            }
        });
    });
</script>
</body>
</html>
