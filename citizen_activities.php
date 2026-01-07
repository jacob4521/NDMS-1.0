<?php
require_once 'config.php';
require_once 'citizen_activities_helper.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['UserID']) || $_SESSION['Role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['UserID'];

// Handle form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'verify':
                $activityId = $_POST['ActivityID'];
                if (verifyActivity($activityId, $userId)) {
                    $message = "Activity verified successfully!";
                    $messageType = 'success';
                } else {
                    $message = "Error verifying activity. Please try again.";
                    $messageType = 'danger';
                }
                break;
                
            case 'edit':
                $activityId = $_POST['ActivityID'];
                $category = $_POST['ActivityCategory'];
                $name = trim($_POST['ActivityName']);
                $level = trim($_POST['AchievementLevel']);
                $details = trim($_POST['Details']);
                $proofPath = null;
                
                // Handle file upload
                if (isset($_FILES['Proof']) && $_FILES['Proof']['error'] === UPLOAD_ERR_OK) {
                    $uploadResult = uploadActivityProof($_FILES['Proof']);
                    if ($uploadResult['success']) {
                        $proofPath = $uploadResult['path'];
                    } else {
                        $message = $uploadResult['error'];
                        $messageType = 'danger';
                        break;
                    }
                }
                
                if (updateActivity($activityId, $category, $name, $level, $details, $proofPath)) {
                    $message = "Activity updated successfully!";
                    $messageType = 'success';
                } else {
                    $message = "Error updating activity. Please try again.";
                    $messageType = 'danger';
                }
                break;
                
            case 'delete':
                $activityId = $_POST['ActivityID'];
                if (deleteActivity($activityId)) {
                    $message = "Activity deleted successfully!";
                    $messageType = 'success';
                } else {
                    $message = "Error deleting activity. Please try again.";
                    $messageType = 'danger';
                }
                break;
        }
    }
}

// Get filter parameters
$categoryFilter = $_GET['category'] ?? '';
$verifiedFilter = $_GET['verified'] ?? '';
$searchTerm = $_GET['search'] ?? '';

// Get activities with filters
$activities = getAllActivities($categoryFilter ?: null, $verifiedFilter !== '' ? (bool)$verifiedFilter : null);

// Filter by search term if provided
if ($searchTerm) {
    $activities = array_filter($activities, function($activity) use ($searchTerm) {
        return stripos($activity['ActivityName'], $searchTerm) !== false ||
               stripos($activity['FirstName'] . ' ' . $activity['LastName'], $searchTerm) !== false ||
               stripos($activity['Details'], $searchTerm) !== false;
    });
}

// Get overall statistics
$allStats = getActivityStats();
$totalActivities = array_sum(array_column($allStats, 'count'));
$totalVerified = array_sum(array_column($allStats, 'verified_count'));
$pendingVerification = $totalActivities - $totalVerified;

