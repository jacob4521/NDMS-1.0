<?php
include "config.php";

// Check if user is logged in and has access (Admin or Education Officer)
if (!isset($_SESSION['UserID']) || !in_array($_SESSION['Role'], ['Admin', 'EducationOfficer'])) {
    header("Location: login.php");
    exit();
}

// Pagination
$records_per_page = 15;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $records_per_page;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$district_filter = isset($_GET['district']) ? trim($_GET['district']) : '';

// Build WHERE clause
$where_conditions = [];
$params = [];
$param_types = '';

if (!empty($search)) {
    $where_conditions[] = "(cs.FullName LIKE ? OR cs.Email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $param_types .= 'ss';
}

if (!empty($district_filter)) {
    $where_conditions[] = "cs.District = ?";
    $params[] = $district_filter;
    $param_types .= 's';
}

$where_clause = '';
if (!empty($where_conditions)) {
    $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
}

// Get total count
$count_query = "SELECT COUNT(*) as total FROM career_students cs $where_clause";
if (!empty($params)) {
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->bind_param($param_types, ...$params);
    $count_stmt->execute();
    $total_records = $count_stmt->get_result()->fetch_assoc()['total'];
} else {
    $total_records = $conn->query($count_query)->fetch_assoc()['total'];
}

$total_pages = ceil($total_records / $records_per_page);

// Get students with their suggestions
$main_query = "
    SELECT 
        cs.*,
        csg.PrimarySuggestion,
        csg.MatchScore,
        csg.GeneratedAt as SuggestionDate,
        GROUP_CONCAT(DISTINCT ci.InterestArea ORDER BY ci.Priority) as Interests,
        COUNT(DISTINCT olr.ResultID) as SubjectCount,
        AVG(CASE 
            WHEN olr.Grade = 'A' THEN 5
            WHEN olr.Grade = 'B' THEN 4
            WHEN olr.Grade = 'C' THEN 3
            WHEN olr.Grade = 'S' THEN 2
            WHEN olr.Grade = 'W' THEN 1
            ELSE 0 END) as AverageGrade
    FROM career_students cs
    LEFT JOIN career_suggestions csg ON cs.StudentID = csg.StudentID
    LEFT JOIN career_interests ci ON cs.StudentID = ci.StudentID
    LEFT JOIN ol_results olr ON cs.StudentID = olr.StudentID
    $where_clause
    GROUP BY cs.StudentID
    ORDER BY cs.CreatedAt DESC
    LIMIT $records_per_page OFFSET $offset
";

if (!empty($params)) {
    $stmt = $conn->prepare($main_query);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $students_result = $stmt->get_result();
} else {
    $students_result = $conn->query($main_query);
}

