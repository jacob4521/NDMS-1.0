<?php
include "config.php";

// Role check - Only Education Officers and Admins can access
if (!isset($_SESSION['UserID']) || !in_array($_SESSION['Role'], ['Admin', 'EducationOfficer'])) {
    header("Location: login.php");
    exit;
}

// Handle form submission for adding subjects
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_subject'])) {
    $subjectName = trim($_POST['subject_name']);
    $subjectCode = trim(strtoupper($_POST['subject_code']));
    $category = trim($_POST['category']);
    $createdBy = $_SESSION['UserID'];
    
    if (!empty($subjectName) && !empty($subjectCode) && !empty($category)) {
        // Check if subject already exists
        $checkStmt = $conn->prepare("SELECT SubjectID FROM Subjects WHERE SubjectName = ? OR SubjectCode = ?");
        $checkStmt->bind_param("ss", $subjectName, $subjectCode);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            $error = "Subject name or code already exists";
        } else {
            $stmt = $conn->prepare("INSERT INTO Subjects (SubjectName, SubjectCode, Category, CreatedBy) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $subjectName, $subjectCode, $category, $createdBy);
            
            if ($stmt->execute()) {
                $msg = "Subject '$subjectName' added successfully!";
            } else {
                $error = "Error adding subject: " . $stmt->error;
            }
            $stmt->close();
        }
        $checkStmt->close();
    } else {
        $error = "Please fill in all required fields";
    }
}

// Handle subject deletion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_subject'])) {
    $subjectId = intval($_POST['subject_id']);
    
    // Check if subject is used in any education records
    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM EducationRecords WHERE SubjectID = ?");
    $checkStmt->bind_param("i", $subjectId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $usage = $checkResult->fetch_assoc();
    
    if ($usage['count'] > 0) {
        $error = "Cannot delete subject: it is used in " . $usage['count'] . " education records";
    } else {
        $stmt = $conn->prepare("DELETE FROM Subjects WHERE SubjectID = ?");
        $stmt->bind_param("i", $subjectId);
        
        if ($stmt->execute()) {
            $msg = "Subject deleted successfully!";
        } else {
            $error = "Error deleting subject: " . $stmt->error;
        }
        $stmt->close();
    }
    $checkStmt->close();
}

