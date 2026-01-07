<?php
include "config.php";

// Role check - Only Employers can access
if (!isset($_SESSION['UserID']) || $_SESSION['Role'] != "Employer") {
    header("Location: login.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $citizenEID = $_POST['citizen_eid'];
    $companyName = $_POST['company_name'];
    $jobTitle = $_POST['job_title'];
    $startDate = $_POST['start_date'];
    $endDate = !empty($_POST['end_date']) ? $_POST['end_date'] : NULL;
    $salary = !empty($_POST['salary']) ? $_POST['salary'] : NULL;
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
        
        $stmt = $conn->prepare("INSERT INTO EmploymentRecords (CitizenID, CompanyName, JobTitle, StartDate, EndDate, Salary, RegisteredBy) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssdi", $citizenId, $companyName, $jobTitle, $startDate, $endDate, $salary, $registeredBy);
        
        if ($stmt->execute()) {
            $msg = "Employment record added successfully for Citizen eID: $citizenEID";
        } else {
            $error = "Error adding record: " . $stmt->error;
        }
        $stmt->close();
    }
    $citizenStmt->close();
}

// Get recent employment records
$recentRecords = $conn->query("
    SELECT er.*, c.FirstName, c.LastName, c.Citizen_eID, u.Username as RegisteredByName
    FROM EmploymentRecords er
    JOIN Citizens c ON er.CitizenID = c.CitizenID
    JOIN Users u ON er.RegisteredBy = u.UserID
    ORDER BY er.RegisteredAt DESC
    LIMIT 10
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Employment Records Management - NDMS</title>
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
            padding: 20px;
        }
        
        .container { 
            max-width: 1400px; 
            margin: 0 auto; 
        }
        
        .nav-bar {
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
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-xl);
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
        
        .content-section {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            position: relative;
            margin-bottom: 30px;
        }
        
        .content-section::before {
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
        
        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .form-group {
            flex: 1;
            min-width: 250px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-primary);
            font-weight: 600;
            font-size: 14px;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 14px;
            transition: border-color 0.3s ease;
            background: var(--card-bg);
            font-family: inherit;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
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
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: var(--shadow-sm);
            cursor: pointer;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            filter: brightness(1.1);
            color: white;
        }
        
        .btn-success {
            background: var(--accent-color);
        }
        
        .btn-success:hover {
            background: #059669;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: var(--card-bg);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
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
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .active {
            background: var(--accent-color);
            color: white;
        }
        
        .inactive {
            background: var(--danger-color);
            color: white;
        }
        
        .currency {
            color: var(--primary-color);
            font-weight: 700;
        }
        
        .success {
            color: var(--accent-color);
            font-weight: 700;
        }
        
        .error {
            color: var(--danger-color);
            font-weight: 700;
        }
        
        .text-muted {
            color: var(--text-secondary);
        }
        
        .text-center {
            text-align: center;
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
            transition: background 0.3s ease;
        }
        
        .suggestion-item:hover {
            background: var(--light-bg);
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
                font-size: 28px;
            }
            
            .nav-bar {
                flex-direction: column;
                gap: 10px;
            }
            
            .form-row {
                flex-direction: column;
            }
            
            .form-group {
                min-width: 100%;
            }
            
            .table {
                font-size: 14px;
            }
            
            .table th,
            .table td {
                padding: 10px 8px;
            }
        }
        
        /* Loading Animation */
        .content-section, .nav-bar {
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
            <div class="nav-bar">
                <a href="dashboard.php">🏠 Dashboard</a>
                <a href="view_employment.php">👁️ View All Records</a>
            </div>

            <div class="header">
                <h1>💼 Employment Records Management</h1>
                <p>National Digital Management System - Employer Officer Portal</p>
            </div>

            <div class="content-section">
                <div class="section-header">
                    <h2>➕ Add New Employment Record</h2>
                    <p class="text-muted">Register a new citizen employment record</p>
                </div>
                
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="citizen_eid">Citizen eID:</label>
                            <div class="autocomplete-container">
                                <input type="text" name="citizen_eid" id="citizen_eid" required placeholder="Type citizen eID or name..." autocomplete="off">
                                <div id="suggestions" class="suggestions-dropdown"></div>
                            </div>
                            <small class="text-muted">Start typing the citizen's eID or name to see suggestions</small>
                        </div>
                        <div class="form-group">
                            <label for="company_name">Company/Organization Name:</label>
                            <input type="text" name="company_name" id="company_name" required placeholder="e.g., Dialog Axiata PLC">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="job_title">Job Title/Position:</label>
                            <input type="text" name="job_title" id="job_title" required placeholder="e.g., Software Engineer, Manager">
                        </div>
                        <div class="form-group">
                            <label for="salary">Monthly Salary (LKR):</label>
                            <input type="number" name="salary" id="salary" step="0.01" min="0" placeholder="e.g., 75000.00">
                            <small class="text-muted">Optional - Leave empty if confidential</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="start_date">Employment Start Date:</label>
                            <input type="date" name="start_date" id="start_date" required>
                        </div>
                        <div class="form-group">
                            <label for="end_date">Employment End Date:</label>
                            <input type="date" name="end_date" id="end_date">
                            <small class="text-muted">Leave empty if currently employed</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group" style="display: flex; align-items: end;">
                            <button type="submit" class="btn btn-success">💼 Add Employment Record</button>
                        </div>
                    </div>
                </form>

                <?php if(isset($msg)): ?>
                    <div style="margin-top: 20px; padding: 15px; background: #dcfce7; color: #166534; border-radius: 10px; border: 1px solid #bbf7d0;">
                        <strong>✅ Success:</strong> <?= htmlspecialchars($msg) ?>
                    </div>
                <?php endif; ?>
                
                <?php if(isset($error)): ?>
                    <div style="margin-top: 20px; padding: 15px; background: #fef2f2; color: #991b1b; border-radius: 10px; border: 1px solid #fecaca;">
                        <strong>❌ Error:</strong> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="content-section">
                <div class="section-header">
                    <h2>🔍 Quick Search Employment Records</h2>
                    <p class="text-muted">Search existing employment records</p>
                </div>
                <form method="GET" action="view_employment.php">
                    <div class="form-row">
                        <div class="form-group">
                            <input type="text" name="search" placeholder="Search by name, company, or job title..." style="padding: 12px;">
                        </div>
                        <div class="form-group" style="flex: 0;">
                            <button type="submit" class="btn">🔍 Search Records</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="content-section">
                <div class="section-header">
                    <h2>📋 Recent Employment Records</h2>
                    <p class="text-muted">Latest 10 employment records added to the system</p>
                </div>
                
                <?php if ($recentRecords->num_rows == 0): ?>
                    <div class="text-center" style="padding: 60px 20px;">
                        <h3 style="color: var(--text-secondary); margin-bottom: 15px;">📄 No employment records found</h3>
                        <p class="text-muted">No employment records have been added yet.</p>
                    </div>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Company</th>
                                <th>Job Title</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Status</th>
                                <th>Salary</th>
                                <th>Registered By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($record = $recentRecords->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <a href="view_employment.php?citizen_id=<?= $record['CitizenID'] ?>" style="color: var(--primary-color); text-decoration: none; font-weight: 600;">
                                            <?= htmlspecialchars($record['FirstName'] . ' ' . $record['LastName']) ?>
                                            <br><small class="text-muted">eID: <?= htmlspecialchars($record['Citizen_eID'] ?? 'N/A') ?></small>
                                        </a>
                                    </td>
                                    <td><strong><?= htmlspecialchars($record['CompanyName'] ?? '') ?></strong></td>
                                    <td><?= htmlspecialchars($record['JobTitle'] ?? '') ?></td>
                                    <td><?= $record['StartDate'] ?></td>
                                    <td><?= $record['EndDate'] ?? '<span class="text-muted">Current</span>' ?></td>
                                    <td>
                                        <?php if (empty($record['EndDate'])): ?>
                                            <span class="status-badge active">✅ Active</span>
                                        <?php else: ?>
                                            <span class="status-badge inactive">⛔ Ended</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($record['Salary']): ?>
                                            <span class="currency">LKR <?= number_format($record['Salary'], 2) ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">Not disclosed</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($record['RegisteredByName'] ?? '') ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="includes/sidebar.js"></script>
    <script>
        // Autocomplete functionality for citizen eID
        const citizenInput = document.getElementById('citizen_eid');
        const suggestionsContainer = document.getElementById('suggestions');
        let currentSuggestions = [];
        let selectedIndex = -1;

        citizenInput.addEventListener('input', function() {
            const query = this.value.trim();
            
            if (query.length < 1) {
                hideSuggestions();
                return;
            }

            fetch(`api_citizen_suggestions.php?q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    currentSuggestions = data;
                    displaySuggestions(data);
                })
                .catch(error => {
                    console.error('Error fetching suggestions:', error);
                    hideSuggestions();
                });
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
                hideSuggestions();
                return;
            }

            suggestions.forEach((suggestion, index) => {
                const item = document.createElement('div');
                item.className = 'suggestion-item';
                item.innerHTML = `
                    <div class="suggestion-eid">${suggestion.eid}</div>
                    <div class="suggestion-name">${suggestion.name}</div>
                `;
                
                item.addEventListener('click', () => selectSuggestion(suggestion));
                suggestionsContainer.appendChild(item);
            });

            suggestionsContainer.style.display = 'block';
        }

        function updateSelection() {
            const items = suggestionsContainer.querySelectorAll('.suggestion-item');
            items.forEach((item, index) => {
                if (index === selectedIndex) {
                    item.style.backgroundColor = '#e9ecef';
                } else {
                    item.style.backgroundColor = '';
                }
            });
        }

        function selectSuggestion(suggestion) {
            citizenInput.value = suggestion.eid;
            hideSuggestions();
            citizenInput.focus();
        }

        function hideSuggestions() {
            suggestionsContainer.style.display = 'none';
            currentSuggestions = [];
            selectedIndex = -1;
        }

        document.addEventListener('click', function(e) {
            if (!citizenInput.contains(e.target) && !suggestionsContainer.contains(e.target)) {
                hideSuggestions();
            }
        });
    </script>
</body>
</html>
