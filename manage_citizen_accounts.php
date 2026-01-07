<?php
include "config.php";

// Check if user is logged in and is an admin
if (!isset($_SESSION['UserID']) || $_SESSION['Role'] !== 'Admin') {
    header("Location: login.php");
    exit;
}

$message = "";
$messageType = "";

// Handle account status changes
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'toggle_status' && isset($_POST['citizen_id'])) {
        $citizenID = $_POST['citizen_id'];
        // Toggle active status in Citizens table
        $stmt = $conn->prepare("UPDATE Citizens SET IsActive = !IsActive WHERE CitizenID = ?");
        $stmt->bind_param("i", $citizenID);
        if ($stmt->execute()) {
            $message = "Account status updated successfully!";
            $messageType = "success";
        } else {
            $message = "Error updating account status.";
            $messageType = "error";
        }
    } elseif ($action === 'reset_password' && isset($_POST['citizen_id'])) {
        $citizenID = $_POST['citizen_id'];
        // Reset password in Users table if linked
        $getUserStmt = $conn->prepare("SELECT UserID FROM Citizens WHERE CitizenID = ?");
        $getUserStmt->bind_param("i", $citizenID);
        $getUserStmt->execute();
        $userResult = $getUserStmt->get_result();
        
        if ($userResult->num_rows > 0) {
            $userRow = $userResult->fetch_assoc();
            $userID = $userRow['UserID'];
            
            if ($userID) {
                // Generate new password
                $newPassword = bin2hex(random_bytes(8)); // Generate 16-character random password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                
                $stmt = $conn->prepare("UPDATE Users SET PasswordHash = ? WHERE UserID = ?");
                $stmt->bind_param("si", $hashedPassword, $userID);
                
                if ($stmt->execute()) {
                    $message = "Password reset successfully! New password: <strong>$newPassword</strong><br><small>Please provide this to the citizen securely.</small>";
                    $messageType = "success";
                } else {
                    $message = "Error resetting password.";
                    $messageType = "error";
                }
            } else {
                $message = "No user account linked to this citizen.";
                $messageType = "error";
            }
        } else {
            $message = "Citizen not found.";
            $messageType = "error";
        }
    } elseif ($action === 'bulk_activate') {
        $citizenIDs = $_POST['citizen_ids'] ?? [];
        if (!empty($citizenIDs)) {
            $placeholders = str_repeat('?,', count($citizenIDs) - 1) . '?';
            $stmt = $conn->prepare("UPDATE Citizens SET IsActive = 1 WHERE CitizenID IN ($placeholders)");
            $stmt->bind_param(str_repeat('i', count($citizenIDs)), ...$citizenIDs);
            if ($stmt->execute()) {
                $message = "Successfully activated " . count($citizenIDs) . " accounts!";
                $messageType = "success";
            } else {
                $message = "Error in bulk activation.";
                $messageType = "error";
            }
        }
    } elseif ($_POST['action'] === 'bulk_deactivate') {
        $citizenIDs = $_POST['citizen_ids'] ?? [];
        if (!empty($citizenIDs)) {
            $placeholders = str_repeat('?,', count($citizenIDs) - 1) . '?';
            $stmt = $conn->prepare("UPDATE Citizens SET IsActive = 0 WHERE CitizenID IN ($placeholders)");
            $stmt->bind_param(str_repeat('i', count($citizenIDs)), ...$citizenIDs);
            if ($stmt->execute()) {
                $message = "Successfully deactivated " . count($citizenIDs) . " accounts!";
                $messageType = "success";
            } else {
                $message = "Error in bulk deactivation.";
                $messageType = "error";
            }
        }
    }
}

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // Set CSV headers
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="citizen_accounts_' . date('Y-m-d_H-i-s') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // CSV Headers
    fputcsv($output, [
        'Citizen ID',
        'Citizen eID', 
        'First Name',
        'Last Name',
        'NIC',
        'Date of Birth',
        'Status',
        'User Account',
        'Username',
        'Registration Date'
    ]);
    
    // Get all citizen data for export
    $exportSql = "SELECT c.CitizenID, c.Citizen_eID, c.FirstName, c.LastName, c.NIC, c.DOB, c.IsActive, c.CreatedAt,
                         u.UserID, u.Username
                  FROM Citizens c 
                  LEFT JOIN Users u ON c.UserID = u.UserID 
                  ORDER BY c.CreatedAt DESC";
    
    $exportStmt = $conn->prepare($exportSql);
    $exportStmt->execute();
    $exportResult = $exportStmt->get_result();
    
    // Output data rows
    while ($row = $exportResult->fetch_assoc()) {
        fputcsv($output, [
            $row['CitizenID'],
            $row['Citizen_eID'],
            $row['FirstName'],
            $row['LastName'],
            $row['NIC'],
            $row['DOB'],
            $row['IsActive'] ? 'Active' : 'Inactive',
            $row['UserID'] ? 'Yes' : 'No',
            $row['Username'] ?? 'N/A',
            date('Y-m-d H:i:s', strtotime($row['CreatedAt']))
        ]);
    }
    
    fclose($output);
    exit; // Stop further execution after CSV export
}

