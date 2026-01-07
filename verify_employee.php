<?php
include "config.php";

// Only Employer role can access
if (!isset($_SESSION['UserID']) || $_SESSION['Role'] !== 'Employer') {
    header("Location: unauthorized.php");
    exit();
}

$results = [];
$message = "";

if (isset($_GET['search'])) {
    $search = trim($_GET['search']);
    // Try to interpret as CitizenID (integer), else use as name
    if (is_numeric($search)) {
        $stmt = $conn->prepare("
            SELECT c.CitizenID, CONCAT(c.FirstName, ' ', c.LastName) AS FullName, e.CompanyName, e.JobTitle, e.StartDate, e.EndDate, e.Verified
            FROM Citizens c
            LEFT JOIN EmploymentRecords e ON c.CitizenID = e.CitizenID
            WHERE c.CitizenID = ?
        ");
        $stmt->bind_param("i", $search);
    } else {
        $likeSearch = "%$search%";
        $stmt = $conn->prepare("
            SELECT c.CitizenID, CONCAT(c.FirstName, ' ', c.LastName) AS FullName, e.CompanyName, e.JobTitle, e.StartDate, e.EndDate, e.Verified
            FROM Citizens c
            LEFT JOIN EmploymentRecords e ON c.CitizenID = e.CitizenID
            WHERE CONCAT(c.FirstName, ' ', c.LastName) LIKE ?
        ");
        $stmt->bind_param("s", $likeSearch);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $results[] = $row;
        }
    } else {
        $message = "❌ No employment record found for this citizen.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="includes/sidebar.css">
    <meta charset="UTF-8">
    <title>Verify Employee - NDMS</title>
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
            max-width: 1000px;
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
        
        .main-container {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 40px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            position: relative;
        }
        
        .main-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-bg);
            border-radius: 20px 20px 0 0;
        }
        
        h2 {
            color: var(--primary-color);
            margin-bottom: 30px;
            font-size: 32px;
            font-weight: 700;
            text-align: center;
            background: var(--gradient-bg);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        form {
            background: var(--light-bg);
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            border: 1px solid var(--border-color);
            display: flex;
            gap: 15px;
            align-items: end;
            flex-wrap: wrap;
        }
        
        .form-group {
            flex: 1;
            min-width: 300px;
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
        
        input[type="text"] {
            width: 100%;
            padding: 15px;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: var(--card-bg);
            color: var(--text-primary);
        }
        
        input[type="text"]:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            transform: translateY(-1px);
        }
        
        button {
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
            min-width: 150px;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            filter: brightness(1.1);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 30px 0;
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
        }
        
        tr:hover {
            background: var(--light-bg);
        }
        
        .verified {
            color: var(--accent-color);
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .verified::before {
            content: '✅';
        }
        
        .not-verified {
            color: var(--danger-color);
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .not-verified::before {
            content: '❌';
        }
        
        .message {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
            border: 1px solid rgba(239, 68, 68, 0.2);
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
            text-align: center;
            font-weight: 600;
        }
        
        .success-message {
            background: rgba(16, 185, 129, 0.1);
            color: var(--accent-color);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-verified {
            background: var(--accent-color);
            color: white;
        }
        
        .status-pending {
            background: var(--warning-color);
            color: white;
        }
        
        .employee-card {
            background: var(--light-bg);
            border: 1px solid var(--border-color);
            border-radius: 15px;
            padding: 20px;
            margin: 15px 0;
            transition: all 0.3s ease;
        }
        
        .employee-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        .employee-name {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .employee-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .detail-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .detail-value {
            font-weight: 600;
            color: var(--text-primary);
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }
            
            .main-container {
                padding: 25px;
            }
            
            h2 {
                font-size: 24px;
            }
            
            form {
                flex-direction: column;
                align-items: stretch;
            }
            
            .form-group {
                min-width: auto;
            }
            
            button {
                min-width: auto;
            }
            
            .nav-bar {
                flex-direction: column;
                gap: 10px;
            }
            
            table {
                font-size: 14px;
            }
            
            th, td {
                padding: 10px 8px;
            }
            
            .employee-details {
                grid-template-columns: 1fr;
            }
        }
        
        /* Loading Animation */
        .main-container, .nav-bar {
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
            <a href="dashboard.php">🏠 Employer Dashboard</a>
            <a href="verify_employee.php">🔎 Verify Employee</a>
            <a href="search_citizens.php">👥 Citizens</a>
        </div>
        
        <div class="main-container">
            <h2>🔎 Employee Verification System</h2>
            
            <form method="GET" action="">
                <div class="form-group">
                    <label for="search">Search Employee</label>
                    <input type="text" id="search" name="search" placeholder="Enter Citizen ID or Full Name" required>
                </div>
                <button type="submit">🔍 Verify Employee</button>
            </form>
            
            <?php if ($message) { echo "<div class='message'>$message</div>"; } ?>
            
            <?php if (!empty($results)) : ?>
            <table>
                <tr>
                    <th>Citizen ID</th>
                    <th>Name</th>
                    <th>Employer</th>
                    <th>Job Title</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Verification</th>
                </tr>
                <?php foreach ($results as $res) : ?>
                    <tr>
                        <td><?= htmlspecialchars($res['CitizenID']) ?></td>
                        <td><?= htmlspecialchars($res['FullName']) ?></td>
                        <td><?= htmlspecialchars($res['CompanyName'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($res['JobTitle'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($res['StartDate'] ?? '-') ?></td>
                        <td><?= $res['EndDate'] ? htmlspecialchars($res['EndDate']) : 'Present' ?></td>
                        <td class="<?= isset($res['Verified']) && $res['Verified'] == 1 ? 'verified' : 'not-verified' ?>">
                            <?= isset($res['Verified']) && $res['Verified'] == 1 ? '✅ Verified' : '❌ Not Verified' ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
    </div>
    <script src="includes/sidebar.js"></script>
</body>
</html>
