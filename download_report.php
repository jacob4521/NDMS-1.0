<?php
require_once 'config.php';
require_once 'citizen_reports_helper.php';

// Check if user is logged in and is a citizen
if (!isset($_SESSION['Role']) || $_SESSION['Role'] !== 'Citizen') {
    die('Access denied. Citizens only.');
}

// Get parameters
$reportType = $_GET['type'] ?? '';
$citizenID = $_GET['citizen_id'] ?? 0;

// Security: Only the citizen can download their own reports
$userId = $_SESSION['UserID'] ?? 0;
$userCitizenQuery = $conn->prepare("SELECT c.CitizenID FROM Citizens c JOIN Users u ON c.Citizen_eID = u.Username WHERE u.UserID = ?");
$userCitizenQuery->bind_param('i', $userId);
$userCitizenQuery->execute();
$userCitizenResult = $userCitizenQuery->get_result();
$userCitizen = $userCitizenResult->fetch_assoc();

if (!$userCitizen || $userCitizen['CitizenID'] != $citizenID) {
    die('Access denied. You can only download your own reports.');
}

// Generate the appropriate report
switch ($reportType) {
    case 'vaccination':
        $pdf = generateVaccinationReport($citizenID);
        $filename = 'Vaccination_Report.pdf';
        break;
        
    case 'education':
        $pdf = generateEducationReport($citizenID);
        $filename = 'Education_Report.pdf';
        break;
        
    case 'medical':
        $pdf = generateMedicalReport($citizenID);
        $filename = 'Medical_Report.pdf';
        break;
        
    case 'employment':
        $pdf = generateEmploymentReport($citizenID);
        $filename = 'Employment_Report.pdf';
        break;
        
    default:
        die('Invalid report type specified.');
}

if (!$pdf) {
    die('Failed to generate report.');
}

// Output the PDF
$pdf->Output($filename, 'D');
exit;
?>