// Get filter parameters
$statusFilter = $_GET['status'] ?? 'all';
$searchTerm = $_GET['search'] ?? '';

// Build WHERE clause for filtering
$whereConditions = ["1=1"]; // Base condition
$params = [];
$paramTypes = "";

if ($statusFilter === 'active') {
    $whereConditions[] = "c.IsActive = 1";
} elseif ($statusFilter === 'inactive') {
    $whereConditions[] = "c.IsActive = 0";
}

if (!empty($searchTerm)) {
    $whereConditions[] = "(c.FirstName LIKE ? OR c.LastName LIKE ? OR c.NIC LIKE ? OR c.Citizen_eID LIKE ? OR CONCAT(c.FirstName, ' ', c.LastName) LIKE ?)";
    $searchParam = "%$searchTerm%";
    $params = array_fill(0, 5, $searchParam);
    $paramTypes = str_repeat('s', 5);
}

$whereClause = implode(' AND ', $whereConditions);

// Get all citizen accounts with their basic info and linked user info
$sql = "SELECT c.CitizenID, c.Citizen_eID, c.FirstName, c.LastName, c.NIC, c.DOB, c.IsActive, c.CreatedAt,
               u.UserID, u.Username, u.CreatedAt as UserCreatedAt
        FROM Citizens c 
        LEFT JOIN Users u ON c.UserID = u.UserID 
        WHERE $whereClause
        ORDER BY c.CreatedAt DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($paramTypes, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get statistics
$totalStmt = $conn->prepare("SELECT COUNT(*) as total FROM Citizens");
$totalStmt->execute();
$totalCount = $totalStmt->get_result()->fetch_assoc()['total'];

$activeStmt = $conn->prepare("SELECT COUNT(*) as active FROM Citizens WHERE IsActive = 1");
$activeStmt->execute();
$activeCount = $activeStmt->get_result()->fetch_assoc()['active'];

$inactiveCount = $totalCount - $activeCount;

$recentStmt = $conn->prepare("SELECT COUNT(*) as recent FROM Citizens WHERE CreatedAt >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$recentStmt->execute();
$recentCount = $recentStmt->get_result()->fetch_assoc()['recent'];

$linkedStmt = $conn->prepare("SELECT COUNT(*) as linked FROM Citizens WHERE UserID IS NOT NULL");
$linkedStmt->execute();
$linkedCount = $linkedStmt->get_result()->fetch_assoc()['linked'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Citizen Accounts - NDMS Admin</title>
    <link rel="stylesheet" href="includes/sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #1e3a8a;
            --secondary-color: #3b82f6;
            --accent-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --light-bg: #f8fafc;
            --card-bg: #ffffff;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --border-color: #e5e7eb;
            --gradient-bg: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
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
        
        .page-header {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-md);
        }
        
        .page-header h1 {
            margin: 0 0 10px 0;
            color: var(--primary-color);
            font-size: 28px;
            font-weight: 700;
        }
        
        .page-header p {
            color: var(--text-secondary);
            margin: 0;
            font-size: 16px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 25px;
            box-shadow: var(--shadow-md);
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .stat-card.success { border-left-color: var(--accent-color); }
        .stat-card.warning { border-left-color: var(--warning-color); }
        .stat-card.danger { border-left-color: var(--danger-color); }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: var(--text-secondary);
            font-weight: 500;
            font-size: 14px;
        }
        
        .controls-section {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-md);
        }
        
        .controls-grid {
            display: grid;
            grid-template-columns: 1fr auto auto auto;
            gap: 20px;
            align-items: center;
        }
        
        .search-box {
            position: relative;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 45px 12px 20px;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: var(--light-bg);
        }
        
        .search-box input:focus {
            outline: none;
            border-color: var(--primary-color);
            background: white;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .search-box i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
        }
        
        .filter-select {
            padding: 12px 20px;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-size: 16px;
            background: var(--light-bg);
            color: var(--text-primary);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .filter-select:focus {
            outline: none;
            border-color: var(--primary-color);
            background: white;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
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
            box-shadow: var(--shadow-lg);
        }
        
        .btn-success {
            background: var(--accent-color);
            color: white;
        }
        
        .btn-warning {
            background: var(--warning-color);
            color: white;
        }
        
        .btn-danger {
            background: var(--danger-color);
            color: white;
        }
        
        .btn-secondary {
            background: var(--text-secondary);
            color: white;
        }
        
        .btn-sm {
            padding: 8px 16px;
            font-size: 12px;
        }
        
        .table-container {
            background: var(--card-bg);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            margin-bottom: 30px;
        }
        
        .table-header {
            padding: 20px 25px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .table-header h3 {
            margin: 0;
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .bulk-actions {
            display: none;
            gap: 10px;
        }
        
        .bulk-actions.show {
            display: flex;
        }
        
        .accounts-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .accounts-table th,
        .accounts-table td {
            padding: 15px 25px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        .accounts-table th {
            background: var(--light-bg);
            font-weight: 600;
            color: var(--text-primary);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .accounts-table tr:hover {
            background: var(--light-bg);
        }
        
        .citizen-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .citizen-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: var(--gradient-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 18px;
        }
        
        .citizen-details h4 {
            margin: 0 0 5px 0;
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .citizen-details p {
            margin: 0;
            font-size: 13px;
            color: var(--text-secondary);
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-active {
            background: rgba(16, 185, 129, 0.1);
            color: var(--accent-color);
        }
        
        .status-inactive {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            border-left: 4px solid;
            background: var(--card-bg);
            box-shadow: var(--shadow-sm);
        }
        
        .alert-success {
            border-left-color: var(--accent-color);
            background: rgba(16, 185, 129, 0.05);
            color: #065f46;
        }
        
        .alert-error {
            border-left-color: var(--danger-color);
            background: rgba(239, 68, 68, 0.05);
            color: #991b1b;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
        }
        
        .empty-state i {
            font-size: 48px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .checkbox-column {
            width: 50px;
        }
        
        .account-checkbox {
            width: 18px;
            height: 18px;
            accent-color: var(--primary-color);
            cursor: pointer;
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
            
            .controls-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .accounts-table {
                font-size: 14px;
            }
            
            .accounts-table th,
            .accounts-table td {
                padding: 10px 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Include Sidebar -->
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-users-cog"></i> Manage Citizen Accounts</h1>
            <p>Administrative management of citizen user accounts and access control</p>
        </div>

        <!-- Alert Messages -->
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?= $messageType ?>">
                <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
                <?= $message ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $totalCount ?></div>
                <div class="stat-label"><i class="fas fa-users"></i> Total Citizens</div>
            </div>
            <div class="stat-card success">
                <div class="stat-number"><?= $activeCount ?></div>
                <div class="stat-label"><i class="fas fa-user-check"></i> Active Citizens</div>
            </div>
            <div class="stat-card danger">
                <div class="stat-number"><?= $inactiveCount ?></div>
                <div class="stat-label"><i class="fas fa-user-times"></i> Inactive Citizens</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-number"><?= $linkedCount ?></div>
                <div class="stat-label"><i class="fas fa-link"></i> With User Accounts</div>
            </div>
        </div>

        <!-- Controls Section -->
        <div class="controls-section">
            <form method="GET" class="controls-grid">
                <div class="search-box">
                    <input type="text" name="search" value="<?= htmlspecialchars($searchTerm) ?>" 
                           placeholder="Search by name, NIC, eID, or address..." onchange="this.form.submit()">
                    <i class="fas fa-search"></i>
                </div>
                
                <select name="status" class="filter-select" onchange="this.form.submit()">
                    <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>All Citizens</option>
                    <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active Only</option>
                    <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Inactive Only</option>
                </select>
                
                <a href="register.php" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Register New Citizen
                </a>
                
                <a href="?export=csv" class="btn btn-secondary">
                    <i class="fas fa-download"></i> Export CSV
                </a>
            </form>
        </div>

        <!-- Accounts Table -->
        <div class="table-container">
            <div class="table-header">
                <h3><i class="fas fa-table"></i> Citizens (<?= $result->num_rows ?> records)</h3>
                <div class="bulk-actions" id="bulkActions">
                    <button type="button" class="btn btn-success btn-sm" onclick="bulkAction('activate')">
                        <i class="fas fa-user-check"></i> Activate Selected
                    </button>
                    <button type="button" class="btn btn-danger btn-sm" onclick="bulkAction('deactivate')">
                        <i class="fas fa-user-times"></i> Deactivate Selected
                    </button>
                </div>
            </div>
            
            <?php if ($result->num_rows > 0): ?>
                <table class="accounts-table" id="accountsTable">
                    <thead>
                        <tr>
                            <th class="checkbox-column">
                                <input type="checkbox" class="account-checkbox" id="selectAll" onchange="toggleSelectAll()">
                            </th>
                            <th>Citizen Information</th>
                            <th>Account Details</th>
                            <th>Status</th>
                            <th>Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td class="checkbox-column">
                                    <input type="checkbox" class="account-checkbox row-checkbox" 
                                           value="<?= $row['CitizenID'] ?>" onchange="updateBulkActions()">
                                </td>
                                <td>
                                    <div class="citizen-info">
                                        <div class="citizen-avatar">
                                            <?= strtoupper(substr($row['FirstName'] ?? 'U', 0, 1)) ?>
                                        </div>
                                        <div class="citizen-details">
                                            <h4><?= htmlspecialchars(($row['FirstName'] ?? 'Unknown') . ' ' . ($row['LastName'] ?? 'User')) ?></h4>
                                            <p><i class="fas fa-id-card"></i> NIC: <?= htmlspecialchars($row['NIC'] ?? 'Not set') ?></p>
                                            <p><i class="fas fa-calendar"></i> DOB: <?= $row['DOB'] ? date('M j, Y', strtotime($row['DOB'])) : 'Not set' ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <strong>Citizen eID:</strong> <?= htmlspecialchars($row['Citizen_eID']) ?><br>
                                    <small>Citizen ID: #<?= $row['CitizenID'] ?></small><br>
                                    <?php if ($row['UserID']): ?>
                                        <small>User Account: <?= htmlspecialchars($row['Username']) ?> (#<?= $row['UserID'] ?>)</small>
                                    <?php else: ?>
                                        <small style="color: #f59e0b;">No user account linked</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge <?= $row['IsActive'] ? 'status-active' : 'status-inactive' ?>">
                                        <i class="fas fa-<?= $row['IsActive'] ? 'check-circle' : 'times-circle' ?>"></i>
                                        <?= $row['IsActive'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td><?= date('M j, Y H:i', strtotime($row['CreatedAt'])) ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="view_citizen.php?citizen_id=<?= $row['CitizenID'] ?>" 
                                           class="btn btn-primary btn-sm" title="View Citizen Profile">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        <form method="post" style="display: inline;">
                                            <input type="hidden" name="citizen_id" value="<?= $row['CitizenID'] ?>">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <button type="submit" class="btn <?= $row['IsActive'] ? 'btn-warning' : 'btn-success' ?> btn-sm" 
                                                    title="<?= $row['IsActive'] ? 'Deactivate' : 'Activate' ?> Account"
                                                    onclick="return confirm('<?= $row['IsActive'] ? 'Deactivate' : 'Activate' ?> account for <?= htmlspecialchars(($row['FirstName'] ?? '') . ' ' . ($row['LastName'] ?? '')) ?>?')">
                                                <i class="fas fa-<?= $row['IsActive'] ? 'user-times' : 'user-check' ?>"></i>
                                            </button>
                                        </form>
                                        
                                        <?php if ($row['UserID']): ?>
                                            <form method="post" style="display: inline;">
                                                <input type="hidden" name="citizen_id" value="<?= $row['CitizenID'] ?>">
                                                <input type="hidden" name="action" value="reset_password">
                                                <button type="submit" class="btn btn-secondary btn-sm" 
                                                        title="Reset Password"
                                                        onclick="return confirm('Reset password for <?= htmlspecialchars(($row['FirstName'] ?? '') . ' ' . ($row['LastName'] ?? '')) ?>? A new random password will be generated.')">
                                                    <i class="fas fa-key"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="btn btn-secondary btn-sm" style="opacity: 0.5; cursor: not-allowed;" title="No user account to reset">
                                                <i class="fas fa-key"></i>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-users"></i>
                    <h3>No Citizens Found</h3>
                    <p><?= !empty($searchTerm) ? 'No citizens match your search criteria.' : 'No citizens have been registered yet.' ?></p>
                    <?php if (!empty($searchTerm)): ?>
                        <a href="?" class="btn btn-primary">Clear Search</a>
                    <?php endif; ?>
                    <a href="register.php" class="btn btn-primary">Register First Citizen</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Include Sidebar JavaScript -->
    <script src="includes/sidebar.js"></script>
    <script>
        // Bulk actions functionality
        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.row-checkbox');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
            
            updateBulkActions();
        }
        
        function updateBulkActions() {
            const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
            const bulkActions = document.getElementById('bulkActions');
            
            if (checkedBoxes.length > 0) {
                bulkActions.classList.add('show');
            } else {
                bulkActions.classList.remove('show');
            }
            
            // Update select all checkbox state
            const allCheckboxes = document.querySelectorAll('.row-checkbox');
            const selectAllCheckbox = document.getElementById('selectAll');
            
            if (checkedBoxes.length === allCheckboxes.length) {
                selectAllCheckbox.checked = true;
                selectAllCheckbox.indeterminate = false;
            } else if (checkedBoxes.length > 0) {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = true;
            } else {
                selectAllCheckbox.checked = false;
                selectAllCheckbox.indeterminate = false;
            }
        }
        
        function bulkAction(action) {
            const checkedBoxes = document.querySelectorAll('.row-checkbox:checked');
            const citizenIds = Array.from(checkedBoxes).map(cb => cb.value);
            
            if (citizenIds.length === 0) {
                alert('Please select at least one citizen.');
                return;
            }
            
            const actionText = action === 'activate' ? 'activate' : 'deactivate';
            const confirmMessage = `Are you sure you want to ${actionText} ${citizenIds.length} selected citizen(s)?`;
            
            if (confirm(confirmMessage)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="bulk_${action}">
                    ${citizenIds.map(id => `<input type="hidden" name="citizen_ids[]" value="${id}">`).join('')}
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Auto-hide success messages after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const successMessages = document.querySelectorAll('.alert-success');
            successMessages.forEach(function(message) {
                setTimeout(function() {
                    message.style.opacity = '0';
                    setTimeout(function() {
                        message.remove();
                    }, 300);
                }, 5000);
            });
        });
        
        // Handle CSV export
        document.querySelector('a[href*="export=csv"]')?.addEventListener('click', function(e) {
            if (confirm('Export all citizen account data to CSV file?')) {
                // Allow the download to proceed
                return true;
            } else {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>