// Get citizens for dropdown
$citizens = getAllCitizensForDropdown();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Citizen Activities - NDMS Admin</title>
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
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 25%, #f8fafc 50%, #ecfdf5 75%, #f0fdf4 100%);
            background-attachment: fixed;
            min-height: 100vh;
            color: var(--text-primary);
            line-height: 1.6;
            padding: 20px;
            position: relative;
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
        
        .nav-bar {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(255, 255, 255, 0.7) 100%);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            backdrop-filter: blur(10px);
            position: relative;
        }

        .nav-bar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: var(--gradient-bg);
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
            backdrop-filter: blur(10px);
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

        .header::after {
            content: '🏆';
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
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(255, 255, 255, 0.8) 100%);
            padding: 30px;
            border-radius: 20px;
            text-align: center;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease;
            backdrop-filter: blur(5px);
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

        .stat-box::after {
            content: '';
            position: absolute;
            top: 10px;
            right: 10px;
            width: 30px;
            height: 30px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.3) 0%, transparent 70%);
            border-radius: 50%;
        }
        
        .stat-box:hover {
            transform: translateY(-5px);
        }
        
        .stat-box h3 {
            font-size: 42px;
            font-weight: 800;
            color: var(--primary-color);
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
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(255, 255, 255, 0.8) 100%);
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            position: relative;
            margin-bottom: 30px;
            backdrop-filter: blur(5px);
            overflow: hidden;
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

        .content-section::after {
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

        .content-section:hover::after {
            opacity: 1;
            right: 100%;
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
        
        .btn-primary {
            background: var(--secondary-color);
        }
        
        .btn-primary:hover {
            background: #2563eb;
        }
        
        .btn-danger {
            background: var(--danger-color);
        }
        
        .btn-danger:hover {
            background: #dc2626;
        }
        
        .btn-warning {
            background: var(--warning-color);
            color: white;
        }
        
        .btn-warning:hover {
            background: #d97706;
        }
        
        .btn-outline-primary {
            background: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-outline-secondary {
            background: transparent;
            color: var(--text-secondary);
            border: 2px solid var(--text-secondary);
        }
        
        .btn-outline-secondary:hover {
            background: var(--text-secondary);
            color: white;
        }
        
        .btn-sm {
            padding: 8px 16px;
            font-size: 12px;
            border-radius: 8px;
        }
        
        .form-control, .form-select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 14px;
            transition: border-color 0.3s ease;
            background: var(--card-bg);
        }
        
        .form-control:focus, .form-select:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-primary);
            font-weight: 600;
            font-size: 14px;
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
        
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            position: relative;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: #dcfce7;
            color: #166534;
            border-color: #bbf7d0;
        }
        
        .alert-danger {
            background: #fef2f2;
            color: #991b1b;
            border-color: #fecaca;
        }
        
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .category-sports { background: var(--accent-color); color: white; }
        .category-arts { background: var(--danger-color); color: white; }
        .category-education { background: var(--warning-color); color: white; }
        
        .verified-badge { background: var(--accent-color); color: white; }
        .pending-badge { background: var(--warning-color); color: white; }
        
        .admin-badge {
            background: var(--danger-color);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn-group-vertical {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .btn-group-vertical .btn {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-muted {
            color: var(--text-secondary);
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }
        
        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-dialog {
            background: var(--card-bg);
            border-radius: 15px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-title {
            margin: 0;
            color: var(--primary-color);
            font-size: 20px;
            font-weight: 700;
        }
        
        .btn-close {
            background: transparent;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--text-secondary);
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-footer {
            padding: 20px;
            border-top: 1px solid var(--border-color);
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        .row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .col-md-3 {
            flex: 1;
            min-width: 200px;
        }
        
        .col-md-4 {
            flex: 1;
            min-width: 250px;
        }
        
        .col-md-6 {
            flex: 1;
            min-width: 300px;
        }
        
        .mb-3 {
            margin-bottom: 20px;
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
            
            .table {
                font-size: 14px;
            }
            
            .table th,
            .table td {
                padding: 10px 8px;
            }
            
            .btn-group-vertical {
                flex-direction: row;
                flex-wrap: wrap;
            }
            
            .row {
                flex-direction: column;
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
                <a href="manage_users.php">👤 Users</a>
                <a href="citizen_activities.php">🏆 Activities</a>
                <a href="admin_notifications.php">🔔 Notifications</a>
                <a href="login.php">🚪 Logout</a>
            </div>
            <div style="display: flex; align-items: center; gap: 15px;">
                <?php 
                if (file_exists('notification_component.php')) {
                    include 'notification_component.php'; 
                }
                ?>
                <span class="admin-badge">Admin Panel</span>
            </div>
        </div>

        <div class="header">
            <div>
                <h1>🏆 Citizen Activities Management</h1>
                <p>National Digital Management System - Manage and verify citizen achievements</p>
            </div>
            <div>
                <button class="btn btn-outline-primary" onclick="location.reload()">
                    🔄 Refresh
                </button>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" onclick="this.parentElement.style.display='none'">&times;</button>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-section">
            <div class="stat-box">
                <h3><?php echo $totalActivities; ?></h3>
                <p>Total Activities</p>
            </div>
            <div class="stat-box">
                <h3><?php echo $totalVerified; ?></h3>
                <p>Verified Activities</p>
            </div>
            <div class="stat-box">
                <h3><?php echo $pendingVerification; ?></h3>
                <p>Pending Verification</p>
            </div>
            <div class="stat-box">
                <h3><?php echo count($citizens); ?></h3>
                <p>Total Citizens</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="content-section">
            <div class="section-header">
                <h2>🔍 Filter Activities</h2>
                <p class="text-muted">Search and filter citizen achievements</p>
            </div>
            <form method="GET" class="row">
                <div class="col-md-3">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        <option value="Sports" <?php echo $categoryFilter === 'Sports' ? 'selected' : ''; ?>>Sports</option>
                        <option value="Arts" <?php echo $categoryFilter === 'Arts' ? 'selected' : ''; ?>>Arts</option>
                        <option value="Education" <?php echo $categoryFilter === 'Education' ? 'selected' : ''; ?>>Education</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Verification Status</label>
                    <select name="verified" class="form-select">
                        <option value="">All Status</option>
                        <option value="1" <?php echo $verifiedFilter === '1' ? 'selected' : ''; ?>>Verified</option>
                        <option value="0" <?php echo $verifiedFilter === '0' ? 'selected' : ''; ?>>Pending</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search by activity name, citizen name..." 
                           value="<?php echo htmlspecialchars($searchTerm); ?>">
                </div>
                <div class="col-md-2" style="display: flex; align-items: end; gap: 10px;">
                    <button type="submit" class="btn btn-primary">
                        🔍 Filter
                    </button>
                    <a href="citizen_activities.php" class="btn btn-outline-secondary">
                        ❌
                    </a>
                </div>
            </form>
        </div>

        <!-- Activities Table -->
        <div class="content-section">
            <div class="section-header">
                <h2>📋 Activities (<?php echo count($activities); ?> found)</h2>
                <p class="text-muted">Manage citizen achievement records</p>
            </div>
            
            <?php if (empty($activities)): ?>
                <div class="text-center" style="padding: 60px 20px;">
                    <h3 style="color: var(--text-secondary); margin-bottom: 15px;">🔍 No activities found</h3>
                    <p class="text-muted">Try adjusting your search filters</p>
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Citizen</th>
                                <th>Category</th>
                                <th>Activity</th>
                                <th>Level</th>
                                <th>Details</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Proof</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($activities as $activity): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($activity['FirstName'] . ' ' . $activity['LastName']); ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($activity['NIC'] ?? $activity['Citizen_eID']); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge category-<?php echo strtolower($activity['ActivityCategory']); ?>">
                                            <?php echo $activity['ActivityCategory']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($activity['ActivityName']); ?></strong>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($activity['AchievementLevel'] ?? 'Not specified'); ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $details = $activity['Details'];
                                        echo htmlspecialchars($details ? (strlen($details) > 50 ? substr($details, 0, 50) . '...' : $details) : 'No details');
                                        ?>
                                    </td>
                                    <td>
                                        <small><?php echo date('M j, Y', strtotime($activity['CreatedAt'])); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($activity['VerifiedBy']): ?>
                                            <span class="badge verified-badge">
                                                ✅ Verified
                                            </span>
                                            <br>
                                            <small class="text-muted">
                                                by <?php echo htmlspecialchars($activity['VerifiedByName']); ?>
                                            </small>
                                        <?php else: ?>
                                            <span class="badge pending-badge">
                                                ⏳ Pending
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($activity['ProofPath']): ?>
                                            <a href="<?php echo htmlspecialchars($activity['ProofPath']); ?>" 
                                               target="_blank" class="btn btn-sm btn-outline-primary">
                                                📄
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">No proof</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group-vertical">
                                            <?php if (!$activity['VerifiedBy']): ?>
                                                <button class="btn btn-success btn-sm verify-btn" 
                                                        data-id="<?php echo $activity['ActivityID']; ?>"
                                                        data-name="<?php echo htmlspecialchars($activity['ActivityName']); ?>">
                                                    ✅ Verify
                                                </button>
                                            <?php endif; ?>
                                            
                                            <button class="btn btn-primary btn-sm edit-btn" 
                                                    data-activity='<?php echo json_encode($activity); ?>'>
                                                ✏️ Edit
                                            </button>
                                            
                                            <button class="btn btn-danger btn-sm delete-btn" 
                                                    data-id="<?php echo $activity['ActivityID']; ?>"
                                                    data-name="<?php echo htmlspecialchars($activity['ActivityName']); ?>">
                                                🗑️ Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Edit Activity Modal -->
    <div class="modal" id="editActivityModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Activity</h5>
                        <button type="button" class="btn-close" onclick="closeModal()">&times;</button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="ActivityID" id="edit_activity_id">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Citizen</label>
                                    <input type="text" id="edit_citizen" class="form-control" readonly>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Category *</label>
                                    <select name="ActivityCategory" id="edit_category" class="form-select" required>
                                        <option value="">Select Category</option>
                                        <option value="Sports">Sports</option>
                                        <option value="Arts">Arts</option>
                                        <option value="Education">Education</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Activity Name *</label>
                            <input type="text" name="ActivityName" id="edit_name" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Achievement Level</label>
                            <input type="text" name="AchievementLevel" id="edit_level" class="form-control">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Details</label>
                            <textarea name="Details" id="edit_details" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Update Proof (optional)</label>
                            <input type="file" name="Proof" class="form-control" 
                                   accept=".jpg,.jpeg,.png,.gif,.pdf">
                            <small class="text-muted">Leave empty to keep current proof</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" onclick="closeModal()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Activity</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="includes/sidebar.js"></script>
    <script>
        // Enhanced interactions and animations
        document.addEventListener('DOMContentLoaded', function() {
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
        });

        // Modal functions
        function showModal(modalId) {
            document.getElementById(modalId).classList.add('show');
        }

        function closeModal() {
            document.querySelector('.modal.show').classList.remove('show');
        }

        // Verify button functionality
        document.querySelectorAll('.verify-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                const name = this.dataset.name;
                
                if (confirm(`✅ Verify activity "${name}"?\n\nThis action will mark the activity as verified.`)) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="action" value="verify">
                        <input type="hidden" name="ActivityID" value="${id}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });

        // Edit button functionality
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const activity = JSON.parse(this.dataset.activity);
                
                document.getElementById('edit_activity_id').value = activity.ActivityID;
                document.getElementById('edit_citizen').value = `${activity.FirstName} ${activity.LastName}`;
                document.getElementById('edit_category').value = activity.ActivityCategory;
                document.getElementById('edit_name').value = activity.ActivityName;
                document.getElementById('edit_level').value = activity.AchievementLevel || '';
                document.getElementById('edit_details').value = activity.Details || '';
                
                showModal('editActivityModal');
            });
        });

        // Delete button functionality
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                const name = this.dataset.name;
                
                if (confirm(`⚠️ Are you sure you want to delete "${name}"?\n\nThis action cannot be undone.`)) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="ActivityID" value="${id}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        });

        // Close modal when clicking outside
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                closeModal();
            }
        });
    </script>
</div>
</body>
</html>