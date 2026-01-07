<?php
include "config.php";

// Check if user is logged in
if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit;
}

// Get the CitizenID from URL
if (!isset($_GET['citizen_id']) && !isset($_GET['id'])) {
    die("Citizen ID not provided.");
}

$citizenId = isset($_GET['citizen_id']) ? intval($_GET['citizen_id']) : intval($_GET['id']);
$userRole = $_SESSION['Role'];

// Handle Citizen Profile Edit (Only Admin can edit basic info)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_citizen_profile']) && $userRole === 'Admin') {
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $nic = trim($_POST['nic']);
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $address = trim($_POST['address']);
    
    $sql = "UPDATE Citizens SET FirstName = ?, LastName = ?, NIC = ?, DOB = ?, Gender = ?, Address = ? WHERE CitizenID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssi", $firstName, $lastName, $nic, $dob, $gender, $address, $citizenId);
    
    if ($stmt->execute()) {
        $profileMessage = "Citizen profile updated successfully!";
        $profileMessageType = "success";
    } else {
        $profileMessage = "Error updating citizen profile: " . $stmt->error;
        $profileMessageType = "error";
    }
}

// Security check: Citizens can only view their own details
if ($userRole === 'Citizen') {
    // Get the citizen ID associated with the logged-in user (via eID/Username)
    $userCitizenQuery = $conn->prepare("
        SELECT c.CitizenID 
        FROM Citizens c 
        JOIN Users u ON c.Citizen_eID = u.Username 
        WHERE u.UserID = ?
    ");
    $userCitizenQuery->bind_param("i", $_SESSION['UserID']);
    $userCitizenQuery->execute();
    $userCitizenResult = $userCitizenQuery->get_result();
    
    if ($userCitizenResult->num_rows === 0) {
        die("Access denied: No citizen profile associated with your account.");
    }
    
    $userCitizenData = $userCitizenResult->fetch_assoc();
    $userCitizenID = $userCitizenData['CitizenID'];
    
    // Check if the citizen is trying to access their own data
    if ($citizenId !== $userCitizenID) {
        die("Access denied: You can only view your own details.");
    }
}

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
// Handle Medical Record Add
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_medical']) && ($userRole === 'MedicalOfficer' || $userRole === 'Admin')) {
    $citizenID = $_POST['citizenID'];
    $diagnosis = $_POST['diagnosis'];
    $treatment = $_POST['treatment'];
    $doctorName = $_POST['doctorName'];
    $hospitalName = $_POST['hospitalName'];
    $recordDate = $_POST['recordDate'];
    $sql = "INSERT INTO MedicalRecords (citizenID, diagnosis, treatment, doctorName, hospitalName, recordDate) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssss", $citizenID, $diagnosis, $treatment, $doctorName, $hospitalName, $recordDate);
    $stmt->execute();
}

// Handle Medical Record Delete
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_medical']) && ($userRole === 'MedicalOfficer' || $userRole === 'Admin')) {
    $recordID = $_POST['delete_medical'];
    $sql = "DELETE FROM MedicalRecords WHERE recordID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $recordID);
    $stmt->execute();
}

// Handle Medical Record Edit
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_medical']) && ($userRole === 'MedicalOfficer' || $userRole === 'Admin')) {
    $recordID = $_POST['recordID'];
    $diagnosis = $_POST['diagnosis'];
    $treatment = $_POST['treatment'];
    $doctorName = $_POST['doctorName'];
    $hospitalName = $_POST['hospitalName'];
    $recordDate = $_POST['recordDate'];
    $sql = "UPDATE MedicalRecords SET diagnosis = ?, treatment = ?, doctorName = ?, hospitalName = ?, recordDate = ? WHERE recordID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssi", $diagnosis, $treatment, $doctorName, $hospitalName, $recordDate, $recordID);
    $stmt->execute();
}

// Handle Education Record Add
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_education']) && ($userRole === 'EducationOfficer' || $userRole === 'Admin')) {
    $citizenID = $_POST['citizenID'];
    $schoolName = $_POST['schoolName'];
    $gradeLevel = $_POST['gradeLevel'];
    $examName = $_POST['examName'];
    $subjectID = !empty($_POST['subjectID']) ? $_POST['subjectID'] : NULL;
    $result = $_POST['result'];
    $marksObtained = $_POST['marksObtained'];
    $recordDate = $_POST['recordDate'];
    $registeredBy = $_SESSION['UserID'];
    $sql = "INSERT INTO EducationRecords (CitizenID, SchoolName, GradeLevel, ExamName, SubjectID, Result, MarksObtained, RecordDate, RegisteredBy) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isssisisi", $citizenID, $schoolName, $gradeLevel, $examName, $subjectID, $result, $marksObtained, $recordDate, $registeredBy);
    $stmt->execute();
}

// Handle Education Record Edit
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_education']) && ($userRole === 'EducationOfficer' || $userRole === 'Admin')) {
    $eduID = $_POST['eduID'];
    $schoolName = $_POST['schoolName'];
    $gradeLevel = $_POST['gradeLevel'];
    $examName = $_POST['examName'];
    $subjectID = !empty($_POST['subjectID']) ? $_POST['subjectID'] : NULL;
    $result = $_POST['result'];
    $marksObtained = $_POST['marksObtained'];
    $recordDate = $_POST['recordDate'];
    $sql = "UPDATE EducationRecords SET SchoolName = ?, GradeLevel = ?, ExamName = ?, SubjectID = ?, Result = ?, MarksObtained = ?, RecordDate = ? WHERE EduID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssisisi", $schoolName, $gradeLevel, $examName, $subjectID, $result, $marksObtained, $recordDate, $eduID);
    $stmt->execute();
}

// Handle Education Record Delete
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_education']) && ($userRole === 'EducationOfficer' || $userRole === 'Admin')) {
    $eduID = $_POST['delete_education'];
    $sql = "DELETE FROM EducationRecords WHERE EduID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $eduID);
    $stmt->execute();
}

// Handle Employment Record Add
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_employment']) && ($userRole === 'Employer' || $userRole === 'Admin')) {
    $citizenID = $_POST['citizenID'];
    $companyName = $_POST['companyName'];
    $jobTitle = $_POST['jobTitle'];
    $startDate = $_POST['startDate'];
    $endDate = !empty($_POST['endDate']) ? $_POST['endDate'] : NULL;
    $salary = $_POST['salary'];
    $registeredBy = $_SESSION['UserID'];
    $sql = "INSERT INTO EmploymentRecords (CitizenID, CompanyName, JobTitle, StartDate, EndDate, Salary, RegisteredBy) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssdi", $citizenID, $companyName, $jobTitle, $startDate, $endDate, $salary, $registeredBy);
    $stmt->execute();
}

