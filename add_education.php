<?php
include "config.php";

// Role check - Only Education Officers and Admins can access
if (!isset($_SESSION['UserID']) || !in_array($_SESSION['Role'], ['Admin', 'EducationOfficer'])) {
    header("Location: login.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle subject addition
    if (isset($_POST['add_subject'])) {
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
            $error = "Please fill in all required fields for subject";
        }
    }
    // Handle education record addition
    elseif (isset($_POST['add_education'])) {
        $citizenEID = $_POST['citizen_eid'];
        $schoolName = $_POST['school_name'];
        $gradeLevel = $_POST['grade_level'];
        $examName = $_POST['exam_name'];
        $subjectID = !empty($_POST['subject_id']) ? $_POST['subject_id'] : NULL;
        $result = $_POST['result'];
        $marksObtained = $_POST['marks_obtained'];
        $recordDate = $_POST['record_date'];
        $registeredBy = $_SESSION['UserID'];

        // Find citizen by eID
        $citizenStmt = $conn->prepare("SELECT CitizenID FROM Citizens WHERE Citizen_eID = ?");
        $citizenStmt->bind_param("s", $citizenEID);
        $citizenStmt->execute();
        $citizenResult = $citizenStmt->get_result();
        
        if ($citizenResult->num_rows == 0) {
            $error = "Citizen with eID '$citizenEID' not found.";
        } else {
            $citizen = $citizenResult->fetch_assoc();
            $citizenId = $citizen['CitizenID'];
            
            $stmt = $conn->prepare("INSERT INTO EducationRecords (CitizenID, SchoolName, GradeLevel, ExamName, SubjectID, Result, MarksObtained, RecordDate, RegisteredBy) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssisisi", $citizenId, $schoolName, $gradeLevel, $examName, $subjectID, $result, $marksObtained, $recordDate, $registeredBy);
            
            if ($stmt->execute()) {
                $msg = "Education record added successfully for Citizen eID: $citizenEID";
            } else {
                $error = "Error adding record: " . $stmt->error;
            }
            $stmt->close();
        }
        $citizenStmt->close();
    }
}

