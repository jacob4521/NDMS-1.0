<?php
include "config.php";

// Check if user is logged in and is Admin
if (!isset($_SESSION['UserID']) || $_SESSION['Role'] !== 'Admin') {
    header("Location: login.php");
    exit;
}

// Fetch all users from database
$usersQuery = $conn->query("
    SELECT UserID, Username, Role, CreatedAt 
    FROM Users 
    ORDER BY CreatedAt DESC
");

// Get user statistics
$totalUsers = $conn->query("SELECT COUNT(*) as count FROM Users")->fetch_assoc()['count'];
$adminCount = $conn->query("SELECT COUNT(*) as count FROM Users WHERE Role = 'Admin'")->fetch_assoc()['count'];
$medicalCount = $conn->query("SELECT COUNT(*) as count FROM Users WHERE Role = 'MedicalOfficer'")->fetch_assoc()['count'];
$educationCount = $conn->query("SELECT COUNT(*) as count FROM Users WHERE Role = 'EducationOfficer'")->fetch_assoc()['count'];
$employerCount = $conn->query("SELECT COUNT(*) as count FROM Users WHERE Role = 'Employer'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - NDMS Admin</title>
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
            margin: 0;
            display: flex;
            min-height: 100vh;
        }
        
        .container { 
            max-width: 1400px; 
            margin: 0 auto; 
            width: 100%;
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
        
        .users-section {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            position: relative;
        }
        
        .users-section::before {
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
        
        .section-header p {
            color: var(--text-secondary);
            margin: 0;
            font-size: 16px;
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
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            filter: brightness(1.1);
            color: white;
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
        
        .btn-sm {
            padding: 8px 16px;
            font-size: 12px;
            border-radius: 8px;
        }
        
        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: var(--card-bg);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
        }
        
        .users-table th,
        .users-table td {
            padding: 15px 12px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        .users-table th {
            background: var(--primary-color);
            color: white;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .users-table tr:hover {
            background: var(--light-bg);
        }
        
        .role-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .role-admin { background: var(--danger-color); color: white; }
        .role-medical { background: var(--accent-color); color: white; }
        .role-education { background: var(--secondary-color); color: white; }
        .role-employer { background: var(--warning-color); color: white; }
        
        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .no-users {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
        }
        
        .no-users h4 {
            color: var(--primary-color);
            margin-bottom: 15px;
            font-size: 24px;
        }
        
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
        
        .user-indicator {
            color: var(--accent-color);
            font-size: 12px;
            font-weight: 600;
        }
        
        .btn-disabled {
            background: var(--text-secondary) !important;
            cursor: not-allowed !important;
            opacity: 0.6;
        }
        
        .btn-disabled:hover {
            transform: none !important;
            filter: none !important;
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
            
            .users-table {
                font-size: 14px;
            }
            
            .users-table th,
            .users-table td {
                padding: 10px 8px;
            }
            
            .actions {
                flex-direction: column;
            }
        }
        
        /* Loading Animation */
        .stat-box, .users-section, .nav-bar {
            animation: slideUp 0.3s ease-out;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
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
            <div>
                <h1>👤 User Management System</h1>
                <p>National Digital Management System - Administrative Control Panel</p>
            </div>
            <div>
                <a href="add_user.php" class="btn">➕ Add New User</a>
            </div>
        </div>

        <div class="stats-section">
            <div class="stat-box">
                <h3><?= $totalUsers ?></h3>
                <p>Total Users</p>
            </div>
            <div class="stat-box">
                <h3><?= $adminCount ?></h3>
                <p>Administrators</p>
            </div>
            <div class="stat-box">
                <h3><?= $medicalCount ?></h3>
                <p>Medical Officers</p>
            </div>
            <div class="stat-box">
                <h3><?= $educationCount ?></h3>
                <p>Education Officers</p>
            </div>
            <div class="stat-box">
                <h3><?= $employerCount ?></h3>
                <p>Employers</p>
            </div>
        </div>

        <div class="users-section">
            <div class="section-header">
                <h2>System Users</h2>
                <p style="color: #6c757d; margin: 0;">Manage user accounts and permissions</p>
            </div>

            <?php if ($usersQuery && $usersQuery->num_rows > 0): ?>
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Created Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($user = $usersQuery->fetch_assoc()): ?>
                            <tr>
                                <td><strong>#<?= $user['UserID'] ?></strong></td>
                                <td>
                                    <?= htmlspecialchars($user['Username']) ?>
                                    <?php if ($user['UserID'] == $_SESSION['UserID']): ?>
                                        <span class="user-indicator">(Current User)</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="role-badge role-<?= strtolower(str_replace('Officer', '', $user['Role'])) ?>">
                                        <?= htmlspecialchars($user['Role']) ?>
                                    </span>
                                </td>
                                <td><?= date('M j, Y', strtotime($user['CreatedAt'])) ?></td>
                                <td>
                                    <div class="actions">
                                        <a href="edit_user.php?id=<?= $user['UserID'] ?>" class="btn btn-warning btn-sm">✏️ Edit</a>
                                        <?php if ($user['UserID'] != $_SESSION['UserID']): ?>
                                            <a href="delete_user.php?id=<?= $user['UserID'] ?>" 
                                               class="btn btn-danger btn-sm"
                                               onclick="return confirm('Are you sure you want to delete this user?')">🗑️ Delete</a>
                                        <?php else: ?>
                                            <span class="btn btn-sm btn-disabled">🚫 Cannot Delete Self</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-users">
                    <h4>👤 No Users Found</h4>
                    <p>There are no users in the system. This shouldn't happen in a normal setup.</p>
                    <a href="add_user.php" class="btn">Add First User</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Enhanced interactions and animations
        document.addEventListener('DOMContentLoaded', function() {
            // Add hover effects to table rows
            const tableRows = document.querySelectorAll('.users-table tbody tr');
            tableRows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.01)';
                    this.style.transition = 'transform 0.2s ease';
                });
                
                row.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1)';
                });
            });

            // Enhanced delete confirmation
            const deleteButtons = document.querySelectorAll('.btn-danger');
            deleteButtons.forEach(button => {
                if (button.onclick) {
                    button.addEventListener('click', function(e) {
                        e.preventDefault();
                        const username = this.closest('tr').querySelector('td:nth-child(2)').textContent.trim();
                        if (confirm(`⚠️ Are you sure you want to delete user "${username}"?\n\nThis action cannot be undone.`)) {
                            window.location.href = this.href;
                        }
                    });
                }
            });

            // Add smooth transitions for buttons
            document.querySelectorAll('.btn').forEach(button => {
                if (!button.classList.contains('btn-disabled')) {
                    button.addEventListener('mouseenter', function() {
                        this.style.transform = 'translateY(-2px)';
                        this.style.boxShadow = '0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1)';
                    });
                    
                    button.addEventListener('mouseleave', function() {
                        this.style.transform = 'translateY(0)';
                        this.style.boxShadow = '0 1px 2px 0 rgb(0 0 0 / 0.05)';
                    });
                }
            });
        });
    </script>
        </div> <!-- End container -->
    </div> <!-- End main-content -->
    
    <!-- Include Sidebar JavaScript -->
    <script src="includes/sidebar.js"></script>
</body>
</html>
