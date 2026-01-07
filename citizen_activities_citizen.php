<?php
require_once 'config.php';
require_once 'citizen_activities_helper.php';

// Check if user is logged in and is a citizen
if (!isset($_SESSION['UserID']) || $_SESSION['Role'] !== 'Citizen') {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['UserID'];
$citizenId = getCitizenIdFromUser($userId);

if (!$citizenId) {
    die("Error: Citizen profile not found. Please contact administrator.");
}

// Handle form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
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
                
                if (addCitizenActivity($citizenId, $category, $name, $level, $details, $proofPath)) {
                    $message = "Activity added successfully!";
                    $messageType = 'success';
                } else {
                    $message = "Error adding activity. Please try again.";
                    $messageType = 'danger';
                }
                break;
                
            case 'edit':
                $activityId = $_POST['ActivityID'];
                if (canEditActivity($activityId, $userId, $_SESSION['Role'])) {
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
                } else {
                    $message = "You don't have permission to edit this activity.";
                    $messageType = 'danger';
                }
                break;
                
            case 'delete':
                $activityId = $_POST['ActivityID'];
                if (canEditActivity($activityId, $userId, $_SESSION['Role'])) {
                    if (deleteActivity($activityId)) {
                        $message = "Activity deleted successfully!";
                        $messageType = 'success';
                    } else {
                        $message = "Error deleting activity. Please try again.";
                        $messageType = 'danger';
                    }
                } else {
                    $message = "You don't have permission to delete this activity.";
                    $messageType = 'danger';
                }
                break;
        }
    }
}

// Get citizen activities and stats
$activities = getCitizenActivities($citizenId);
$stats = getActivityStats($citizenId);

