<?php
include "config.php";

// Role check - Only Employers can access
if (!isset($_SESSION['UserID']) || $_SESSION['Role'] != "Employer") {
    header("Location: login.php");
    exit;
}

$citizenId = $_GET['citizen_id'] ?? 0;
$searchTerm = $_GET['search'] ?? '';

// Build query based on search criteria
if ($citizenId > 0) {
    // View specific citizen's records
    $stmt = $conn->prepare("
        SELECT er.*, c.FirstName, c.LastName, c.NIC, u.Username as RegisteredByName 
        FROM EmploymentRecords er 
        JOIN Citizens c ON er.CitizenID = c.CitizenID
        LEFT JOIN Users u ON er.RegisteredBy = u.UserID
        WHERE er.CitizenID = ?
        ORDER BY er.StartDate DESC, er.RegisteredAt DESC
    ");
    $stmt->bind_param("i", $citizenId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Get citizen info
    $citizenInfo = $conn->query("SELECT * FROM Citizens WHERE CitizenID = $citizenId")->fetch_assoc();
    
} elseif (!empty($searchTerm)) {
    // Search by name, company, or job title
    $searchPattern = "%$searchTerm%";
    $stmt = $conn->prepare("
        SELECT er.*, c.FirstName, c.LastName, c.NIC, u.Username as RegisteredByName 
        FROM EmploymentRecords er 
        JOIN Citizens c ON er.CitizenID = c.CitizenID
        LEFT JOIN Users u ON er.RegisteredBy = u.UserID
        WHERE c.FirstName LIKE ? OR c.LastName LIKE ? OR er.CompanyName LIKE ? OR er.JobTitle LIKE ?
        ORDER BY er.RegisteredAt DESC
    ");
    $stmt->bind_param("ssss", $searchPattern, $searchPattern, $searchPattern, $searchPattern);
    $stmt->execute();
    $result = $stmt->get_result();
    
} else {
    // Show all records
    $result = $conn->query("
        SELECT er.*, c.FirstName, c.LastName, c.NIC, u.Username as RegisteredByName 
        FROM EmploymentRecords er 
        JOIN Citizens c ON er.CitizenID = c.CitizenID
        LEFT JOIN Users u ON er.RegisteredBy = u.UserID
        ORDER BY er.RegisteredAt DESC
        LIMIT 50
    ");
}

// Get statistics
$statsQuery = $conn->query("
    SELECT 
        COUNT(*) as totalRecords,
        COUNT(DISTINCT CitizenID) as totalEmployees,
        COUNT(CASE WHEN EndDate IS NULL THEN 1 END) as activeEmployments,
        AVG(Salary) as avgSalary
    FROM EmploymentRecords
");
$stats = $statsQuery->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="includes/sidebar.css">
    <title>View Employment Records - NDMS</title>
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
            background: var(--gradient-bg);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .nav a:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            filter: brightness(1.1);
        }
        
        .header-section {
            background: var(--gradient-bg);
            color: white;
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-xl);
        }
        
        .header-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 200px;
            height: 200px;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="2"/><circle cx="50" cy="50" r="30" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/><circle cx="50" cy="50" r="20" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></svg>') no-repeat center;
            opacity: 0.3;
        }
        
        .header-section h1 {
            font-size: 36px;
            font-weight: 700;
            margin: 0 0 8px 0;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
            z-index: 1;
        }
        
        .header-section p {
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
        
        .search-section {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            position: relative;
        }
        
        .search-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-bg);
            border-radius: 20px 20px 0 0;
        }
        
        .citizen-info {
            background: rgba(16, 185, 129, 0.1);
            color: var(--accent-color);
            border: 1px solid rgba(16, 185, 129, 0.2);
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            font-weight: 600;
        }
        
        .form-row {
            display: flex;
            gap: 20px;
            align-items: end;
            flex-wrap: wrap;
        }
        
        .form-group {
            flex: 1;
            min-width: 200px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-primary);
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .form-group input {
            width: 100%;
            padding: 15px;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: var(--card-bg);
            color: var(--text-primary);
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            transform: translateY(-1px);
        }
        
        .btn {
            background: var(--gradient-bg);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: var(--shadow-md);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            filter: brightness(1.1);
            color: white;
        }
        
        .btn-secondary {
            background: var(--text-secondary);
        }
        
        .btn-secondary:hover {
            background: #4b5563;
        }
        
        .records-section {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            position: relative;
        }
        
        .records-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-bg);
            border-radius: 20px 20px 0 0;
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
            background: var(--primary-color);
            color: white;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            position: sticky;
            top: 0;
        }
        
        tr:hover {
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
            font-size: 16px;
        }
        
        .company-badge {
            background: var(--secondary-color);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .no-records {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
        }
        
        .no-records h4 {
            color: var(--primary-color);
            margin-bottom: 15px;
            font-size: 24px;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }
            
            .header-section {
                padding: 30px 20px;
            }
            
            .header-section h1 {
                font-size: 28px;
            }
            
            .nav {
                flex-direction: column;
                gap: 10px;
            }
            
            .stats-section {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                flex-direction: column;
            }
            
            .form-group {
                min-width: auto;
            }
            
            table {
                font-size: 14px;
            }
            
            th, td {
                padding: 10px 8px;
            }
        }
        
        /* Loading Animation */
        .stat-box, .search-section, .records-section, .nav {
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
        <div class="nav">
            <a href="dashboard.php">← Back to Dashboard</a>
            <a href="add_employment.php">+ Add New Record</a>
            <?php if ($citizenId > 0): ?>
                <a href="view_employment.php" class="btn-secondary">View All Records</a>
            <?php endif; ?>
        </div>

        <h2>💼 Employment Records Database</h2>

        <div class="stats-section">
            <div class="stat-box">
                <h3><?= $stats['totalRecords'] ?></h3>
                <p>Total Records</p>
            </div>
            <div class="stat-box">
                <h3><?= $stats['totalEmployees'] ?></h3>
                <p>Employees</p>
            </div>
            <div class="stat-box">
                <h3><?= $stats['activeEmployments'] ?></h3>
                <p>Active Jobs</p>
            </div>
            <div class="stat-box">
                <h3><?= $stats['avgSalary'] ? 'LKR ' . number_format($stats['avgSalary'], 0) : 'N/A' ?></h3>
                <p>Avg Salary</p>
            </div>
        </div>

        <?php if (isset($citizenInfo)): ?>
            <div class="citizen-info">
                <h3>💼 Employment History for: <?= htmlspecialchars($citizenInfo['FirstName'] . ' ' . $citizenInfo['LastName']) ?></h3>
                <p><strong>Citizen ID:</strong> <?= $citizenInfo['CitizenID'] ?> | 
                   <strong>NIC:</strong> <?= htmlspecialchars($citizenInfo['NIC'] ?? 'Not Set') ?> | 
                   <strong>DOB:</strong> <?= $citizenInfo['DOB'] ?></p>
            </div>
        <?php endif; ?>

        <div class="search-section">
            <h3>🔍 Search Employment Records</h3>
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
                        <input type="text" name="search" placeholder="Search by name, company, or job title..." 
                               value="<?= htmlspecialchars($searchTerm) ?>">
                    </div>
                    <div class="form-group">
                        <input type="number" name="citizen_id" placeholder="Specific Citizen ID..." 
                               value="<?= $citizenId > 0 ? $citizenId : '' ?>">
                    </div>
                    <div class="form-group" style="flex: 0;">
                        <button type="submit" class="btn">Search</button>
                        <a href="view_employment.php" class="btn btn-secondary">Clear</a>
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
            <h3>Employment Records
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
                    <h4>No employment records found</h4>
                    <p>
                        <?php if ($citizenId > 0 || !empty($searchTerm)): ?>
                            Try adjusting your search criteria or <a href="add_employment.php">add a new employment record</a>.
                        <?php else: ?>
                            <a href="add_employment.php">Add the first employment record</a> to get started.
                        <?php endif; ?>
                    </p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Company</th>
                            <th>Job Title</th>
                            <th>Employment Period</th>
                            <th>Status</th>
                            <th>Salary</th>
                            <th>Duration</th>
                            <th>Registered By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($record = $result->fetch_assoc()): ?>
                            <?php
                            // Calculate employment duration
                            $start = new DateTime($record['StartDate']);
                            $end = $record['EndDate'] ? new DateTime($record['EndDate']) : new DateTime();
                            $duration = $start->diff($end);
                            $durationText = $duration->y > 0 ? $duration->y . 'y ' : '';
                            $durationText .= $duration->m > 0 ? $duration->m . 'm' : '';
                            if (empty($durationText)) $durationText = $duration->d . 'd';
                            ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($record['FirstName'] . ' ' . $record['LastName']) ?></strong><br>
                                    <small>ID: <?= $record['CitizenID'] ?></small>
                                </td>
                                <td>
                                    <span class="company-badge"><?= htmlspecialchars($record['CompanyName'] ?? '') ?></span>
                                </td>
                                <td><?= htmlspecialchars($record['JobTitle'] ?? '') ?></td>
                                <td>
                                    <strong>Start:</strong> <?= $record['StartDate'] ?><br>
                                    <strong>End:</strong> <?= $record['EndDate'] ?? 'Current' ?>
                                </td>
                                <td>
                                    <?php if (empty($record['EndDate'])): ?>
                                        <span class="status-badge active">Active</span>
                                    <?php else: ?>
                                        <span class="status-badge inactive">Ended</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($record['Salary']): ?>
                                        <span class="currency">LKR <?= number_format($record['Salary'], 2) ?></span>
                                    <?php else: ?>
                                        <span style="color: #666;">Not disclosed</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $durationText ?></td>
                                <td><?= htmlspecialchars($record['RegisteredByName'] ?? 'Unknown') ?></td>
                                <td>
                                    <a href="view_employment.php?citizen_id=<?= $record['CitizenID'] ?>" 
                                       style="color: #007cba; text-decoration: none; font-size: 12px;">
                                        View All
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                
                <div style="margin-top: 20px; padding: 10px; background: #f8f9fa; border-radius: 5px;">
                    <small>
                        Showing <?= $result->num_rows ?> record(s). 
                        <?php if (!$citizenId && empty($searchTerm)): ?>
                            Limited to last 50 records for performance.
                        <?php endif; ?>
                    </small>
                </div>
            <?php endif; ?>
        </div>
    </div>
    </div>
    <script src="includes/sidebar.js"></script>
</body>
</html>
