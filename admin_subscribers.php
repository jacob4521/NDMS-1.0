<?php
/**
 * NDMS Admin Subscribers Management
 * Enhanced with Advanced Pagination Features
 * 
 * Features:
 * - 10 subscribers per page (configurable)
 * - Smart pagination with first/last page jumps
 * - Previous/Next buttons with disabled states
 * - Page dots (...) for large page sets
 * - Search functionality with pagination preservation
 * - Mobile-responsive pagination design
 * - Export to CSV functionality
 * - Subscriber status toggle (Active/Inactive)
 * - Real-time statistics dashboard
 */

include "config.php";

// Check if user is logged in and is admin
if (!isset($_SESSION['UserID']) || $_SESSION['Role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// Get current admin details
$role = $_SESSION['Role'];
$username = '';

// Get username for display
$userResult = $conn->query("SELECT Username FROM Users WHERE UserID = " . $_SESSION['UserID']);
if ($userResult && $user = $userResult->fetch_assoc()) {
    $username = $user['Username'];
}

// Pagination settings
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $records_per_page;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_condition = '';
$search_param = '';

if (!empty($search)) {
    $search_condition = "WHERE Email LIKE ?";
    $search_param = "%$search%";
}

// Get total count
if (!empty($search)) {
    $count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM subscribers WHERE Email LIKE ?");
    $count_stmt->bind_param("s", $search_param);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $total_records = $count_result->fetch_assoc()['total'];
} else {
    $count_result = $conn->query("SELECT COUNT(*) as total FROM subscribers");
    $total_records = $count_result->fetch_assoc()['total'];
}
$total_pages = ceil($total_records / $records_per_page);

// Get subscribers
if (!empty($search)) {
    $stmt = $conn->prepare("SELECT SubscriberID, Email, SubscribedAt, IsActive FROM subscribers WHERE Email LIKE ? ORDER BY SubscribedAt DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("sii", $search_param, $records_per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $subscribers = [];
    while ($row = $result->fetch_assoc()) {
        $subscribers[] = $row;
    }
} else {
    $stmt = $conn->prepare("SELECT SubscriberID, Email, SubscribedAt, IsActive FROM subscribers ORDER BY SubscribedAt DESC LIMIT ? OFFSET ?");
    $stmt->bind_param("ii", $records_per_page, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    $subscribers = [];
    while ($row = $result->fetch_assoc()) {
        $subscribers[] = $row;
    }
}

// Get statistics
$stats_result = $conn->query("SELECT 
    COUNT(*) as total_subscribers,
    COUNT(CASE WHEN IsActive = 1 THEN 1 END) as active_subscribers,
    COUNT(CASE WHEN DATE(SubscribedAt) = CURDATE() THEN 1 END) as today_subscribers,
    COUNT(CASE WHEN WEEK(SubscribedAt) = WEEK(NOW()) AND YEAR(SubscribedAt) = YEAR(NOW()) THEN 1 END) as week_subscribers
    FROM subscribers");
$stats = $stats_result->fetch_assoc();

// Handle toggle active status
if (isset($_POST['toggle_status']) && isset($_POST['subscriber_id'])) {
    $subscriber_id = (int)$_POST['subscriber_id'];
    $new_status = (int)$_POST['new_status'];
    
    $update_stmt = $conn->prepare("UPDATE subscribers SET IsActive = ? WHERE SubscriberID = ?");
    $update_stmt->bind_param("ii", $new_status, $subscriber_id);
    $update_stmt->execute();
    
    header("Location: admin_subscribers.php?page=$page" . ($search ? "&search=" . urlencode($search) : ""));
    exit();
}

// Handle CSV export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="newsletter_subscribers_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Email', 'Subscribed Date', 'Status']);
    
    if (!empty($search)) {
        $export_stmt = $conn->prepare("SELECT SubscriberID, Email, SubscribedAt, IsActive FROM subscribers WHERE Email LIKE ? ORDER BY SubscribedAt DESC");
        $export_stmt->bind_param("s", $search_param);
        $export_stmt->execute();
        $export_result = $export_stmt->get_result();
    } else {
        $export_result = $conn->query("SELECT SubscriberID, Email, SubscribedAt, IsActive FROM subscribers ORDER BY SubscribedAt DESC");
    }
    
    while ($row = $export_result->fetch_assoc()) {
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
    <link rel="stylesheet" href="includes/sidebar.css">
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
            --card-bg: #ffffff;           /* Card Background */
            --light-bg: #f8fafc;          /* Light Gray Background */
            --border-color: #e5e7eb;      /* Light Border */
            --gradient-bg: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
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
            margin: 0;
            display: flex;
            min-height: 100vh;
        }
        
        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            transition: margin-left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            padding: 20px;
            min-height: 100vh;
        }
        
        .sidebar.collapsed + .main-content {
            margin-left: 80px;
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
        
        /* Enhanced Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .page-btn {
            padding: 10px 16px;
            border: 2px solid #e5e7eb;
            background: white;
            color: var(--text-primary);
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s ease;
            min-width: 44px;
            text-align: center;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .page-btn:hover:not(.disabled):not(.active) {
            border-color: var(--primary-color);
            color: var(--primary-color);
            background: #f8fafc;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .page-btn.active {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
            box-shadow: 0 4px 12px rgba(30, 58, 138, 0.3);
        }
        
        .page-btn.disabled {
            background: #f3f4f6;
            border-color: #e5e7eb;
            color: #9ca3af;
            cursor: not-allowed;
        }
        
        .page-btn.prev-next {
            padding: 10px 20px;
            font-weight: 600;
        }
        
        .page-dots {
            color: var(--text-secondary);
            font-weight: bold;
            padding: 10px 8px;
            font-size: 16px;
        }
        
        .page-info {
            color: var(--text-secondary);
            font-size: 14px;
            margin-left: 20px;
            padding: 8px 0;
            border-left: 2px solid #e5e7eb;
            padding-left: 20px;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
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
            
            /* Mobile Pagination */
            .pagination {
                flex-direction: column;
                gap: 15px;
            }
            
            .pagination > div:first-child {
                display: flex;
                flex-wrap: wrap;
                justify-content: center;
                gap: 8px;
            }
            
            .page-btn {
                padding: 8px 12px;
                font-size: 13px;
                min-width: 38px;
            }
            
            .page-btn.prev-next {
                padding: 8px 16px;
            }
            
            .page-info {
                margin-left: 0;
                border-left: none;
                padding-left: 0;
                text-align: center;
                border-top: 2px solid #e5e7eb;
                padding-top: 15px;
                font-size: 13px;
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
    <?php include 'includes/sidebar.php'; ?>
    
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
                                <td><?= htmlspecialchars($subscriber['SubscriberID']) ?></td>
                                <td><?= htmlspecialchars($subscriber['Email']) ?></td>
                                <td><?= date('M j, Y g:i A', strtotime($subscriber['SubscribedAt'])) ?></td>
                                <td>
                                    <span class="status-badge <?= $subscriber['IsActive'] ? 'status-active' : 'status-inactive' ?>">
                                        <?= $subscriber['IsActive'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="subscriber_id" value="<?= htmlspecialchars($subscriber['SubscriberID']) ?>">
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
        
        <!-- Enhanced Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <!-- First Page -->
                <?php if ($page > 3): ?>
                    <a href="?page=1<?= $search ? '&search=' . urlencode($search) : '' ?>" class="page-btn">1</a>
                    <?php if ($page > 4): ?>
                        <span class="page-dots">...</span>
                    <?php endif; ?>
                <?php endif; ?>
                
                <!-- Previous Button -->
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="page-btn prev-next">
                        &laquo; Prev
                    </a>
                <?php else: ?>
                    <span class="page-btn disabled">&laquo; Prev</span>
                <?php endif; ?>
                
                <!-- Page Numbers -->
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
                
                <!-- Next Button -->
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="page-btn prev-next">
                        Next &raquo;
                    </a>
                <?php else: ?>
                    <span class="page-btn disabled">Next &raquo;</span>
                <?php endif; ?>
                
                <!-- Last Page -->
                <?php if ($page < $total_pages - 2): ?>
                    <?php if ($page < $total_pages - 3): ?>
                        <span class="page-dots">...</span>
                    <?php endif; ?>
                    <a href="?page=<?= $total_pages ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="page-btn"><?= $total_pages ?></a>
                <?php endif; ?>
                
                <!-- Page Info -->
                <div class="page-info">
                    <strong>Page <?= $page ?> of <?= $total_pages ?></strong> | 
                    Showing <?= ($offset + 1) ?> to <?= min($offset + $records_per_page, $total_records) ?> of <?= number_format($total_records) ?> entries
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="includes/sidebar.js"></script>
    
    <script>
        // Enhanced Pagination JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            // Keyboard navigation for pagination
            document.addEventListener('keydown', function(e) {
                // Only handle if no input is focused
                if (document.activeElement.tagName !== 'INPUT') {
                    const currentPage = <?= $page ?>;
                    const totalPages = <?= $total_pages ?>;
                    const searchParam = <?= json_encode($search) ?>;
                    
                    if (e.key === 'ArrowLeft' && currentPage > 1) {
                        // Previous page
                        e.preventDefault();
                        window.location.href = `?page=${currentPage - 1}${searchParam ? '&search=' + encodeURIComponent(searchParam) : ''}`;
                    } else if (e.key === 'ArrowRight' && currentPage < totalPages) {
                        // Next page
                        e.preventDefault();
                        window.location.href = `?page=${currentPage + 1}${searchParam ? '&search=' + encodeURIComponent(searchParam) : ''}`;
                    } else if (e.key === 'Home' && currentPage > 1) {
                        // First page
                        e.preventDefault();
                        window.location.href = `?page=1${searchParam ? '&search=' + encodeURIComponent(searchParam) : ''}`;
                    } else if (e.key === 'End' && currentPage < totalPages) {
                        // Last page
                        e.preventDefault();
                        window.location.href = `?page=${totalPages}${searchParam ? '&search=' + encodeURIComponent(searchParam) : ''}`;
                    }
                }
            });
            
            // Add tooltips to pagination buttons
            const pageButtons = document.querySelectorAll('.page-btn');
            pageButtons.forEach(btn => {
                if (btn.classList.contains('prev-next')) {
                    btn.title = btn.textContent.trim() === '« Prev' ? 
                        'Previous page (←)' : 
                        'Next page (→)';
                } else if (!btn.classList.contains('disabled') && !btn.classList.contains('active')) {
                    btn.title = `Go to page ${btn.textContent.trim()}`;
                }
            });
            
            // Show keyboard shortcuts info (optional)
            console.log('📄 Pagination Keyboard Shortcuts:');
            console.log('← → : Previous/Next page');
            console.log('Home/End : First/Last page');
        });
        
        // Auto-submit search form on Enter
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput) {
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    this.form.submit();
                }
            });
        }
        
        // Confirmation for status changes
        const statusForms = document.querySelectorAll('form[method="POST"]');
        statusForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const button = this.querySelector('button[name="toggle_status"]');
                if (button) {
                    const action = button.textContent.includes('Deactivate') ? 'deactivate' : 'activate';
                    const email = this.closest('tr').cells[1].textContent.trim();
                    
                    if (!confirm(`Are you sure you want to ${action} the subscriber "${email}"?`)) {
                        e.preventDefault();
                    }
                }
            });
        });
    </script>
</body>
</html>
