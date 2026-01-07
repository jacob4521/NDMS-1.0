<?php
include "config.php";

// Check if user is logged in and has access
if (!isset($_SESSION['UserID']) || !in_array($_SESSION['Role'], ['Admin', 'EducationOfficer'])) {
    header("Location: login.php");
    exit();
}

// Handle form submission for testing
$test_error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_test'])) {
    try {
        // Debug: Log that form submission was received
        error_log("Testing form submitted with data: " . print_r($_POST, true));
        
        // Get form data
        $student_name = $_POST['student_name'] ?? 'Test Student';
        $student_age = $_POST['student_age'] ?? 17;
        $student_district = $_POST['student_district'] ?? 'Colombo';
        $selected_interests = $_POST['interests'] ?? [];
        $ol_results = $_POST['ol_results'] ?? [];
        $optional_subjects = $_POST['optional_subjects'] ?? [];
        
        // Validate data
        $valid_ol_results = array_filter($ol_results, function($grade) {
            return !empty($grade) && $grade !== 'none';
        });
        
        $valid_optional_results = array_filter($optional_subjects, function($grade) {
            return !empty($grade) && $grade !== 'none';
        });
        
        if (empty($valid_ol_results) && empty($valid_optional_results)) {
            throw new Exception("Please select at least one O/L subject result with a grade");
        }
        
        if (empty($selected_interests)) {
            throw new Exception("Please select at least one interest area");
        }
        
        // Create temporary student entry
        $temp_student_query = "INSERT INTO career_students (FullName, Age, District, CreatedAt) VALUES (?, ?, ?, NOW())";
        $temp_student_stmt = $conn->prepare($temp_student_query);
        $temp_student_stmt->bind_param("sis", $student_name, $student_age, $student_district);
        
        if (!$temp_student_stmt->execute()) {
            throw new Exception("Failed to create temporary student record");
        }
        
        $temp_student_id = $conn->insert_id;
        
        // Insert O/L results (compulsory subjects)
        foreach ($ol_results as $subject => $grade) {
            if (!empty($grade) && $grade !== 'none') {
                $result_query = "INSERT INTO ol_results (StudentID, SubjectName, Grade) VALUES (?, ?, ?)";
                $result_stmt = $conn->prepare($result_query);
                $result_stmt->bind_param("iss", $temp_student_id, $subject, $grade);
                $result_stmt->execute();
            }
        }
        
        // Insert optional subjects (limit to 3)
        $optional_count = 0;
        foreach ($optional_subjects as $subject => $grade) {
            if (!empty($grade) && $grade !== 'none' && $optional_count < 3) {
                $result_query = "INSERT INTO ol_results (StudentID, SubjectName, Grade) VALUES (?, ?, ?)";
                $result_stmt = $conn->prepare($result_query);
                $result_stmt->bind_param("iss", $temp_student_id, $subject, $grade);
                $result_stmt->execute();
                $optional_count++;
            }
        }
        
        // Insert interests
        $priority = 1;
        foreach ($selected_interests as $interest) {
            $interest_query = "INSERT INTO career_interests (StudentID, InterestArea, Priority) VALUES (?, ?, ?)";
            $interest_stmt = $conn->prepare($interest_query);
            $interest_stmt->bind_param("isi", $temp_student_id, $interest, $priority);
            $interest_stmt->execute();
            $priority++;
        }
        
        // Redirect to career suggestions page with the temporary student ID
        header("Location: career_suggestions.php?student_id=" . $temp_student_id);
        exit();
        
    } catch (Exception $e) {
        $test_error = $e->getMessage();
        // Clean up on error
        if (isset($temp_student_id)) {
            $conn->query("DELETE FROM career_interests WHERE StudentID = $temp_student_id");
            $conn->query("DELETE FROM ol_results WHERE StudentID = $temp_student_id");
            $conn->query("DELETE FROM career_students WHERE StudentID = $temp_student_id");
        }
    }
}

// Get available subjects and interests for dropdowns
$subjects_query = "SELECT DISTINCT SubjectName FROM ol_subjects ORDER BY SubjectName";
$subjects_result = $conn->query($subjects_query);

