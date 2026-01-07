<?php
include "config.php";

// Check if user is logged in
if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit;
}

// Get the CitizenID from URL
if (!isset($_GET['citizen_id'])) {
    die("Citizen ID not provided.");
}

$citizenId = intval($_GET['citizen_id']);
$userRole = $_SESSION['Role'];

// Fetch citizen basic info
$citizenQuery = $conn->prepare("SELECT * FROM Citizens WHERE CitizenID = ?");
$citizenQuery->bind_param("i", $citizenId);
$citizenQuery->execute();
$citizenResult = $citizenQuery->get_result();

if ($citizenResult->num_rows === 0) {
    die("Citizen not found.");
}

$citizen = $citizenResult->fetch_assoc();

// Calculate age
$dob = new DateTime($citizen['DOB']);
$today = new DateTime();
$age = $today->diff($dob)->y;

// Fetch data based on role permissions
$birthCert = null;
$medicalRecords = null;
$educationRecords = null;
$employmentRecords = null;

// Medical Officer or Admin - can see birth certificates and medical records
if ($userRole === 'MedicalOfficer' || $userRole === 'Admin') {
    $birthCertQuery = $conn->prepare("
        SELECT bc.*, u.Username as RegisteredByName 
        FROM BirthCertificates bc 
        LEFT JOIN Users u ON bc.RegisteredBy = u.UserID 
        WHERE bc.CitizenID = ?
    ");
    $birthCertQuery->bind_param("i", $citizenId);
    $birthCertQuery->execute();
    $birthCertResult = $birthCertQuery->get_result();
    $birthCert = $birthCertResult->fetch_assoc();

    $medicalQuery = $conn->prepare("SELECT * FROM MedicalRecords WHERE CitizenID = ? ORDER BY RecordDate DESC");
    $medicalQuery->bind_param("i", $citizenId);
    $medicalQuery->execute();
    $medicalRecords = $medicalQuery->get_result();
}

// Education Officer or Admin - can see education records
if ($userRole === 'EducationOfficer' || $userRole === 'Admin') {
    $educationQuery = $conn->prepare("
        SELECT er.*, u.Username as RegisteredByName 
        FROM EducationRecords er 
        LEFT JOIN Users u ON er.RegisteredBy = u.UserID 
        WHERE er.CitizenID = ? 
        ORDER BY er.RecordDate DESC
    ");
    $educationQuery->bind_param("i", $citizenId);
    $educationQuery->execute();
    $educationRecords = $educationQuery->get_result();
}

// Employer or Admin - can see employment records
if ($userRole === 'Employer' || $userRole === 'Admin') {
    $employmentQuery = $conn->prepare("
        SELECT er.*, u.Username as RegisteredByName 
        FROM EmploymentRecords er 
        LEFT JOIN Users u ON er.RegisteredBy = u.UserID 
        WHERE er.CitizenID = ? 
        ORDER BY er.StartDate DESC
    ");
    $employmentQuery->bind_param("i", $citizenId);
    $employmentQuery->execute();
    $employmentRecords = $employmentQuery->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($citizen['FirstName'] . ' ' . $citizen['LastName']) ?> - Profile</title>
    <style>
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
            margin: 0; 
            padding: 20px; 
            background: #f8f9fa; 
        }
        
        .container { max-width: 1200px; margin: 0 auto; }
        
        .header {
            background: linear-gradient(135deg, #007cba, #005a87);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .citizen-avatar {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
        }
        
        .header-info h1 { margin: 0 0 5px 0; }
        .header-info p { margin: 0; opacity: 0.9; }
        
        .nav-bar {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .nav-bar a {
            color: #007cba;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
            margin-right: 10px;
            transition: background 0.3s;
        }
        
        .nav-bar a:hover {
            background: #e3f2fd;
        }
        
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 25px;
            border-bottom: 3px solid #e9ecef;
        }
        
        .tab {
            padding: 15px 25px;
            background: white;
            border: none;
            border-radius: 10px 10px 0 0;
            cursor: pointer;
            font-weight: 600;
            color: #6c757d;
            transition: all 0.3s;
            border-bottom: 3px solid transparent;
        }
        
        .tab.active {
            color: #007cba;
            border-bottom-color: #007cba;
            background: #f8f9fa;
        }
        
        .tab:hover {
            background: #e3f2fd;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        
        .section h3 {
            color: #007cba;
            margin: 0 0 20px 0;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .info-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            border-left: 4px solid #007cba;
        }
        
        .info-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        .info-value {
            color: #212529;
            font-size: 16px;
        }
        
        .record-item {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid #007cba;
        }
        
        .record-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .record-date {
            background: #007cba;
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-success { background: #d4edda; color: #155724; }
        .badge-warning { background: #fff3cd; color: #856404; }
        .badge-info { background: #d1ecf1; color: #0c5460; }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        
        .no-data img {
            width: 80px;
            opacity: 0.5;
            margin-bottom: 15px;
        }
        
        .btn {
            background: #007cba;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            display: inline-block;
            font-weight: 600;
            transition: background 0.3s;
        }
        
        .btn:hover {
            background: #005a87;
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .role-badge {
            background: #17a2b8;
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        @media (max-width: 768px) {
            .tabs {
                flex-wrap: wrap;
            }
            
            .tab {
                flex: 1;
                min-width: 120px;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="nav-bar">
            <a href="dashboard.php">🏠 Dashboard</a>
            <a href="search_citizens.php">👥 Citizen Directory</a>
            <?php if ($userRole === 'Admin'): ?>
                <a href="register.php">➕ Register New Citizen</a>
            <?php endif; ?>
            <span style="float: right;" class="role-badge"><?= $userRole ?></span>
        </div>

        <div class="header">
            <div class="citizen-avatar">
                👤
            </div>
            <div class="header-info">
                <h1><?= htmlspecialchars($citizen['FirstName'] . ' ' . $citizen['LastName']) ?></h1>
                <p>Citizen ID: NDMS-<?= $citizen['CitizenID'] ?> | Age: <?= $age ?> years | <?= htmlspecialchars($citizen['Gender']) ?></p>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="tabs">
            <button class="tab active" onclick="showTab('basic')">📋 Basic Information</button>
            <?php if ($userRole === 'MedicalOfficer' || $userRole === 'Admin'): ?>
                <button class="tab" onclick="showTab('medical')">🏥 Medical Records</button>
            <?php endif; ?>
            <?php if ($userRole === 'EducationOfficer' || $userRole === 'Admin'): ?>
                <button class="tab" onclick="showTab('education')">🎓 Education Records</button>
            <?php endif; ?>
            <?php if ($userRole === 'Employer' || $userRole === 'Admin'): ?>
                <button class="tab" onclick="showTab('employment')">💼 Employment Records</button>
            <?php endif; ?>
        </div>

        <!-- Basic Information Tab (Visible to All) -->
        <div id="basic" class="tab-content active">
            <div class="section">
                <h3>📋 Basic Citizen Information</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Full Name</div>
                        <div class="info-value"><?= htmlspecialchars($citizen['FirstName'] . ' ' . $citizen['LastName']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Citizen ID</div>
                        <div class="info-value">NDMS-<?= $citizen['CitizenID'] ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">National ID (NIC)</div>
                        <div class="info-value"><?= htmlspecialchars($citizen['NIC'] ?? 'Not Set') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Date of Birth</div>
                        <div class="info-value"><?= $citizen['DOB'] ?> (<?= $age ?> years old)</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Gender</div>
                        <div class="info-value"><?= htmlspecialchars($citizen['Gender']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Address</div>
                        <div class="info-value"><?= htmlspecialchars($citizen['Address']) ?></div>
                    </div>
                </div>
                
                <?php if (!empty($citizen['QRCodePath']) && file_exists($citizen['QRCodePath'])): ?>
                <div style="text-align: center; margin-top: 20px;">
                    <h4>QR Code for Public Profile</h4>
                    <img src="<?= htmlspecialchars($citizen['QRCodePath']) ?>" alt="QR Code" style="border: 2px solid #007cba; border-radius: 10px; padding: 10px;">
                    <p><small>Scan to view public profile</small></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Medical Records Tab (Medical Officer & Admin Only) -->
        <?php if ($userRole === 'MedicalOfficer' || $userRole === 'Admin'): ?>
        <div id="medical" class="tab-content">
            <?php if ($birthCert): ?>
            <div class="section">
                <h3>📜 Birth Certificate</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Father's Name</div>
                        <div class="info-value"><?= htmlspecialchars($birthCert['FatherName'] ?? 'Not recorded') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Mother's Name</div>
                        <div class="info-value"><?= htmlspecialchars($birthCert['MotherName'] ?? 'Not recorded') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Place of Birth</div>
                        <div class="info-value"><?= htmlspecialchars($birthCert['PlaceOfBirth'] ?? 'Not recorded') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Hospital</div>
                        <div class="info-value"><?= htmlspecialchars($birthCert['HospitalName'] ?? 'Not recorded') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Registered By</div>
                        <div class="info-value"><?= htmlspecialchars($birthCert['RegisteredByName'] ?? 'Unknown') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Registration Date</div>
                        <div class="info-value"><?= $birthCert['RegisteredAt'] ?></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="section">
                <h3>🏥 Medical History</h3>
                <?php if ($medicalRecords && $medicalRecords->num_rows > 0): ?>
                    <?php while($record = $medicalRecords->fetch_assoc()): ?>
                        <div class="record-item">
                            <div class="record-header">
                                <span class="record-date"><?= $record['RecordDate'] ?></span>
                            </div>
                            <p><strong>Hospital:</strong> <?= htmlspecialchars($record['Hospital'] ?? 'Not specified') ?></p>
                            <p><strong>Diagnosis:</strong> <?= htmlspecialchars($record['Diagnosis'] ?? 'No diagnosis recorded') ?></p>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-data">
                        <h4>No Medical Records</h4>
                        <p>No medical history has been recorded for this citizen.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Education Records Tab (Education Officer & Admin Only) -->
        <?php if ($userRole === 'EducationOfficer' || $userRole === 'Admin'): ?>
        <div id="education" class="tab-content">
            <div class="section">
                <h3>🎓 Education History</h3>
                <?php if ($educationRecords && $educationRecords->num_rows > 0): ?>
                    <?php while($record = $educationRecords->fetch_assoc()): ?>
                        <div class="record-item">
                            <div class="record-header">
                                <span class="record-date"><?= $record['RecordDate'] ?? 'Date not set' ?></span>
                                <?php if ($record['Result']): ?>
                                    <span class="badge badge-success"><?= htmlspecialchars($record['Result']) ?></span>
                                <?php endif; ?>
                            </div>
                            <p><strong>School:</strong> <?= htmlspecialchars($record['SchoolName'] ?? 'Not specified') ?></p>
                            <p><strong>Grade/Level:</strong> <?= htmlspecialchars($record['GradeLevel'] ?? 'Not specified') ?></p>
                            <?php if ($record['ExamName']): ?>
                                <p><strong>Exam:</strong> <?= htmlspecialchars($record['ExamName']) ?></p>
                            <?php endif; ?>
                            <?php if ($record['MarksObtained']): ?>
                                <p><strong>Marks:</strong> <?= htmlspecialchars($record['MarksObtained']) ?></p>
                            <?php endif; ?>
                            <p><small><strong>Recorded by:</strong> <?= htmlspecialchars($record['RegisteredByName'] ?? 'Unknown') ?></small></p>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-data">
                        <h4>No Education Records</h4>
                        <p>No education history has been recorded for this citizen.</p>
                        <a href="add_education.php" class="btn">Add Education Record</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Employment Records Tab (Employer & Admin Only) -->
        <?php if ($userRole === 'Employer' || $userRole === 'Admin'): ?>
        <div id="employment" class="tab-content">
            <div class="section">
                <h3>💼 Employment History</h3>
                <?php if ($employmentRecords && $employmentRecords->num_rows > 0): ?>
                    <?php while($record = $employmentRecords->fetch_assoc()): ?>
                        <div class="record-item">
                            <div class="record-header">
                                <span class="record-date"><?= $record['StartDate'] ?> <?= $record['EndDate'] ? '- ' . $record['EndDate'] : '- Present' ?></span>
                                <span class="badge <?= $record['EndDate'] ? 'badge-warning' : 'badge-success' ?>">
                                    <?= $record['EndDate'] ? 'Former Employee' : 'Current Employee' ?>
                                </span>
                            </div>
                            <p><strong>Company:</strong> <?= htmlspecialchars($record['CompanyName'] ?? 'Not specified') ?></p>
                            <p><strong>Job Title:</strong> <?= htmlspecialchars($record['JobTitle'] ?? 'Not specified') ?></p>
                            <?php if ($record['Salary']): ?>
                                <p><strong>Salary:</strong> LKR <?= number_format($record['Salary'], 2) ?></p>
                            <?php endif; ?>
                            <p><small><strong>Recorded by:</strong> <?= htmlspecialchars($record['RegisteredByName'] ?? 'Unknown') ?></small></p>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-data">
                        <h4>No Employment Records</h4>
                        <p>No employment history has been recorded for this citizen.</p>
                        <a href="add_employment.php" class="btn">Add Employment Record</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tab contents
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all tabs
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked tab
            event.target.classList.add('active');
        }
    </script>
</body>
</html>