// Handle Employment Record Edit
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_employment']) && ($userRole === 'Employer' || $userRole === 'Admin')) {
    $empID = $_POST['empID'];
    $companyName = $_POST['companyName'];
    $jobTitle = $_POST['jobTitle'];
    $startDate = $_POST['startDate'];
    $endDate = !empty($_POST['endDate']) ? $_POST['endDate'] : NULL;
    $salary = $_POST['salary'];
    $sql = "UPDATE EmploymentRecords SET CompanyName = ?, JobTitle = ?, StartDate = ?, EndDate = ?, Salary = ? WHERE EmpID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssdi", $companyName, $jobTitle, $startDate, $endDate, $salary, $empID);
    $stmt->execute();
}

// Handle Employment Record Delete
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_employment']) && ($userRole === 'Employer' || $userRole === 'Admin')) {
    $empID = $_POST['delete_employment'];
    $sql = "DELETE FROM EmploymentRecords WHERE EmpID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $empID);
    $stmt->execute();
}

// Handle Vaccination Record Add
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_vaccination']) && ($userRole === 'MedicalOfficer' || $userRole === 'Admin')) {
    $citizenID = $_POST['citizenID'];
    $vaccineName = $_POST['vaccineName'];
    $doseNumber = $_POST['doseNumber'];
    $dateAdministered = $_POST['dateAdministered'];
    $administeredBy = $_POST['administeredBy'];
    $notes = $_POST['notes'];
    $sql = "INSERT INTO VaccinationRecords (CitizenID, VaccineName, DoseNumber, DateAdministered, AdministeredBy, Notes) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isisss", $citizenID, $vaccineName, $doseNumber, $dateAdministered, $administeredBy, $notes);
    $stmt->execute();
}

// Handle Vaccination Record Edit
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_vaccination']) && ($userRole === 'MedicalOfficer' || $userRole === 'Admin')) {
    $vaccineID = $_POST['vaccineID'];
    $vaccineName = $_POST['vaccineName'];
    $doseNumber = $_POST['doseNumber'];
    $dateAdministered = $_POST['dateAdministered'];
    $administeredBy = $_POST['administeredBy'];
    $notes = $_POST['notes'];
    $sql = "UPDATE VaccinationRecords SET VaccineName = ?, DoseNumber = ?, DateAdministered = ?, AdministeredBy = ?, Notes = ? WHERE VaccineID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sisssi", $vaccineName, $doseNumber, $dateAdministered, $administeredBy, $notes, $vaccineID);
    $stmt->execute();
}

// Handle Vaccination Record Delete
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_vaccination']) && ($userRole === 'MedicalOfficer' || $userRole === 'Admin')) {
    $vaccineID = $_POST['delete_vaccination'];
    $sql = "DELETE FROM VaccinationRecords WHERE VaccineID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $vaccineID);
    $stmt->execute();
}
$birthCert = null;
$medicalRecords = null;
$vaccinationRecords = null;
$educationRecords = null;
$employmentRecords = null;

// Citizens can see all their own records, but cannot edit them
// Medical Officer or Admin - can see birth certificates, medical records, and vaccination records
if ($userRole === 'MedicalOfficer' || $userRole === 'Admin' || $userRole === 'Citizen') {
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

    $medicalQuery = $conn->prepare("SELECT * FROM MedicalRecords WHERE citizenID = ? ORDER BY recordDate DESC");
    $medicalQuery->bind_param("i", $citizenId);
    $medicalQuery->execute();
    $medicalRecords = $medicalQuery->get_result();
    
    $vaccinationQuery = $conn->prepare("SELECT * FROM VaccinationRecords WHERE CitizenID = ? ORDER BY DateAdministered DESC");
    $vaccinationQuery->bind_param("i", $citizenId);
    $vaccinationQuery->execute();
    $vaccinationRecords = $vaccinationQuery->get_result();
}

