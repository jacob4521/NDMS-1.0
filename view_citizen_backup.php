<?php
include "config.php";

// Check if user is logged in
if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit;
}

// 1. Get the CitizenID from URL
if (!isset($_GET['citizen_id'])) {
    die("Citizen ID not provided.");
}

$citizenId = intval($_GET['citizen_id']);

// 2. Fetch citizen basic info
$citizenQuery = $conn->prepare("SELECT * FROM Citizens WHERE CitizenID = ?");
$citizenQuery->bind_param("i", $citizenId);
$citizenQuery->execute();
$citizenResult = $citizenQuery->get_result();

if ($citizenResult->num_rows === 0) {
    die("Citizen not found.");
}

$citizen = $citizenResult->fetch_assoc();

// 3. Fetch Birth Certificate (if exists)
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

// 4. Fetch Medical Records
$medicalQuery = $conn->prepare("SELECT * FROM MedicalRecords WHERE CitizenID = ? ORDER BY RecordDate DESC");
$medicalQuery->bind_param("i", $citizenId);
$medicalQuery->execute();
$medicalResult = $medicalQuery->get_result();

// 5. Fetch Education Records
$educationQuery = $conn->prepare("
    SELECT er.*, u.Username as RegisteredByName 
    FROM EducationRecords er 
    LEFT JOIN Users u ON er.RegisteredBy = u.UserID 
    WHERE er.CitizenID = ? 
    ORDER BY er.RecordDate DESC
");
$educationQuery->bind_param("i", $citizenId);
$educationQuery->execute();
$educationResult = $educationQuery->get_result();

// 6. Fetch Employment Records
$employmentQuery = $conn->prepare("
    SELECT er.*, u.Username as RegisteredByName 
    FROM EmploymentRecords er 
    LEFT JOIN Users u ON er.RegisteredBy = u.UserID 
    WHERE er.CitizenID = ? 
    ORDER BY er.StartDate DESC
");
$employmentQuery->bind_param("i", $citizenId);
$employmentQuery->execute();
$employmentResult = $employmentQuery->get_result();

// Calculate age
$dob = new DateTime($citizen['DOB']);
$today = new DateTime();
$age = $today->diff($dob)->y;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NDMS - Citizen Profile</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #007cba, #005a87); color: white; padding: 20px; border-radius: 10px 10px 0 0; }
        .citizen-info { display: flex; align-items: center; gap: 20px; }
        .qr-code { width: 100px; height: 100px; background: white; border-radius: 5px; padding: 5px; }
        .qr-code img { width: 100%; height: 100%; }
        .basic-details { flex: 1; }
        .basic-details h1 { margin: 0; font-size: 24px; }
        .basic-details p { margin: 5px 0; opacity: 0.9; }
        .nav { padding: 15px 20px; background: #f8f9fa; border-bottom: 1px solid #ddd; }
        .nav a { margin-right: 10px; padding: 5px 15px; background: #007cba; color: white; text-decoration: none; border-radius: 5px; font-size: 14px; }
        .nav a:hover { background: #005a87; }
        .content { padding: 20px; }
        .section { margin-bottom: 30px; }
        .section h2 { color: #007cba; border-bottom: 2px solid #007cba; padding-bottom: 5px; margin-bottom: 15px; }
        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .info-card { background: #f8f9fa; padding: 15px; border-radius: 5px; border-left: 4px solid #007cba; }
        .info-card h4 { margin: 0 0 5px 0; color: #333; }
        .info-card p { margin: 0; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
        th { background-color: #007cba; color: white; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .no-records { text-align: center; padding: 30px; color: #666; background: #f8f9fa; border-radius: 5px; }
        .status-badge { padding: 3px 8px; border-radius: 12px; font-size: 11px; color: white; font-weight: bold; }
        .active { background: #28a745; }
        .inactive { background: #dc3545; }
        .currency { color: #007cba; font-weight: bold; }
        .grade-badge { background: #17a2b8; color: white; padding: 2px 6px; border-radius: 10px; font-size: 11px; }
        .age-badge { background: #6f42c1; color: white; padding: 2px 8px; border-radius: 12px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header Section -->
        <div class="header">
            <div class="citizen-info">
                <div class="qr-code">
                    <?php if (!empty($citizen['QRCodePath']) && file_exists($citizen['QRCodePath'])): ?>
                        <img src="<?= htmlspecialchars($citizen['QRCodePath']) ?>" alt="QR Code">
                    <?php else: ?>
                        <div style="background: #ccc; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 10px;">No QR</div>
                    <?php endif; ?>
                </div>
                <div class="basic-details">
                    <h1>🇱🇰 <?= htmlspecialchars($citizen['FirstName'] . ' ' . $citizen['LastName']) ?></h1>
                    <p><strong>Citizen ID:</strong> <?= $citizen['CitizenID'] ?> | 
                       <strong>NIC:</strong> <?= htmlspecialchars($citizen['NIC'] ?? 'Not Assigned') ?></p>
                    <p><strong>Date of Birth:</strong> <?= $citizen['DOB'] ?> 
                       <span class="age-badge"><?= $age ?> years old</span></p>
                    <p><strong>Gender:</strong> <?= htmlspecialchars($citizen['Gender']) ?> | 
                       <strong>Address:</strong> <?= htmlspecialchars($citizen['Address']) ?></p>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <div class="nav">
            <a href="dashboard.php">← Back to Dashboard</a>
            <?php if ($_SESSION['Role'] == 'MedicalOfficer'): ?>
                <a href="birth_certificate.php">Birth Certificate</a>
            <?php elseif ($_SESSION['Role'] == 'EducationOfficer'): ?>
                <a href="add_education.php">Add Education</a>
            <?php elseif ($_SESSION['Role'] == 'Employer'): ?>
                <a href="add_employment.php">Add Employment</a>
            <?php endif; ?>
            <a href="register.php">Register New Citizen</a>
        </div>

        <!-- Content -->
        <div class="content">

            <!-- Birth Certificate Section -->
            <?php if ($birthCert): ?>
            <div class="section">
                <h2>🍼 Birth Certificate Information</h2>
                <div class="info-grid">
                    <div class="info-card">
                        <h4>Father's Name</h4>
                        <p><?= htmlspecialchars($birthCert['FatherName']) ?></p>
                    </div>
                    <div class="info-card">
                        <h4>Mother's Name</h4>
                        <p><?= htmlspecialchars($birthCert['MotherName']) ?></p>
                    </div>
                    <div class="info-card">
                        <h4>Place of Birth</h4>
                        <p><?= htmlspecialchars($birthCert['PlaceOfBirth']) ?></p>
                    </div>
                    <div class="info-card">
                        <h4>Hospital</h4>
                        <p><?= htmlspecialchars($birthCert['HospitalName']) ?></p>
                    </div>
                    <div class="info-card">
                        <h4>Registered By</h4>
                        <p><?= htmlspecialchars($birthCert['RegisteredByName'] ?? 'Unknown') ?></p>
                    </div>
                    <div class="info-card">
                        <h4>Registration Date</h4>
                        <p><?= date('F j, Y', strtotime($birthCert['RegisteredAt'])) ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Medical Records Section -->
            <div class="section">
                <h2>🏥 Medical Records</h2>
                <?php if ($medicalResult->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Record Date</th>
                                <th>Hospital</th>
                                <th>Diagnosis</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $medicalResult->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['RecordDate']) ?></td>
                                <td><?= htmlspecialchars($row['Hospital'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['Diagnosis'] ?? '') ?></td>
                                <td>
                                    <a href="#" style="color: #007cba; text-decoration: none; font-size: 12px;">View Details</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-records">
                        <p>No medical records found for this citizen.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Education Records Section -->
            <div class="section">
                <h2>📚 Education Records</h2>
                <?php if ($educationResult->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>School/Institution</th>
                                <th>Grade/Level</th>
                                <th>Exam/Assessment</th>
                                <th>Result</th>
                                <th>Marks</th>
                                <th>Date</th>
                                <th>Registered By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $educationResult->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['SchoolName'] ?? '') ?></td>
                                <td>
                                    <span class="grade-badge"><?= htmlspecialchars($row['GradeLevel'] ?? '') ?></span>
                                </td>
                                <td><?= htmlspecialchars($row['ExamName'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['Result'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['MarksObtained'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['RecordDate'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['RegisteredByName'] ?? 'Unknown') ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-records">
                        <p>No education records found for this citizen.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Employment Records Section -->
            <div class="section">
                <h2>💼 Employment Records</h2>
                <?php if ($employmentResult->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Company</th>
                                <th>Job Title</th>
                                <th>Employment Period</th>
                                <th>Status</th>
                                <th>Salary</th>
                                <th>Registered By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $employmentResult->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['CompanyName'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['JobTitle'] ?? '') ?></td>
                                <td>
                                    <strong>Start:</strong> <?= htmlspecialchars($row['StartDate'] ?? '') ?><br>
                                    <strong>End:</strong> <?= htmlspecialchars($row['EndDate'] ?? 'Current') ?>
                                </td>
                                <td>
                                    <?php if (empty($row['EndDate'])): ?>
                                        <span class="status-badge active">Active</span>
                                    <?php else: ?>
                                        <span class="status-badge inactive">Ended</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['Salary']): ?>
                                        <span class="currency">LKR <?= number_format($row['Salary'], 2) ?></span>
                                    <?php else: ?>
                                        <span style="color: #666;">Not disclosed</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($row['RegisteredByName'] ?? 'Unknown') ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-records">
                        <p>No employment records found for this citizen.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <script>
        // Print functionality
        function printProfile() {
            window.print();
        }
        
        // Add print button to navigation
        document.addEventListener('DOMContentLoaded', function() {
            const nav = document.querySelector('.nav');
            const printBtn = document.createElement('a');
            printBtn.href = '#';
            printBtn.innerHTML = '🖨️ Print Profile';
            printBtn.onclick = function(e) {
                e.preventDefault();
                printProfile();
            };
            nav.appendChild(printBtn);
        });
    </script>
</body>
</html>
