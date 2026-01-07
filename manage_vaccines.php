<?php
include "config.php";

// Check if user is logged in and has permission
if (!isset($_SESSION['UserID']) || !in_array($_SESSION['Role'], ['Admin', 'MedicalOfficer'])) {
    header("Location: login.php");
    exit;
}

$userRole = $_SESSION['Role'];
$message = '';

// Handle Add Vaccine
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_vaccine'])) {
    $vaccineName = trim($_POST['vaccineName']);
    $description = trim($_POST['description']);
    $createdBy = $_SESSION['UserID'];
    
    if (!empty($vaccineName)) {
        $stmt = $conn->prepare("INSERT INTO Vaccines (VaccineName, Description, CreatedBy) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $vaccineName, $description, $createdBy);
        
        if ($stmt->execute()) {
            $message = "<div class='success-message'>✅ Vaccine '$vaccineName' added successfully!</div>";
        } else {
            $message = "<div class='error-message'>❌ Error: Vaccine name might already exist.</div>";
        }
        $stmt->close();
    }
}

// Handle Edit Vaccine
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_vaccine'])) {
    $vaccineID = $_POST['vaccineID'];
    $vaccineName = trim($_POST['vaccineName']);
    $description = trim($_POST['description']);
    
    if (!empty($vaccineName)) {
        $stmt = $conn->prepare("UPDATE Vaccines SET VaccineName = ?, Description = ? WHERE VaccineID = ?");
        $stmt->bind_param("ssi", $vaccineName, $description, $vaccineID);
        
        if ($stmt->execute()) {
            $message = "<div class='success-message'>✅ Vaccine updated successfully!</div>";
        } else {
            $message = "<div class='error-message'>❌ Error updating vaccine.</div>";
        }
        $stmt->close();
    }
}

// Handle Delete Vaccine
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_vaccine'])) {
    $vaccineID = $_POST['delete_vaccine'];
    
    // Check if vaccine is used in any vaccination records
    $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM VaccinationRecords vr JOIN Vaccines v ON vr.VaccineName = v.VaccineName WHERE v.VaccineID = ?");
    $checkStmt->bind_param("i", $vaccineID);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $usageCount = $checkResult->fetch_assoc()['count'];
    $checkStmt->close();
    
    if ($usageCount > 0) {
        $message = "<div class='error-message'>❌ Cannot delete vaccine: It has been used in $usageCount vaccination record(s).</div>";
    } else {
        $stmt = $conn->prepare("DELETE FROM Vaccines WHERE VaccineID = ?");
        $stmt->bind_param("i", $vaccineID);
        
        if ($stmt->execute()) {
            $message = "<div class='success-message'>✅ Vaccine deleted successfully!</div>";
        } else {
            $message = "<div class='error-message'>❌ Error deleting vaccine.</div>";
        }
        $stmt->close();
    }
}

// Fetch all vaccines with usage count
$vaccinesQuery = "
    SELECT 
        v.VaccineID,
        v.VaccineName,
        v.Description,
        v.CreatedAt,
        u.Username as CreatedByName,
        COUNT(vr.VaccineID) as UsageCount
    FROM Vaccines v
    LEFT JOIN Users u ON v.CreatedBy = u.UserID
    LEFT JOIN VaccinationRecords vr ON v.VaccineName = vr.VaccineName
    GROUP BY v.VaccineID
    ORDER BY v.VaccineName ASC
