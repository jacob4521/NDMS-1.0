<?php
include "config.php";

// Role check - Only Education Officers and Admins can access
if (!isset($_SESSION['UserID']) || !in_array($_SESSION['Role'], ['Admin', 'EducationOfficer'])) {
    header("Location: login.php");
    exit;
}

$citizenId = $_GET['citizen_id'] ?? 0;
$searchTerm = $_GET['search'] ?? '';

// Build query based on search criteria
if ($citizenId > 0) {
    // View specific citizen's records
    $stmt = $conn->prepare("
        SELECT er.*, c.FirstName, c.LastName, c.Citizen_eID, s.SubjectName, s.Category as SubjectCategory, u.Username as RegisteredByName 
        FROM EducationRecords er 
        JOIN Citizens c ON er.CitizenID = c.CitizenID
        LEFT JOIN Subjects s ON er.SubjectID = s.SubjectID
        LEFT JOIN Users u ON er.RegisteredBy = u.UserID
        WHERE er.CitizenID = ?
        ORDER BY er.RecordDate DESC, er.RegisteredAt DESC
    ");
    $stmt->bind_param("i", $citizenId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Get citizen info
    $citizenInfo = $conn->query("SELECT * FROM Citizens WHERE CitizenID = $citizenId")->fetch_assoc();
    
} elseif (!empty($searchTerm)) {
    // Search by name or citizen ID
    $searchPattern = "%$searchTerm%";
    $stmt = $conn->prepare("
        SELECT er.*, c.FirstName, c.LastName, c.Citizen_eID, s.SubjectName, s.Category as SubjectCategory, u.Username as RegisteredByName 
        FROM EducationRecords er 
        JOIN Citizens c ON er.CitizenID = c.CitizenID
        LEFT JOIN Subjects s ON er.SubjectID = s.SubjectID
        LEFT JOIN Users u ON er.RegisteredBy = u.UserID
        WHERE c.FirstName LIKE ? OR c.LastName LIKE ? OR c.Citizen_eID LIKE ?
        ORDER BY er.RegisteredAt DESC
    ");
    $stmt->bind_param("sss", $searchPattern, $searchPattern, $searchPattern);
    $stmt->execute();
    $result = $stmt->get_result();
    
} else {
    // Show all records
    $result = $conn->query("
        SELECT er.*, c.FirstName, c.LastName, c.Citizen_eID, s.SubjectName, s.Category as SubjectCategory, u.Username as RegisteredByName 
        FROM EducationRecords er 
        JOIN Citizens c ON er.CitizenID = c.CitizenID
        LEFT JOIN Subjects s ON er.SubjectID = s.SubjectID
        LEFT JOIN Users u ON er.RegisteredBy = u.UserID
        ORDER BY er.RegisteredAt DESC
        LIMIT 100
    ");
}

// Get statistics
$statsQuery = $conn->query("
    SELECT 
        COUNT(*) as totalRecords,
        COUNT(DISTINCT CitizenID) as totalStudents,
        COUNT(CASE WHEN GradeLevel LIKE '%Level%' THEN 1 END) as examRecords
    FROM EducationRecords
");
$stats = $statsQuery->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="includes/sidebar.css">
    <title>View Education Records - NDMS</title>
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
            border: 1px solid transparent;
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
        
        .nav a.btn-secondary {
            background: var(--text-secondary);
        }
        
        .stats-section { 
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px; 
            margin: 25px 0; 
        }
        
        .stat-box { 
            background: var(--gradient-bg);
            color: white; 
            padding: 25px; 
            border-radius: 15px; 
            text-align: center; 
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        
        .stat-box:hover {
            transform: translateY(-5px);
        }
        
        .stat-box::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100px;
            height: 100px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }
        
        .stat-box h3 {
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 8px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stat-box p {
            font-size: 14px;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .citizen-info { 
            background: linear-gradient(135deg, var(--accent-color), #059669);
            color: white; 
            padding: 25px; 
            border-radius: 15px; 
            margin-bottom: 25px;
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
        }
        
        .citizen-info::before {
            content: '👤';
            position: absolute;
            top: 20px;
            right: 25px;
            font-size: 32px;
            opacity: 0.3;
        }
        
        .citizen-info h3 {
            font-size: 22px;
            margin-bottom: 12px;
            font-weight: 700;
        }
        
        .search-section { 
            background: var(--card-bg);
            padding: 25px; 
            margin: 25px 0; 
            border-radius: 15px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
        }
        
        .search-section h3 {
            color: var(--primary-color);
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-row { 
            display: grid;
            grid-template-columns: 1fr 1fr auto;
            gap: 20px; 
            align-items: end; 
        }
        
        .form-group input { 
            padding: 12px 16px; 
            border: 2px solid var(--border-color); 
            border-radius: 10px; 
            width: 100%; 
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: inherit;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .btn { 
            background: var(--gradient-bg);
            color: white; 
            padding: 12px 20px; 
            border: none; 
            border-radius: 10px; 
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-right: 10px;
        }
        
        .btn:hover { 
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            filter: brightness(1.1);
        }
        
        .btn-secondary { 
            background: var(--text-secondary) !important;
        }
        
        .records-section { 
            margin-top: 25px; 
            background: var(--card-bg);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
        }
        
        .records-section h3 {
            background: var(--gradient-bg);
            color: white;
            padding: 20px 25px;
            margin: 0;
            font-size: 20px;
            font-weight: 700;
        }
        
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 0;
            background: var(--card-bg);
        }
        
        th, td { 
            padding: 15px 12px; 
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        th { 
            background: var(--primary-color);
            color: white;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: sticky; 
            top: 0;
            z-index: 10;
        }
        
        tbody tr:hover {
            background: var(--light-bg);
        }
        
        .grade-badge { 
            background: var(--secondary-color);
            color: white; 
            padding: 4px 12px; 
            border-radius: 20px; 
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .result-badge { 
            background: var(--accent-color);
            color: white; 
            padding: 4px 12px; 
            border-radius: 20px; 
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Subject Badge Styles */
        .subject-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
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
        .subject-badge.technical { background: #64748b; color: white; }
        .subject-badge.other { background: var(--text-secondary); color: white; }
        
        .text-muted {
            color: var(--text-secondary);
            font-style: italic;
            font-size: 12px;
        }
        
        .no-records { 
            text-align: center; 
            padding: 60px 20px; 
            color: var(--text-secondary);
            background: var(--card-bg);
            margin: 25px;
            border-radius: 15px;
        }
        
        .no-records h4 {
            font-size: 24px;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        
        .no-records a {
            color: var(--secondary-color);
            text-decoration: none;
            font-weight: 600;
        }
        
        .no-records a:hover {
            text-decoration: underline;
        }
        
        .records-footer {
            margin-top: 20px; 
            padding: 15px 25px; 
            background: var(--light-bg); 
            border-radius: 0 0 15px 15px;
            border-top: 1px solid var(--border-color);
        }
        
        .records-footer small {
            color: var(--text-secondary);
            font-size: 13px;
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
            
            .stats-section {
                grid-template-columns: 1fr;
            }
            
            table {
                font-size: 14px;
            }
            
            th, td {
                padding: 10px 8px;
            }
        }
        
        /* Loading Animation */
        .records-section {
            animation: slideUp 0.3s ease-out;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
    <div class="container">
        <div class="header">
            <h1>📚 Education Records Database</h1>
            <p>National Digital Management System - Academic Achievement Tracking & Verification</p>
        </div>
        
        <div class="nav">
            <a href="dashboard.php">🏠 Dashboard</a>
            <a href="add_education.php">➕ Add New Record</a>
            <a href="manage_subjects.php">📖 Manage Subjects</a>
            <?php if ($citizenId > 0): ?>
                <a href="view_education.php" class="btn-secondary">📋 View All Records</a>
            <?php endif; ?>
        </div>

        <div class="stats-section">
            <div class="stat-box">
                <h3><?= $stats['totalRecords'] ?></h3>
                <p>Total Records</p>
            </div>
            <div class="stat-box">
                <h3><?= $stats['totalStudents'] ?></h3>
                <p>Students</p>
            </div>
            <div class="stat-box">
                <h3><?= $stats['examRecords'] ?></h3>
                <p>Exam Records</p>
            </div>
        </div>

        <?php if (isset($citizenInfo)): ?>
            <div class="citizen-info">
                <h3>📋 Education Records for: <?= htmlspecialchars($citizenInfo['FirstName'] . ' ' . $citizenInfo['LastName']) ?></h3>
                <p><strong>Citizen ID:</strong> <?= $citizenInfo['CitizenID'] ?> | 
                   <strong>NIC:</strong> <?= htmlspecialchars($citizenInfo['NIC'] ?? 'Not Set') ?> | 
                   <strong>DOB:</strong> <?= $citizenInfo['DOB'] ?></p>
            </div>
        <?php endif; ?>

        <div class="search-section">
            <h3>🔍 Search Education Records</h3>
            <form method="GET">
                <div class="form-row">
                    <div class="form-group">
                        <label for="citizen_eid">Citizen eID:</label>
                        <div class="autocomplete-container">
                            <input type="text" name="citizen_eid" id="citizen_eid" placeholder="Type citizen eID or name..." autocomplete="off">
                            <div id="suggestions" class="suggestions-dropdown"></div>
                        </div>
                        <small>Start typing the citizen's eID or name to see suggestions</small>
                    </div>
                    <div class="form-group">
                        <input type="text" name="search" placeholder="Search by citizen name or ID..." 
                               value="<?= htmlspecialchars($searchTerm) ?>">
                    </div>
                    <div class="form-group">
                        <input type="number" name="citizen_id" placeholder="Specific Citizen ID..." 
                               value="<?= $citizenId > 0 ? $citizenId : '' ?>">
                    </div>
                    <div class="form-group" style="flex: 0;">
                        <button type="submit" class="btn">Search</button>
                        <a href="view_education.php" class="btn btn-secondary">Clear</a>
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
                    this.style.borderColor = '#3b82f6';
                    this.style.backgroundColor = '#f8fafc';
                    debounceTimeout = setTimeout(() => {
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
                    hideSuggestions();
                }

                function hideSuggestions() {
                    suggestionsContainer.innerHTML = '';
                    suggestionsContainer.style.display = 'none';
                    selectedIndex = -1;
                }
                </script>
            </form>
        </div>

        <div class="records-section">
            <h3>Education Records
                <?php if ($citizenId > 0): ?>
                    - Citizen ID: <?= $citizenId ?>
                <?php elseif (!empty($searchTerm)): ?>
                    - Search: "<?= htmlspecialchars($searchTerm) ?>"
                <?php else: ?>
                    - All Records (Last 50)
                <?php endif; ?>
            </h3>
            
            <?php if ($result->num_rows == 0): ?>
                <div class="no-records">
                    <h4>No education records found</h4>
                    <p>
                        <?php if ($citizenId > 0 || !empty($searchTerm)): ?>
                            Try adjusting your search criteria or <a href="add_education.php">add a new education record</a>.
                        <?php else: ?>
                            <a href="add_education.php">Add the first education record</a> to get started.
                        <?php endif; ?>
                    </p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Citizen</th>
                            <th>School/Institution</th>
                            <th>Grade/Level</th>
                            <th>Exam/Assessment</th>
                            <th>Subject</th>
                            <th>Result</th>
                            <th>Marks/Score</th>
                            <th>Record Date</th>
                            <th>Registered By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($record = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($record['FirstName'] . ' ' . $record['LastName']) ?></strong><br>
                                    <small>ID: <?= $record['CitizenID'] ?></small>
                                </td>
                                <td><?= htmlspecialchars($record['SchoolName'] ?? '') ?></td>
                                <td>
                                    <span class="grade-badge"><?= htmlspecialchars($record['GradeLevel'] ?? '') ?></span>
                                </td>
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
                                <td>
                                    <?php if (!empty($record['Result'])): ?>
                                        <span class="result-badge"><?= htmlspecialchars($record['Result'] ?? '') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($record['MarksObtained'] ?? '') ?></td>
                                <td><?= $record['RecordDate'] ?></td>
                                <td><?= htmlspecialchars($record['RegisteredByName'] ?? 'Unknown') ?></td>
                                <td>
                                    <a href="view_education.php?citizen_id=<?= $record['CitizenID'] ?>" 
                                       style="color: #007cba; text-decoration: none; font-size: 12px;">
                                        View All
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                
                <div class="records-footer">
                    <small>
                        📊 Showing <?= $result->num_rows ?> record(s). 
                        <?php if (!$citizenId && empty($searchTerm)): ?>
                            Limited to last 50 records for optimal performance.
                        <?php endif; ?>
                        | Last updated: <?= date('Y-m-d H:i:s') ?>
                    </small>
                </div>
            <?php endif; ?>
        </div>
    </div>
    </div>
    <script src="includes/sidebar.js"></script>
</body>
</html>