// Education Officer or Admin or Citizen - can see education records
if ($userRole === 'EducationOfficer' || $userRole === 'Admin' || $userRole === 'Citizen') {
    $educationQuery = $conn->prepare("
        SELECT er.*, s.SubjectName, s.Category as SubjectCategory, u.Username as RegisteredByName 
        FROM EducationRecords er 
        LEFT JOIN Subjects s ON er.SubjectID = s.SubjectID
        LEFT JOIN Users u ON er.RegisteredBy = u.UserID 
        WHERE er.CitizenID = ? 
        ORDER BY er.RecordDate DESC
    ");
    $educationQuery->bind_param("i", $citizenId);
    $educationQuery->execute();
    $educationRecords = $educationQuery->get_result();
}

// Employer or Admin or Citizen - can see employment records
if ($userRole === 'Employer' || $userRole === 'Admin' || $userRole === 'Citizen') {
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
            font-size: 14px;
        }
        
        .container { 
            max-width: 1400px; 
            margin: 0 auto; 
            padding: 20px;
        }
        
        /* Header Styling - National Government Theme */
        .header {
            background: var(--gradient-bg);
            color: white;
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 25px;
            box-shadow: var(--shadow-xl);
            position: relative;
            overflow: hidden;
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
        
        .citizen-avatar {
            width: 90px;
            height: 90px;
            background: rgba(255,255,255,0.15);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255,255,255,0.2);
            position: relative;
            z-index: 1;
        }
        
        .header-info {
            flex: 1;
            position: relative;
            z-index: 1;
        }
        
        .header-info h1 { 
            margin: 0 0 8px 0; 
            font-size: 28px;
            font-weight: 700;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .header-info p { 
            margin: 0; 
            opacity: 0.9; 
            font-size: 16px;
            font-weight: 400;
        }
        
        /* Navigation Bar - Government Portal Style */
        .nav-bar {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .nav-links {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .nav-bar a {
            color: var(--primary-color);
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            border: 1px solid transparent;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .nav-bar a:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        
        /* Tab System - Modern Government Interface */
        .tabs {
            display: flex;
            gap: 5px;
            margin-bottom: 30px;
            background: var(--card-bg);
            padding: 8px;
            border-radius: 15px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            overflow-x: auto;
        }
        
        .tab {
            padding: 15px 25px;
            background: transparent;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            color: var(--text-secondary);
            transition: all 0.3s ease;
            white-space: nowrap;
            position: relative;
            min-width: fit-content;
        }
        
        .tab.active {
            color: white;
            background: var(--gradient-bg);
            box-shadow: var(--shadow-md);
            transform: translateY(-2px);
        }
        
        .tab:hover:not(.active) {
            background: var(--light-bg);
            color: var(--primary-color);
        }
        
        .tab-content {
            display: none;
            visibility: hidden;
            animation: fadeIn 0.3s ease-in-out;
            position: relative;
            overflow: hidden;
        }
        
        .tab-content.active {
            display: block;
            visibility: visible;
        }
        
        /* Ensure sections within inactive tabs are completely hidden */
        .tab-content:not(.active) .section {
            display: none !important;
            visibility: hidden !important;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Card/Section Styling - Professional Government Look */
        .section {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
        }
        
        .section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-bg);
        }
        
        .section h3 {
            color: var(--primary-color);
            margin: 0 0 25px 0;
            font-size: 22px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--light-bg);
        }
        
        /* Information Grid - Clean Data Display */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .info-item {
            background: var(--light-bg);
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid var(--accent-color);
            transition: all 0.3s ease;
            position: relative;
        }
        
        .info-item:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            border-left-color: var(--primary-color);
        }
        
        .info-label {
            font-weight: 700;
            color: var(--text-secondary);
            margin-bottom: 8px;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .info-value {
            color: var(--text-primary);
            font-size: 16px;
            font-weight: 600;
        }
        
        /* Record Items - Enhanced Cards */
        .record-item {
            background: var(--light-bg);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            border-left: 4px solid var(--secondary-color);
            transition: all 0.3s ease;
            position: relative;
        }
        
        .record-item:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
            border-left-color: var(--accent-color);
        }
        
        .record-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .record-date {
            background: var(--gradient-bg);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
            box-shadow: var(--shadow-sm);
        }
        
        /* Enhanced Badges */
        .badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        
        .badge-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .badge-warning { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .badge-info { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
        
        /* No Data State - Improved Empty States */
        .no-data {
            text-align: center;
            padding: 60px 40px;
            color: var(--text-secondary);
            background: var(--light-bg);
            border-radius: 15px;
            border: 2px dashed var(--border-color);
        }
        
        .no-data h4 {
            color: var(--text-primary);
            margin-bottom: 15px;
            font-size: 20px;
        }
        
        .no-data p {
            font-size: 16px;
            opacity: 0.8;
        }
        
        /* Button System - Government Standard */
        .btn {
            background: var(--gradient-bg);
            color: white;
            padding: 12px 24px;
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
        
        .btn-secondary {
            background: var(--text-secondary);
        }
        
        .btn-secondary:hover {
            background: var(--text-primary);
        }
        
        /* Role Badge - Authority Indicator */
        .role-badge {
            background: var(--gradient-bg);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: var(--shadow-sm);
        }
        
        /* Form Styling - Professional Data Entry */
        .form-section {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border: 2px solid var(--secondary-color);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
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
            border-radius: 15px 15px 0 0;
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
        
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s ease;
            background: white;
        }
        
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            transform: translateY(-1px);
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
        
        /* Action Buttons - Enhanced Interactions */
        .btn-group {
            display: flex;
            gap: 12px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .btn-edit {
            background: var(--accent-color);
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.3s ease;
        }
        
        .btn-edit:hover {
            background: #059669;
            transform: translateY(-1px);
            box-shadow: var(--shadow-sm);
        }
        
        .btn-edit.editing {
            background: #ef4444;
        }
        
        .btn-edit.editing:hover {
            background: #dc2626;
        }
        
        /* Alert styles */
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            border-left: 4px solid;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border-left-color: var(--accent-color);
            color: #065f46;
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border-left-color: var(--danger-color);
            color: #991b1b;
        }
        
        .btn-delete {
            background: var(--danger-color);
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-delete:hover {
            background: #dc2626;
            transform: translateY(-1px);
            box-shadow: var(--shadow-sm);
        }
        
        .btn-cancel {
            background: var(--text-secondary);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-cancel:hover {
            background: var(--text-primary);
            transform: translateY(-1px);
        }
        
        /* Edit Forms - Highlighted Editing State */
        .edit-form {
            display: none;
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
            border: 2px solid var(--warning-color);
            border-radius: 15px;
            padding: 25px;
            margin: 15px 0;
            position: relative;
        }
        
        .edit-form::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--warning-color), #f59e0b);
            border-radius: 15px 15px 0 0;
        }
        
        .edit-form.active {
            display: block;
            animation: slideDown 0.3s ease-out;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .edit-form h5 {
            color: var(--warning-color);
            margin-bottom: 20px;
            font-size: 18px;
            font-weight: 700;
        }
        
        .record-actions {
            margin-top: 20px;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        /* Responsive Design - Mobile Government Portal */
        @media (max-width: 1024px) {
            .container {
                padding: 15px;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
                padding: 25px 20px;
            }
            
            .citizen-avatar {
                width: 80px;
                height: 80px;
                font-size: 32px;
            }
            
            .header-info h1 {
                font-size: 24px;
            }
            
            .nav-bar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .nav-links {
                flex-direction: column;
                gap: 10px;
            }
            
            .tabs {
                flex-direction: column;
                gap: 8px;
            }
            
            .tab {
                text-align: center;
            }
            
            .record-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .btn-group {
                flex-direction: column;
            }
        }
        
        /* Loading and Transition Effects */
        .section {
            animation: slideUp 0.5s ease-out;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Accessibility Improvements */
        .btn:focus, .tab:focus, .nav-bar a:focus {
            outline: 3px solid var(--accent-color);
            outline-offset: 2px;
        }
        
        /* Subject Badge Styles */
        .subject-badge {
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-block;
            margin: 2px 0;
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
        
        .text-muted {
            color: var(--text-secondary);
            font-style: italic;
            font-size: 13px;
        }
        
        /* Citizen Sidebar Integration */
        body.has-citizen-sidebar .citizen-layout {
            padding-left: 280px;
            transition: padding-left 0.3s ease;
            min-height: 100vh;
        }
        
        body.citizen-sidebar-collapsed .citizen-layout {
            padding-left: 60px;
        }

        /* Mobile adjustments */
        @media (max-width: 768px) {
            body.has-citizen-sidebar .citizen-layout {
                padding-left: 0 !important;
                padding-top: 80px; /* Space for mobile menu button */
            }
        }
        
        /* Print Styles for Government Documents */
        @media print {
            .nav-bar, .tabs, .btn, .btn-group { display: none; }
            .section { box-shadow: none; border: 1px solid #ccc; }
            body { background: white; color: black; }
        }
    </style>
</head>
<body>
    <?php if ($userRole === 'Citizen'): ?>
        <?php include 'includes/citizen_sidebar.php'; ?>
    <?php endif; ?>
    
    <div class="container<?= ($userRole === 'Citizen') ? ' citizen-layout' : '' ?>">
        <div class="nav-bar">
            <div class="nav-links">
                <?php if ($userRole === 'Citizen'): ?>
                    <a href="citizen_dashboard.php">🏠 My Dashboard</a>
                    <a href="change_password.php">🔐 Change Password</a>
                <?php else: ?>
                    <a href="dashboard.php">🏠 Dashboard</a>
                    <a href="search_citizens.php">👥 Citizen Directory</a>
                    <?php if ($userRole === 'Admin'): ?>
                        <a href="register.php">➕ Register New Citizen</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="role-badge"><?= $userRole ?></div>
        </div>

        <div class="header">
            <div class="citizen-avatar">
                👤
            </div>
            <div class="header-info">
                <h1><?= htmlspecialchars($citizen['FirstName'] . ' ' . $citizen['LastName']) ?></h1>
                <p>Citizen ID: NDMS-<?= $citizen['CitizenID'] ?> | eID: <?= htmlspecialchars($citizen['Citizen_eID'] ?? 'Not Set') ?> | Age: <?= $age ?> years | <?= htmlspecialchars($citizen['Gender']) ?></p>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="tabs">
            <button class="tab active" onclick="showTab('basic')">📋 Basic Information</button>
            <?php if ($userRole === 'MedicalOfficer' || $userRole === 'Admin' || $userRole === 'Citizen'): ?>
                <button class="tab" onclick="showTab('medical')">🏥 Medical Records</button>
                <button class="tab" onclick="showTab('vaccination')">💉 Vaccination Records</button>
            <?php endif; ?>
            <?php if ($userRole === 'EducationOfficer' || $userRole === 'Admin' || $userRole === 'Citizen'): ?>
                <button class="tab" onclick="showTab('education')">🎓 Education Records</button>
            <?php endif; ?>
            <?php if ($userRole === 'Employer' || $userRole === 'Admin' || $userRole === 'Citizen'): ?>
                <button class="tab" onclick="showTab('employment')">💼 Employment Records</button>
            <?php endif; ?>
        </div>

        <!-- Basic Information Tab (Visible to All) -->
        <div id="basic" class="tab-content active">
            <div class="section">
                <!-- Profile Edit Alert -->
                <?php if (isset($profileMessage)): ?>
                    <div class="alert alert-<?= $profileMessageType ?>">
                        <i class="fas fa-<?= $profileMessageType === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
                        <?= htmlspecialchars($profileMessage) ?>
                    </div>
                <?php endif; ?>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3>📋 Basic Citizen Information</h3>
                    <!-- Admin Edit Button -->
                    <?php if ($userRole === 'Admin'): ?>
                        <button type="button" class="btn btn-edit" onclick="toggleEditProfile()" id="editProfileBtn">
                            <i class="fas fa-edit"></i> Edit Profile
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Profile Edit Form (Only for Admin) -->
                <?php if ($userRole === 'Admin'): ?>
                <div id="editProfileForm" class="edit-form" style="display: none;">
                    <form method="post" action="">
                        <h4><i class="fas fa-user-edit"></i> Edit Citizen Profile</h4>
                        <input type="hidden" name="edit_citizen_profile" value="1">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>First Name:</label>
                                <input type="text" name="first_name" value="<?= htmlspecialchars($citizen['FirstName']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Last Name:</label>
                                <input type="text" name="last_name" value="<?= htmlspecialchars($citizen['LastName']) ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>National ID (NIC):</label>
                                <input type="text" name="nic" value="<?= htmlspecialchars($citizen['NIC'] ?? '') ?>" 
                                       placeholder="e.g., 123456789V or 200012345678" maxlength="12">
                                <small style="color: #6b7280; font-size: 12px;">Sri Lankan NIC format: 9 digits + V/X or 12 digits</small>
                            </div>
                            <div class="form-group">
                                <label>Date of Birth:</label>
                                <input type="date" name="dob" value="<?= $citizen['DOB'] ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Gender:</label>
                                <select name="gender" required>
                                    <option value="Male" <?= $citizen['Gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                                    <option value="Female" <?= $citizen['Gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                                    <option value="Other" <?= $citizen['Gender'] === 'Other' ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Address:</label>
                            <textarea name="address" rows="3" required><?= htmlspecialchars($citizen['Address']) ?></textarea>
                        </div>
                        
                        <div class="form-actions" style="margin-top: 20px;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Profile
                            </button>
                            <button type="button" class="btn btn-secondary" onclick="toggleEditProfile()">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                        </div>
                    </form>
                </div>
                <?php endif; ?>

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
                        <div class="info-label">Citizen eID</div>
                        <div class="info-value"><?= htmlspecialchars($citizen['Citizen_eID'] ?? 'Not Set') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">National ID (NIC)</div>
                        <div class="info-value">
                            <?php if (!empty($citizen['NIC'])): ?>
                                <?= htmlspecialchars($citizen['NIC']) ?>
                            <?php else: ?>
                                <span style="color: #f59e0b; font-weight: 600;">⚠️ Not Set</span>
                            <?php endif; ?>
                        </div>
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

        <!-- Medical Records Tab (Medical Officer, Admin & Citizen) -->
        <?php if ($userRole === 'MedicalOfficer' || $userRole === 'Admin' || $userRole === 'Citizen'): ?>
        <div id="medical" class="tab-content">
            <!-- Add Medical Record Form (Only for Medical Officer & Admin) -->
            <?php if ($userRole === 'MedicalOfficer' || $userRole === 'Admin'): ?>
            <div class="section">
                <div class="form-section">
                    <form method="post" action="">
                        <h4>➕ Add Medical Record</h4>
                        <input type="hidden" name="add_medical" value="1">
                        <input type="hidden" name="citizenID" value="<?= $citizen['CitizenID'] ?>">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Diagnosis:</label>
                                <input type="text" name="diagnosis" required>
                            </div>
                            <div class="form-group">
                                <label>Treatment:</label>
                                <input type="text" name="treatment" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Doctor Name:</label>
                                <input type="text" name="doctorName" required>
                            </div>
                            <div class="form-group">
                                <label>Hospital Name:</label>
                                <input type="text" name="hospitalName" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Record Date:</label>
                            <input type="date" name="recordDate" required>
                        </div>
                        <button type="submit" class="btn">➕ Add Record</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
            
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
                       <?php if (!empty($birthCert['QRCodePath'])): ?>
                       <div class="info-item">
                           <div class="info-label">Verification QR Code</div>
                           <div class="info-value">
                               <img src="<?= htmlspecialchars($birthCert['QRCodePath']) ?>" alt="QR Code" style="max-width:120px; border:2px solid #eee; border-radius:8px;">
                               <br><small>Scan to verify</small>
                           </div>
                       </div>
                       <?php endif; ?>
                </div>
                   <div style="margin-top:20px;">
                       <form method="get" action="download_certificate.php" target="_blank">
                           <input type="hidden" name="cert_id" value="<?= $birthCert['BirthCertID'] ?>">
                           <button type="submit" class="btn btn-primary">
                               <i class="fas fa-download"></i> Download Certificate (PDF)
                           </button>
                       </form>
                   </div>
            </div>
            <?php endif; ?>

            <div class="section">
                <h3>🏥 Medical History</h3>
                <?php if ($medicalRecords && $medicalRecords->num_rows > 0): ?>
                    <?php while($record = $medicalRecords->fetch_assoc()): ?>
                        <div class="record-item">
                            <div class="record-header">
                                <span class="record-date"><?= $record['recordDate'] ?></span>
                            </div>
                            <p><strong>Hospital:</strong> <?= htmlspecialchars($record['hospitalName'] ?? 'Not specified') ?></p>
                            <p><strong>Diagnosis:</strong> <?= htmlspecialchars($record['diagnosis'] ?? 'No diagnosis recorded') ?></p>
                            <p><strong>Treatment:</strong> <?= htmlspecialchars($record['treatment'] ?? 'No treatment recorded') ?></p>
                            <p><strong>Doctor:</strong> <?= htmlspecialchars($record['doctorName'] ?? 'Unknown') ?></p>
                            
                            <?php if ($userRole === 'MedicalOfficer' || $userRole === 'Admin'): ?>
                            <div class="record-actions">
                                <button type="button" class="btn-edit" onclick="toggleEditForm('medical_<?= $record['recordID'] ?>')">✏️ Edit</button>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="delete_medical" value="<?= $record['recordID'] ?>">
                                    <button type="submit" class="btn-delete" onclick="return confirm('Delete this medical record?')">🗑 Delete</button>
                                </form>
                            </div>
                            
                            <!-- Edit Form (Hidden by default) -->
                            <div id="medical_<?= $record['recordID'] ?>" class="edit-form">
                                <form method="post">
                                    <h5>✏️ Edit Medical Record</h5>
                                    <input type="hidden" name="edit_medical" value="1">
                                    <input type="hidden" name="recordID" value="<?= $record['recordID'] ?>">
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Diagnosis:</label>
                                            <input type="text" name="diagnosis" value="<?= htmlspecialchars($record['diagnosis']) ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Treatment:</label>
                                            <input type="text" name="treatment" value="<?= htmlspecialchars($record['treatment']) ?>" required>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Doctor Name:</label>
                                            <input type="text" name="doctorName" value="<?= htmlspecialchars($record['doctorName']) ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Hospital Name:</label>
                                            <input type="text" name="hospitalName" value="<?= htmlspecialchars($record['hospitalName']) ?>" required>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Record Date:</label>
                                        <input type="date" name="recordDate" value="<?= $record['recordDate'] ?>" required>
                                    </div>
                                    <div class="btn-group">
                                        <button type="submit" class="btn">💾 Save Changes</button>
                                        <button type="button" class="btn-cancel" onclick="toggleEditForm('medical_<?= $record['recordID'] ?>')">❌ Cancel</button>
                                    </div>
                                </form>
                            </div>
                            <?php endif; ?>
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

        <!-- Vaccination Records Tab (Medical Officer, Admin & Citizen) -->
        <?php if ($userRole === 'MedicalOfficer' || $userRole === 'Admin' || $userRole === 'Citizen'): ?>
        <div id="vaccination" class="tab-content">
            <!-- Add Vaccination Record Form (Only for Medical Officer & Admin) -->
            <?php if ($userRole === 'MedicalOfficer' || $userRole === 'Admin'): ?>
            <div class="section">
                <div class="form-section">
                    <form method="post" action="">
                        <h4>💉 Add Vaccination Record</h4>
                        <input type="hidden" name="add_vaccination" value="1">
                        <input type="hidden" name="citizenID" value="<?= $citizen['CitizenID'] ?>">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Vaccine Name:</label>
                                <select name="vaccineName" required>
                                    <option value="">Select Vaccine</option>
                                    <?php
                                    $vaccineQuery = "SELECT VaccineName, Description FROM Vaccines ORDER BY VaccineName ASC";
                                    $vaccineResult = $conn->query($vaccineQuery);
                                    while($vaccineRow = $vaccineResult->fetch_assoc()) {
                                        $displayName = $vaccineRow['VaccineName'];
                                        if ($vaccineRow['Description']) {
                                            $displayName .= " - " . $vaccineRow['Description'];
                                        }
                                        echo "<option value='" . htmlspecialchars($vaccineRow['VaccineName']) . "'>" . htmlspecialchars($displayName) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Dose Number:</label>
                                <select name="doseNumber" required>
                                    <option value="">Select Dose</option>
                                    <option value="1">1st Dose</option>
                                    <option value="2">2nd Dose</option>
                                    <option value="3">3rd Dose</option>
                                    <option value="4">4th Dose</option>
                                    <option value="5">Booster</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Date Administered:</label>
                                <input type="date" name="dateAdministered" required>
                            </div>
                            <div class="form-group">
                                <label>Administered By:</label>
                                <input type="text" name="administeredBy" placeholder="Doctor/Nurse name" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Notes (Optional):</label>
                            <textarea name="notes" placeholder="Any additional notes or side effects observed..."></textarea>
                        </div>
                        <button type="submit" class="btn">💉 Add Vaccination Record</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="section">
                <h3>💉 Vaccination History</h3>
                <?php if ($vaccinationRecords && $vaccinationRecords->num_rows > 0): ?>
                    <div class="vaccination-summary" style="background: #e8f5e8; padding: 15px; border-radius: 10px; margin-bottom: 20px;">
                        <h4 style="color: #28a745; margin: 0 0 10px 0;">📊 Vaccination Summary</h4>
                        <p style="margin: 0;"><strong>Total Vaccinations:</strong> <?= $vaccinationRecords->num_rows ?> doses administered</p>
                    </div>
                    
                    <?php 
                    // Reset pointer to fetch records again
                    $vaccinationRecords->data_seek(0);
                    while($record = $vaccinationRecords->fetch_assoc()): 
                    ?>
                        <div class="record-item">
                            <div class="record-header">
                                <span class="record-date"><?= $record['DateAdministered'] ?></span>
                                <span class="badge badge-info">Dose <?= $record['DoseNumber'] ?></span>
                            </div>
                            <p><strong>Vaccine:</strong> <?= htmlspecialchars($record['VaccineName']) ?></p>
                            <p><strong>Administered By:</strong> <?= htmlspecialchars($record['AdministeredBy'] ?? 'Not specified') ?></p>
                            <?php if ($record['Notes']): ?>
                                <p><strong>Notes:</strong> <?= htmlspecialchars($record['Notes']) ?></p>
                            <?php endif; ?>
                            
                            <?php if ($userRole === 'MedicalOfficer' || $userRole === 'Admin'): ?>
                            <div class="record-actions">
                                <button type="button" class="btn-edit" onclick="toggleEditForm('vaccination_<?= $record['VaccineID'] ?>')">✏️ Edit</button>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="delete_vaccination" value="<?= $record['VaccineID'] ?>">
                                    <button type="submit" class="btn-delete" onclick="return confirm('Delete this vaccination record?')">🗑 Delete</button>
                                </form>
                            </div>
                            
                            <!-- Edit Form (Hidden by default) -->
                            <div id="vaccination_<?= $record['VaccineID'] ?>" class="edit-form">
                                <form method="post">
                                    <h5>✏️ Edit Vaccination Record</h5>
                                    <input type="hidden" name="edit_vaccination" value="1">
                                    <input type="hidden" name="vaccineID" value="<?= $record['VaccineID'] ?>">
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Vaccine Name:</label>
                                            <select name="vaccineName" required>
                                                <?php
                                                $editVaccineQuery = "SELECT VaccineName, Description FROM Vaccines ORDER BY VaccineName ASC";
                                                $editVaccineResult = $conn->query($editVaccineQuery);
                                                while($editVaccineRow = $editVaccineResult->fetch_assoc()) {
                                                    $displayName = $editVaccineRow['VaccineName'];
                                                    if ($editVaccineRow['Description']) {
                                                        $displayName .= " - " . $editVaccineRow['Description'];
                                                    }
                                                    $selected = ($record['VaccineName'] == $editVaccineRow['VaccineName']) ? 'selected' : '';
                                                    echo "<option value='" . htmlspecialchars($editVaccineRow['VaccineName']) . "' $selected>" . htmlspecialchars($displayName) . "</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Dose Number:</label>
                                            <select name="doseNumber" required>
                                                <option value="1" <?= $record['DoseNumber'] == 1 ? 'selected' : '' ?>>1st Dose</option>
                                                <option value="2" <?= $record['DoseNumber'] == 2 ? 'selected' : '' ?>>2nd Dose</option>
                                                <option value="3" <?= $record['DoseNumber'] == 3 ? 'selected' : '' ?>>3rd Dose</option>
                                                <option value="4" <?= $record['DoseNumber'] == 4 ? 'selected' : '' ?>>4th Dose</option>
                                                <option value="5" <?= $record['DoseNumber'] == 5 ? 'selected' : '' ?>>Booster</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Date Administered:</label>
                                            <input type="date" name="dateAdministered" value="<?= $record['DateAdministered'] ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Administered By:</label>
                                            <input type="text" name="administeredBy" value="<?= htmlspecialchars($record['AdministeredBy']) ?>" required>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Notes (Optional):</label>
                                        <textarea name="notes"><?= htmlspecialchars($record['Notes']) ?></textarea>
                                    </div>
                                    <div class="btn-group">
                                        <button type="submit" class="btn">💾 Save Changes</button>
                                        <button type="button" class="btn-cancel" onclick="toggleEditForm('vaccination_<?= $record['VaccineID'] ?>')">❌ Cancel</button>
                                    </div>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-data">
                        <h4>No Vaccination Records</h4>
                        <p>No vaccination history has been recorded for this citizen.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Education Records Tab (Education Officer, Admin & Citizen) -->
        <?php if ($userRole === 'EducationOfficer' || $userRole === 'Admin' || $userRole === 'Citizen'): ?>
        <div id="education" class="tab-content">
            <!-- Add Education Record Form (Only for Education Officer & Admin) -->
            <?php if ($userRole === 'EducationOfficer' || $userRole === 'Admin'): ?>
            <div class="section">
                <div class="form-section">
                    <form method="post" action="">
                        <h4>🎓 Add Education Record</h4>
                        <input type="hidden" name="add_education" value="1">
                        <input type="hidden" name="citizenID" value="<?= $citizen['CitizenID'] ?>">
                        <div class="form-row">
                            <div class="form-group">
                                <label>School Name:</label>
                                <input type="text" name="schoolName" required>
                            </div>
                            <div class="form-group">
                                <label>Grade/Level:</label>
                                <select name="gradeLevel" required>
                                    <option value="">-- Select Grade/Level --</option>
                                    <option value="Grade 1">Grade 1</option>
                                    <option value="Grade 2">Grade 2</option>
                                    <option value="Grade 3">Grade 3</option>
                                    <option value="Grade 4">Grade 4</option>
                                    <option value="Grade 5">Grade 5</option>
                                    <option value="Grade 6">Grade 6</option>
                                    <option value="Grade 7">Grade 7</option>
                                    <option value="Grade 8">Grade 8</option>
                                    <option value="Grade 9">Grade 9</option>
                                    <option value="Grade 10">Grade 10</option>
                                    <option value="Grade 11">Grade 11</option>
                                    <option value="Grade 12">Grade 12</option>
                                    <option value="Grade 13">Grade 13</option>
                                    <option value="Ordinary Level">Ordinary Level</option>
                                    <option value="Advanced Level">Advanced Level</option>
                                    <option value="University">University</option>
                                    <option value="Postgraduate">Postgraduate</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Exam Name:</label>
                                <input type="text" name="examName">
                            </div>
                            <div class="form-group">
                                <label>Subject (Optional):</label>
                                <select name="subjectID">
                                    <option value="">-- No Subject Selected --</option>
                                    <?php
                                    $subjectsQuery = $conn->query("SELECT SubjectID, SubjectName, Category FROM Subjects ORDER BY Category, SubjectName");
                                    $currentCategory = '';
                                    while ($subject = $subjectsQuery->fetch_assoc()):
                                        if ($currentCategory != $subject['Category']):
                                            if ($currentCategory != '') echo '</optgroup>';
                                            echo '<optgroup label="' . htmlspecialchars($subject['Category']) . '">';
                                            $currentCategory = $subject['Category'];
                                        endif;
                                    ?>
                                        <option value="<?= $subject['SubjectID'] ?>"><?= htmlspecialchars($subject['SubjectName']) ?></option>
                                    <?php endwhile; ?>
                                    <?php if ($currentCategory != '') echo '</optgroup>'; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Result:</label>
                                <input type="text" name="result">
                            </div>
                            <div class="form-group">
                                <label>Marks/Score:</label>
                                <input type="text" name="marksObtained" placeholder="e.g., 85/100, 3A 2B 4C">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Record Date:</label>
                                <input type="date" name="recordDate" required>
                            </div>
                        </div>
                        </div>
                        <button type="submit" class="btn">🎓 Add Education Record</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Education History Section -->
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
                            <?php if ($record['SubjectName']): ?>
                                <p><strong>Subject:</strong> 
                                    <span class="subject-badge <?= strtolower(str_replace(' ', '-', $record['SubjectCategory'])) ?>">
                                        <?= htmlspecialchars($record['SubjectName']) ?>
                                    </span>
                                </p>
                            <?php else: ?>
                                <p><strong>Subject:</strong> <span class="text-muted">No Subject</span></p>
                            <?php endif; ?>
                            <?php if ($record['MarksObtained']): ?>
                                <p><strong>Marks:</strong> <?= htmlspecialchars($record['MarksObtained']) ?></p>
                            <?php endif; ?>
                            <p><small><strong>Recorded by:</strong> <?= htmlspecialchars($record['RegisteredByName'] ?? 'Unknown') ?></small></p>
                            
                            <?php if ($userRole === 'EducationOfficer' || $userRole === 'Admin'): ?>
                            <div class="record-actions">
                                <button type="button" class="btn-edit" onclick="toggleEditForm('education_<?= $record['EduID'] ?>')">✏️ Edit</button>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="delete_education" value="<?= $record['EduID'] ?>">
                                    <button type="submit" class="btn-delete" onclick="return confirm('Delete this education record?')">🗑 Delete</button>
                                </form>
                            </div>
                            
                            <!-- Edit Form (Hidden by default) -->
                            <div id="education_<?= $record['EduID'] ?>" class="edit-form">
                                <form method="post">
                                    <h5>✏️ Edit Education Record</h5>
                                    <input type="hidden" name="edit_education" value="1">
                                    <input type="hidden" name="eduID" value="<?= $record['EduID'] ?>">
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>School Name:</label>
                                            <input type="text" name="schoolName" value="<?= htmlspecialchars($record['SchoolName']) ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Grade/Level:</label>
                                            <select name="gradeLevel" required>
                                                <option value="">-- Select Grade/Level --</option>
                                                <option value="Grade 1" <?= $record['GradeLevel'] == 'Grade 1' ? 'selected' : '' ?>>Grade 1</option>
                                                <option value="Grade 2" <?= $record['GradeLevel'] == 'Grade 2' ? 'selected' : '' ?>>Grade 2</option>
                                                <option value="Grade 3" <?= $record['GradeLevel'] == 'Grade 3' ? 'selected' : '' ?>>Grade 3</option>
                                                <option value="Grade 4" <?= $record['GradeLevel'] == 'Grade 4' ? 'selected' : '' ?>>Grade 4</option>
                                                <option value="Grade 5" <?= $record['GradeLevel'] == 'Grade 5' ? 'selected' : '' ?>>Grade 5</option>
                                                <option value="Grade 6" <?= $record['GradeLevel'] == 'Grade 6' ? 'selected' : '' ?>>Grade 6</option>
                                                <option value="Grade 7" <?= $record['GradeLevel'] == 'Grade 7' ? 'selected' : '' ?>>Grade 7</option>
                                                <option value="Grade 8" <?= $record['GradeLevel'] == 'Grade 8' ? 'selected' : '' ?>>Grade 8</option>
                                                <option value="Grade 9" <?= $record['GradeLevel'] == 'Grade 9' ? 'selected' : '' ?>>Grade 9</option>
                                                <option value="Grade 10" <?= $record['GradeLevel'] == 'Grade 10' ? 'selected' : '' ?>>Grade 10</option>
                                                <option value="Grade 11" <?= $record['GradeLevel'] == 'Grade 11' ? 'selected' : '' ?>>Grade 11</option>
                                                <option value="Grade 12" <?= $record['GradeLevel'] == 'Grade 12' ? 'selected' : '' ?>>Grade 12</option>
                                                <option value="Grade 13" <?= $record['GradeLevel'] == 'Grade 13' ? 'selected' : '' ?>>Grade 13</option>
                                                <option value="Ordinary Level" <?= $record['GradeLevel'] == 'Ordinary Level' ? 'selected' : '' ?>>Ordinary Level</option>
                                                <option value="Advanced Level" <?= $record['GradeLevel'] == 'Advanced Level' ? 'selected' : '' ?>>Advanced Level</option>
                                                <option value="University" <?= $record['GradeLevel'] == 'University' ? 'selected' : '' ?>>University</option>
                                                <option value="Postgraduate" <?= $record['GradeLevel'] == 'Postgraduate' ? 'selected' : '' ?>>Postgraduate</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Exam Name:</label>
                                            <input type="text" name="examName" value="<?= htmlspecialchars($record['ExamName']) ?>">
                                        </div>
                                        <div class="form-group">
                                            <label>Subject (Optional):</label>
                                            <select name="subjectID">
                                                <option value="">-- No Subject Selected --</option>
                                                <?php
                                                $editSubjectsQuery = $conn->query("SELECT SubjectID, SubjectName, Category FROM Subjects ORDER BY Category, SubjectName");
                                                $currentEditCategory = '';
                                                while ($editSubject = $editSubjectsQuery->fetch_assoc()):
                                                    if ($currentEditCategory != $editSubject['Category']):
                                                        if ($currentEditCategory != '') echo '</optgroup>';
                                                        echo '<optgroup label="' . htmlspecialchars($editSubject['Category']) . '">';
                                                        $currentEditCategory = $editSubject['Category'];
                                                    endif;
                                                ?>
                                                    <option value="<?= $editSubject['SubjectID'] ?>" <?= $record['SubjectID'] == $editSubject['SubjectID'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($editSubject['SubjectName']) ?>
                                                    </option>
                                                <?php endwhile; ?>
                                                <?php if ($currentEditCategory != '') echo '</optgroup>'; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Result:</label>
                                            <input type="text" name="result" value="<?= htmlspecialchars($record['Result']) ?>">
                                        </div>
                                        <div class="form-group">
                                            <label>Marks/Score:</label>
                                            <input type="text" name="marksObtained" value="<?= htmlspecialchars($record['MarksObtained']) ?>" placeholder="e.g., 85/100, 3A 2B 4C">
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Record Date:</label>
                                            <input type="date" name="recordDate" value="<?= $record['RecordDate'] ?>" required>
                                        </div>
                                    </div>
                                    <div class="btn-group">
                                        <button type="submit" class="btn">💾 Save Changes</button>
                                        <button type="button" class="btn-cancel" onclick="toggleEditForm('education_<?= $record['EduID'] ?>')">❌ Cancel</button>
                                    </div>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-data">
                        <h4>No Education Records</h4>
                        <p>No education history has been recorded for this citizen.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Employment Records Tab (Employer, Admin & Citizen) -->
        <?php if ($userRole === 'Employer' || $userRole === 'Admin' || $userRole === 'Citizen'): ?>
        <div id="employment" class="tab-content">
            <!-- Add Employment Record Form (Only for Employer & Admin) -->
            <?php if ($userRole === 'Employer' || $userRole === 'Admin'): ?>
            <div class="section">
                <div class="form-section">
                    <form method="post" action="">
                        <h4>💼 Add Employment Record</h4>
                        <input type="hidden" name="add_employment" value="1">
                        <input type="hidden" name="citizenID" value="<?= $citizen['CitizenID'] ?>">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Company Name:</label>
                                <input type="text" name="companyName" required>
                            </div>
                            <div class="form-group">
                                <label>Job Title:</label>
                                <input type="text" name="jobTitle" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Start Date:</label>
                                <input type="date" name="startDate" required>
                            </div>
                            <div class="form-group">
                                <label>End Date (Leave empty if current):</label>
                                <input type="date" name="endDate">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Salary (LKR):</label>
                            <input type="number" name="salary" step="0.01" min="0">
                        </div>
                        <button type="submit" class="btn">💼 Add Employment Record</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
            
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
                            
                            <?php if ($userRole === 'Employer' || $userRole === 'Admin'): ?>
                            <div class="record-actions">
                                <button type="button" class="btn-edit" onclick="toggleEditForm('employment_<?= $record['EmpID'] ?>')">✏️ Edit</button>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="delete_employment" value="<?= $record['EmpID'] ?>">
                                    <button type="submit" class="btn-delete" onclick="return confirm('Delete this employment record?')">🗑 Delete</button>
                                </form>
                            </div>
                            
                            <!-- Edit Form (Hidden by default) -->
                            <div id="employment_<?= $record['EmpID'] ?>" class="edit-form">
                                <form method="post">
                                    <h5>✏️ Edit Employment Record</h5>
                                    <input type="hidden" name="edit_employment" value="1">
                                    <input type="hidden" name="empID" value="<?= $record['EmpID'] ?>">
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Company Name:</label>
                                            <input type="text" name="companyName" value="<?= htmlspecialchars($record['CompanyName']) ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Job Title:</label>
                                            <input type="text" name="jobTitle" value="<?= htmlspecialchars($record['JobTitle']) ?>" required>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Start Date:</label>
                                            <input type="date" name="startDate" value="<?= $record['StartDate'] ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>End Date (Leave blank if current):</label>
                                            <input type="date" name="endDate" value="<?= $record['EndDate'] ?>">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Salary (LKR):</label>
                                        <input type="number" name="salary" step="0.01" min="0" value="<?= $record['Salary'] ?>">
                                    </div>
                                    <div class="btn-group">
                                        <button type="submit" class="btn">💾 Save Changes</button>
                                        <button type="button" class="btn-cancel" onclick="toggleEditForm('employment_<?= $record['EmpID'] ?>')">❌ Cancel</button>
                                    </div>
                                </form>
                            </div>
                            <?php endif; ?>
                            <p><strong>Job Title:</strong> <?= htmlspecialchars($record['JobTitle'] ?? 'Not specified') ?></p>
                            <?php if ($record['Salary']): ?>
                                <p><strong>Salary:</strong> LKR <?= number_format($record['Salary'], 2) ?></p>
                            <?php endif; ?>
                            <p><small><strong>Recorded by:</strong> <?= htmlspecialchars($record['RegisteredByName'] ?? 'Unknown') ?></small></p>
                            
                            <?php if ($userRole === 'Employer' || $userRole === 'Admin'): ?>
                            <div class="record-actions">
                                <button type="button" class="btn-edit" onclick="toggleEditForm('employment_<?= $record['EmpID'] ?>')">✏️ Edit</button>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="delete_employment" value="<?= $record['EmpID'] ?>">
                                    <button type="submit" class="btn-delete" onclick="return confirm('Delete this employment record?')">🗑 Delete</button>
                                </form>
                            </div>
                            
                            <!-- Edit Form (Hidden by default) -->
                            <div id="employment_<?= $record['EmpID'] ?>" class="edit-form">
                                <form method="post">
                                    <h5>✏️ Edit Employment Record</h5>
                                    <input type="hidden" name="edit_employment" value="1">
                                    <input type="hidden" name="empID" value="<?= $record['EmpID'] ?>">
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Company Name:</label>
                                            <input type="text" name="companyName" value="<?= htmlspecialchars($record['CompanyName']) ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>Job Title:</label>
                                            <input type="text" name="jobTitle" value="<?= htmlspecialchars($record['JobTitle']) ?>" required>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label>Start Date:</label>
                                            <input type="date" name="startDate" value="<?= $record['StartDate'] ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label>End Date (Leave empty if current):</label>
                                            <input type="date" name="endDate" value="<?= $record['EndDate'] ?>">
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label>Salary (LKR):</label>
                                        <input type="number" name="salary" value="<?= $record['Salary'] ?>" step="0.01" min="0">
                                    </div>
                                    <div class="btn-group">
                                        <button type="submit" class="btn">💾 Save Changes</button>
                                        <button type="button" class="btn-cancel" onclick="toggleEditForm('employment_<?= $record['EmpID'] ?>')">❌ Cancel</button>
                                    </div>
                                </form>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-data">
                        <h4>No Employment Records</h4>
                        <p>No employment history has been recorded for this citizen.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tab contents with more specific targeting
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => {
                content.classList.remove('active');
                content.style.display = 'none';
                content.style.visibility = 'hidden'; // Extra safety
            });
            
            // Remove active class from all tabs
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab content
            const selectedTab = document.getElementById(tabName);
            if (selectedTab) {
                selectedTab.classList.add('active');
                selectedTab.style.display = 'block';
                selectedTab.style.visibility = 'visible'; // Extra safety
            }
            
            // Add active class to clicked tab
            const tabButtons = document.querySelectorAll('.tab');
            tabButtons.forEach(tab => {
                if (tab.getAttribute('onclick') && tab.getAttribute('onclick').includes(tabName)) {
                    tab.classList.add('active');
                }
            });
        }
        
        function toggleEditForm(formId) {
            const form = document.getElementById(formId);
            if (form.classList.contains('active')) {
                form.classList.remove('active');
            } else {
                // Hide all other edit forms first
                const allEditForms = document.querySelectorAll('.edit-form');
                allEditForms.forEach(f => f.classList.remove('active'));
                
                // Show the selected form
                form.classList.add('active');
            }
        }
        
        // Toggle profile edit form specifically
        function toggleEditProfile() {
            const form = document.getElementById('editProfileForm');
            const btn = document.getElementById('editProfileBtn');
            
            if (form.style.display === 'none' || form.style.display === '') {
                form.style.display = 'block';
                form.classList.add('active');
                btn.innerHTML = '<i class="fas fa-times"></i> Cancel Edit';
                btn.classList.add('editing');
            } else {
                form.style.display = 'none';
                form.classList.remove('active');
                btn.innerHTML = '<i class="fas fa-edit"></i> Edit Profile';
                btn.classList.remove('editing');
            }
        }
        
        // Check for tab parameter in URL and switch to that tab
        window.addEventListener('DOMContentLoaded', function() {
            // Initialize tabs - ensure only basic tab is shown
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => {
                content.classList.remove('active');
                content.style.display = 'none';
            });
            
            // Show basic tab by default
            const basicTab = document.getElementById('basic');
            if (basicTab) {
                basicTab.classList.add('active');
                basicTab.style.display = 'block';
            }
            
            // Check URL parameter
            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get('tab');
            if (tab && document.getElementById(tab)) {
                showTab(tab);
            }
        });
    </script>
</body>
</html>