// Get statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_students,
        COUNT(CASE WHEN csg.StudentID IS NOT NULL THEN 1 END) as with_suggestions,
        COUNT(CASE WHEN cs.CreatedAt >= CURDATE() THEN 1 END) as today_submissions,
        COUNT(CASE WHEN cs.CreatedAt >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as week_submissions
    FROM career_students cs
    LEFT JOIN career_suggestions csg ON cs.StudentID = csg.StudentID
";
$stats = $conn->query($stats_query)->fetch_assoc();

// Get districts for filter
$districts_query = "SELECT DISTINCT District FROM career_students WHERE District IS NOT NULL AND District != '' ORDER BY District";
$districts_result = $conn->query($districts_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Career Guidance Management - NDMS</title>
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
        }

        .header h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--card-bg);
            padding: 25px;
            border-radius: 15px;
            border-left: 4px solid var(--accent-color);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .stat-card.warning { border-left-color: var(--warning-color); }
        .stat-card.primary { border-left-color: var(--primary-color); }
        .stat-card.success { border-left-color: var(--accent-color); }

        .stat-card h3 {
            font-size: 36px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 8px;
        }

        .controls {
            background: var(--card-bg);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            display: flex;
            gap: 20px;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-box {
            display: flex;
            gap: 10px;
            flex: 1;
            min-width: 300px;
        }

        .search-box input,
        .search-box select {
            padding: 10px 15px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
        }

        .search-box input {
            flex: 1;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-success {
            background: var(--accent-color);
            color: white;
        }

        .btn-secondary {
            background: var(--light-bg);
            color: var(--text-primary);
            border: 2px solid var(--border-color);
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .table-container {
            background: var(--card-bg);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
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

        .suggestion-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-medicine { background: #dcfce7; color: #166534; }
        .badge-engineering { background: #dbeafe; color: #1d4ed8; }
        .badge-ict { background: #f3e8ff; color: #7c3aed; }
        .badge-commerce { background: #fef3c7; color: #92400e; }
        .badge-arts { background: #fed7d7; color: #c53030; }
        .badge-default { background: var(--light-bg); color: var(--text-secondary); }

        .grade-display {
            font-family: monospace;
            font-weight: 600;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 12px;
        }

        .grade-excellent { background: #10b981; color: white; }
        .grade-good { background: #3b82f6; color: white; }
        .grade-average { background: #f59e0b; color: white; }
        .grade-poor { background: #ef4444; color: white; }

        .interests-display {
            font-size: 12px;
            color: var(--text-secondary);
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin-top: 30px;
        }

        .page-btn {
            padding: 10px 16px;
            border: 2px solid var(--border-color);
            background: white;
            color: var(--text-primary);
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .page-btn:hover:not(.disabled):not(.active) {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .page-btn.active {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }

        .page-btn.disabled {
            background: #f3f4f6;
            color: #9ca3af;
            cursor: not-allowed;
        }

        .page-info {
            color: var(--text-secondary);
            font-size: 14px;
            margin-left: 20px;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 80px 15px 15px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .controls {
                flex-direction: column;
                align-items: stretch;
            }

            .search-box {
                min-width: auto;
            }

            .table-container {
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="header">
            <h1>🎓 Career Guidance Management</h1>
            <p>Monitor and manage student career guidance assessments</p>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card success">
                <h3><?= number_format($stats['total_students']) ?></h3>
                <p>Total Students Assessed</p>
            </div>
            
            <div class="stat-card primary">
                <h3><?= number_format($stats['with_suggestions']) ?></h3>
                <p>With Career Suggestions</p>
            </div>
            
            <div class="stat-card warning">
                <h3><?= number_format($stats['today_submissions']) ?></h3>
                <p>Today's Submissions</p>
            </div>
            
            <div class="stat-card success">
                <h3><?= number_format($stats['week_submissions']) ?></h3>
                <p>This Week's Submissions</p>
            </div>
        </div>

        <!-- Controls -->
        <div class="controls">
            <div class="search-box">
                <form method="GET" style="display: flex; gap: 10px; width: 100%;">
                    <input type="text" name="search" placeholder="Search by name or email..." value="<?= htmlspecialchars($search) ?>">
                    
                    <select name="district">
                        <option value="">All Districts</option>
                        <?php while ($district = $districts_result->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($district['District']) ?>" <?= $district_filter === $district['District'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($district['District']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    
                    <button type="submit" class="btn btn-primary">🔍 Search</button>
                    
                    <?php if ($search || $district_filter): ?>
                        <a href="admin_career_guidance.php" class="btn btn-secondary">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <div>
                <a href="career_guidance_form.php" class="btn btn-success">➕ New Assessment</a>
                <a href="?export=csv<?= $search || $district_filter ? '&search=' . urlencode($search) . '&district=' . urlencode($district_filter) : '' ?>" class="btn btn-primary">📥 Export CSV</a>
            </div>
        </div>

        <!-- Students Table -->
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>District</th>
                        <th>Interests</th>
                        <th>Average Grade</th>
                        <th>Primary Suggestion</th>
                        <th>Match Score</th>
                        <th>Assessment Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($students_result->num_rows === 0): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px; color: var(--text-secondary);">
                                <div style="font-size: 48px; margin-bottom: 16px;">📝</div>
                                <div style="font-size: 18px; font-weight: 500;">No career assessments found</div>
                                <div style="font-size: 14px; margin-top: 8px;">
                                    <?= $search || $district_filter ? 'Try adjusting your search criteria.' : 'Career guidance submissions will appear here.' ?>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php while ($student = $students_result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 600;"><?= htmlspecialchars($student['FullName']) ?></div>
                                    <?php if ($student['Email']): ?>
                                        <div style="font-size: 12px; color: var(--text-secondary);"><?= htmlspecialchars($student['Email']) ?></div>
                                    <?php endif; ?>
                                    <div style="font-size: 12px; color: var(--text-secondary);">Age: <?= $student['Age'] ?></div>
                                </td>
                                
                                <td><?= htmlspecialchars($student['District'] ?: 'Not specified') ?></td>
                                
                                <td>
                                    <div class="interests-display" title="<?= htmlspecialchars($student['Interests'] ?: 'No interests recorded') ?>">
                                        <?= htmlspecialchars($student['Interests'] ?: 'No interests') ?>
                                    </div>
                                </td>
                                
                                <td>
                                    <?php 
                                    $avg = $student['AverageGrade'];
                                    $gradeClass = 'grade-poor';
                                    $gradeText = 'N/A';
                                    
                                    if ($avg) {
                                        if ($avg >= 4.5) {
                                            $gradeClass = 'grade-excellent';
                                            $gradeText = number_format($avg, 1);
                                        } elseif ($avg >= 3.5) {
                                            $gradeClass = 'grade-good';
                                            $gradeText = number_format($avg, 1);
                                        } elseif ($avg >= 2.5) {
                                            $gradeClass = 'grade-average';
                                            $gradeText = number_format($avg, 1);
                                        } else {
                                            $gradeClass = 'grade-poor';
                                            $gradeText = number_format($avg, 1);
                                        }
                                    }
                                    ?>
                                    <span class="grade-display <?= $gradeClass ?>"><?= $gradeText ?></span>
                                    <div style="font-size: 11px; color: var(--text-secondary); margin-top: 2px;">
                                        <?= $student['SubjectCount'] ?> subjects
                                    </div>
                                </td>
                                
                                <td>
                                    <?php if ($student['PrimarySuggestion']): ?>
                                        <?php
                                        $suggestion = $student['PrimarySuggestion'];
                                        $badgeClass = 'badge-default';
                                        
                                        if (stripos($suggestion, 'medicine') !== false || stripos($suggestion, 'medical') !== false) {
                                            $badgeClass = 'badge-medicine';
                                        } elseif (stripos($suggestion, 'engineering') !== false) {
                                            $badgeClass = 'badge-engineering';
                                        } elseif (stripos($suggestion, 'technology') !== false || stripos($suggestion, 'IT') !== false || stripos($suggestion, 'computer') !== false) {
                                            $badgeClass = 'badge-ict';
                                        } elseif (stripos($suggestion, 'business') !== false || stripos($suggestion, 'commerce') !== false || stripos($suggestion, 'accounting') !== false) {
                                            $badgeClass = 'badge-commerce';
                                        } elseif (stripos($suggestion, 'arts') !== false || stripos($suggestion, 'law') !== false || stripos($suggestion, 'teaching') !== false) {
                                            $badgeClass = 'badge-arts';
                                        }
                                        ?>
                                        <span class="suggestion-badge <?= $badgeClass ?>">
                                            <?= htmlspecialchars($suggestion) ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: var(--text-secondary); font-style: italic;">No suggestion yet</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td>
                                    <?php if ($student['MatchScore']): ?>
                                        <strong style="color: var(--accent-color);"><?= number_format($student['MatchScore'], 1) ?>%</strong>
                                    <?php else: ?>
                                        <span style="color: var(--text-secondary);">-</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td>
                                    <div><?= date('M j, Y', strtotime($student['CreatedAt'])) ?></div>
                                    <div style="font-size: 12px; color: var(--text-secondary);">
                                        <?= date('g:i A', strtotime($student['CreatedAt'])) ?>
                                    </div>
                                </td>
                                
                                <td>
                                    <div style="display: flex; gap: 5px;">
                                        <a href="career_suggestions.php?student_id=<?= $student['StudentID'] ?>" 
                                           class="btn btn-primary" style="padding: 6px 12px; font-size: 12px;" 
                                           title="View Career Suggestions">
                                            👁️ View
                                        </a>
                                        
                                        <?php if (!$student['PrimarySuggestion']): ?>
                                            <a href="generate_suggestions.php?student_id=<?= $student['StudentID'] ?>" 
                                               class="btn btn-success" style="padding: 6px 12px; font-size: 12px;" 
                                               title="Generate Suggestions">
                                                🚀 Generate
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?><?= $search || $district_filter ? '&search=' . urlencode($search) . '&district=' . urlencode($district_filter) : '' ?>" class="page-btn">&laquo; Prev</a>
                <?php else: ?>
                    <span class="page-btn disabled">&laquo; Prev</span>
                <?php endif; ?>
                
                <?php
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                for ($i = $start_page; $i <= $end_page; $i++):
                ?>
                    <a href="?page=<?= $i ?><?= $search || $district_filter ? '&search=' . urlencode($search) . '&district=' . urlencode($district_filter) : '' ?>" 
                       class="page-btn <?= $i == $page ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?><?= $search || $district_filter ? '&search=' . urlencode($search) . '&district=' . urlencode($district_filter) : '' ?>" class="page-btn">Next &raquo;</a>
                <?php else: ?>
                    <span class="page-btn disabled">Next &raquo;</span>
                <?php endif; ?>
                
                <div class="page-info">
                    Showing <?= ($offset + 1) ?> to <?= min($offset + $records_per_page, $total_records) ?> of <?= number_format($total_records) ?> entries
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="includes/sidebar.js"></script>
</body>
</html>