$interests_query = "SELECT DISTINCT InterestArea FROM career_interests ORDER BY InterestArea";
// Use the same interest areas as the main form and career algorithm
$predefined_interests = [
    'Science & Medical',
    'Engineering & Technology', 
    'ICT & Computing',
    'Commerce & Business',
    'Arts & Humanities',
    'Law & Social Sciences',
    'Creative Arts & Design',
    'Vocational / Skilled Trades',
    'Sports & Physical Education'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Career Guidance Testing Lab - NDMS</title>
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

        .test-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .test-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .test-card h3 {
            color: var(--primary-color);
            margin-bottom: 20px;
            font-size: 20px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-primary);
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.1);
        }

        .subjects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 10px;
        }

        .subject-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: white;
        }

        .subject-item label {
            flex: 1;
            margin-bottom: 0;
            font-weight: 500;
            font-size: 13px;
        }

        .grade-select {
            width: auto;
            padding: 5px 8px;
            font-size: 12px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
        }

        .interests-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }

        .interest-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 20px;
            background: white;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .interest-item:hover {
            background: var(--light-bg);
        }

        .interest-item input[type="checkbox"] {
            margin: 0;
        }

        .interest-item.selected {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-success {
            background: var(--accent-color);
            color: white;
        }

        .btn-warning {
            background: var(--warning-color);
            color: white;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .results-section {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-top: 30px;
        }

        .suggestion-card {
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            background: white;
            transition: all 0.3s ease;
        }

        .suggestion-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transform: translateY(-1px);
        }

        .suggestion-rank {
            background: var(--primary-color);
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
            float: left;
            margin-right: 15px;
        }

        .suggestion-content {
            margin-left: 45px;
        }

        .suggestion-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 8px;
        }

        .suggestion-score {
            background: var(--accent-color);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 10px;
        }

        .suggestion-details {
            color: var(--text-secondary);
            font-size: 14px;
            line-height: 1.5;
        }

        .eligibility-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 10px;
        }

        .eligible {
            background: #10b981;
            color: white;
        }

        .not-eligible {
            background: #ef4444;
            color: white;
        }

        .error-message {
            background: #fef2f2;
            color: #dc2626;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #dc2626;
            margin-bottom: 20px;
        }

        .preset-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .preset-btn {
            padding: 8px 16px;
            background: var(--light-bg);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s ease;
        }

        .preset-btn:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 80px 15px 15px;
            }

            .test-grid {
                grid-template-columns: 1fr;
            }

            .subjects-grid {
                grid-template-columns: 1fr;
            }

            .interests-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    
    <div class="main-content">
        <div class="header">
            <h1>🧪 Career Guidance Testing Lab</h1>
            <p>Test the career suggestion algorithm with different combinations of O/L results and interests</p>
        </div>

        <?php if ($test_error): ?>
            <div class="error-message">
                <strong>❌ Test Error:</strong> <?= htmlspecialchars($test_error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="testForm">
            <div class="test-grid">
                <!-- Student Information -->
                <div class="test-card">
                    <h3>
                        <span>👤</span>
                        Student Information
                    </h3>
                    
                    <div class="form-group">
                        <label for="student_name">Student Name</label>
                        <input type="text" id="student_name" name="student_name" class="form-control" 
                               value="<?= htmlspecialchars($_POST['student_name'] ?? 'Test Student ' . date('His')) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="student_age">Age</label>
                        <input type="number" id="student_age" name="student_age" class="form-control" 
                               value="<?= htmlspecialchars($_POST['student_age'] ?? '17') ?>" min="15" max="20" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="student_district">District</label>
                        <select id="student_district" name="student_district" class="form-control" required>
                            <option value="Colombo" <?= ($_POST['student_district'] ?? '') === 'Colombo' ? 'selected' : '' ?>>Colombo</option>
                            <option value="Kandy" <?= ($_POST['student_district'] ?? '') === 'Kandy' ? 'selected' : '' ?>>Kandy</option>
                            <option value="Galle" <?= ($_POST['student_district'] ?? '') === 'Galle' ? 'selected' : '' ?>>Galle</option>
                            <option value="Kurunegala" <?= ($_POST['student_district'] ?? '') === 'Kurunegala' ? 'selected' : '' ?>>Kurunegala</option>
                            <option value="Matara" <?= ($_POST['student_district'] ?? '') === 'Matara' ? 'selected' : '' ?>>Matara</option>
                        </select>
                    </div>
                </div>

                <!-- Interest Areas -->
                <div class="test-card">
                    <h3>
                        <span>💭</span>
                        Interest Areas
                    </h3>
                    
                    <div class="preset-buttons">
                        <span class="preset-btn" onclick="selectPresetInterests(['Engineering & Technology', 'ICT & Computing', 'Science & Medical'])">🔧 STEM</span>
                        <span class="preset-btn" onclick="selectPresetInterests(['Science & Medical', 'Engineering & Technology', 'ICT & Computing'])">🏥 Medical/Science</span>
                        <span class="preset-btn" onclick="selectPresetInterests(['Commerce & Business', 'Law & Social Sciences', 'ICT & Computing'])">💼 Business</span>
                        <span class="preset-btn" onclick="selectPresetInterests(['Arts & Humanities', 'Creative Arts & Design', 'Sports & Physical Education'])">🎨 Arts & Creative</span>
                        <span class="preset-btn" onclick="selectPresetInterests(['ICT & Computing', 'Engineering & Technology', 'Vocational / Skilled Trades'])">💻 Technology</span>
                    </div>
                    
                    <div class="interests-grid">
                        <?php foreach ($predefined_interests as $interest): ?>
                            <div class="interest-item" onclick="toggleInterest(this)">
                                <input type="checkbox" name="interests[]" value="<?= htmlspecialchars($interest) ?>" 
                                       <?= in_array($interest, $_POST['interests'] ?? []) ? 'checked' : '' ?>>
                                <label><?= htmlspecialchars($interest) ?></label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- O/L Results -->
            <div class="test-card">
                <h3>
                    <span>📊</span>
                    Compulsory O/L Subjects (6 subjects - choose language & religion)
                </h3>
                
                <div class="preset-buttons">
                    <span class="preset-btn" onclick="setGradePreset('excellent')">⭐ Excellent Student (Mostly A's)</span>
                    <span class="preset-btn" onclick="setGradePreset('good')">👍 Good Student (A's & B's)</span>
                    <span class="preset-btn" onclick="setGradePreset('average')">📝 Average Student (B's & C's)</span>
                    <span class="preset-btn" onclick="setGradePreset('struggling')">📉 Struggling Student (C's & S's)</span>
                    <span class="preset-btn" onclick="setGradePreset('clear')">🗑️ Clear All</span>
                </div>
                
                <div class="subjects-grid">
                    <!-- Core subjects that everyone takes -->
                    <?php 
                    $core_subjects = ['Mathematics', 'Science', 'History', 'English Language', 'Religion'];
                    
                    foreach ($core_subjects as $subject): 
                        $selected_grade = $_POST['ol_results'][$subject] ?? 'none';
                    ?>
                        <div class="subject-item">
                            <label for="<?= str_replace([' ', '&'], ['_', '_'], $subject) ?>">
                                <?= htmlspecialchars($subject) ?>
                                <?php if ($subject === 'English'): ?>
                                    <small>(Second Language)</small>
                                <?php endif; ?>
                            </label>
                            <select name="ol_results[<?= htmlspecialchars($subject) ?>]" 
                                    class="grade-select compulsory-grade" 
                                    id="<?= str_replace([' ', '&'], ['_', '_'], $subject) ?>" required>
                                <option value="none" <?= $selected_grade === 'none' ? 'selected' : '' ?>>-</option>
                                <option value="A" <?= $selected_grade === 'A' ? 'selected' : '' ?>>A</option>
                                <option value="B" <?= $selected_grade === 'B' ? 'selected' : '' ?>>B</option>
                                <option value="C" <?= $selected_grade === 'C' ? 'selected' : '' ?>>C</option>
                                <option value="S" <?= $selected_grade === 'S' ? 'selected' : '' ?>>S</option>
                                <option value="W" <?= $selected_grade === 'W' ? 'selected' : '' ?>>W</option>
                            </select>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- First Language Choice -->
                    <div class="subject-item" style="grid-column: span 2; background: #f8f9fa; padding: 15px; border-radius: 8px;">
                        <label style="font-weight: 600; color: #2563eb; margin-bottom: 10px;">First Language (Choose One):</label>
                        <div style="display: flex; gap: 15px;">
                            <div style="flex: 1;">
                                <label for="Sinhala" style="font-size: 14px; margin-bottom: 5px;">Sinhala</label>
                                <select name="ol_results[Sinhala]" class="grade-select compulsory-grade" id="Sinhala" onchange="handleLanguageSelection('Sinhala')">
                                    <?php $selected_grade = $_POST['ol_results']['Sinhala'] ?? 'none'; ?>
                                    <option value="none" <?= $selected_grade === 'none' ? 'selected' : '' ?>>-</option>
                                    <option value="A" <?= $selected_grade === 'A' ? 'selected' : '' ?>>A</option>
                                    <option value="B" <?= $selected_grade === 'B' ? 'selected' : '' ?>>B</option>
                                    <option value="C" <?= $selected_grade === 'C' ? 'selected' : '' ?>>C</option>
                                    <option value="S" <?= $selected_grade === 'S' ? 'selected' : '' ?>>S</option>
                                    <option value="W" <?= $selected_grade === 'W' ? 'selected' : '' ?>>W</option>
                                </select>
                            </div>
                            <div style="flex: 1;">
                                <label for="Tamil" style="font-size: 14px; margin-bottom: 5px;">Tamil</label>
                                <select name="ol_results[Tamil]" class="grade-select compulsory-grade" id="Tamil" onchange="handleLanguageSelection('Tamil')">
                                    <?php $selected_grade = $_POST['ol_results']['Tamil'] ?? 'none'; ?>
                                    <option value="none" <?= $selected_grade === 'none' ? 'selected' : '' ?>>-</option>
                                    <option value="A" <?= $selected_grade === 'A' ? 'selected' : '' ?>>A</option>
                                    <option value="B" <?= $selected_grade === 'B' ? 'selected' : '' ?>>B</option>
                                    <option value="C" <?= $selected_grade === 'C' ? 'selected' : '' ?>>C</option>
                                    <option value="S" <?= $selected_grade === 'S' ? 'selected' : '' ?>>S</option>
                                    <option value="W" <?= $selected_grade === 'W' ? 'selected' : '' ?>>W</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Religion Choice -->
                    <div class="subject-item" style="grid-column: span 2; background: #f8f9fa; padding: 15px; border-radius: 8px;">
                        <label style="font-weight: 600; color: #2563eb; margin-bottom: 10px;">Religion (Choose One):</label>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                            <div>
                                <label for="Buddhism" style="font-size: 14px; margin-bottom: 5px;">Buddhism</label>
                                <select name="ol_results[Buddhism]" class="grade-select compulsory-grade" id="Buddhism" onchange="handleReligionSelection('Buddhism')">
                                    <?php $selected_grade = $_POST['ol_results']['Buddhism'] ?? 'none'; ?>
                                    <option value="none" <?= $selected_grade === 'none' ? 'selected' : '' ?>>-</option>
                                    <option value="A" <?= $selected_grade === 'A' ? 'selected' : '' ?>>A</option>
                                    <option value="B" <?= $selected_grade === 'B' ? 'selected' : '' ?>>B</option>
                                    <option value="C" <?= $selected_grade === 'C' ? 'selected' : '' ?>>C</option>
                                    <option value="S" <?= $selected_grade === 'S' ? 'selected' : '' ?>>S</option>
                                    <option value="W" <?= $selected_grade === 'W' ? 'selected' : '' ?>>W</option>
                                </select>
                            </div>
                            <div>
                                <label for="Christianity" style="font-size: 14px; margin-bottom: 5px;">Christianity</label>
                                <select name="ol_results[Christianity]" class="grade-select compulsory-grade" id="Christianity" onchange="handleReligionSelection('Christianity')">
                                    <?php $selected_grade = $_POST['ol_results']['Christianity'] ?? 'none'; ?>
                                    <option value="none" <?= $selected_grade === 'none' ? 'selected' : '' ?>>-</option>
                                    <option value="A" <?= $selected_grade === 'A' ? 'selected' : '' ?>>A</option>
                                    <option value="B" <?= $selected_grade === 'B' ? 'selected' : '' ?>>B</option>
                                    <option value="C" <?= $selected_grade === 'C' ? 'selected' : '' ?>>C</option>
                                    <option value="S" <?= $selected_grade === 'S' ? 'selected' : '' ?>>S</option>
                                    <option value="W" <?= $selected_grade === 'W' ? 'selected' : '' ?>>W</option>
                                </select>
                            </div>
                            <div>
                                <label for="Hinduism" style="font-size: 14px; margin-bottom: 5px;">Hinduism</label>
                                <select name="ol_results[Hinduism]" class="grade-select compulsory-grade" id="Hinduism" onchange="handleReligionSelection('Hinduism')">
                                    <?php $selected_grade = $_POST['ol_results']['Hinduism'] ?? 'none'; ?>
                                    <option value="none" <?= $selected_grade === 'none' ? 'selected' : '' ?>>-</option>
                                    <option value="A" <?= $selected_grade === 'A' ? 'selected' : '' ?>>A</option>
                                    <option value="B" <?= $selected_grade === 'B' ? 'selected' : '' ?>>B</option>
                                    <option value="C" <?= $selected_grade === 'C' ? 'selected' : '' ?>>C</option>
                                    <option value="S" <?= $selected_grade === 'S' ? 'selected' : '' ?>>S</option>
                                    <option value="W" <?= $selected_grade === 'W' ? 'selected' : '' ?>>W</option>
                                </select>
                            </div>
                            <div>
                                <label for="Islam" style="font-size: 14px; margin-bottom: 5px;">Islam</label>
                                <select name="ol_results[Islam]" class="grade-select compulsory-grade" id="Islam" onchange="handleReligionSelection('Islam')">
                                    <?php $selected_grade = $_POST['ol_results']['Islam'] ?? 'none'; ?>
                                    <option value="none" <?= $selected_grade === 'none' ? 'selected' : '' ?>>-</option>
                                    <option value="A" <?= $selected_grade === 'A' ? 'selected' : '' ?>>A</option>
                                    <option value="B" <?= $selected_grade === 'B' ? 'selected' : '' ?>>B</option>
                                    <option value="C" <?= $selected_grade === 'C' ? 'selected' : '' ?>>C</option>
                                    <option value="S" <?= $selected_grade === 'S' ? 'selected' : '' ?>>S</option>
                                    <option value="W" <?= $selected_grade === 'W' ? 'selected' : '' ?>>W</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div style="margin-top: 15px; padding: 10px; background: #e0f2fe; border-radius: 8px; text-align: center; color: #0277bd;">
                    <strong>📋 Subject Count:</strong> 
                    <span id="compulsory-count">4</span>/6 compulsory subjects completed
                </div>
            </div>

            <!-- Optional Subjects -->
            <div class="test-card">
                <h3>
                    <span>🎯</span>
                    Optional O/L Subjects (Must choose exactly 3 subjects)
                </h3>
                
                <div class="preset-buttons">
                    <span class="preset-btn" onclick="selectOptionalPreset('science')">🔬 Science Stream</span>
                    <span class="preset-btn" onclick="selectOptionalPreset('commerce')">💼 Commerce Stream</span>
                    <span class="preset-btn" onclick="selectOptionalPreset('arts')">🎨 Arts Stream</span>
                    <span class="preset-btn" onclick="selectOptionalPreset('technology')">💻 Technology Stream</span>
                    <span class="preset-btn" onclick="selectOptionalPreset('random')">🎲 Random 3</span>
                    <span class="preset-btn" onclick="clearOptionalSubjects()">🗑️ Clear</span>
                </div>
                
                <div class="subjects-grid">
                    <?php 
                    $optional_subjects = [
                        'Agriculture & Food Technology', 'Aquatic Bioresources Technology', 'Arabic',
                        'Art', 'Business & Accounting Studies', 'Chinese', 'Civic Education',
                        'Dancing', 'Drama & Theatre', 'Economics', 'Engineering Technology',
                        'English Literature', 'Entrepreneurship Studies', 'French', 'Geography',
                        'German', 'Health & Physical Education', 'Hindi', 'Home Economics',
                        'Information & Communication Technology', 'Japanese', 'Korean', 'Music',
                        'Pali', 'Sanskrit', 'Sinhala Literature', 'Tamil Literature'
                    ];
                    
                    foreach ($optional_subjects as $subject): 
                        $selected_grade = $_POST['optional_subjects'][$subject] ?? 'none';
                    ?>
                        <div class="subject-item optional-subject">
                            <label for="opt_<?= str_replace([' ', '&'], ['_', '_'], $subject) ?>">
                                <?= htmlspecialchars($subject) ?>
                            </label>
                            <select name="optional_subjects[<?= htmlspecialchars($subject) ?>]" 
                                    class="grade-select optional-grade" 
                                    id="opt_<?= str_replace([' ', '&'], ['_', '_'], $subject) ?>"
                                    onchange="limitOptionalSelections(this)">
                                <option value="none" <?= $selected_grade === 'none' ? 'selected' : '' ?>>-</option>
                                <option value="A" <?= $selected_grade === 'A' ? 'selected' : '' ?>>A</option>
                                <option value="B" <?= $selected_grade === 'B' ? 'selected' : '' ?>>B</option>
                                <option value="C" <?= $selected_grade === 'C' ? 'selected' : '' ?>>C</option>
                                <option value="S" <?= $selected_grade === 'S' ? 'selected' : '' ?>>S</option>
                                <option value="W" <?= $selected_grade === 'W' ? 'selected' : '' ?>>W</option>
                            </select>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div style="margin-top: 15px; padding: 15px; background: #fef3c7; border-radius: 8px; text-align: center; color: #92400e;">
                    <strong>📊 Total O/L Subjects:</strong> 
                    <span id="total-subjects">4</span>/9 subjects selected 
                    | Optional: <span id="optional-count">0</span>/3
                    <div style="margin-top: 8px; font-size: 14px;">
                        <span id="subject-status">⚠️ Please complete compulsory subjects and select exactly 3 optional subjects</span>
                    </div>
                </div>
            </div>

            <div style="text-align: center; margin: 30px 0;">
                <button type="submit" name="run_test" class="btn btn-primary" style="font-size: 16px; padding: 15px 30px;">
                    🚀 Run Career Guidance Test
                </button>
                <button type="button" onclick="randomizeTest()" class="btn btn-warning" style="font-size: 16px; padding: 15px 30px;">
                    🎲 Randomize Test Data
                </button>
            </div>
            
            <!-- Debug Information -->
            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                <div style="background: #f0f9ff; padding: 15px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #0ea5e9;">
                    <strong>🔍 Debug Info:</strong><br>
                    Form submitted: <?= isset($_POST['run_test']) ? 'Yes' : 'No' ?><br>
                    Selected interests: <?= count($_POST['interests'] ?? []) ?><br>
                    O/L results filled: <?= count(array_filter($_POST['ol_results'] ?? [], function($g) { return $g !== 'none'; })) ?><br>
                    Optional subjects filled: <?= count(array_filter($_POST['optional_subjects'] ?? [], function($g) { return $g !== 'none'; })) ?>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <script src="includes/sidebar.js"></script>
    <script>
        function toggleInterest(element) {
            const checkbox = element.querySelector('input[type="checkbox"]');
            checkbox.checked = !checkbox.checked;
            
            if (checkbox.checked) {
                element.classList.add('selected');
            } else {
                element.classList.remove('selected');
            }
            
            // Limit to 3 selections
            const checkedBoxes = document.querySelectorAll('.interest-item input[type="checkbox"]:checked');
            if (checkedBoxes.length > 3) {
                checkbox.checked = false;
                element.classList.remove('selected');
                alert('Please select maximum 3 interest areas for better results.');
            }
        }
        
        function selectPresetInterests(interests) {
            // Clear all selections first
            document.querySelectorAll('.interest-item input[type="checkbox"]').forEach(checkbox => {
                checkbox.checked = false;
                checkbox.closest('.interest-item').classList.remove('selected');
            });
            
            // Select preset interests
            interests.forEach(interest => {
                const checkbox = document.querySelector(`input[value="${interest}"]`);
                if (checkbox) {
                    checkbox.checked = true;
                    checkbox.closest('.interest-item').classList.add('selected');
                }
            });
        }
        
        function setGradePreset(preset) {
            const gradeSelects = document.querySelectorAll('.compulsory-grade');
            
            switch(preset) {
                case 'excellent':
                    gradeSelects.forEach((select, index) => {
                        select.value = Math.random() > 0.3 ? 'A' : 'B';
                    });
                    break;
                    
                case 'good':
                    gradeSelects.forEach((select, index) => {
                        const grades = ['A', 'A', 'B', 'B', 'B'];
                        select.value = grades[Math.floor(Math.random() * grades.length)];
                    });
                    break;
                    
                case 'average':
                    gradeSelects.forEach((select, index) => {
                        const grades = ['B', 'B', 'C', 'C', 'C'];
                        select.value = grades[Math.floor(Math.random() * grades.length)];
                    });
                    break;
                    
                case 'struggling':
                    gradeSelects.forEach((select, index) => {
                        const grades = ['C', 'C', 'S', 'S', 'W'];
                        select.value = grades[Math.floor(Math.random() * grades.length)];
                    });
                    break;
                    
                case 'clear':
                    gradeSelects.forEach(select => {
                        select.value = 'none';
                    });
                    break;
            }
        }
        
        function handleLanguageSelection(selectedLanguage) {
            const languages = ['Sinhala', 'Tamil'];
            languages.forEach(lang => {
                if (lang !== selectedLanguage) {
                    document.getElementById(lang).value = 'none';
                }
            });
            updateSubjectCounts();
        }
        
        function handleReligionSelection(selectedReligion) {
            const religions = ['Buddhism', 'Christianity', 'Hinduism', 'Islam'];
            religions.forEach(religion => {
                if (religion !== selectedReligion) {
                    document.getElementById(religion).value = 'none';
                }
            });
            updateSubjectCounts();
        }
        
        function updateSubjectCounts() {
            // Count compulsory subjects
            const coreSubjects = ['Mathematics', 'Science', 'History', 'English Language'];
            const languages = ['Sinhala Language', 'Tamil Language'];
            const religions = ['Religion'];
            
            let compulsoryCount = 0;
            
            // Count core subjects
            coreSubjects.forEach(subject => {
                const select = document.getElementById(subject);
                if (select && select.value !== 'none') {
                    compulsoryCount++;
                }
            });
            
            // Count selected language
            const selectedLanguage = languages.find(lang => {
                const select = document.getElementById(lang);
                return select && select.value !== 'none';
            });
            if (selectedLanguage) compulsoryCount++;
            
            // Count selected religion
            const selectedReligion = religions.find(religion => {
                const select = document.getElementById(religion);
                return select && select.value !== 'none';
            });
            if (selectedReligion) compulsoryCount++;
            
            // Count optional subjects
            const optionalSelects = document.querySelectorAll('.optional-grade');
            const optionalCount = Array.from(optionalSelects).filter(select => select.value !== 'none').length;
            
            // Update displays
            document.getElementById('compulsory-count').textContent = compulsoryCount;
            document.getElementById('optional-count').textContent = optionalCount;
            document.getElementById('total-subjects').textContent = compulsoryCount + optionalCount;
            
            // Update status message
            const statusElement = document.getElementById('subject-status');
            if (compulsoryCount === 6 && optionalCount === 3) {
                statusElement.textContent = '✅ Perfect! Ready for O/L examination (9 subjects)';
                statusElement.style.color = '#16a34a';
            } else if (compulsoryCount < 6) {
                statusElement.textContent = `⚠️ Need ${6 - compulsoryCount} more compulsory subjects (select language & religion)`;
                statusElement.style.color = '#dc2626';
            } else if (optionalCount < 3) {
                statusElement.textContent = `⚠️ Need ${3 - optionalCount} more optional subjects`;
                statusElement.style.color = '#ea580c';
            } else if (optionalCount > 3) {
                statusElement.textContent = '❌ Too many optional subjects! Remove some to have exactly 3';
                statusElement.style.color = '#dc2626';
            }
        }
        
        function limitOptionalSelections(changedSelect) {
            const optionalSelects = document.querySelectorAll('.optional-grade');
            const selectedCount = Array.from(optionalSelects).filter(select => select.value !== 'none').length;
            
            // If trying to select more than 3, reset the changed select
            if (selectedCount > 3 && changedSelect.value !== 'none') {
                changedSelect.value = 'none';
                alert('You can only select exactly 3 optional subjects for O/L examination.');
            }
            
            // Update styling
            optionalSelects.forEach(select => {
                const container = select.closest('.optional-subject');
                if (select.value !== 'none') {
                    container.style.background = '#e3f2fd';
                    container.style.borderColor = '#1976d2';
                } else {
                    container.style.background = 'white';
                    container.style.borderColor = '#e5e7eb';
                }
            });
            
            updateSubjectCounts();
        }
        
        function selectOptionalPreset(preset) {
            // Clear all optional subjects first
            clearOptionalSubjects();
            
            const optionalSelects = document.querySelectorAll('.optional-grade');
            let subjectsToSelect = [];
            
            switch(preset) {
                case 'science':
                    subjectsToSelect = ['Information & Communication Technology', 'Agriculture & Food Technology', 'Engineering Technology'];
                    break;
                case 'commerce':
                    subjectsToSelect = ['Business & Accounting Studies', 'Economics', 'Geography'];
                    break;
                case 'arts':
                    subjectsToSelect = ['Art', 'Music', 'Geography'];
                    break;
                case 'technology':
                    subjectsToSelect = ['Information & Communication Technology', 'Engineering Technology', 'Entrepreneurship Studies'];
                    break;
                case 'random':
                    const allOptionalSubjects = Array.from(optionalSelects).map(select => 
                        select.getAttribute('name').match(/\[(.*?)\]/)[1]
                    );
                    subjectsToSelect = allOptionalSubjects.sort(() => 0.5 - Math.random()).slice(0, 3);
                    break;
            }
            
            // Set grades for selected subjects
            subjectsToSelect.forEach(subject => {
                const select = document.querySelector(`select[name="optional_subjects[${subject}]"]`);
                if (select) {
                    const grades = ['A', 'A', 'B', 'B', 'C'];
                    select.value = grades[Math.floor(Math.random() * grades.length)];
                    limitOptionalSelections(select);
                }
            });
        }
        
        function clearOptionalSubjects() {
            const optionalSelects = document.querySelectorAll('.optional-grade');
            optionalSelects.forEach(select => {
                select.value = 'none';
                limitOptionalSelections(select);
            });
        }
        
        function randomizeTest() {
            // Randomize student info
            const names = ['Kamal Perera', 'Nimali Silva', 'Rajitha Fernando', 'Sanduni Wickramasinghe', 'Tharindu Jayawardena', 'Priya Kumari', 'Ashen Bandara', 'Kavindi Rathnayake'];
            document.getElementById('student_name').value = names[Math.floor(Math.random() * names.length)];
            document.getElementById('student_age').value = Math.floor(Math.random() * 3) + 16; // 16-18
            
            const districts = ['Colombo', 'Kandy', 'Galle', 'Kurunegala', 'Matara'];
            document.getElementById('student_district').value = districts[Math.floor(Math.random() * districts.length)];
            
            // Randomize interests
            const allInterests = <?= json_encode($predefined_interests) ?>;
            selectPresetInterests(allInterests.sort(() => 0.5 - Math.random()).slice(0, 3));
            
            // Clear all subjects first
            document.querySelectorAll('.compulsory-grade, .optional-grade').forEach(select => {
                select.value = 'none';
            });
            
            // Randomize compulsory grades - core subjects
            const coreSubjects = ['Mathematics', 'Science', 'History', 'English'];
            const gradeOptions = ['A', 'A', 'B', 'B', 'C'];
            
            coreSubjects.forEach(subject => {
                const select = document.getElementById(subject);
                if (select) {
                    select.value = gradeOptions[Math.floor(Math.random() * gradeOptions.length)];
                }
            });
            
            // Select random language (Sinhala more likely)
            const language = Math.random() > 0.2 ? 'Sinhala' : 'Tamil';
            document.getElementById(language).value = gradeOptions[Math.floor(Math.random() * gradeOptions.length)];
            
            // Select random religion (Buddhism more likely in Sri Lanka)
            const religions = ['Buddhism', 'Christianity', 'Hinduism', 'Islam'];
            const religionWeights = [0.7, 0.15, 0.1, 0.05]; // Approximate Sri Lankan demographics
            let religionRandom = Math.random();
            let selectedReligion = 'Buddhism';
            
            let cumulative = 0;
            for (let i = 0; i < religions.length; i++) {
                cumulative += religionWeights[i];
                if (religionRandom <= cumulative) {
                    selectedReligion = religions[i];
                    break;
                }
            }
            
            document.getElementById(selectedReligion).value = gradeOptions[Math.floor(Math.random() * gradeOptions.length)];
            
            // Randomize optional subjects (exactly 3)
            const optionalPresets = ['science', 'commerce', 'arts', 'technology', 'random'];
            selectOptionalPreset(optionalPresets[Math.floor(Math.random() * optionalPresets.length)]);
            
            // Update counts
            updateSubjectCounts();
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.interest-item input[type="checkbox"]:checked').forEach(checkbox => {
                checkbox.closest('.interest-item').classList.add('selected');
            });
            
            // Initialize subject counters and styling
            document.querySelectorAll('.optional-grade').forEach(select => {
                limitOptionalSelections(select);
            });
            
            // Initialize compulsory subject tracking
            document.querySelectorAll('.compulsory-grade').forEach(select => {
                select.addEventListener('change', updateSubjectCounts);
            });
            
            // Initial count update
            updateSubjectCounts();
        });
    </script>
</body>
</html>
