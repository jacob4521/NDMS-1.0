<?php
include "config.php";

// Check if user is logged in and has access
if (!isset($_SESSION['UserID']) || !in_array($_SESSION['Role'], ['Admin', 'EducationOfficer'])) {
    header("Location: login.php");
    exit();
}

// Get dashboard statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_students,
        COUNT(CASE WHEN csg.StudentID IS NOT NULL THEN 1 END) as with_suggestions,
        COUNT(CASE WHEN cs.CreatedAt >= CURDATE() THEN 1 END) as today_submissions,
        COUNT(CASE WHEN cs.CreatedAt >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as week_submissions,
        COUNT(CASE WHEN cs.CreatedAt >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as month_submissions
    FROM career_students cs
    LEFT JOIN career_suggestions csg ON cs.StudentID = csg.StudentID
";
$stats = $conn->query($stats_query)->fetch_assoc();

// Get recent submissions
$recent_query = "
    SELECT cs.*, csg.PrimarySuggestion, csg.MatchScore
    FROM career_students cs
    LEFT JOIN career_suggestions csg ON cs.StudentID = csg.StudentID
    ORDER BY cs.CreatedAt DESC
    LIMIT 5
";
$recent_students = $conn->query($recent_query);

// Get top career suggestions
$top_careers_query = "
    SELECT PrimarySuggestion, COUNT(*) as count
    FROM career_suggestions
    WHERE PrimarySuggestion IS NOT NULL
    GROUP BY PrimarySuggestion
    ORDER BY count DESC
    LIMIT 5
";
$top_careers = $conn->query($top_careers_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Career Guidance Dashboard - NDMS</title>
    <link rel="stylesheet" href="includes/sidebar.css">
    <style>
        /* NDMS Theme */
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
        }

        .main-content {
            margin-left: 280px;
            transition: margin-left 0.3s ease;
            padding: 20px;
            min-height: 100vh;
            background: var(--light-bg);
        }

        .sidebar.collapsed + .main-content {
            margin-left: 80px;
        }

        .header {
            background: var(--gradient-bg);
            color: white;
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
        }

        .header h1 {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--card-bg);
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border-top: 4px solid var(--accent-color);
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .stat-card.primary { border-top-color: var(--primary-color); }
        .stat-card.warning { border-top-color: var(--warning-color); }
        .stat-card.success { border-top-color: var(--accent-color); }

        .stat-card .icon {
            font-size: 48px;
            margin-bottom: 15px;
        }

        .stat-card h3 {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 8px;
        }

        .stat-card p {
            color: var(--text-secondary);
            font-weight: 500;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .action-card {
            background: var(--card-bg);
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            text-decoration: none;
            color: var(--text-primary);
        }

        .action-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            color: var(--primary-color);
        }

        .action-card .icon {
            font-size: 32px;
            margin-bottom: 10px;
            display: block;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .content-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .content-card h3 {
            color: var(--primary-color);
            margin-bottom: 20px;
            font-size: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .recent-item {
            padding: 15px 0;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .recent-item:last-child {
            border-bottom: none;
        }

        .recent-item .name {
            font-weight: 600;
            color: var(--text-primary);
        }

        .recent-item .details {
            font-size: 12px;
            color: var(--text-secondary);
        }

        .recent-item .badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 500;
            background: var(--light-bg);
            color: var(--text-secondary);
        }

        .career-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .career-item:last-child {
            border-bottom: none;
        }

        .career-name {
            font-weight: 500;
            color: var(--text-primary);
        }

        .career-count {
            background: var(--primary-color);
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 80px 15px 15px;
            }

            .content-grid {
                grid-template-columns: 1fr;
            }

            .dashboard-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .quick-actions {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="header">
            <h1>🎓 Career Guidance Dashboard</h1>
            <p>Monitor student career assessments and guidance performance</p>
        </div>

        <!-- Statistics Dashboard -->
        <div class="dashboard-grid">
            <div class="stat-card success">
                <div class="icon">👥</div>
                <h3><?= number_format($stats['total_students']) ?></h3>
                <p>Total Students Assessed</p>
            </div>
            
            <div class="stat-card primary">
                <div class="icon">🎯</div>
                <h3><?= number_format($stats['with_suggestions']) ?></h3>
                <p>With Career Suggestions</p>
            </div>
            
            <div class="stat-card warning">
                <div class="icon">📝</div>
                <h3><?= number_format($stats['today_submissions']) ?></h3>
                <p>Today's Submissions</p>
            </div>
            
            <div class="stat-card success">
                <div class="icon">📊</div>
                <h3><?= number_format($stats['week_submissions']) ?></h3>
                <p>This Week</p>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="career_guidance_form.php" class="action-card">
                <span class="icon">➕</span>
                <div>New Assessment</div>
            </a>
            
            <a href="admin_career_guidance.php" class="action-card">
                <span class="icon">📋</span>
                <div>Manage Students</div>
            </a>
            
            <a href="admin_career_guidance.php?export=csv" class="action-card">
                <span class="icon">📥</span>
                <div>Export Data</div>
            </a>
            
            <a href="career_paths_admin.php" class="action-card">
                <span class="icon">🛤️</span>
                <div>Manage Career Paths</div>
            </a>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Recent Submissions -->
            <div class="content-card">
                <h3>
                    <span>🕒</span>
                    Recent Submissions
                </h3>
                
                <?php if ($recent_students->num_rows === 0): ?>
                    <div style="text-align: center; padding: 40px; color: var(--text-secondary);">
                        <div style="font-size: 32px; margin-bottom: 10px;">📝</div>
                        <div>No submissions yet</div>
                    </div>
                <?php else: ?>
                    <?php while ($student = $recent_students->fetch_assoc()): ?>
                        <div class="recent-item">
                            <div>
                                <div class="name"><?= htmlspecialchars($student['FullName']) ?></div>
                                <div class="details">
                                    <?= date('M j, Y g:i A', strtotime($student['CreatedAt'])) ?>
                                    <?php if ($student['District']): ?>
                                        • <?= htmlspecialchars($student['District']) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div>
                                <?php if ($student['PrimarySuggestion']): ?>
                                    <div class="badge" style="background: var(--accent-color); color: white;">
                                        Suggested: <?= htmlspecialchars($student['PrimarySuggestion']) ?>
                                    </div>
                                    <?php if ($student['MatchScore']): ?>
                                        <div class="details" style="text-align: right; margin-top: 4px;">
                                            Match: <?= number_format($student['MatchScore'], 1) ?>%
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <div class="badge" style="background: var(--warning-color); color: white;">
                                        Pending Analysis
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                    
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="admin_career_guidance.php" style="color: var(--primary-color); text-decoration: none; font-weight: 500;">
                            View All Students →
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Top Career Suggestions -->
            <div class="content-card">
                <h3>
                    <span>🏆</span>
                    Popular Career Paths
                </h3>
                
                <?php if ($top_careers->num_rows === 0): ?>
                    <div style="text-align: center; padding: 40px; color: var(--text-secondary);">
                        <div style="font-size: 32px; margin-bottom: 10px;">🎯</div>
                        <div>No career suggestions yet</div>
                    </div>
                <?php else: ?>
                    <?php while ($career = $top_careers->fetch_assoc()): ?>
                        <div class="career-item">
                            <div class="career-name">
                                <?= htmlspecialchars($career['PrimarySuggestion']) ?>
                            </div>
                            <div class="career-count">
                                <?= $career['count'] ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                    
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="career_analytics.php" style="color: var(--primary-color); text-decoration: none; font-weight: 500;">
                            View Analytics →
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="includes/sidebar.js"></script>
</body>
</html>