// Get recent education records
$recentRecords = $conn->query("
    SELECT er.*, c.FirstName, c.LastName, c.Citizen_eID, s.SubjectName, s.Category as SubjectCategory, u.Username as RegisteredByName
    FROM EducationRecords er
    JOIN Citizens c ON er.CitizenID = c.CitizenID
    LEFT JOIN Subjects s ON er.SubjectID = s.SubjectID
    JOIN Users u ON er.RegisteredBy = u.UserID
    ORDER BY er.RegisteredAt DESC
    LIMIT 10
");
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="includes/sidebar.css">
    <title>Education Records Management - NDMS</title>
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
            color: var(--primary-color);
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            border: 1px solid transparent;
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--gradient-bg);
            color: white;
        }
        
        .nav a:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            filter: brightness(1.1);
        }
        
        .form-section { 
            background: var(--card-bg);
            padding: 30px;
            border-radius: 20px;
            margin: 25px 0;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            position: relative;
        }
        
        .form-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-bg);
            border-radius: 20px 20px 0 0;
        }
        
        .section-title {
            color: var(--primary-color);
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 25px;
            padding-bottom: 10px;
            border-bottom: 3px solid var(--secondary-color);
            display: flex;
            align-items: center;
            gap: 12px;
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
            font-size: 14px;
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
            font-family: inherit;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-group small { 
            color: var(--text-secondary); 
            font-size: 12px; 
            margin-top: 5px;
            display: block;
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
            box-shadow: var(--shadow-sm);
        }
        
        .btn:hover { 
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            filter: brightness(1.1);
        }
        
        .success { 
            color: white;
            background: var(--accent-color);
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
        }
        
        .success::before {
            content: '✅';
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 24px;
            opacity: 0.7;
        }
        
        .error { 
            color: white;
            background: var(--danger-color);
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
        }
        
        .error::before {
            content: '❌';
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 24px;
            opacity: 0.7;
        }
        
        .records-section { 
            margin-top: 30px; 
        }
        
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 20px;
            background: var(--card-bg);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
        }
        
        th, td { 
            padding: 15px 12px; 
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        th { 
            background: var(--gradient-bg);
            color: white;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        tr:hover {
            background: var(--light-bg);
        }
        
        .grade-badge {
            background: var(--secondary-color);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .result-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .result-badge.pass {
            background: var(--accent-color);
            color: white;
        }
        
        .result-badge.fail {
            background: var(--danger-color);
            color: white;
        }
        
        /* Autocomplete Styles */
        .autocomplete-container { 
            position: relative; 
        }
        
        .suggestions-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: var(--card-bg);
            border: 2px solid var(--border-color);
            border-top: none;
            border-radius: 0 0 10px 10px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
            box-shadow: var(--shadow-md);
        }
        
        .suggestion-item {
            padding: 12px 16px;
            cursor: pointer;
            border-bottom: 1px solid var(--border-color);
            transition: all 0.2s ease;
        }
        
        .suggestion-item:hover {
            background-color: var(--light-bg);
            border-left: 4px solid var(--secondary-color);
        }
        
        .suggestion-item:last-child {
            border-bottom: none;
        }
        
        .suggestion-eid {
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .suggestion-name {
            color: var(--text-secondary);
            font-size: 14px;
            margin-top: 4px;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }
            
            .header h1 {
                font-size: 24px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .nav {
                flex-direction: column;
            }
            
            table {
                font-size: 14px;
            }
            
            th, td {
                padding: 10px 8px;
            }
        }
        
        /* Loading Animation */
        .form-section {
            animation: slideUp 0.3s ease-out;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .suggestions-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 4px 4px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }
        .suggestion-item {
            padding: 10px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
        }
        .suggestion-item:hover {
            background-color: #f8f9fa;
        }
        .suggestion-item:last-child {
            border-bottom: none;
        }
        .suggestion-eid {
            font-weight: bold;
            color: #007cba;
        }
        .suggestion-name {
            color: #666;
            font-size: 14px;
        }
        
        /* Subject Badge Styles */
        .subject-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .subject-badge.core { background: var(--primary-color); color: white; }
        .subject-badge.languages { background: var(--secondary-color); color: white; }
        .subject-badge.science { background: var(--accent-color); color: white; }
        .subject-badge.social-studies { background: var(--warning-color); color: white; }
        .subject-badge.religion { background: #8b5cf6; color: white; }
        .subject-badge.technology { background: #06b6d4; color: white; }
        .subject-badge.creative { background: #f59e0b; color: white; }
        .subject-badge.physical { background: #10b981; color: white; }
        .subject-badge.commerce { background: #6366f1; color: white; }
        
        .text-muted {
            color: var(--text-secondary);
            font-style: italic;
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
    <div class="container">
        <div class="header">
            <h1>📚 Education Records Management</h1>
            <p>National Digital Management System - Academic Achievement Tracking</p>
        </div>
        
        <div class="nav">
            <a href="dashboard.php">🏠 Dashboard</a>
            <a href="add_education.php">📚 Add Education Record</a>
            <a href="view_education.php">📋 View All Records</a>
            <a href="manage_subjects.php">📖 Manage Subjects</a>
            <a href="search_citizens.php">🔍 Search Citizens</a>
        </div>

        <div class="form-section">
            <h3 class="section-title">➕ Add New Education Record</h3>
            
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="citizen_eid">Citizen eID:</label>
                        <div class="autocomplete-container">
                            <input type="text" name="citizen_eid" id="citizen_eid" required placeholder="Type citizen eID or name..." autocomplete="off">
                            <div id="suggestions" class="suggestions-dropdown"></div>
                        </div>
                        <small>Start typing the citizen's eID or name to see suggestions</small>
                    </div>
                    <div class="form-group">
                        <label for="school_name">School/Institution Name:</label>
                        <input type="text" name="school_name" id="school_name" required placeholder="e.g., Royal College Colombo">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="grade_level">Grade/Level:</label>
                        <select name="grade_level" id="grade_level" required>
                            <option value="">-- Select Grade/Level --</option>
                            <option value="Grade 1">Grade 1</option>
                            <option value="Grade 2">Grade 2</option>
                            <option value="Grade 3">Grade 3</option>
                            <option value="Grade 4">Grade 4</option>
                            <option value="Grade 5">Grade 5</option>
                            <option value="Grade 6">Grade 6</option>
                            <option value="Grade 7">Grade 7</option>
                            <option value="Grade 8">Grade 8</option>
                            <option value="Grade 9">Grade 9</option>
                            <option value="Grade 10">Grade 10</option>
                            <option value="Grade 11">Grade 11</option>
                            <option value="Grade 12">Grade 12</option>
                            <option value="Grade 13">Grade 13</option>
                            <option value="Ordinary Level">Ordinary Level</option>
                            <option value="Advanced Level">Advanced Level</option>
                            <option value="University">University</option>
                            <option value="Postgraduate">Postgraduate</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="exam_name">Exam/Assessment:</label>
                        <input type="text" name="exam_name" id="exam_name" placeholder="e.g., G.C.E O/L, G.C.E A/L, Term Test">
                    </div>
                    <div class="form-group">
                        <label for="subject_id">Subject (Optional):</label>
                        <select name="subject_id" id="subject_id">
                            <option value="">-- No Subject Selected --</option>
                            <?php
                            $subjectsQuery = $conn->query("SELECT SubjectID, SubjectName, Category FROM Subjects ORDER BY Category, SubjectName");
                            $currentCategory = '';
                            while ($subject = $subjectsQuery->fetch_assoc()):
                                if ($currentCategory != $subject['Category']):
                                    if ($currentCategory != '') echo '</optgroup>';
                                    echo '<optgroup label="' . htmlspecialchars($subject['Category']) . '">';
                                    $currentCategory = $subject['Category'];
                                endif;
                            ?>
                                <option value="<?= $subject['SubjectID'] ?>"><?= htmlspecialchars($subject['SubjectName']) ?></option>
                            <?php endwhile; ?>
                            <?php if ($currentCategory != '') echo '</optgroup>'; ?>
                        </select>
                        <small>Select a subject if this record is subject-specific</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="result">Result/Grade:</label>
                        <input type="text" name="result" id="result" placeholder="e.g., A, B, C, Pass, Distinction">
                    </div>
                    <div class="form-group">
                        <label for="marks_obtained">Marks/Score:</label>
                        <input type="text" name="marks_obtained" id="marks_obtained" placeholder="e.g., 85/100, 3A 2B 4C">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="record_date">Record Date:</label>
                        <input type="date" name="record_date" id="record_date" required value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group" style="display: flex; align-items: end;">
                        <button type="submit" name="add_education" class="btn">Add Education Record</button>
                    </div>
                </div>
            </form>

            <?php if(isset($msg)): ?>
                <p class="success"><?= htmlspecialchars($msg) ?></p>
            <?php endif; ?>
            
            <?php if(isset($error)): ?>
                <p class="error"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>
        </div>

        <!-- Quick Add Subject Section -->
        <div class="form-section">
            <h3 class="section-title">➕ Quick Add Subject</h3>
            
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="subject_name">Subject Name:</label>
                        <input type="text" name="subject_name" id="subject_name" placeholder="e.g., Advanced Mathematics">
                    </div>
                    <div class="form-group">
                        <label for="subject_code">Subject Code:</label>
                        <input type="text" name="subject_code" id="subject_code" placeholder="e.g., ADV_MATH">
                    </div>
                    <div class="form-group">
                        <label for="category">Category:</label>
                        <select name="category" id="category">
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
                    <div class="form-group" style="display: flex; align-items: end;">
                        <button type="submit" name="add_subject" class="btn" style="background: var(--accent-color);">➕ Add Subject</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="search-section">
            <h3>🔍 Quick Search Citizen Records</h3>
            <form method="GET" action="view_education.php">
                <div class="form-row">
                    <div class="form-group">
                        <input type="text" name="search" placeholder="Search by name or citizen ID..." style="padding: 10px;">
                    </div>
                    <div class="form-group" style="flex: 0;">
                        <button type="submit" class="btn">Search Records</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="records-section">
            <h3>Recent Education Records</h3>
            
            <?php if ($recentRecords->num_rows == 0): ?>
                <p>No education records found.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Citizen</th>
                            <th>School</th>
                            <th>Grade/Level</th>
                            <th>Exam</th>
                            <th>Subject</th>
                            <th>Result</th>
                            <th>Marks</th>
                            <th>Date</th>
                            <th>Registered By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($record = $recentRecords->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <a href="view_education.php?citizen_id=<?= $record['CitizenID'] ?>" style="color: #007cba; text-decoration: none;">
                                        <?= htmlspecialchars(($record['FirstName'] ?? '') . ' ' . ($record['LastName'] ?? '')) ?>
                                        <br><small>eID: <?= htmlspecialchars($record['Citizen_eID'] ?? 'N/A') ?></small>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($record['SchoolName'] ?? '') ?></td>
                                <td><?= htmlspecialchars($record['GradeLevel'] ?? '') ?></td>
                                <td><?= htmlspecialchars($record['ExamName'] ?? '') ?></td>
                                <td>
                                    <?php if($record['SubjectName']): ?>
                                        <span class="subject-badge <?= strtolower(str_replace(' ', '-', $record['SubjectCategory'])) ?>">
                                            <?= htmlspecialchars($record['SubjectName']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">No Subject</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($record['Result'] ?? '') ?></td>
                                <td><?= htmlspecialchars($record['MarksObtained'] ?? '') ?></td>
                                <td><?= $record['RecordDate'] ?? '' ?></td>
                                <td><?= htmlspecialchars($record['RegisteredByName'] ?? '') ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Enhanced Autocomplete functionality for citizen eID with modern NDMS styling
        const citizenInput = document.getElementById('citizen_eid');
        const suggestionsContainer = document.getElementById('suggestions');
        let currentSuggestions = [];
        let selectedIndex = -1;
        let debounceTimeout;

        citizenInput.addEventListener('input', function() {
            clearTimeout(debounceTimeout);
            const query = this.value.trim();
            
            if (query.length < 1) {
                hideSuggestions();
                return;
            }

            // Add loading state
            this.style.borderColor = '#3b82f6';
            this.style.backgroundColor = '#f8fafc';

            debounceTimeout = setTimeout(() => {
                // Fetch suggestions
                fetch(`api_citizen_suggestions.php?q=${encodeURIComponent(query)}`)
                    .then(response => response.json())
                    .then(data => {
                        currentSuggestions = data;
                        displaySuggestions(data);
                        citizenInput.style.borderColor = '#e5e7eb';
                        citizenInput.style.backgroundColor = 'white';
                    })
                    .catch(error => {
                        console.error('Error fetching suggestions:', error);
                        hideSuggestions();
                        citizenInput.style.borderColor = '#ef4444';
                        citizenInput.style.backgroundColor = '#fef2f2';
                    });
            }, 300);
        });

        citizenInput.addEventListener('keydown', function(e) {
            if (currentSuggestions.length === 0) return;

            switch(e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    selectedIndex = Math.min(selectedIndex + 1, currentSuggestions.length - 1);
                    updateSelection();
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    selectedIndex = Math.max(selectedIndex - 1, -1);
                    updateSelection();
                    break;
                case 'Enter':
                    e.preventDefault();
                    if (selectedIndex >= 0) {
                        selectSuggestion(currentSuggestions[selectedIndex]);
                    }
                    break;
                case 'Escape':
                    hideSuggestions();
                    break;
            }
        });

        citizenInput.addEventListener('blur', function() {
            // Delay hiding to allow click events
            setTimeout(hideSuggestions, 200);
        });

        function displaySuggestions(suggestions) {
            suggestionsContainer.innerHTML = '';
            selectedIndex = -1;

            if (suggestions.length === 0) {
                const noResults = document.createElement('div');
                noResults.className = 'suggestion-item';
                noResults.style.color = '#6b7280';
                noResults.style.fontStyle = 'italic';
                noResults.innerHTML = '🔍 No citizens found';
                suggestionsContainer.appendChild(noResults);
                suggestionsContainer.style.display = 'block';
                return;
            }

            suggestions.forEach((suggestion, index) => {
                const item = document.createElement('div');
                item.className = 'suggestion-item';
                item.innerHTML = `
                    <div class="suggestion-eid">🆔 ${suggestion.eid}</div>
                    <div class="suggestion-name">👤 ${suggestion.name}</div>
                `;
                
                item.addEventListener('click', () => selectSuggestion(suggestion));
                item.addEventListener('mouseenter', () => {
                    selectedIndex = index;
                    updateSelection();
                });
                
                suggestionsContainer.appendChild(item);
            });

            suggestionsContainer.style.display = 'block';
        }

        function updateSelection() {
            const items = suggestionsContainer.querySelectorAll('.suggestion-item');
            items.forEach((item, index) => {
                if (index === selectedIndex) {
                    item.style.backgroundColor = '#f0f9ff';
                    item.style.borderLeft = '4px solid #3b82f6';
                    item.style.transform = 'translateX(2px)';
                } else {
                    item.style.backgroundColor = '';
                    item.style.borderLeft = '';
                    item.style.transform = '';
                }
            });
        }

        function selectSuggestion(suggestion) {
            citizenInput.value = suggestion.eid;
            citizenInput.style.borderColor = '#10b981';
            citizenInput.style.backgroundColor = '#f0fdf4';
            hideSuggestions();
            citizenInput.focus();
            
            // Reset to normal colors after success indication
            setTimeout(() => {
                citizenInput.style.borderColor = '#e5e7eb';
                citizenInput.style.backgroundColor = 'white';
            }, 1000);
        }

        function hideSuggestions() {
            const items = suggestionsContainer.querySelectorAll('.suggestion-item');
            items.forEach((item, index) => {
                setTimeout(() => {
                    item.style.opacity = '0';
                    item.style.transform = 'translateY(-5px)';
                }, index * 30);
            });
            
            setTimeout(() => {
                suggestionsContainer.style.display = 'none';
                suggestionsContainer.innerHTML = '';
                currentSuggestions = [];
                selectedIndex = -1;
            }, items.length * 30 + 150);
        }

        // Enhanced form validation with modern feedback
        document.querySelector('form').addEventListener('submit', function(e) {
            const requiredFields = this.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.style.borderColor = '#ef4444';
                    field.style.backgroundColor = '#fef2f2';
                    field.style.animation = 'shake 0.5s ease-in-out';
                    isValid = false;
                } else {
                    field.style.borderColor = '#10b981';
                    field.style.backgroundColor = '#f0fdf4';
                    setTimeout(() => {
                        field.style.borderColor = '#e5e7eb';
                        field.style.backgroundColor = 'white';
                    }, 1000);
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('🚨 Please fill in all required fields to continue.');
            }
        });
        
        // Add smooth transitions and modern interactions for form elements
        document.querySelectorAll('input, select').forEach(element => {
            element.addEventListener('focus', function() {
                this.style.transform = 'scale(1.02)';
                this.style.boxShadow = '0 0 0 3px rgba(59, 130, 246, 0.1)';
                this.style.borderColor = '#3b82f6';
            });
            
            element.addEventListener('blur', function() {
                this.style.transform = 'scale(1)';
                this.style.boxShadow = 'none';
                if (this.value) {
                    this.style.borderColor = '#10b981';
                } else {
                    this.style.borderColor = '#e5e7eb';
                }
            });
        });

        // Hide suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (!citizenInput.contains(e.target) && !suggestionsContainer.contains(e.target)) {
                hideSuggestions();
            }
        });

        // Add shake animation for validation errors
        const style = document.createElement('style');
        style.textContent = `
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                25% { transform: translateX(-5px); }
                75% { transform: translateX(5px); }
            }
        `;
        document.head.appendChild(style);
        
        // Auto-format subject code as user types
        const subjectCodeInput = document.getElementById('subject_code');
        if (subjectCodeInput) {
            subjectCodeInput.addEventListener('input', function() {
                this.value = this.value.toUpperCase().replace(/[^A-Z0-9_]/g, '');
            });
        }
    </script>
    </div>
    <script src="includes/sidebar.js"></script>
</body>
</html>
