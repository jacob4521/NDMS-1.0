<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

require_once 'config.php';

// Get current admin details
$username = $_SESSION['username'];
$role = $_SESSION['role'];

// Pagination settings
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_condition = '';
$search_params = [];

if (!empty($search)) {
    $search_condition = "WHERE Email LIKE ?";
    $search_params[] = "%$search%";
}

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM subscribers $search_condition";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($search_params);
$total_records = $count_stmt->fetch()['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get subscribers
$sql = "SELECT SubscriberID, Email, SubscribedAt, IsActive 
        FROM subscribers 
        $search_condition 
        ORDER BY SubscribedAt DESC 
        LIMIT $records_per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($search_params);
$subscribers = $stmt->fetchAll();

// Get statistics
$stats_sql = "SELECT 
    COUNT(*) as total_subscribers,
    COUNT(CASE WHEN IsActive = 1 THEN 1 END) as active_subscribers,
    COUNT(CASE WHEN DATE(SubscribedAt) = CURDATE() THEN 1 END) as today_subscribers,
    COUNT(CASE WHEN WEEK(SubscribedAt) = WEEK(NOW()) AND YEAR(SubscribedAt) = YEAR(NOW()) THEN 1 END) as week_subscribers
    FROM subscribers";
$stats_stmt = $pdo->query($stats_sql);
$stats = $stats_stmt->fetch();

// Handle toggle active status
if (isset($_POST['toggle_status']) && isset($_POST['subscriber_id'])) {
    $subscriber_id = (int)$_POST['subscriber_id'];
    $new_status = (int)$_POST['new_status'];
    
    $update_sql = "UPDATE subscribers SET IsActive = ? WHERE SubscriberID = ?";
    $update_stmt = $pdo->prepare($update_sql);
    $update_stmt->execute([$new_status, $subscriber_id]);
    
    header("Location: admin_subscribers.php?page=$page" . ($search ? "&search=" . urlencode($search) : ""));
    exit();
}

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="newsletter_subscribers_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Email', 'Subscribed Date', 'Status']);
    
    $export_sql = "SELECT SubscriberID, Email, SubscribedAt, IsActive FROM subscribers $search_condition ORDER BY SubscribedAt DESC";
    $export_stmt = $pdo->prepare($export_sql);
    $export_stmt->execute($search_params);
    
    while ($row = $export_stmt->fetch()) {
        fputcsv($output, [
            $row['SubscriberID'],
            $row['Email'],
            $row['SubscribedAt'],
            $row['IsActive'] ? 'Active' : 'Inactive'
        ]);
    }
    
    fclose($output);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Newsletter Subscribers - NDMS Admin</title>
    <style>
        :root {
            --primary-color: #1e3a8a;      /* Deep Blue - Government/Authority */
            --secondary-color: #3b82f6;    /* Bright Blue - Modern Tech */
            --accent-color: #10b981;       /* Emerald - Success/Progress */
            --warning-color: #f59e0b;      /* Amber - Warnings */
            --danger-color: #ef4444;       /* Red - Errors/Danger */
            --success-color: #10b981;      /* Green - Success */
            --text-primary: #1f2937;      /* Dark Gray Text */
            --text-secondary: #6b7280;    /* Medium Gray Text */
            --bg-primary: #ffffff;        /* White Background */
            --gradient-bg: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8fafc;
            color: var(--text-primary);
            display: flex;
            min-height: 100vh;
        }
        
        /* Mobile Menu Button */
        .mobile-menu-btn {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1001;
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-size: 18px;
            cursor: pointer;
            display: none;
            box-shadow: var(--card-shadow);
        }
        
        /* Mobile Overlay */
        .mobile-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .mobile-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        /* Sidebar */
        .sidebar {
            width: 260px;
            background: var(--bg-primary);
            border-right: 1px solid #e5e7eb;
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            transition: all 0.3s ease;
            z-index: 1000;
            box-shadow: 2px 0 4px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar.collapsed {
            width: 70px;
        }
        
        .sidebar-header {
            padding: 24px 20px;
            border-bottom: 1px solid #e5e7eb;
            background: var(--gradient-bg);
            color: white;
            position: relative;
        }
        
        .sidebar-logo {
            font-size: 28px;
            margin-bottom: 8px;
            text-align: center;
        }
        
        .sidebar-title {
            font-size: 18px;
            font-weight: 600;
            text-align: center;
            opacity: 1;
            transition: opacity 0.3s ease;
        }
        
        .sidebar.collapsed .sidebar-title {
            opacity: 0;
        }
        
        .sidebar-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 24px;
            height: 24px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .sidebar-nav {
            padding: 20px 0;
        }
        
        .nav-section {
            margin-bottom: 32px;
        }
        
        .nav-section-title {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 0 20px 12px;
            opacity: 1;
            transition: opacity 0.3s ease;
        }
        
        .sidebar.collapsed .nav-section-title {
            opacity: 0;
        }
        
        .nav-item {
            position: relative;
            margin-bottom: 4px;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: var(--text-primary);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .nav-link:hover {
            background: #f1f5f9;
            border-left-color: var(--secondary-color);
        }
        
        .nav-link.active {
            background: #eff6ff;
            border-left-color: var(--primary-color);
            color: var(--primary-color);
            font-weight: 500;
        }
        
        .nav-icon {
            font-size: 18px;
            margin-right: 12px;
            width: 24px;
            text-align: center;
            flex-shrink: 0;
        }
        
        .nav-text {
            opacity: 1;
            transition: opacity 0.3s ease;
            white-space: nowrap;
        }
        
        .sidebar.collapsed .nav-text {
            opacity: 0;
        }
        
        .nav-tooltip {
            position: absolute;
            left: 70px;
            top: 50%;
            transform: translateY(-50%);
            background: var(--text-primary);
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 12px;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1001;
        }
        
        .nav-tooltip::before {
            content: '';
            position: absolute;
            left: -4px;
            top: 50%;
            transform: translateY(-50%);
            border: 4px solid transparent;
            border-right-color: var(--text-primary);
        }
        
        .sidebar.collapsed .nav-item:hover .nav-tooltip {
            opacity: 1;
            visibility: visible;
        }
        
        .sidebar-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 20px;
            border-top: 1px solid #e5e7eb;
            background: var(--bg-primary);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            opacity: 1;
            transition: opacity 0.3s ease;
        }
        
        .sidebar.collapsed .user-info {
            opacity: 0;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 12px;
            flex-shrink: 0;
        }
        
        .user-name {
            font-weight: 500;
            color: var(--primary-color);
            margin-bottom: 2px;
        }
        
        .user-role {
            font-size: 12px;
            color: var(--text-secondary);
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 260px;
            transition: margin-left 0.3s ease;
            padding: 30px;
            min-height: 100vh;
        }
        
        .sidebar.collapsed + .main-content {
            margin-left: 70px;
        }
        
        .content-header {
            background: white;
            padding: 25px 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: var(--card-shadow);
            border-left: 4px solid var(--accent-color);
        }
        
        .content-header h1 {
            font-size: 28px;
            color: var(--text-primary);
            margin-bottom: 8px;
        }
        
        .content-header p {
            color: var(--text-secondary);
            font-size: 16px;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            border-left: 4px solid var(--primary-color);
        }
        
        .stat-card.accent { border-left-color: var(--accent-color); }
        .stat-card.warning { border-left-color: var(--warning-color); }
        .stat-card.success { border-left-color: var(--success-color); }
        
        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }
        
        .stat-title {
            font-size: 14px;
            color: var(--text-secondary);
            font-weight: 500;
        }
        
        .stat-icon {
            font-size: 24px;
            padding: 12px;
            border-radius: 8px;
            background: #f1f5f9;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: var(--text-primary);
        }
        
        /* Controls */
        .controls {
            background: white;
            padding: 20px 30px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: var(--card-shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .search-box {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .search-box input {
            padding: 10px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            width: 300px;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: #1e40af;
            transform: translateY(-1px);
        }
        
        .btn-success {
            background: var(--success-color);
            color: white;
        }
        
        .btn-success:hover {
            background: #059669;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        /* Table */
        .table-container {
            background: white;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th {
            background: #f8fafc;
            padding: 16px 20px;
            text-align: left;
            font-weight: 600;
            color: var(--text-primary);
            border-bottom: 2px solid #e5e7eb;
        }
        
        .table td {
            padding: 16px 20px;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .table tr:hover {
            background: #f8fafc;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .status-active {
            background: #dcfce7;
            color: #166534;
        }
        
        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 30px;
        }
        
        .page-btn {
            padding: 8px 12px;
            border: 2px solid #e5e7eb;
            background: white;
            color: var(--text-primary);
            text-decoration: none;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .page-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }
        
        .page-btn.active {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }
        
        .page-info {
            color: var(--text-secondary);
            font-size: 14px;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .mobile-menu-btn {
                display: block;
            }
            
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.mobile-open {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding: 80px 20px 20px;
            }
            
            .sidebar.collapsed + .main-content {
                margin-left: 0;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .controls {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-box {
                justify-content: center;
            }
            
            .search-box input {
                width: 100%;
            }
            
            .content-header {
                padding: 20px;
            }
            
            .content-header h1 {
                font-size: 24px;
            }
            
            .table-container {
                overflow-x: auto;
            }
        }
        
        /* Loading Animation */
        .stat-card, .table-container, .controls {
            animation: slideUp 0.5s ease-out;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <!-- Mobile Menu Button -->
    <button class="mobile-menu-btn" onclick="toggleMobileSidebar()">☰</button>
    
    <!-- Mobile Overlay -->
    <div class="mobile-overlay" onclick="toggleMobileSidebar()"></div>
    
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="sidebar-logo">🇱🇰</div>
            <div class="sidebar-title">NDMS Portal</div>
            <div class="sidebar-toggle" onclick="toggleSidebar()">◀</div>
        </div>
        
        <nav class="sidebar-nav">
            <!-- Main Navigation -->
            <div class="nav-section">
                <div class="nav-section-title">Main</div>
                <div class="nav-item">
                    <a href="dashboard.php" class="nav-link">
                        <span class="nav-icon">🏠</span>
                        <span class="nav-text">Dashboard</span>
                    </a>
                    <div class="nav-tooltip">Dashboard</div>
                </div>
            </div>
            
            <!-- Admin Navigation -->
            <div class="nav-section">
                <div class="nav-section-title">Administration</div>
                <div class="nav-item">
                    <a href="register.php" class="nav-link">
                        <span class="nav-icon">👤</span>
                        <span class="nav-text">Register Citizen</span>
                    </a>
                    <div class="nav-tooltip">Register New Citizen</div>
                </div>
                <div class="nav-item">
                    <a href="search_citizens.php" class="nav-link">
                        <span class="nav-icon">🔍</span>
                        <span class="nav-text">Citizen Directory</span>
                    </a>
                    <div class="nav-tooltip">Citizen Directory</div>
                </div>
                <div class="nav-item">
                    <a href="manage_users.php" class="nav-link">
                        <span class="nav-icon">👥</span>
                        <span class="nav-text">Manage Users</span>
                    </a>
                    <div class="nav-tooltip">Manage Users</div>
                </div>
                <div class="nav-item">
                    <a href="manage_citizen_accounts.php" class="nav-link">
                        <span class="nav-icon">🏠</span>
                        <span class="nav-text">Citizen Accounts</span>
                    </a>
                    <div class="nav-tooltip">Manage Citizen Accounts</div>
                </div>
                <div class="nav-item">
                    <a href="admin_notifications.php" class="nav-link">
                        <span class="nav-icon">🔔</span>
                        <span class="nav-text">Notifications</span>
                    </a>
                    <div class="nav-tooltip">Admin Notifications</div>
                </div>
                <div class="nav-item">
                    <a href="manage_subjects.php" class="nav-link">
                        <span class="nav-icon">📖</span>
                        <span class="nav-text">Manage Subjects</span>
                    </a>
                    <div class="nav-tooltip">Manage Academic Subjects</div>
                </div>
                <div class="nav-item">
                    <a href="admin_subscribers.php" class="nav-link active">
                        <span class="nav-icon">📧</span>
                        <span class="nav-text">Newsletter Subscribers</span>
                    </a>
                    <div class="nav-tooltip">Manage Newsletter Subscribers</div>
                </div>
            </div>
            
            <!-- Common Services -->
            <div class="nav-section">
                <div class="nav-section-title">Services</div>
                <div class="nav-item">
                    <a href="citizen_profile.php" class="nav-link">
                        <span class="nav-icon">👤</span>
                        <span class="nav-text">Profile</span>
                    </a>
                    <div class="nav-tooltip">My Profile</div>
                </div>
                <div class="nav-item">
                    <a href="login.php?logout=1" class="nav-link">
                        <span class="nav-icon">🚪</span>
                        <span class="nav-text">Logout</span>
                    </a>
                    <div class="nav-tooltip">Logout</div>
                </div>
            </div>
        </nav>
        
        <div class="sidebar-footer">
            <div class="user-info">
                <div class="user-avatar">
                    <?= strtoupper(substr($username, 0, 1)) ?>
                </div>
                <div class="user-details">
                    <div class="user-name"><?= htmlspecialchars($username) ?></div>
                    <div class="user-role"><?= $role ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Content Header -->
        <div class="content-header">
            <h1>📧 Newsletter Subscribers</h1>
            <p>Manage and monitor newsletter subscription activity</p>
        </div>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-title">Total Subscribers</div>
                    <div class="stat-icon">👥</div>
                </div>
                <div class="stat-value"><?= number_format($stats['total_subscribers']) ?></div>
            </div>
            
            <div class="stat-card accent">
                <div class="stat-header">
                    <div class="stat-title">Active Subscribers</div>
                    <div class="stat-icon">✅</div>
                </div>
                <div class="stat-value"><?= number_format($stats['active_subscribers']) ?></div>
            </div>
            
            <div class="stat-card warning">
                <div class="stat-header">
                    <div class="stat-title">Today's Signups</div>
                    <div class="stat-icon">📈</div>
                </div>
                <div class="stat-value"><?= number_format($stats['today_subscribers']) ?></div>
            </div>
            
            <div class="stat-card success">
                <div class="stat-header">
                    <div class="stat-title">This Week</div>
                    <div class="stat-icon">📊</div>
                </div>
                <div class="stat-value"><?= number_format($stats['week_subscribers']) ?></div>
            </div>
        </div>
        
        <!-- Controls -->
        <div class="controls">
            <div class="search-box">
                <form method="GET" style="display: flex; gap: 10px;">
                    <input type="text" name="search" placeholder="Search by email..." value="<?= htmlspecialchars($search) ?>">
                    <button type="submit" class="btn btn-primary">🔍 Search</button>
                    <?php if ($search): ?>
                        <a href="admin_subscribers.php" class="btn btn-primary">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <div>
                <a href="?export=csv<?= $search ? '&search=' . urlencode($search) : '' ?>" class="btn btn-success">
                    📥 Export CSV
                </a>
            </div>
        </div>
        
        <!-- Subscribers Table -->
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Email Address</th>
                        <th>Subscribed Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($subscribers)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px; color: var(--text-secondary);">
                                <div style="font-size: 48px; margin-bottom: 16px;">📭</div>
                                <div style="font-size: 18px; font-weight: 500;">No subscribers found</div>
                                <div style="font-size: 14px; margin-top: 8px;">
                                    <?= $search ? 'Try adjusting your search criteria.' : 'Newsletter subscriptions will appear here.' ?>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($subscribers as $subscriber): ?>
                            <tr>
                                <td><?= $subscriber['SubscriberID'] ?></td>
                                <td><?= htmlspecialchars($subscriber['Email']) ?></td>
                                <td><?= date('M j, Y g:i A', strtotime($subscriber['SubscribedAt'])) ?></td>
                                <td>
                                    <span class="status-badge <?= $subscriber['IsActive'] ? 'status-active' : 'status-inactive' ?>">
                                        <?= $subscriber['IsActive'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="subscriber_id" value="<?= $subscriber['SubscriberID'] ?>">
                                        <input type="hidden" name="new_status" value="<?= $subscriber['IsActive'] ? 0 : 1 ?>">
                                        <button type="submit" name="toggle_status" class="btn btn-sm <?= $subscriber['IsActive'] ? 'btn-primary' : 'btn-success' ?>">
                                            <?= $subscriber['IsActive'] ? '🚫 Deactivate' : '✅ Activate' ?>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="page-btn">Previous</a>
                <?php endif; ?>
                
                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                for ($i = $start_page; $i <= $end_page; $i++):
                ?>
                    <a href="?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?>" 
                       class="page-btn <?= $i == $page ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="page-btn">Next</a>
                <?php endif; ?>
                
                <div class="page-info">
                    Showing <?= ($offset + 1) ?> to <?= min($offset + $records_per_page, $total_records) ?> of <?= $total_records ?> entries
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Sidebar functionality
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const toggle = document.querySelector('.sidebar-toggle');
            
            sidebar.classList.toggle('collapsed');
            
            if (sidebar.classList.contains('collapsed')) {
                toggle.innerHTML = '▶';
                localStorage.setItem('sidebarCollapsed', 'true');
            } else {
                toggle.innerHTML = '◀';
                localStorage.setItem('sidebarCollapsed', 'false');
            }
        }
        
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.querySelector('.mobile-overlay');
            
            sidebar.classList.toggle('mobile-open');
            overlay.classList.toggle('active');
        }
        
        // Set active navigation link
        function setActiveNavLink() {
            const currentPage = window.location.pathname.split('/').pop();
            const navLinks = document.querySelectorAll('.nav-link');
            
            navLinks.forEach(link => {
                link.classList.remove('active');
                const href = link.getAttribute('href');
                if (href && href.split('/').pop() === currentPage) {
                    link.classList.add('active');
                }
            });
        }
        
        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            // Restore sidebar state from localStorage
            const sidebarCollapsed = localStorage.getItem('sidebarCollapsed');
            if (sidebarCollapsed === 'true') {
                document.getElementById('sidebar').classList.add('collapsed');
                document.querySelector('.sidebar-toggle').innerHTML = '▶';
            }
            
            // Set active navigation link
            setActiveNavLink();
            
            // Add smooth animations to stat cards
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
            });
        });
        
        // Close mobile sidebar when clicking on a link
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    toggleMobileSidebar();
                }
            });
        });
        
        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.querySelector('.mobile-overlay');
                sidebar.classList.remove('mobile-open');
                overlay.classList.remove('active');
            }
        });
    </script>
</body>
</html>