";
$vaccinesResult = $conn->query($vaccinesQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Vaccines - NDMS</title>
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
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .nav-links {
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
            text-align: center;
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
            margin-bottom: 12px;
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
        
        .section {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            position: relative;
        }
        
        .section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-bg);
            border-radius: 20px 20px 0 0;
        }
        
        .section h3 {
            color: var(--primary-color);
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .form-section {
            background: var(--light-bg);
            border: 2px dashed var(--accent-color);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            position: relative;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: inherit;
            background: white;
        }
        
        .form-group input:focus, .form-group textarea:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-group textarea {
            height: 100px;
            resize: vertical;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .btn {
            background: var(--gradient-bg);
            color: white;
            padding: 14px 28px;
            border: none;
            border-radius: 10px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            cursor: pointer;
            box-shadow: var(--shadow-sm);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            filter: brightness(1.1);
        }
        
        .btn-edit {
            background: var(--secondary-color);
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-size: 12px;
            cursor: pointer;
            margin-right: 8px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        .btn-edit:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }
        
        .btn-delete {
            background: var(--danger-color);
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        .btn-delete:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }
        
        .btn-cancel {
            background: var(--text-secondary);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            margin-left: 12px;
            transition: all 0.3s ease;
            font-weight: 600;
        }
        
        .btn-cancel:hover {
            background: #4b5563;
            transform: translateY(-1px);
        }
        
        .vaccine-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: var(--card-bg);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
        }
        
        .vaccine-table th,
        .vaccine-table td {
            padding: 15px 12px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        .vaccine-table th {
            background: var(--primary-color);
            color: white;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .vaccine-table tr:hover {
            background: var(--light-bg);
        }
        
        .usage-count {
            background: var(--secondary-color);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .edit-form {
            display: none;
            background: #fff3cd;
            border: 2px solid var(--warning-color);
            border-radius: 15px;
            padding: 25px;
            margin: 15px 0;
            position: relative;
        }
        
        .edit-form.active {
            display: block;
            animation: slideDown 0.3s ease-out;
        }
        
        .edit-form h4 {
            color: var(--primary-color);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .success-message {
            background: var(--accent-color);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
        }
        
        .success-message::before {
            content: '✅';
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 24px;
            opacity: 0.7;
        }
        
        .error-message {
            background: var(--danger-color);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
        }
        
        .error-message::before {
            content: '❌';
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 24px;
            opacity: 0.7;
        }
        
        .role-badge {
            background: var(--accent-color);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-secondary);
            background: var(--light-bg);
            border-radius: 15px;
            margin: 20px 0;
        }
        
        .empty-state h4 {
            color: var(--primary-color);
            margin-bottom: 15px;
            font-size: 24px;
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
                gap: 15px;
            }
            
            .nav-links {
                width: 100%;
                justify-content: center;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .vaccine-table {
                font-size: 14px;
            }
            
            .vaccine-table th,
            .vaccine-table td {
                padding: 10px 8px;
            }
        }
        
        /* Animations */
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .section, .nav-bar {
            animation: slideUp 0.3s ease-out;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <!-- Include Sidebar -->
    <?php include 'includes/sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
        <div class="nav-bar">
            <div class="nav-links">
                <a href="dashboard.php">🏠 Dashboard</a>
                <a href="search_citizens.php">👥 Citizen Directory</a>
                <a href="manage_vaccines.php">💉 Manage Vaccines</a>
            </div>
            <span class="role-badge"><?= $userRole ?></span>
        </div>

        <div class="header">
            <h1>💉 Vaccine Management System</h1>
            <p>National Digital Management System - Comprehensive Vaccine Database</p>
        </div>

        <?= $message ?>

        <!-- Add New Vaccine Form -->
        <div class="section">
            <div class="form-section">
                <form method="post" action="">
                    <h3>➕ Add New Vaccine</h3>
                    <input type="hidden" name="add_vaccine" value="1">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Vaccine Name:</label>
                            <input type="text" name="vaccineName" required placeholder="e.g., COVID-19, MMR, BCG">
                        </div>
                        <div class="form-group">
                            <label>Description:</label>
                            <input type="text" name="description" placeholder="e.g., Measles, Mumps, Rubella vaccine">
                        </div>
                    </div>
                    <button type="submit" class="btn">💉 Add Vaccine</button>
                </form>
            </div>
        </div>

        <!-- Vaccine List -->
        <div class="section">
            <h3>📋 Available Vaccines</h3>
            <?php if ($vaccinesResult && $vaccinesResult->num_rows > 0): ?>
                <table class="vaccine-table">
                    <thead>
                        <tr>
                            <th>Vaccine Name</th>
                            <th>Description</th>
                            <th>Usage Count</th>
                            <th>Created By</th>
                            <th>Created Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($vaccine = $vaccinesResult->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($vaccine['VaccineName']) ?></strong></td>
                                <td><?= htmlspecialchars($vaccine['Description'] ?? 'No description') ?></td>
                                <td><span class="usage-count"><?= $vaccine['UsageCount'] ?> records</span></td>
                                <td><?= htmlspecialchars($vaccine['CreatedByName'] ?? 'System') ?></td>
                                <td><?= date('M d, Y', strtotime($vaccine['CreatedAt'])) ?></td>
                                <td>
                                    <button type="button" class="btn-edit" onclick="toggleEditForm('vaccine_<?= $vaccine['VaccineID'] ?>')">✏️ Edit</button>
                                    <form method="post" style="display:inline;">
                                        <input type="hidden" name="delete_vaccine" value="<?= $vaccine['VaccineID'] ?>">
                                        <button type="submit" class="btn-delete" onclick="return confirm('Delete vaccine \'<?= htmlspecialchars($vaccine['VaccineName']) ?>\'?')">🗑 Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="6">
                                    <!-- Edit Form (Hidden by default) -->
                                    <div id="vaccine_<?= $vaccine['VaccineID'] ?>" class="edit-form">
                                        <form method="post">
                                            <h4>✏️ Edit Vaccine: <?= htmlspecialchars($vaccine['VaccineName']) ?></h4>
                                            <input type="hidden" name="edit_vaccine" value="1">
                                            <input type="hidden" name="vaccineID" value="<?= $vaccine['VaccineID'] ?>">
                                            <div class="form-row">
                                                <div class="form-group">
                                                    <label>Vaccine Name:</label>
                                                    <input type="text" name="vaccineName" value="<?= htmlspecialchars($vaccine['VaccineName']) ?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label>Description:</label>
                                                    <input type="text" name="description" value="<?= htmlspecialchars($vaccine['Description']) ?>">
                                                </div>
                                            </div>
                                            <button type="submit" class="btn">💾 Save Changes</button>
                                            <button type="button" class="btn-cancel" onclick="toggleEditForm('vaccine_<?= $vaccine['VaccineID'] ?>')">❌ Cancel</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <h4>💉 No Vaccines Available</h4>
                    <p>Add your first vaccine using the form above to get started.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function toggleEditForm(formId) {
            const form = document.getElementById(formId);
            if (form.classList.contains('active')) {
                form.style.transition = 'opacity 0.3s, transform 0.3s';
                form.style.opacity = '0';
                form.style.transform = 'translateY(-10px)';
                setTimeout(() => {
                    form.classList.remove('active');
                    form.style.opacity = '';
                    form.style.transform = '';
                }, 300);
            } else {
                // Hide all other edit forms first
                const allEditForms = document.querySelectorAll('.edit-form');
                allEditForms.forEach(f => f.classList.remove('active'));
                
                // Show the selected form
                form.classList.add('active');
            }
        }

        // Enhanced form validation and interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Add smooth transitions for form elements
            document.querySelectorAll('input, textarea').forEach(element => {
                element.addEventListener('focus', function() {
                    this.style.transform = 'scale(1.02)';
                    this.style.boxShadow = '0 0 0 3px rgba(59, 130, 246, 0.1)';
                });
                
                element.addEventListener('blur', function() {
                    this.style.transform = 'scale(1)';
                    this.style.boxShadow = 'none';
                });
            });

            // Auto-hide success messages
            setTimeout(function() {
                const successMessages = document.querySelectorAll('.success-message');
                successMessages.forEach(msg => {
                    msg.style.transition = 'opacity 0.5s, transform 0.5s';
                    msg.style.opacity = '0';
                    msg.style.transform = 'translateY(-10px)';
                    setTimeout(() => msg.remove(), 500);
                });
            }, 5000);

            // Form validation
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function(e) {
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
                        alert('🚨 Please fill in all required fields.');
                    }
                });
            });
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
    </script>
</body>
</html>
