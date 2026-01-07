<?php
include "config.php";
if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit;
}

$role = $_SESSION['Role'];
$username = '';

// Get username for display
$userResult = $conn->query("SELECT Username FROM Users WHERE UserID = " . $_SESSION['UserID']);
if ($userResult && $user = $userResult->fetch_assoc()) {
    $username = $user['Username'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>NDMS Dashboard</title>
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
            margin: 0;
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 280px;
            background: var(--gradient-bg);
            color: white;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1000;
            overflow-y: auto;
            box-shadow: var(--shadow-xl);
            display: flex;
            flex-direction: column;
        }
        
        .sidebar.collapsed {
            width: 80px;
        }
        
        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            gap: 15px;
            position: relative;
        }
        
        .sidebar-logo {
            width: 40px;
            height: 40px;
            background: white;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: var(--primary-color);
            font-weight: 800;
            flex-shrink: 0;
        }
        
        .sidebar-title {
            font-size: 18px;
            font-weight: 700;
            opacity: 1;
            transition: opacity 0.3s ease;
        }
        
        .sidebar.collapsed .sidebar-title {
            opacity: 0;
        }
        
        .sidebar-toggle {
            position: absolute;
            right: -15px;
            top: 50%;
            transform: translateY(-50%);
            width: 30px;
            height: 30px;
            background: var(--card-bg);
            border: 2px solid var(--primary-color);
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            font-size: 14px;
            transition: all 0.3s ease;
            z-index: 1001;
        }
        
        .sidebar-toggle:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-50%) scale(1.1);
        }
        
        .sidebar-nav {
            padding: 20px 0;
            flex: 1;
            overflow-y: auto;
        }
        
        .nav-section {
            margin-bottom: 30px;
        }
        
        .nav-section-title {
            padding: 0 20px 10px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.7;
            transition: opacity 0.3s ease;
        }
        
        .sidebar.collapsed .nav-section-title {
            opacity: 0;
        }
        
        .nav-item {
            position: relative;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 20px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            border-right: 3px solid transparent;
            position: relative;
        }
        
        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            border-right-color: rgba(255, 255, 255, 0.5);
        }
        
        .nav-link.active {
            background: rgba(255, 255, 255, 0.15);
            border-right-color: white;
        }
        
        .nav-icon {
            width: 20px;
            text-align: center;
            flex-shrink: 0;
            font-size: 18px;
        }
        
        .nav-text {
            font-weight: 500;
            transition: opacity 0.3s ease;
        }
        
        .sidebar.collapsed .nav-text {
            opacity: 0;
        }
        
        .nav-badge {
            background: var(--accent-color);
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: 600;
            margin-left: auto;
            transition: opacity 0.3s ease;
        }
        
        .sidebar.collapsed .nav-badge {
            opacity: 0;
        }
        
        .sidebar-footer {
            margin-top: auto;
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            flex-shrink: 0;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .user-avatar {
            width: 35px;
            height: 35px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            font-weight: 700;
            flex-shrink: 0;
        }
        
        .user-details {
            flex: 1;
            transition: opacity 0.3s ease;
        }
        
        .sidebar.collapsed .user-details {
            opacity: 0;
        }
        
        .user-name {
            font-weight: 600;
            font-size: 14px;
        }
        
        .user-role {
            font-size: 12px;
            opacity: 0.8;
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
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.mobile-open {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .sidebar.collapsed + .main-content {
                margin-left: 0;
            }
            
            .mobile-overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
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
            
            .mobile-menu-btn {
                position: fixed;
                top: 20px;
                left: 20px;
                width: 50px;
                height: 50px;
                background: var(--primary-color);
                color: white;
                border: none;
                border-radius: 12px;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 20px;
                z-index: 1002;
                box-shadow: var(--shadow-lg);
                transition: all 0.3s ease;
            }
            
            .mobile-menu-btn:hover {
                transform: scale(1.05);
            }
        }
        
        @media (min-width: 769px) {
            .mobile-menu-btn {
                display: none;
            }
        }
        
        /* Tooltip for collapsed sidebar */
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
            font-weight: 500;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1001;
            pointer-events: none;
        }
        
        .nav-tooltip::before {
            content: '';
            position: absolute;
            right: 100%;
            top: 50%;
            transform: translateY(-50%);
            border: 5px solid transparent;
            border-right-color: var(--text-primary);
        }
        
        .sidebar.collapsed .nav-item:hover .nav-tooltip {
            opacity: 1;
            visibility: visible;
        }
        
        /* Container and existing styles */
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .header { 
            background: var(--gradient-bg);
            color: white;
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-xl);
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 200px;
            height: 200px;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="2"/><circle cx="50" cy="50" r="30" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/><circle cx="50" cy="50" r="20" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></svg>') no-repeat center;
            opacity: 0.3;
        }
        
        .header h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 8px;
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
        
        .logout {
            position: absolute;
            top: 30px;
            right: 30px;
            background: var(--danger-color) !important;
            z-index: 2;
        }
        
        .logout:hover {
            background: #dc2626 !important;
        }
        
        .role-section { 
            background: var(--card-bg);
            padding: 30px;
            border-radius: 20px;
            margin: 25px 0;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            position: relative;
        }
        
        .role-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-bg);
            border-radius: 20px 20px 0 0;
        }
        
        .role-section h2 {
            color: var(--primary-color);
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .role-section p {
            color: var(--text-secondary);
            font-size: 16px;
            margin-bottom: 25px;
        }
        
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        /* Dashboard Section Styles */
        .dashboard-section {
            margin-bottom: 40px;
            background: var(--card-bg);
            border-radius: 16px;
            padding: 25px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }
        
        .dashboard-section:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .section-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--secondary-color);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .dashboard-section .menu-grid {
            margin-top: 15px;
        }
        
        .menu-item { 
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 18px 20px;
            background: var(--gradient-bg);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
            border: 1px solid transparent;
        }
        
        .menu-item:hover { 
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
            filter: brightness(1.1);
        }
        
        .stats { 
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .stat-box { 
            background: var(--card-bg);
            padding: 25px;
            border-radius: 15px;
            border-left: 4px solid var(--accent-color);
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            position: relative;
        }
        
        .stat-box:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            border-left-color: var(--primary-color);
        }
        
        .stat-box h3 {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 8px;
        }
        
        .stat-box p {
            color: var(--text-secondary);
            font-size: 14px;
            font-weight: 500;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }
            
            .header {
                padding: 25px 20px;
                text-align: center;
            }
            
            .header h1 {
                font-size: 24px;
            }
            
            .logout {
                position: static;
                margin-top: 20px;
                display: inline-block;
            }
            
            .menu-grid {
                grid-template-columns: 1fr;
            }
            
            .stats {
                grid-template-columns: 1fr;
            }
        }
        
        /* Loading Animation */
        .role-section, .stat-box {
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
                    <a href="dashboard.php" class="nav-link active">
                        <span class="nav-icon">🏠</span>
                        <span class="nav-text">Dashboard</span>
                    </a>
                    <div class="nav-tooltip">Dashboard</div>
                </div>
            </div>
            
            <!-- Role-based Navigation -->
            <?php if ($role == "Admin"): ?>
                <?php
                // Get unread contact messages count for badge
                $unreadContactsQuery = $conn->query("SELECT COUNT(*) as count FROM contact_messages WHERE status = 'new'");
                $unreadContactsCount = $unreadContactsQuery ? $unreadContactsQuery->fetch_assoc()['count'] : 0;
                ?>
                
                <!-- User Management Section -->
                <div class="nav-section">
                    <div class="nav-section-title">User Management</div>
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
                </div>
                
                <!-- Academic & Career Section -->
                <div class="nav-section">
                    <div class="nav-section-title">Academic & Career</div>
                    <div class="nav-item">
                        <a href="manage_subjects.php" class="nav-link">
                            <span class="nav-icon">�</span>
                            <span class="nav-text">Manage Subjects</span>
                        </a>
                        <div class="nav-tooltip">Manage Academic Subjects</div>
                    </div>
                </div>
                
                <!-- Communication Section -->
                <div class="nav-section">
                    <div class="nav-section-title">Communication</div>
                    <div class="nav-item">
                        <a href="admin_notifications.php" class="nav-link">
                            <span class="nav-icon">�</span>
                            <span class="nav-text">Notifications</span>
                        </a>
                        <div class="nav-tooltip">Admin Notifications</div>
                    </div>
                    <div class="nav-item">
                        <a href="admin_subscribers.php" class="nav-link">
                            <span class="nav-icon">📧</span>
                            <span class="nav-text">Newsletter Subscribers</span>
                        </a>
                        <div class="nav-tooltip">Manage Newsletter Subscribers</div>
                    </div>
                    <div class="nav-item">
                        <a href="admin_contacts.php" class="nav-link">
                            <span class="nav-icon">📞</span>
                            <span class="nav-text">Contact Messages</span>
                            <?php if ($unreadContactsCount > 0): ?>
                                <span class="nav-badge"><?php echo $unreadContactsCount; ?></span>
                            <?php endif; ?>
                        </a>
                        <div class="nav-tooltip">Manage Contact Form Messages<?php if ($unreadContactsCount > 0) echo " ($unreadContactsCount new)"; ?></div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($role == "MedicalOfficer"): ?>
                <div class="nav-section">
                    <div class="nav-section-title">Medical Services</div>
                    <div class="nav-item">
                        <a href="birth_certificate.php" class="nav-link">
                            <span class="nav-icon">📄</span>
                            <span class="nav-text">Birth Certificates</span>
                        </a>
                        <div class="nav-tooltip">Birth Certificates</div>
                    </div>
                    <div class="nav-item">
                        <a href="search_citizens.php" class="nav-link">
                            <span class="nav-icon">👥</span>
                            <span class="nav-text">Citizens</span>
                        </a>
                        <div class="nav-tooltip">Search Citizens</div>
                    </div>
                    <div class="nav-item">
                        <a href="manage_vaccines.php" class="nav-link">
                            <span class="nav-icon">💉</span>
                            <span class="nav-text">Vaccines</span>
                        </a>
                        <div class="nav-tooltip">Manage Vaccines</div>
                    </div>
                    <div class="nav-item">
                        <a href="manage_vaccination_schedule.php" class="nav-link">
                            <span class="nav-icon">📅</span>
                            <span class="nav-text">Vaccination Schedule</span>
                        </a>
                        <div class="nav-tooltip">Vaccination Schedule</div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($role == "EducationOfficer"): ?>
                <div class="nav-section">
                    <div class="nav-section-title">Education Services</div>
                    <div class="nav-item">
                        <a href="add_education.php" class="nav-link">
                            <span class="nav-icon">🎓</span>
                            <span class="nav-text">Add Education</span>
                        </a>
                        <div class="nav-tooltip">Add Education Record</div>
                    </div>
                    <div class="nav-item">
                        <a href="view_education.php" class="nav-link">
                            <span class="nav-icon">📚</span>
                            <span class="nav-text">View Education</span>
                        </a>
                        <div class="nav-tooltip">View Education Records</div>
                    </div>
                    <div class="nav-item">
                        <a href="manage_subjects.php" class="nav-link">
                            <span class="nav-icon">📖</span>
                            <span class="nav-text">Manage Subjects</span>
                        </a>
                        <div class="nav-tooltip">Manage Subjects</div>
                    </div>
                    <div class="nav-item">
                        <a href="search_citizens.php" class="nav-link">
                            <span class="nav-icon">👥</span>
                            <span class="nav-text">Citizens</span>
                        </a>
                        <div class="nav-tooltip">Search Citizens</div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($role == "Employer"): ?>
                <div class="nav-section">
                    <div class="nav-section-title">Employment Services</div>
                    <div class="nav-item">
                        <a href="add_employment.php" class="nav-link">
                            <span class="nav-icon">💼</span>
                            <span class="nav-text">Add Employment</span>
                        </a>
                        <div class="nav-tooltip">Add Employment Record</div>
                    </div>
                    <div class="nav-item">
                        <a href="view_employment.php" class="nav-link">
                            <span class="nav-icon">👔</span>
                            <span class="nav-text">View Employment</span>
                        </a>
                        <div class="nav-tooltip">View Employment Records</div>
                    </div>
                    <div class="nav-item">
                        <a href="verify_employee.php" class="nav-link">
                            <span class="nav-icon">✅</span>
                            <span class="nav-text">Verify Employee</span>
                        </a>
                        <div class="nav-tooltip">Verify Employee</div>
                    </div>
                    <div class="nav-item">
                        <a href="search_citizens.php" class="nav-link">
                            <span class="nav-icon">👥</span>
                            <span class="nav-text">Citizens</span>
                        </a>
                        <div class="nav-tooltip">Search Citizens</div>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($role == "Citizen"): ?>
                <div class="nav-section">
                    <div class="nav-section-title">Citizen Services</div>
                    <div class="nav-item">
                        <a href="citizen_activities.php" class="nav-link">
                            <span class="nav-icon">📋</span>
                            <span class="nav-text">My Activities</span>
                        </a>
                        <div class="nav-tooltip">My Activities</div>
                    </div>
                    <div class="nav-item">
                        <a href="view_certificates.php" class="nav-link">
                            <span class="nav-icon">📄</span>
                            <span class="nav-text">My Certificates</span>
                        </a>
                        <div class="nav-tooltip">View Certificates</div>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Common Services -->
            <div class="nav-section">
                <div class="nav-section-title">Account</div>
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
    <div class="main-content" id="mainContent">
        <div class="container">
        <div class="header">
            <h1>🇱🇰 NDMS - National Digital Management System</h1>
            <p>Welcome, <strong><?= htmlspecialchars($username) ?></strong> (<?= $role ?>)</p>
            <a href="login.php?logout=1" class="menu-item logout">🚪 Logout</a>
        </div>

        <div class="role-section">
            <?php if ($role == "Admin"): ?>
                <h2>👨‍💼 Administrator Dashboard</h2>
                <p>Full system access and user management</p>
                
                <!-- User Management Section -->
                <div class="dashboard-section">
                    <h3 class="section-title">👥 User Management</h3>
                    <div class="menu-grid">
                        <a href="register.php" class="menu-item">👤 Register New Citizen</a>
                        <a href="search_citizens.php" class="menu-item">🔍 Citizen Directory</a>
                        <a href="manage_users.php" class="menu-item">👥 Manage Users</a>
                        <a href="manage_citizen_accounts.php" class="menu-item">🏠 Manage Citizen Accounts</a>
                    </div>
                </div>
                
                <!-- Health & Medical Section -->
                <div class="dashboard-section">
                    <h3 class="section-title">� Health & Medical</h3>
                    <div class="menu-grid">
                        <a href="manage_vaccines.php" class="menu-item">💉 Manage Vaccines</a>
                        <a href="manage_vaccination_schedule.php" class="menu-item">📅 Vaccination Schedule</a>
                    </div>
                </div>
                
                <!-- Education & Career Section -->
                <div class="dashboard-section">
                    <h3 class="section-title">� Education & Career</h3>
                    <div class="menu-grid">
                        <a href="manage_subjects.php" class="menu-item">� Manage Subjects</a>
                        <a href="career_guidance_form.php" class="menu-item">🎯 Career Guidance System</a>
                        <a href="career_guidance_testing.php" class="menu-item">🧪 Career Testing Lab</a>
                        <a href="admin_career_guidance.php" class="menu-item">👥 Manage Career Students</a>
                    </div>
                </div>
                
                <!-- Communication & Activities Section -->
                <div class="dashboard-section">
                    <h3 class="section-title">💬 Communication & Activities</h3>
                    <div class="menu-grid">
                        <a href="citizen_activities.php" class="menu-item">🏆 Citizen Activities</a>
                        <a href="admin_notifications.php" class="menu-item">🔔 Notifications</a>
                        <a href="admin_subscribers.php" class="menu-item">📧 Newsletter Subscribers</a>
                    </div>
                </div>
                
            <?php elseif ($role == "MedicalOfficer"): ?>
                <h2>👩‍⚕️ Medical Officer Dashboard</h2>
                <p>Newborn registration and birth certificate management</p>
                <div class="menu-grid">
                    <a href="register.php" class="menu-item">🍼 Register New Citizen (Newborn)</a>
                    <a href="birth_certificate.php" class="menu-item">📋 Register Birth Certificate</a>
                    <a href="search_citizens.php" class="menu-item">🔍 Citizen Directory</a>
                    <a href="add_vaccination.php" class="menu-item">💉 Add Vaccination Record</a>
                    <a href="add_medical.php" class="menu-item">🏥 Add Medical Record</a>
                    <a href="manage_vaccines.php" class="menu-item">� Manage Vaccines</a>
                    <a href="manage_vaccination_schedule.php" class="menu-item">📅 Vaccination Schedule</a>
                </div>
                
            <?php elseif ($role == "EducationOfficer"): ?>
                <h2>👩‍🎓 Education Officer Dashboard</h2>
                <p>Education records and certification management</p>
                <div class="menu-grid">
                    <a href="add_education.php" class="menu-item">📝 Add Education Record</a>
                    <a href="view_education.php" class="menu-item">📚 View All Records</a>
                    <a href="manage_subjects.php" class="menu-item">📖 Manage Subjects</a>
                    <a href="search_citizens.php" class="menu-item">🔍 Citizen Directory</a>
                    <a href="career_guidance_form.php" class="menu-item">🎯 Career Guidance System</a>
                    <a href="career_guidance_testing.php" class="menu-item">🧪 Career Testing Lab</a>
                    <a href="admin_career_guidance.php" class="menu-item">👥 Manage Career Students</a>
                </div>
                
            <?php elseif ($role == "Employer"): ?>
                <h2>👔 Employer Dashboard</h2>
                <p>Employee verification and employment records management</p>
                <div class="menu-grid">
                    <a href="add_employment.php" class="menu-item">📝 Add Employment Record</a>
                    <a href="view_employment.php" class="menu-item">💼 View All Records</a>
                    <a href="search_citizens.php" class="menu-item">🔍 Citizen Directory</a>
                    <a href="verify_employee.php" class="menu-item">✅ Verify Employee</a>
                </div>
            <?php endif; ?>
        </div>

    <?php if ($role == "MedicalOfficer"): ?>
        <div class="stats">
            <?php
            $newborns = $conn->query("SELECT COUNT(*) as count FROM Citizens WHERE TIMESTAMPDIFF(YEAR, DOB, CURDATE()) < 1");
            $birthCerts = $conn->query("SELECT COUNT(*) as count FROM BirthCertificates");
            $totalVaccinations = $conn->query("SELECT COUNT(*) as count FROM VaccinationRecords");
            $availableVaccines = $conn->query("SELECT COUNT(*) as count FROM Vaccines");
            $newbornCount = $newborns->fetch_assoc()['count'];
            $certCount = $birthCerts->fetch_assoc()['count'];
            $vaccinationCount = $totalVaccinations->fetch_assoc()['count'];
            $vaccineCount = $availableVaccines->fetch_assoc()['count'];
            ?>
            <div class="stat-box">
                <h3><?= $newbornCount ?></h3>
                <p>Newborns Registered (< 1 year)</p>
            </div>
            <div class="stat-box">
                <h3><?= $certCount ?></h3>
                <p>Birth Certificates Issued</p>
            </div>
            <div class="stat-box">
                <h3><?= $vaccinationCount ?></h3>
                <p>Vaccination Records</p>
            </div>
            <div class="stat-box">
                <h3><?= $vaccineCount ?></h3>
                <p>Available Vaccines</p>
            </div>
        </div>
    <?php elseif ($role == "EducationOfficer"): ?>
        <div class="stats">
            <?php
            $totalRecords = $conn->query("SELECT COUNT(*) as count FROM EducationRecords");
            $totalStudents = $conn->query("SELECT COUNT(DISTINCT CitizenID) as count FROM EducationRecords");
            $examRecords = $conn->query("SELECT COUNT(*) as count FROM EducationRecords WHERE GradeLevel LIKE '%Level%'");
            $recordCount = $totalRecords->fetch_assoc()['count'];
            $studentCount = $totalStudents->fetch_assoc()['count'];
            $examCount = $examRecords->fetch_assoc()['count'];
            ?>
            <div class="stat-box">
                <h3><?= $recordCount ?></h3>
                <p>Total Education Records</p>
            </div>
            <div class="stat-box">
                <h3><?= $studentCount ?></h3>
                <p>Students with Records</p>
            </div>
            <div class="stat-box">
                <h3><?= $examCount ?></h3>
                <p>O/L & A/L Records</p>
            </div>
        </div>
    <?php elseif ($role == "Employer"): ?>
        <div class="stats">
            <?php
            $totalEmploymentRecords = $conn->query("SELECT COUNT(*) as count FROM EmploymentRecords");
            $totalEmployees = $conn->query("SELECT COUNT(DISTINCT CitizenID) as count FROM EmploymentRecords");
            $activeJobs = $conn->query("SELECT COUNT(*) as count FROM EmploymentRecords WHERE EndDate IS NULL");
            $avgSalaryResult = $conn->query("SELECT AVG(Salary) as avg FROM EmploymentRecords WHERE Salary IS NOT NULL");
            
            $empRecordCount = $totalEmploymentRecords->fetch_assoc()['count'];
            $employeeCount = $totalEmployees->fetch_assoc()['count'];
            $activeJobCount = $activeJobs->fetch_assoc()['count'];
            $avgSalary = $avgSalaryResult->fetch_assoc()['avg'];
            ?>
            <div class="stat-box">
                <h3><?= $empRecordCount ?></h3>
                <p>Total Employment Records</p>
            </div>
            <div class="stat-box">
                <h3><?= $employeeCount ?></h3>
                <p>Employees Registered</p>
            </div>
            <div class="stat-box">
                <h3><?= $activeJobCount ?></h3>
                <p>Active Jobs</p>
            </div>
            <div class="stat-box">
                <h3><?= $avgSalary ? 'LKR ' . number_format($avgSalary, 0) : 'N/A' ?></h3>
                <p>Average Salary</p>
            </div>
        </div>
    <?php endif; ?>
        </div> <!-- End container -->
    </div> <!-- End main-content -->

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
        
        // Initialize sidebar state
        document.addEventListener('DOMContentLoaded', function() {
            // Restore sidebar state from localStorage
            const sidebarCollapsed = localStorage.getItem('sidebarCollapsed');
            if (sidebarCollapsed === 'true') {
                document.getElementById('sidebar').classList.add('collapsed');
                document.querySelector('.sidebar-toggle').innerHTML = '▶';
            }
            
            // Set active navigation link
            setActiveNavLink();
            
            // Add smooth animations to stats boxes
            const statBoxes = document.querySelectorAll('.stat-box');
            statBoxes.forEach((box, index) => {
                box.style.animationDelay = `${index * 0.1}s`;
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
