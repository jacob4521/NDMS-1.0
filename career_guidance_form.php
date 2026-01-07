<?php
include "config.php";

// Get all available O/L subjects for dropdowns
$compulsory_subjects = $conn->query("SELECT * FROM ol_subjects WHERE IsCompulsory = TRUE ORDER BY SubjectName");
$optional_subjects = $conn->query("SELECT * FROM ol_subjects WHERE IsCompulsory = FALSE ORDER BY Category, SubjectName");

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $conn->begin_transaction();
        
        // Insert student basic info
        $stmt = $conn->prepare("INSERT INTO career_students (FullName, NIC, Age, District, Email, Phone) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssisss", 
            $_POST['full_name'], 
            $_POST['nic'], 
            $_POST['age'], 
            $_POST['district'], 
            $_POST['email'], 
            $_POST['phone']
        );
        $stmt->execute();
        $student_id = $conn->insert_id;
        
        // Insert O/L results
        foreach ($_POST['subjects'] as $subject => $grade) {
            if (!empty($grade)) {
                $is_compulsory = in_array($subject, ['Sinhala Language', 'Tamil Language', 'English Language', 'Mathematics', 'Science', 'History', 'Religion']);
                $stmt = $conn->prepare("INSERT INTO ol_results (StudentID, SubjectName, Grade, IsCompulsory) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("issi", $student_id, $subject, $grade, $is_compulsory);
                $stmt->execute();
            }
        }
        
        // Insert career interests
        if (isset($_POST['interests'])) {
            foreach ($_POST['interests'] as $index => $interest) {
                $priority = $index + 1; // First selected gets priority 1
                $stmt = $conn->prepare("INSERT INTO career_interests (StudentID, InterestArea, Priority) VALUES (?, ?, ?)");
                $stmt->bind_param("isi", $student_id, $interest, $priority);
                $stmt->execute();
            }
        }
        
        $conn->commit();
        $success_message = "Student information saved successfully! Generating career suggestions...";
        
        // Redirect to results page
        header("Location: career_suggestions.php?student_id=" . $student_id);
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Error saving data: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Career Guidance System - NDMS</title>
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
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: var(--gradient-bg);
            color: white;
            padding: 40px 20px;
            border-radius: 20px;
            margin-bottom: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 18px;
            opacity: 0.9;
        }

        .form-container {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .form-section {
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 2px solid var(--border-color);
        }

        .form-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .section-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-primary);
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .subject-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .subject-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            background: var(--light-bg);
            border-radius: 10px;
            border: 2px solid var(--border-color);
        }

        .subject-item.compulsory {
            background: #fef3c7;
            border-color: var(--warning-color);
        }

        .subject-item label {
            flex: 1;
            margin: 0;
            font-weight: 500;
        }

        .subject-item select {
            width: 80px;
            padding: 6px 8px;
            margin: 0;
        }

        .interests-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }

        .interest-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px;
            background: var(--light-bg);
            border: 2px solid var(--border-color);
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .interest-item:hover {
            border-color: var(--secondary-color);
            background: #eff6ff;
        }

        .interest-item.selected {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .interest-item input[type="checkbox"] {
            width: auto;
            margin: 0;
        }

        .optional-subject {
            transition: all 0.3s ease;
        }

        .optional-subject.selected {
            border-color: var(--primary-color);
            background: #eff6ff;
        }

        .submit-btn {
            background: var(--gradient-bg);
            color: white;
            border: none;
            padding: 16px 40px;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: block;
            margin: 30px auto 0;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.3);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert.success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #10b981;
        }

        .alert.error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #ef4444;
        }

        .info-box {
            background: #eff6ff;
            border: 2px solid var(--secondary-color);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .info-box h3 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .grade-info {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-top: 10px;
        }

        .grade-item {
            background: white;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .form-container {
                padding: 20px;
            }

            .header h1 {
                font-size: 28px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .subject-grid,
            .interests-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Sidebar Integration Styles */
        body.has-citizen-sidebar {
            padding-left: 280px;
            transition: padding-left 0.3s ease;
        }
        
        body.citizen-sidebar-collapsed {
            padding-left: 60px;
        }

        .container {
            min-height: 100vh;
            /* Remove margin since body now has padding */
        }

        /* Mobile adjustments */
        @media (max-width: 768px) {
            body.has-citizen-sidebar {
                padding-left: 0 !important;
                padding-top: 80px; /* Space for mobile menu button */
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/citizen_sidebar.php'; ?>
    
    <div class="container">
        <div class="header">
            <h1>🎓 Career Guidance System</h1>
            <p>Discover your ideal career path based on your O/L results and interests</p>
        </div>

        <?php if ($success_message): ?>
            <div class="alert success"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert error"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <div class="info-box">
            <h3>📋 How it works:</h3>
            <p>Fill in your O/L results and career interests. Our system will analyze your academic performance and suggest the best career paths for your future success.</p>
            
            <div class="grade-info">
                <div class="grade-item">A = Excellent</div>
                <div class="grade-item">B = Very Good</div>
                <div class="grade-item">C = Credit</div>
                <div class="grade-item">S = Simple Pass</div>
                <div class="grade-item">W = Weak/Fail</div>
            </div>
        </div>

        <form method="POST" class="form-container">
            <!-- Student Information Section -->
            <div class="form-section">
                <h2 class="section-title">👤 Student Information</h2>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="full_name">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="nic">NIC Number</label>
                        <input type="text" id="nic" name="nic" placeholder="Optional">
                    </div>
                    
                    <div class="form-group">
                        <label for="age">Age *</label>
                        <input type="number" id="age" name="age" min="15" max="25" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="district">District</label>
                        <select id="district" name="district">
                            <option value="">Select District</option>
                            <option value="Colombo">Colombo</option>
                            <option value="Gampaha">Gampaha</option>
                            <option value="Kalutara">Kalutara</option>
                            <option value="Kandy">Kandy</option>
                            <option value="Matale">Matale</option>
                            <option value="Nuwara Eliya">Nuwara Eliya</option>
                            <option value="Galle">Galle</option>
                            <option value="Matara">Matara</option>
                            <option value="Hambantota">Hambantota</option>
                            <option value="Jaffna">Jaffna</option>
                            <option value="Kilinochchi">Kilinochchi</option>
                            <option value="Mannar">Mannar</option>
                            <option value="Vavuniya">Vavuniya</option>
                            <option value="Mullaitivu">Mullaitivu</option>
                            <option value="Batticaloa">Batticaloa</option>
                            <option value="Ampara">Ampara</option>
                            <option value="Trincomalee">Trincomalee</option>
                            <option value="Kurunegala">Kurunegala</option>
                            <option value="Puttalam">Puttalam</option>
                            <option value="Anuradhapura">Anuradhapura</option>
                            <option value="Polonnaruwa">Polonnaruwa</option>
                            <option value="Badulla">Badulla</option>
                            <option value="Moneragala">Moneragala</option>
                            <option value="Ratnapura">Ratnapura</option>
                            <option value="Kegalle">Kegalle</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" placeholder="Optional">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" placeholder="Optional">
                    </div>
                </div>
            </div>

            <!-- O/L Results Section -->
            <div class="form-section">
                <h2 class="section-title">📚 O/L Results</h2>
                <p style="margin-bottom: 20px; color: var(--text-secondary);">Enter your grades for each subject. You must take exactly 9 subjects: 6 compulsory + 3 optional.</p>
                
                <h3 style="margin-bottom: 15px; color: var(--primary-color);">📌 Core Compulsory Subjects (4 subjects)</h3>
                <div class="subject-grid">
                    <div class="subject-item compulsory">
                        <label>Mathematics *</label>
                        <select name="subjects[Mathematics]" id="Mathematics" class="compulsory-grade" onchange="updateSubjectCounts()" required>
                            <option value="">Grade</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="S">S</option>
                            <option value="W">W</option>
                        </select>
                    </div>
                    
                    <div class="subject-item compulsory">
                        <label>Science *</label>
                        <select name="subjects[Science]" id="Science" class="compulsory-grade" onchange="updateSubjectCounts()" required>
                            <option value="">Grade</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="S">S</option>
                            <option value="W">W</option>
                        </select>
                    </div>
                    
                    <div class="subject-item compulsory">
                        <label>English *</label>
                        <select name="subjects[English]" id="English" class="compulsory-grade" onchange="updateSubjectCounts()" required>
                            <option value="">Grade</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="S">S</option>
                            <option value="W">W</option>
                        </select>
                    </div>
                    
                    <div class="subject-item compulsory">
                        <label>History *</label>
                        <select name="subjects[History]" id="History" class="compulsory-grade" onchange="updateSubjectCounts()" required>
                            <option value="">Grade</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="S">S</option>
                            <option value="W">W</option>
                        </select>
                    </div>
                </div>
                
                <h3 style="margin: 25px 0 15px; color: var(--primary-color);">🌐 Language Selection (Choose 1)</h3>
                <div class="subject-grid">
                    <div class="subject-item compulsory">
                        <label>Sinhala</label>
                        <select name="subjects[Sinhala]" id="Sinhala" class="compulsory-grade" onchange="handleLanguageSelection('Sinhala')">
                            <option value="">Not Selected</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="S">S</option>
                            <option value="W">W</option>
                        </select>
                    </div>
                    
                    <div class="subject-item compulsory">
                        <label>Tamil</label>
                        <select name="subjects[Tamil]" id="Tamil" class="compulsory-grade" onchange="handleLanguageSelection('Tamil')">
                            <option value="">Not Selected</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="S">S</option>
                            <option value="W">W</option>
                        </select>
                    </div>
                </div>
                
                <h3 style="margin: 25px 0 15px; color: var(--primary-color);">🙏 Religion Selection (Choose 1)</h3>
                <div class="subject-grid">
                    <div class="subject-item compulsory">
                        <label>Buddhism</label>
                        <select name="subjects[Buddhism]" id="Buddhism" class="compulsory-grade" onchange="handleReligionSelection('Buddhism')">
                            <option value="">Not Selected</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="S">S</option>
                            <option value="W">W</option>
                        </select>
                    </div>
                    
                    <div class="subject-item compulsory">
                        <label>Christianity</label>
                        <select name="subjects[Christianity]" id="Christianity" class="compulsory-grade" onchange="handleReligionSelection('Christianity')">
                            <option value="">Not Selected</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="S">S</option>
                            <option value="W">W</option>
                        </select>
                    </div>
                    
                    <div class="subject-item compulsory">
                        <label>Hinduism</label>
                        <select name="subjects[Hinduism]" id="Hinduism" class="compulsory-grade" onchange="handleReligionSelection('Hinduism')">
                            <option value="">Not Selected</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="S">S</option>
                            <option value="W">W</option>
                        </select>
                    </div>
                    
                    <div class="subject-item compulsory">
                        <label>Islam</label>
                        <select name="subjects[Islam]" id="Islam" class="compulsory-grade" onchange="handleReligionSelection('Islam')">
                            <option value="">Not Selected</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="S">S</option>
                            <option value="W">W</option>
                        </select>
                    </div>
                </div>
                
                <div style="margin: 15px 0; padding: 15px; background: #e0f2fe; border-radius: 8px; text-align: center; color: #0277bd;">
                    📊 <strong>Compulsory Subjects:</strong> <span id="compulsory-count">0</span>/6 selected
                </div>

                <h3 style="margin: 30px 0 15px; color: var(--primary-color);">📝 Optional Subjects (Select exactly 3)</h3>
                <p style="margin-bottom: 15px; color: var(--text-secondary); font-size: 14px;">Choose 3 optional subjects that align with your interests and career goals.</p>
                
                <div class="subject-grid">
                    <?php
                    // Generate optional subjects dynamically from database
                    $optional_subjects->data_seek(0); // Reset pointer to start
                    while ($row = $optional_subjects->fetch_assoc()): 
                        $subject_name = htmlspecialchars($row['SubjectName']);
                        $subject_id = str_replace([' ', '&'], ['_', '_'], $subject_name);
                    ?>
                        <div class="subject-item optional-subject">
                            <label><?= $subject_name ?></label>
                            <select name="subjects[<?= $subject_name ?>]" class="optional-grade" onchange="limitOptionalSelections(this)">
                                <option value="">Not Selected</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                                <option value="S">S</option>
                                <option value="W">W</option>
                            </select>
                        </div>
                    <?php endwhile; ?>
                </div>
                
                <div style="margin: 15px 0; padding: 15px; background: #fef3c7; border-radius: 8px; text-align: center; color: #92400e;">
                    📝 <strong>Optional Subjects:</strong> <span id="optional-count">0</span>/3 selected | 
                    <strong>Total Subjects:</strong> <span id="total-subjects">0</span>/9
                    <br><br>
                    <span id="subject-status" style="font-weight: 600;">⚠️ Please select your subjects to complete the form</span>
                </div>
            </div>

            <!-- Career Interests Section -->
            <div class="form-section">
                <h2 class="section-title">💡 Career Interests</h2>
                <p style="margin-bottom: 20px; color: var(--text-secondary);">Select exactly 3 areas you're most interested in. The order matters - select your top preference first.</p>
                
                <div class="interests-grid">
                    <label class="interest-item" onclick="toggleInterest(this)">
                        <input type="checkbox" name="interests[]" value="Science & Medical">
                        <span>🔬 Science & Medical</span>
                    </label>
                    
                    <label class="interest-item" onclick="toggleInterest(this)">
                        <input type="checkbox" name="interests[]" value="Engineering & Technology">
                        <span>⚙️ Engineering & Technology</span>
                    </label>
                    
                    <label class="interest-item" onclick="toggleInterest(this)">
                        <input type="checkbox" name="interests[]" value="ICT & Computing">
                        <span>💻 ICT & Computing</span>
                    </label>
                    
                    <label class="interest-item" onclick="toggleInterest(this)">
                        <input type="checkbox" name="interests[]" value="Commerce & Business">
                        <span>💼 Commerce & Business</span>
                    </label>
                    
                    <label class="interest-item" onclick="toggleInterest(this)">
                        <input type="checkbox" name="interests[]" value="Arts & Humanities">
                        <span>🎨 Arts & Humanities</span>
                    </label>
                    
                    <label class="interest-item" onclick="toggleInterest(this)">
                        <input type="checkbox" name="interests[]" value="Law & Social Sciences">
                        <span>⚖️ Law & Social Sciences</span>
                    </label>
                    
                    <label class="interest-item" onclick="toggleInterest(this)">
                        <input type="checkbox" name="interests[]" value="Creative Arts & Design">
                        <span>🎭 Creative Arts & Design</span>
                    </label>
                    
                    <label class="interest-item" onclick="toggleInterest(this)">
                        <input type="checkbox" name="interests[]" value="Vocational / Skilled Trades">
                        <span>🔧 Vocational / Skilled Trades</span>
                    </label>
                    
                    <label class="interest-item" onclick="toggleInterest(this)">
                        <input type="checkbox" name="interests[]" value="Sports & Physical Education">
                        <span>⚽ Sports & Physical Education</span>
                    </label>
                </div>
                
                <div style="margin-top: 15px; padding: 10px; background: #f0f9ff; border-radius: 8px; text-align: center; color: #1e40af;">
                    💡 <strong>Selected Interests:</strong> <span id="interest-count">0</span>/3
                </div>
            </div>

            <button type="submit" class="submit-btn">🚀 Get Career Suggestions</button>
        </form>
    </div>

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
                alert('Please select exactly 3 interest areas for better results.');
            }
            
            updateInterestCount();
        }
        
        function updateInterestCount() {
            const checkedBoxes = document.querySelectorAll('.interest-item input[type="checkbox"]:checked');
            document.getElementById('interest-count').textContent = checkedBoxes.length;
        }
        
        function handleLanguageSelection(selectedLanguage) {
            const languages = ['Sinhala', 'Tamil'];
            languages.forEach(lang => {
                if (lang !== selectedLanguage) {
                    document.getElementById(lang).value = '';
                }
            });
            updateSubjectCounts();
        }
        
        function handleReligionSelection(selectedReligion) {
            const religions = ['Buddhism', 'Christianity', 'Hinduism', 'Islam'];
            religions.forEach(religion => {
                if (religion !== selectedReligion) {
                    document.getElementById(religion).value = '';
                }
            });
            updateSubjectCounts();
        }
        
        function updateSubjectCounts() {
            // Count compulsory subjects
            const coreSubjects = ['Mathematics', 'Science', 'English', 'History'];
            const languages = ['Sinhala', 'Tamil'];
            const religions = ['Buddhism', 'Christianity', 'Hinduism', 'Islam'];
            
            let compulsoryCount = 0;
            
            // Count core subjects
            coreSubjects.forEach(subject => {
                const select = document.getElementById(subject);
                if (select && select.value !== '') {
                    compulsoryCount++;
                }
            });
            
            // Count selected language
            const selectedLanguage = languages.find(lang => {
                const select = document.getElementById(lang);
                return select && select.value !== '';
            });
            if (selectedLanguage) compulsoryCount++;
            
            // Count selected religion
            const selectedReligion = religions.find(religion => {
                const select = document.getElementById(religion);
                return select && select.value !== '';
            });
            if (selectedReligion) compulsoryCount++;
            
            // Count optional subjects
            const optionalSelects = document.querySelectorAll('.optional-grade');
            const optionalCount = Array.from(optionalSelects).filter(select => select.value !== '').length;
            
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
            const selectedCount = Array.from(optionalSelects).filter(select => select.value !== '').length;
            
            // If trying to select more than 3, reset the changed select
            if (selectedCount > 3 && changedSelect.value !== '') {
                changedSelect.value = '';
                alert('You can only select exactly 3 optional subjects for O/L examination.');
            }
            
            // Update styling
            optionalSelects.forEach(select => {
                const container = select.closest('.optional-subject');
                if (select.value !== '') {
                    container.classList.add('selected');
                } else {
                    container.classList.remove('selected');
                }
            });
            
            updateSubjectCounts();
        }

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            // Check interests
            const checkedInterests = document.querySelectorAll('input[name="interests[]"]:checked');
            if (checkedInterests.length === 0) {
                e.preventDefault();
                alert('Please select at least one career interest.');
                return;
            }
            
            if (checkedInterests.length > 3) {
                e.preventDefault();
                alert('Please select maximum 3 career interests.');
                return;
            }

            // Check compulsory subjects
            const compulsoryCount = parseInt(document.getElementById('compulsory-count').textContent);
            if (compulsoryCount < 6) {
                e.preventDefault();
                alert('Please complete all 6 compulsory subjects (4 core + 1 language + 1 religion).');
                return;
            }

            // Check optional subjects
            const optionalCount = parseInt(document.getElementById('optional-count').textContent);
            if (optionalCount !== 3) {
                e.preventDefault();
                alert('Please select exactly 3 optional subjects.');
                return;
            }
        });
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
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
            updateInterestCount();
        });
    </script>
</body>
</html>