// Fetch subjects with usage statistics
$subjectsQuery = $conn->query("
    SELECT 
        s.*, 
        u.Username as CreatedByName,
        COUNT(er.EduID) as UsageCount
    FROM Subjects s 
    LEFT JOIN Users u ON s.CreatedBy = u.UserID 
    LEFT JOIN EducationRecords er ON s.SubjectID = er.SubjectID
    GROUP BY s.SubjectID
    ORDER BY s.Category, s.SubjectName
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subjects - NDMS</title>
    <link rel="stylesheet" href="includes/sidebar.css">
    <style>
        /* NDMS Modern Theme */
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
            padding: 20px;
        }
        
        .container { 
            max-width: 1200px; 
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
        
        .header h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 8px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .header p {
            font-size: 18px;
            opacity: 0.9;
        }
        
        .nav {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .nav a {
            color: white;
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--gradient-bg);
        }
        
        .nav a:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            filter: brightness(1.1);
        }
        
        .content-section { 
            background: var(--card-bg);
            padding: 30px;
            border-radius: 20px;
            margin: 25px 0;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
        }
        
        .section-title {
            color: var(--primary-color);
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 25px;
            padding-bottom: 10px;
            border-bottom: 3px solid var(--secondary-color);
        }
        
        .form-row { 
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .form-group { 
            margin-bottom: 20px;
        }
        
        .form-group label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: 700;
            color: var(--text-primary);
        }
        
        .form-group input, 
        .form-group select { 
            width: 100%; 
            padding: 12px 16px; 
            border: 2px solid var(--border-color); 
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: white;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .btn { 
            background: var(--gradient-bg);
            color: white;
            padding: 14px 28px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn:hover { 
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            filter: brightness(1.1);
        }
        
        .btn-danger {
            background: var(--danger-color);
            padding: 8px 16px;
            font-size: 12px;
        }
        
        .btn-danger:hover {
            background: #dc2626;
        }
        
        .success { 
            color: white;
            background: var(--accent-color);
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
            box-shadow: var(--shadow-md);
        }
        
        .error { 
            color: white;
            background: var(--danger-color);
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
            box-shadow: var(--shadow-md);
        }
        
        .subjects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .subject-card {
            background: var(--light-bg);
            border: 2px solid var(--border-color);
            border-radius: 15px;
            padding: 20px;
            transition: all 0.3s ease;
        }
        
        .subject-card:hover {
            border-color: var(--secondary-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .subject-name {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 8px;
        }
        
        .subject-code {
            font-family: 'Courier New', monospace;
            background: var(--secondary-color);
            color: white;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 10px;
        }
        
        .subject-category {
            color: var(--text-secondary);
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .subject-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid var(--border-color);
        }
        
        .usage-count {
            color: var(--accent-color);
            font-weight: 600;
            font-size: 14px;
        }
        
        .category-filter {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 8px 16px;
            border: 2px solid var(--border-color);
            background: white;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
        }
        
        .filter-btn.active,
        .filter-btn:hover {
            border-color: var(--secondary-color);
            background: var(--secondary-color);
            color: white;
        }
        
        @media (max-width: 768px) {
            .subjects-grid {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="container">
            <div class="header">
                <h1>📖 Subject Management</h1>
                <p>Manage educational subjects for the NDMS system</p>
            </div>
            
            <div class="nav">
                <a href="dashboard.php">🏠 Dashboard</a>
                <a href="add_education.php">📚 Add Education Record</a>
                <a href="view_education.php">📋 View All Records</a>
                <a href="manage_subjects.php">📖 Manage Subjects</a>
            </div>

            <?php if(isset($msg)): ?>
                <div class="success">✅ <?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>
            
            <?php if(isset($error)): ?>
                <div class="error">❌ <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <!-- Add Subject Form -->
            <div class="content-section">
                <h2 class="section-title">➕ Add New Subject</h2>
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="subject_name">Subject Name:</label>
                            <input type="text" name="subject_name" id="subject_name" required placeholder="e.g., Advanced Mathematics">
                        </div>
                        <div class="form-group">
                            <label for="subject_code">Subject Code:</label>
                            <input type="text" name="subject_code" id="subject_code" required placeholder="e.g., ADV_MATH">
                        </div>
                        <div class="form-group">
                            <label for="category">Category:</label>
                            <select name="category" id="category" required>
                                <option value="">-- Select Category --</option>
                                <option value="Core">Core</option>
                                <option value="Languages">Languages</option>
                                <option value="Science">Science</option>
                                <option value="Social Studies">Social Studies</option>
                                <option value="Religion">Religion</option>
                                <option value="Technology">Technology</option>
                                <option value="Creative">Creative</option>
                                <option value="Physical">Physical</option>
                                <option value="Commerce">Commerce</option>
                                <option value="Technical">Technical</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="add_subject" class="btn">➕ Add Subject</button>
                </form>
            </div>

            <!-- Subjects List -->
            <div class="content-section">
                <h2 class="section-title">📚 Existing Subjects</h2>
                
                <!-- Category Filter -->
                <div class="category-filter">
                    <button class="filter-btn active" onclick="filterByCategory('all')">All Categories</button>
                    <button class="filter-btn" onclick="filterByCategory('Core')">Core</button>
                    <button class="filter-btn" onclick="filterByCategory('Languages')">Languages</button>
                    <button class="filter-btn" onclick="filterByCategory('Science')">Science</button>
                    <button class="filter-btn" onclick="filterByCategory('Technology')">Technology</button>
                    <button class="filter-btn" onclick="filterByCategory('Commerce')">Commerce</button>
                    <button class="filter-btn" onclick="filterByCategory('Other')">Other</button>
                </div>
                
                <div class="subjects-grid">
                    <?php while ($subject = $subjectsQuery->fetch_assoc()): ?>
                        <div class="subject-card" data-category="<?= htmlspecialchars($subject['Category']) ?>">
                            <div class="subject-name"><?= htmlspecialchars($subject['SubjectName']) ?></div>
                            <div class="subject-code"><?= htmlspecialchars($subject['SubjectCode']) ?></div>
                            <div class="subject-category">📂 <?= htmlspecialchars($subject['Category']) ?></div>
                            
                            <div class="subject-stats">
                                <span class="usage-count">
                                    📊 Used in <?= $subject['UsageCount'] ?> records
                                </span>
                                
                                <?php if ($subject['UsageCount'] == 0): ?>
                                    <form method="POST" style="display: inline;" 
                                          onsubmit="return confirm('Are you sure you want to delete this subject?')">
                                        <input type="hidden" name="subject_id" value="<?= $subject['SubjectID'] ?>">
                                        <button type="submit" name="delete_subject" class="btn-danger">🗑️ Delete</button>
                                    </form>
                                <?php else: ?>
                                    <span style="color: var(--text-secondary); font-size: 12px;">Cannot delete (in use)</span>
                                <?php endif; ?>
                            </div>
                            
                            <div style="margin-top: 10px; font-size: 12px; color: var(--text-secondary);">
                                Created by: <?= htmlspecialchars($subject['CreatedByName'] ?? 'Unknown') ?><br>
                                Created: <?= date('M j, Y', strtotime($subject['CreatedAt'])) ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Category filtering
        function filterByCategory(category) {
            const cards = document.querySelectorAll('.subject-card');
            const buttons = document.querySelectorAll('.filter-btn');
            
            // Update active button
            buttons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            // Filter cards
            cards.forEach(card => {
                if (category === 'all' || card.dataset.category === category) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
        
        // Auto-format subject code
        document.getElementById('subject_code').addEventListener('input', function() {
            this.value = this.value.toUpperCase().replace(/[^A-Z0-9_]/g, '');
        });
    </script>
    
    <script src="includes/sidebar.js"></script>
</body>
</html>
