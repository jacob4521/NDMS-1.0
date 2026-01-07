<?php
require_once 'config.php';
require_once 'server_config.php'; // Include server configuration
require_once 'includes/TCPDF-main/tcpdf.php';
require_once 'includes/phpqrcode/phpqrcode.php';
require_once 'birth_certificate_helper.php';

if (!isset($_GET['cert_id'])) {
    die('Certificate ID not provided.');
}
$certId = intval($_GET['cert_id']);

// Fetch certificate
$stmt = $conn->prepare("SELECT bc.*, c.FirstName, c.LastName FROM BirthCertificates bc JOIN Citizens c ON bc.CitizenID = c.CitizenID WHERE bc.BirthCertID = ?");
$stmt->bind_param('i', $certId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die('Certificate not found.');
}
$cert = $result->fetch_assoc();
$stmt->close();

// Security: Only the citizen or authorized roles can download
$userRole = $_SESSION['Role'] ?? '';
$userId = $_SESSION['UserID'] ?? 0;
if ($userRole === 'Citizen') {
    $userCitizenQuery = $conn->prepare("SELECT c.CitizenID FROM Citizens c JOIN Users u ON c.Citizen_eID = u.Username WHERE u.UserID = ?");
    $userCitizenQuery->bind_param('i', $userId);
    $userCitizenQuery->execute();
    $userCitizenResult = $userCitizenQuery->get_result();
    $userCitizen = $userCitizenResult->fetch_assoc();
    if (!$userCitizen || $userCitizen['CitizenID'] != $cert['CitizenID']) {
        die('Access denied.');
    }
}

// Prepare PDF
$pdf = new TCPDF();
$pdf->SetCreator('NDMS');
$pdf->SetAuthor('NDMS');
$pdf->SetTitle('Birth Certificate');
$pdf->SetMargins(20, 20, 20);
$pdf->AddPage();

$html = '<h2 style="color:#667eea;">Birth Certificate</h2>';
$html .= '<table cellpadding="6" style="font-size:14px;">';

// Basic Information
$html .= '<tr><td colspan="2" style="background-color:#f0f0f0;"><strong>CERTIFICATE INFORMATION</strong></td></tr>';
$html .= '<tr><td><strong>Certificate Number:</strong></td><td>' . htmlspecialchars($cert['CertificateNumber']) . '</td></tr>';
$html .= '<tr><td><strong>Issue Date:</strong></td><td>' . htmlspecialchars($cert['IssueDate']) . '</td></tr>';
$html .= '<tr><td><strong>Status:</strong></td><td>' . htmlspecialchars($cert['Status']) . '</td></tr>';

// Child Information
$html .= '<tr><td colspan="2" style="background-color:#f0f0f0;"><strong>CHILD INFORMATION</strong></td></tr>';
$html .= '<tr><td><strong>Child Full Name:</strong></td><td>' . htmlspecialchars($cert['ChildFullName']) . '</td></tr>';
$html .= '<tr><td><strong>Date of Birth:</strong></td><td>' . htmlspecialchars($cert['DateOfBirth']) . '</td></tr>';
$html .= '<tr><td><strong>Time of Birth:</strong></td><td>' . htmlspecialchars($cert['TimeOfBirth'] ?? 'Not specified') . '</td></tr>';
$html .= '<tr><td><strong>Sex:</strong></td><td>' . htmlspecialchars($cert['Sex']) . '</td></tr>';
$html .= '<tr><td><strong>Nationality:</strong></td><td>' . htmlspecialchars($cert['Nationality']) . '</td></tr>';
$html .= '<tr><td><strong>Birth Weight (grams):</strong></td><td>' . htmlspecialchars($cert['BirthWeightGrams'] ?? 'Not recorded') . '</td></tr>';
$html .= '<tr><td><strong>Birth Length (cm):</strong></td><td>' . htmlspecialchars($cert['BirthLengthCm'] ?? 'Not recorded') . '</td></tr>';

// Birth Location Information
$html .= '<tr><td colspan="2" style="background-color:#f0f0f0;"><strong>BIRTH LOCATION</strong></td></tr>';
$html .= '<tr><td><strong>Place of Birth:</strong></td><td>' . htmlspecialchars($cert['PlaceOfBirth']) . '</td></tr>';
$html .= '<tr><td><strong>Hospital/Institution:</strong></td><td>' . htmlspecialchars($cert['HospitalName']) . '</td></tr>';
$html .= '<tr><td><strong>Delivery Type:</strong></td><td>' . htmlspecialchars($cert['DeliveryType'] ?? 'Not specified') . '</td></tr>';

// Parents Information
$html .= '<tr><td colspan="2" style="background-color:#f0f0f0;"><strong>PARENTS INFORMATION</strong></td></tr>';
$html .= '<tr><td><strong>Father Name:</strong></td><td>' . htmlspecialchars($cert['FatherName']) . '</td></tr>';
$html .= '<tr><td><strong>Father NIC:</strong></td><td>' . htmlspecialchars($cert['FatherNIC'] ?? 'Not provided') . '</td></tr>';
$html .= '<tr><td><strong>Mother Name:</strong></td><td>' . htmlspecialchars($cert['MotherName']) . '</td></tr>';
$html .= '<tr><td><strong>Mother NIC:</strong></td><td>' . htmlspecialchars($cert['MotherNIC'] ?? 'Not provided') . '</td></tr>';
$html .= '<tr><td><strong>Mother Maiden Name:</strong></td><td>' . htmlspecialchars($cert['MotherMaidenName'] ?? 'Not provided') . '</td></tr>';
$html .= '<tr><td><strong>Parents Address:</strong></td><td>' . htmlspecialchars($cert['ParentsAddress'] ?? 'Not provided') . '</td></tr>';

// Registration Information
$html .= '<tr><td colspan="2" style="background-color:#f0f0f0;"><strong>REGISTRATION DETAILS</strong></td></tr>';
$html .= '<tr><td><strong>Registrar Office:</strong></td><td>' . htmlspecialchars($cert['RegistrarOffice'] ?? 'Not specified') . '</td></tr>';
$html .= '<tr><td><strong>Registered By:</strong></td><td>' . htmlspecialchars($cert['RegisteredBy'] ?? 'System') . '</td></tr>';
$html .= '<tr><td><strong>Registered At:</strong></td><td>' . htmlspecialchars($cert['RegisteredAt']) . '</td></tr>';

$html .= '</table>';

// QR code with dynamic server URL
$serverUrl = getServerUrl();
$verifyUrl = $cert['VerificationHash'] ? $serverUrl . '/verify_certificate.php?cert=' . urlencode($cert['CertificateNumber']) . '&hash=' . urlencode($cert['VerificationHash']) : '';
if ($verifyUrl) {
    $qrFile = tempnam(sys_get_temp_dir(), 'qr_') . '.png';
    QRcode::png($verifyUrl, $qrFile, QR_ECLEVEL_M, 6, 2);
    $pdf->Image($qrFile, 150, 40, 40, 40, 'PNG');
    unlink($qrFile);
    $html .= '<br><br><small>Scan QR code to verify certificate</small>';
}

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('BirthCertificate.pdf', 'D');
exit;
