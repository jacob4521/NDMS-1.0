<?php
include "config.php";

// Role check - Only Medical Officers and Admin can access
if (!isset($_SESSION['UserID']) || !in_array($_SESSION['Role'], ['MedicalOfficer', 'Admin'])) {
    header("Location: login.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $citizenEID = $_POST['citizen_eid'];
    $diagnosis = $_POST['diagnosis'];
    $treatment = $_POST['treatment'];
    $doctorName = $_POST['doctor_name'];
    $hospitalName = $_POST['hospital_name'];
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
        
        $stmt = $conn->prepare("INSERT INTO MedicalRecords (citizenID, recordDate, hospitalName, diagnosis, treatment, doctorName) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssss", $citizenId, $recordDate, $hospitalName, $diagnosis, $treatment, $doctorName);
        
        if ($stmt->execute()) {
            $msg = "Medical record added successfully for Citizen eID: $citizenEID";
        } else {
            $error = "Error adding record: " . $stmt->error;
        }
        $stmt->close();
    }
    $citizenStmt->close();
}

// Get recent medical records
$recentRecords = $conn->query("
    SELECT mr.*, c.FirstName, c.LastName, c.Citizen_eID
    FROM MedicalRecords mr
    JOIN Citizens c ON mr.citizenID = c.CitizenID
    ORDER BY mr.recordDate DESC
    LIMIT 10
");
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="includes/sidebar.css">
    <title>Medical Records Management - NDMS</title>
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
        
        .nav-bar a:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            filter: brightness(1.1);
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
        .form-group select, 
        .form-group textarea { 
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
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-group textarea { 
            min-height: 100px; 
            resize: vertical; 
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
            
            .nav-bar {
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
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>
    <div class="main-content">
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🏥 Medical Records Management</h1>
            <p>Comprehensive healthcare tracking and medical record management system</p>
        </div>
        
        <!-- Navigation -->
        <div class="nav-bar">
            <a href="dashboard.php">🏠 Dashboard</a>
            <a href="add_vaccination.php">💉 Add Vaccination Record</a>
            <a href="add_education.php">🎓 Add Education Record</a>
            <a href="add_employment.php">💼 Add Employment Record</a>
            <a href="manage_vaccines.php">💊 Manage Vaccines</a>
            <a href="view_citizen_role_based.php">👥 View Citizens</a>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($msg)): ?>
            <div class="success">
                <strong>Success!</strong> <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="error">
                <strong>Error!</strong> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Add Medical Record Form -->
        <div class="form-section">
            <div class="section-title">
                ➕ Add New Medical Record
            </div>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="citizen_eid">Citizen eID:</label>
                        <div class="autocomplete-container">
                            <input type="text" name="citizen_eid" id="citizen_eid" required placeholder="Type eID or name to search... (e.g., CIT001)">
                            <div id="suggestions-dropdown" class="suggestions-dropdown"></div>
                        </div>
                        <small>Start typing citizen eID or name to see suggestions</small>
                    </div>
                    <div class="form-group">
                        <label for="record_date">Record Date:</label>
                        <input type="date" name="record_date" id="record_date" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="hospital_name">Hospital/Clinic Name:</label>
                        <input type="text" name="hospital_name" id="hospital_name" required placeholder="e.g., National Hospital Colombo">
                    </div>
                    <div class="form-group">
                        <label for="doctor_name">Doctor Name:</label>
                        <input type="text" name="doctor_name" id="doctor_name" required placeholder="e.g., Dr. Silva">
                    </div>
                </div>

                <div class="form-group">
                    <label for="diagnosis">Diagnosis:</label>
                    <textarea name="diagnosis" id="diagnosis" required placeholder="Patient's medical condition or diagnosis..."></textarea>
                </div>

                <div class="form-group">
                    <label for="treatment">Treatment:</label>
                    <textarea name="treatment" id="treatment" required placeholder="Treatment provided, medications prescribed, etc..."></textarea>
                </div>

                <button type="submit" class="btn">🏥 Add Medical Record</button>
            </form>
        </div>

        <!-- Recent Records -->
        <div class="records-section">
            <h3>📋 Recent Medical Records</h3>
            <table>
                <thead>
                    <tr>
                        <th>Citizen</th>
                        <th>Date</th>
                        <th>Hospital</th>
                        <th>Doctor</th>
                        <th>Diagnosis</th>
                        <th>Treatment</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($record = $recentRecords->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <a href="view_citizen.php?citizen_id=<?= $record['citizenID'] ?>" style="color: #007cba; text-decoration: none;">
                                    <?= htmlspecialchars(($record['FirstName'] ?? '') . ' ' . ($record['LastName'] ?? '')) ?>
                                    <br><small>eID: <?= htmlspecialchars($record['Citizen_eID'] ?? 'N/A') ?></small>
                                </a>
                            </td>
                            <td><?= $record['recordDate'] ?? '' ?></td>
                            <td><?= htmlspecialchars($record['hospitalName'] ?? '') ?></td>
                            <td><?= htmlspecialchars($record['doctorName'] ?? '') ?></td>
                            <td><?= htmlspecialchars($record['diagnosis'] ?? '') ?></td>
                            <td><?= htmlspecialchars($record['treatment'] ?? '') ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    let currentFocus = -1;
    
    function initializeAutocomplete() {
        const eidInput = document.getElementById('citizen_eid');
        const suggestionsDropdown = document.getElementById('suggestions-dropdown');
        let currentSelection = -1;
        
        eidInput.addEventListener('input', function() {
            const query = this.value.trim();
            if (query.length >= 2) {
                fetchSuggestions(query);
            } else {
                hideSuggestions();
            }
        });
        
        eidInput.addEventListener('keydown', function(e) {
            const suggestions = suggestionsDropdown.querySelectorAll('.suggestion-item');
            
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                currentSelection = (currentSelection + 1) % suggestions.length;
                updateSelection(suggestions);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                currentSelection = currentSelection <= 0 ? suggestions.length - 1 : currentSelection - 1;
                updateSelection(suggestions);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (currentSelection >= 0 && suggestions[currentSelection]) {
                    selectSuggestion(suggestions[currentSelection]);
                }
            } else if (e.key === 'Escape') {
                hideSuggestions();
            }
        });
        
        // Hide suggestions when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.autocomplete-container')) {
                hideSuggestions();
            }
        });
    }
    
    function fetchSuggestions(query) {
        fetch('api_citizen_suggestions.php?query=' + encodeURIComponent(query))
            .then(response => response.json())
            .then(data => {
                displaySuggestions(data);
            })
            .catch(error => {
                console.error('Error fetching suggestions:', error);
            });
    }
    
    function displaySuggestions(suggestions) {
        const dropdown = document.getElementById('suggestions-dropdown');
        dropdown.innerHTML = '';
        
        if (suggestions.length === 0) {
            dropdown.style.display = 'none';
            return;
        }
        
        suggestions.forEach(suggestion => {
            const item = document.createElement('div');
            item.className = 'suggestion-item';
            item.innerHTML = `
                <div class="suggestion-eid">${suggestion.eid}</div>
                <div class="suggestion-name">${suggestion.name}</div>
            `;
            item.dataset.eid = suggestion.eid;
            item.dataset.name = suggestion.name;
            item.dataset.citizenId = suggestion.citizen_id;
            
            item.addEventListener('click', function() {
                selectSuggestion(this);
            });
            
            dropdown.appendChild(item);
        });
        
        dropdown.style.display = 'block';
        currentSelection = -1;
    }
    
    function updateSelection(suggestions) {
        suggestions.forEach((item, index) => {
            if (index === currentSelection) {
                item.style.backgroundColor = '#e9ecef';
            } else {
                item.style.backgroundColor = '';
            }
        });
    }
    
    function selectSuggestion(item) {
        const eidInput = document.getElementById('citizen_eid');
        eidInput.value = item.dataset.eid;
        hideSuggestions();
        currentSelection = -1;
    }
    
    function hideSuggestions() {
        document.getElementById('suggestions-dropdown').style.display = 'none';
        currentSelection = -1;
    }
    
    // Initialize when page loads
    document.addEventListener('DOMContentLoaded', initializeAutocomplete);
    </script>
    </div>
    <script src="includes/sidebar.js"></script>
</body>
</html>
