<?php
include "config.php";
session_start();

// Force admin session for testing
$_SESSION['UserID'] = 1;
$_SESSION['Role'] = 'Admin';
$_SESSION['Username'] = 'TestAdmin';

$role = $_SESSION['Role'];
$username = 'TestAdmin';

// Get unread contact messages count for badge
$unreadContactsQuery = $conn->query("SELECT COUNT(*) as count FROM contact_messages WHERE status = 'new'");
$unreadContactsCount = $unreadContactsQuery ? $unreadContactsQuery->fetch_assoc()['count'] : 0;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Dashboard Navigation</title>
    <style>
        .nav-section {
            background: #f8f9fa;
            border: 2px solid #dee2e6;
            margin: 10px 0;
            padding: 15px;
            border-radius: 8px;
        }
        .nav-section-title {
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #495057;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid #dee2e6;
        }
        .nav-item {
            background: white;
            margin: 5px 0;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #e9ecef;
        }
        .nav-link {
            text-decoration: none;
            color: #495057;
            display: flex;
            align-items: center;
        }
        .nav-icon {
            margin-right: 10px;
            font-size: 16px;
        }
        .nav-text {
            font-weight: 500;
        }
        .nav-badge {
            background: #dc3545;
            color: white;
            font-size: 12px;
            padding: 2px 6px;
            border-radius: 10px;
            margin-left: auto;
        }
    </style>
</head>
<body>
    <h1>Debug: Dashboard Navigation Groups</h1>
    <p><strong>Role:</strong> <?= $role ?></p>
    <p><strong>Unread Contacts:</strong> <?= $unreadContactsCount ?></p>
    
    <?php if ($role == "Admin"): ?>
        
        <!-- User Management Section -->
        <div class="nav-section">
            <div class="nav-section-title">👥 User Management</div>
            <div class="nav-item">
                <a href="register.php" class="nav-link">
                    <span class="nav-icon">👤</span>
                    <span class="nav-text">Register Citizen</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="search_citizens.php" class="nav-link">
                    <span class="nav-icon">🔍</span>
                    <span class="nav-text">Citizen Directory</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="manage_users.php" class="nav-link">
                    <span class="nav-icon">👥</span>
                    <span class="nav-text">Manage Users</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="manage_citizen_accounts.php" class="nav-link">
                    <span class="nav-icon">🏠</span>
                    <span class="nav-text">Citizen Accounts</span>
                </a>
            </div>
        </div>
        
        <!-- Academic & Career Section -->
        <div class="nav-section">
            <div class="nav-section-title">📚 Academic & Career</div>
            <div class="nav-item">
                <a href="manage_subjects.php" class="nav-link">
                    <span class="nav-icon">📖</span>
                    <span class="nav-text">Manage Subjects</span>
                </a>
            </div>
        </div>
        
        <!-- Communication Section -->
        <div class="nav-section">
            <div class="nav-section-title">💬 Communication</div>
            <div class="nav-item">
                <a href="admin_notifications.php" class="nav-link">
                    <span class="nav-icon">🔔</span>
                    <span class="nav-text">Notifications</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="admin_subscribers.php" class="nav-link">
                    <span class="nav-icon">📧</span>
                    <span class="nav-text">Newsletter Subscribers</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="admin_contacts.php" class="nav-link">
                    <span class="nav-icon">📞</span>
                    <span class="nav-text">Contact Messages</span>
                    <?php if ($unreadContactsCount > 0): ?>
                        <span class="nav-badge"><?php echo $unreadContactsCount; ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
        
    <?php else: ?>
        <p>This is only visible to Admin users.</p>
    <?php endif; ?>
    
    <hr>
    <p><a href="dashboard.php">Go to Real Dashboard</a></p>
    
</body>
</html>