// Get citizen info
$stmt = $conn->prepare("SELECT FirstName, LastName FROM Citizens WHERE CitizenID = ?");
$stmt->bind_param("i", $citizenId);
$stmt->execute();
$citizenInfo = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Activities - NDMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 25%, #f8fafc 50%, #ecfdf5 75%, #f0fdf4 100%);
            background-attachment: fixed;
            min-height: auto;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: var(--text-primary);
            line-height: 1.6;
            margin: 0;
            padding: 10px 20px 20px 0;
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

        .container-fluid {
            max-width: 1400px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
            min-height: auto;
            height: auto;
        }

        .container-fluid::before {
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

        .container-fluid::after {
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

        /* Header Styling */
        .page-header {
            background: var(--citizen-gradient);
            color: white;
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 20px;
            box-shadow: var(--shadow-xl);
            position: relative;
            backdrop-filter: blur(10px);
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="2"/><circle cx="50" cy="50" r="30" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></svg>') no-repeat center;
            opacity: 0.3;
        }

        .page-header::after {
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

        .page-header h2 {
            margin: 0 0 10px 0;
            font-size: 28px;
            font-weight: 700;
            position: relative;
            z-index: 1;
        }

        .page-header p {
            margin: 0;
            opacity: 0.9;
            font-size: 16px;
            position: relative;
            z-index: 1;
        }

        /* Stats Cards */
        .stat-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(255, 255, 255, 0.8) 100%);
            border-radius: 20px;
            padding: 25px;
            text-align: center;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(5px);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--citizen-gradient);
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

        .category-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            background: var(--citizen-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .stat-label {
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Activity Cards */
        .activity-card {
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

        .activity-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: var(--secondary-color);
        }

        .activity-card::after {
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

        .activity-card:hover::after {
            opacity: 1;
            right: 100%;
        }

        .activity-card.sports::before {
            background: var(--accent-color);
        }

        .activity-card.arts::before {
            background: var(--danger-color);
        }

        .activity-card.education::before {
            background: var(--warning-color);
        }

        .activity-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
        }

        /* Badges */
        .verified-badge {
            background: var(--citizen-gradient);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: var(--shadow-sm);
        }

        .pending-badge {
            background: linear-gradient(135deg, #6b7280 0%, #9ca3af 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: var(--shadow-sm);
        }

        /* Buttons */
        .btn-primary {
            background: var(--gradient-bg);
            border: none;
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            filter: brightness(1.1);
        }

        .btn-outline-primary {
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
            border-radius: 12px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-outline-primary:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--danger-color) 0%, #dc2626 100%);
            border: none;
            border-radius: 12px;
            padding: 8px 16px;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            filter: brightness(1.1);
        }

        /* Modal Styling */
        .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: var(--shadow-xl);
        }

        .modal-header {
            background: var(--citizen-gradient);
            color: white;
            border-radius: 20px 20px 0 0;
            border-bottom: none;
            padding: 25px;
        }

        .modal-title {
            font-weight: 700;
            font-size: 20px;
        }

        .modal-body {
            padding: 30px;
        }

        .modal-footer {
            border-top: 1px solid var(--border-color);
            padding: 20px 30px;
        }

        /* Form Controls */
        .form-control, .form-select {
            border-radius: 12px;
            border: 2px solid var(--border-color);
            padding: 12px 16px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.2rem rgba(16, 185, 129, 0.25);
        }

        .form-label {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 8px;
        }

        /* Navigation Bar */
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
            background: var(--citizen-gradient);
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
            background: var(--citizen-gradient);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-bar a:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            filter: brightness(1.1);
        }

        .citizen-badge {
            background: var(--citizen-gradient);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }
            
            .nav-bar {
                flex-direction: column;
                gap: 15px;
            }
            
            .nav-links {
                width: 100%;
                justify-content: center;
            }
            
            .page-header {
                padding: 20px;
                text-align: center;
            }
            
            .page-header h2 {
                font-size: 24px;
            }
        }

        /* Loading Animation */
        .activity-card {
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Sidebar Integration Styles */
        body.has-citizen-sidebar {
            padding-left: 290px;
            transition: padding-left 0.3s ease;
        }
        
        body.citizen-sidebar-collapsed {
            padding-left: 70px;
        }

        body.has-citizen-sidebar .container-fluid {
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
            
            body.has-citizen-sidebar .container-fluid {
                margin: 0;
                height: auto;
                min-height: auto;
            }
        }
    </style>
</head>
<body class="bg-light">
    <?php include 'includes/citizen_sidebar.php'; ?>
    
    <div class="container-fluid">
        <!-- Navigation Bar -->
        <div class="nav-bar">
            <div class="nav-links">
                <a href="citizen_dashboard.php">🏠 Dashboard</a>
                <a href="citizen_activities_citizen.php">🏆 My Activities</a>
                <a href="citizen_vaccination.php">💉 Vaccinations</a>
                <a href="career_guidance_form.php">🎯 Career Guidance</a>
                <a href="change_password.php">🔐 Change Password</a>
                <a href="login.php?logout=1">🚪 Logout</a>
            </div>
            <div style="display: flex; align-items: center; gap: 15px;">
                <?php 
                if (file_exists('notification_component.php')) {
                    include 'notification_component.php'; 
                }
                ?>
                <span class="citizen-badge">Citizen Portal</span>
            </div>
        </div>
        
        <div class="row">
                <!-- Main Content -->
                <div class="col-12">
                    <!-- Header -->
                    <div class="page-header">
                        <div>
                            <h2><i class="fas fa-trophy"></i> My Activities</h2>
                            <p>Manage your sports, arts, and educational achievements</p>
                        </div>
                    </div>

                    <!-- Messages -->
                    <?php if ($message): ?>
                        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <?php 
                        $categoryIcons = [
                            'Sports' => 'fas fa-running',
                            'Arts' => 'fas fa-palette',
                            'Education' => 'fas fa-graduation-cap'
                        ];
                        $totalActivities = 0;
                        $totalVerified = 0;
                        
                        foreach ($stats as $stat) {
                            $totalActivities += $stat['count'];
                            $totalVerified += $stat['verified_count'];
                        }
                        ?>
                        
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="category-icon">
                                    <i class="fas fa-trophy"></i>
                                </div>
                                <h3><?php echo $totalActivities; ?></h3>
                                <p class="mb-0">Total Activities</p>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="stat-card">
                                <div class="category-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <h3><?php echo $totalVerified; ?></h3>
                                <p class="mb-0">Verified</p>
                            </div>
                        </div>
                        
                        <?php foreach ($stats as $stat): ?>
                            <div class="col-md-3">
                                <div class="stat-card">
                                    <div class="category-icon">
                                        <i class="<?php echo $categoryIcons[$stat['ActivityCategory']] ?? 'fas fa-star'; ?>"></i>
                                    </div>
                                    <h3><?php echo $stat['count']; ?></h3>
                                    <p class="mb-0"><?php echo $stat['ActivityCategory']; ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Activities List -->
                    <div class="card">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0"><i class="fas fa-list"></i> Your Activities</h5>
                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addActivityModal">
                                    <i class="fas fa-plus"></i> Add Activity
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php if (empty($activities)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-trophy fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No activities added yet</h5>
                                    <p class="text-muted">Start by adding your first achievement!</p>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addActivityModal">
                                        <i class="fas fa-plus"></i> Add Your First Activity
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($activities as $activity): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="card activity-card <?php echo strtolower($activity['ActivityCategory']); ?>">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <h6 class="card-title mb-0">
                                                            <i class="<?php echo $categoryIcons[$activity['ActivityCategory']] ?? 'fas fa-star'; ?>"></i>
                                                            <?php echo htmlspecialchars($activity['ActivityName']); ?>
                                                        </h6>
                                                        <?php if ($activity['VerifiedBy']): ?>
                                                            <span class="verified-badge">
                                                                <i class="fas fa-check-circle"></i> Verified
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="pending-badge">
                                                                <i class="fas fa-clock"></i> Pending
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <p class="text-muted small mb-1">
                                                        <strong>Category:</strong> <?php echo $activity['ActivityCategory']; ?>
                                                    </p>
                                                    
                                                    <?php if ($activity['AchievementLevel']): ?>
                                                        <p class="text-muted small mb-1">
                                                            <strong>Level:</strong> <?php echo htmlspecialchars($activity['AchievementLevel']); ?>
                                                        </p>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($activity['Details']): ?>
                                                        <p class="card-text small">
                                                            <?php echo htmlspecialchars(substr($activity['Details'], 0, 100)) . (strlen($activity['Details']) > 100 ? '...' : ''); ?>
                                                        </p>
                                                    <?php endif; ?>
                                                    
                                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                                        <small class="text-muted">
                                                            <i class="fas fa-calendar"></i> 
                                                            <?php echo date('M j, Y', strtotime($activity['CreatedAt'])); ?>
                                                        </small>
                                                        
                                                        <div>
                                                            <?php if ($activity['ProofPath']): ?>
                                                                <a href="<?php echo htmlspecialchars($activity['ProofPath']); ?>" 
                                                                   target="_blank" class="btn btn-sm btn-outline-info">
                                                                    <i class="fas fa-file"></i> Proof
                                                                </a>
                                                            <?php endif; ?>
                                                            
                                                            <button class="btn btn-sm btn-outline-primary edit-btn" 
                                                                    data-activity='<?php echo json_encode($activity); ?>'>
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            
                                                            <button class="btn btn-sm btn-outline-danger delete-btn" 
                                                                    data-id="<?php echo $activity['ActivityID']; ?>"
                                                                    data-name="<?php echo htmlspecialchars($activity['ActivityName']); ?>">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Activity Modal -->
    <div class="modal fade" id="addActivityModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Activity</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label class="form-label">Category *</label>
                            <select name="ActivityCategory" class="form-select" required>
                                <option value="">Select Category</option>
                                <option value="Sports">Sports</option>
                                <option value="Arts">Arts</option>
                                <option value="Education">Education</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Activity Name *</label>
                            <input type="text" name="ActivityName" class="form-control" required 
                                   placeholder="e.g., School Cricket Championship">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Achievement Level</label>
                            <input type="text" name="AchievementLevel" class="form-control" 
                                   placeholder="e.g., School Level, District Level, National Level">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Details</label>
                            <textarea name="Details" class="form-control" rows="3" 
                                      placeholder="Describe your achievement..."></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Proof (Certificate/Photo)</label>
                            <input type="file" name="Proof" class="form-control" 
                                   accept=".jpg,.jpeg,.png,.gif,.pdf">
                            <div class="form-text">Max 5MB. Supported: JPG, PNG, GIF, PDF</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Activity</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Activity Modal -->
    <div class="modal fade" id="editActivityModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Activity</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="ActivityID" id="edit_activity_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Category *</label>
                            <select name="ActivityCategory" id="edit_category" class="form-select" required>
                                <option value="">Select Category</option>
                                <option value="Sports">Sports</option>
                                <option value="Arts">Arts</option>
                                <option value="Education">Education</option>
                            </select>
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
                            <div class="form-text">Leave empty to keep current proof</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Activity</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Ensure Bootstrap is loaded before initializing
        document.addEventListener('DOMContentLoaded', function() {
            // Explicitly handle all "Add Activity" buttons
            document.querySelectorAll('[data-bs-target="#addActivityModal"]').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const modal = new bootstrap.Modal(document.getElementById('addActivityModal'));
                    modal.show();
                });
            });

            // Handle modal cleanup when closed
            const addActivityModal = document.getElementById('addActivityModal');
            if (addActivityModal) {
                addActivityModal.addEventListener('hidden.bs.modal', function () {
                    // Remove any lingering backdrops
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    backdrops.forEach(backdrop => backdrop.remove());
                    
                    // Remove modal-open class from body
                    document.body.classList.remove('modal-open');
                    
                    // Reset body styles
                    document.body.style.overflow = '';
                    document.body.style.paddingRight = '';
                    
                    // Clear form if needed
                    const form = addActivityModal.querySelector('form');
                    if (form) {
                        form.reset();
                    }
                });
            }

            // Edit button functionality
            document.querySelectorAll('.edit-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const activity = JSON.parse(this.dataset.activity);
                    
                    document.getElementById('edit_activity_id').value = activity.ActivityID;
                    document.getElementById('edit_category').value = activity.ActivityCategory;
                    document.getElementById('edit_name').value = activity.ActivityName;
                    document.getElementById('edit_level').value = activity.AchievementLevel || '';
                    document.getElementById('edit_details').value = activity.Details || '';
                    
                    new bootstrap.Modal(document.getElementById('editActivityModal')).show();
                });
            });

            // Delete button functionality
            document.querySelectorAll('.delete-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.dataset.id;
                    const name = this.dataset.name;
                    
                    if (confirm(`Are you sure you want to delete "${name}"?`)) {
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
        });
    </script>
</body>
</html>