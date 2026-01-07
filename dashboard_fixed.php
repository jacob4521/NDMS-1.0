<?php
include "config.php";
if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit;
}

$role = $_SESSION['Role'];
$username = '';

// Get username for display
$userResult = $conn->query("SELECT Username FROM Users WHERE UserID = " . $_SESSION['UserID']);
if ($userResult && $user = $userResult->fetch_assoc()) {
    $username = $user['Username'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>NDMS Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { background: #2c3e50; color: white; padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .role-section { background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 10px 0; }
        .menu-item { display: inline-block; margin: 10px; padding: 10px 15px; background: #007cba; color: white; text-decoration: none; border-radius: 5px; }
        .menu-item:hover { background: #005a87; }
        .logout { float: right; background: #dc3545; }
        .stats { display: flex; gap: 20px; margin: 20px 0; }
        .stat-box { background: white; padding: 15px; border-radius: 5px; border-left: 4px solid #007cba; flex: 1; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🇱🇰 NDMS - National Digital Management System</h1>
        <p>Welcome, <strong><?= htmlspecialchars($username) ?></strong> (<?= $role ?>)</p>
        <a href="login.php?logout=1" class="menu-item logout">Logout</a>
    </div>

    <div class="role-section">
        <?php if ($role == "Admin"): ?>
            <h2>👨‍💼 Administrator Dashboard</h2>
            <p>Full system access and user management</p>
            <a href="register.php" class="menu-item">👤 Register New Citizen</a>
            <a href="search_citizens.php" class="menu-item">🔍 Citizen Directory</a>
            <a href="#" class="menu-item">👥 Manage Users</a>
            <a href="#" class="menu-item">📊 System Reports</a>
            
        <?php elseif ($role == "MedicalOfficer"): ?>
            <h2>👩‍⚕️ Medical Officer Dashboard</h2>
            <p>Newborn registration and birth certificate management</p>
            <a href="register.php" class="menu-item">🍼 Register New Citizen (Newborn)</a>
            <a href="birth_certificate.php" class="menu-item">📋 Register Birth Certificate</a>
            <a href="search_citizens.php" class="menu-item">🔍 Citizen Directory</a>
            <a href="#" class="menu-item">🏥 Medical Records</a>
            
        <?php elseif ($role == "EducationOfficer"): ?>
            <h2>👩‍🎓 Education Officer Dashboard</h2>
            <p>Education records and certification management</p>
            <a href="add_education.php" class="menu-item">📝 Add Education Record</a>
            <a href="view_education.php" class="menu-item">📚 View All Records</a>
            <a href="search_citizens.php" class="menu-item">🔍 Citizen Directory</a>
            <a href="#" class="menu-item">🎓 Certifications</a>
            <a href="#" class="menu-item">🏫 Institution Management</a>
            
        <?php elseif ($role == "Employer"): ?>
            <h2>👔 Employer Dashboard</h2>
            <p>Employee verification and employment records management</p>
            <a href="add_employment.php" class="menu-item">📝 Add Employment Record</a>
            <a href="view_employment.php" class="menu-item">💼 View All Records</a>
            <a href="search_citizens.php" class="menu-item">🔍 Citizen Directory</a>
            <a href="#" class="menu-item">✅ Verify Employee</a>
            <a href="#" class="menu-item">📄 Generate Reports</a>
        <?php endif; ?>
    </div>

    <?php if ($role == "MedicalOfficer"): ?>
        <div class="stats">
            <?php
            $newborns = $conn->query("SELECT COUNT(*) as count FROM Citizens WHERE TIMESTAMPDIFF(YEAR, DOB, CURDATE()) < 1");
            $birthCerts = $conn->query("SELECT COUNT(*) as count FROM BirthCertificates");
            $newbornCount = $newborns->fetch_assoc()['count'];
            $certCount = $birthCerts->fetch_assoc()['count'];
            ?>
            <div class="stat-box">
                <h3><?= $newbornCount ?></h3>
                <p>Newborns Registered (< 1 year)</p>
            </div>
            <div class="stat-box">
                <h3><?= $certCount ?></h3>
                <p>Birth Certificates Issued</p>
            </div>
            <div class="stat-box">
                <h3><?= $newbornCount - $certCount ?></h3>
                <p>Pending Birth Certificates</p>
            </div>
        </div>
    <?php elseif ($role == "EducationOfficer"): ?>
        <div class="stats">
            <?php
            $totalRecords = $conn->query("SELECT COUNT(*) as count FROM EducationRecords");
            $totalStudents = $conn->query("SELECT COUNT(DISTINCT CitizenID) as count FROM EducationRecords");
            $examRecords = $conn->query("SELECT COUNT(*) as count FROM EducationRecords WHERE GradeLevel LIKE '%Level%'");
            $recordCount = $totalRecords->fetch_assoc()['count'];
            $studentCount = $totalStudents->fetch_assoc()['count'];
            $examCount = $examRecords->fetch_assoc()['count'];
            ?>
            <div class="stat-box">
                <h3><?= $recordCount ?></h3>
                <p>Total Education Records</p>
            </div>
            <div class="stat-box">
                <h3><?= $studentCount ?></h3>
                <p>Students with Records</p>
            </div>
            <div class="stat-box">
                <h3><?= $examCount ?></h3>
                <p>O/L & A/L Records</p>
            </div>
        </div>
    <?php elseif ($role == "Employer"): ?>
        <div class="stats">
            <?php
            $totalEmploymentRecords = $conn->query("SELECT COUNT(*) as count FROM EmploymentRecords");
            $totalEmployees = $conn->query("SELECT COUNT(DISTINCT CitizenID) as count FROM EmploymentRecords");
            $activeJobs = $conn->query("SELECT COUNT(*) as count FROM EmploymentRecords WHERE EndDate IS NULL");
            $avgSalaryResult = $conn->query("SELECT AVG(Salary) as avg FROM EmploymentRecords WHERE Salary IS NOT NULL");
            
            $empRecordCount = $totalEmploymentRecords->fetch_assoc()['count'];
            $employeeCount = $totalEmployees->fetch_assoc()['count'];
            $activeJobCount = $activeJobs->fetch_assoc()['count'];
            $avgSalary = $avgSalaryResult->fetch_assoc()['avg'];
            ?>
            <div class="stat-box">
                <h3><?= $empRecordCount ?></h3>
                <p>Total Employment Records</p>
            </div>
            <div class="stat-box">
                <h3><?= $employeeCount ?></h3>
                <p>Employees Registered</p>
            </div>
            <div class="stat-box">
                <h3><?= $activeJobCount ?></h3>
                <p>Active Jobs</p>
            </div>
            <div class="stat-box">
                <h3><?= $avgSalary ? 'LKR ' . number_format($avgSalary, 0) : 'N/A' ?></h3>
                <p>Average Salary</p>
            </div>
        </div>
    <?php endif; ?>

</body>
</html>
