<?php
// citizen_reports_helper.php
// Helper functions for generating citizen reports

require_once 'config.php';
require_once 'includes/TCPDF-main/tcpdf.php';

/**
 * Get vaccination records for a citizen
 */
function getVaccinationRecords($citizenID) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT vr.*, v.Description 
        FROM VaccinationRecords vr 
        LEFT JOIN Vaccines v ON vr.VaccineName = v.VaccineName 
        WHERE vr.CitizenID = ? 
        ORDER BY vr.DateAdministered DESC
    ");
    $stmt->bind_param('i', $citizenID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $records = [];
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
    $stmt->close();
    
    return $records;
}

/**
 * Get education records for a citizen
 */
function getEducationRecords($citizenID) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT * FROM EducationRecords 
        WHERE CitizenID = ? 
        ORDER BY RecordDate DESC
    ");
    $stmt->bind_param('i', $citizenID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $records = [];
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
    $stmt->close();
    
    return $records;
}

/**
 * Get medical records for a citizen
 */
function getMedicalRecords($citizenID) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT * FROM MedicalRecords 
        WHERE citizenID = ? 
        ORDER BY recordDate DESC
    ");
    $stmt->bind_param('i', $citizenID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $records = [];
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
    $stmt->close();
    
    return $records;
}

/**
 * Get employment records for a citizen
 */
function getEmploymentRecords($citizenID) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT er.*, u.Username as RegisteredByUser
        FROM EmploymentRecords er 
        LEFT JOIN Users u ON er.RegisteredBy = u.UserID
        WHERE er.CitizenID = ? 
        ORDER BY er.StartDate DESC
    ");
    $stmt->bind_param('i', $citizenID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $records = [];
    while ($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
    $stmt->close();
    
    return $records;
}

/**
 * Generate Vaccination Report PDF
 */
function generateVaccinationReport($citizenID) {
    global $conn;
    
    // Get citizen info
    $stmt = $conn->prepare("SELECT * FROM Citizens WHERE CitizenID = ?");
    $stmt->bind_param('i', $citizenID);
    $stmt->execute();
    $citizen = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$citizen) {
        return false;
    }
    
    // Get vaccination records
    $vaccinations = getVaccinationRecords($citizenID);
    
    // Create PDF
    $pdf = new TCPDF();
    $pdf->SetCreator('NDMS');
    $pdf->SetAuthor('NDMS');
    $pdf->SetTitle('Vaccination Report');
    $pdf->SetMargins(20, 20, 20);
    $pdf->AddPage();
    
    // Header
    $html = '<h2 style="color:#667eea; text-align:center;">Sri Lanka Vaccination Report</h2>';
    $html .= '<h3 style="text-align:center; color:#666;">National Digital Management System</h3>';
    $html .= '<hr><br>';
    
    // Citizen Information
    $html .= '<table cellpadding="8" style="font-size:14px; width:100%;">';
    $html .= '<tr><td colspan="2" style="background-color:#f0f0f0;"><strong>CITIZEN INFORMATION</strong></td></tr>';
    $html .= '<tr><td width="30%"><strong>Full Name:</strong></td><td>' . htmlspecialchars($citizen['FirstName'] . ' ' . $citizen['LastName']) . '</td></tr>';
    $html .= '<tr><td><strong>NIC:</strong></td><td>' . htmlspecialchars($citizen['NIC'] ?? 'Not provided') . '</td></tr>';
    $html .= '<tr><td><strong>Date of Birth:</strong></td><td>' . htmlspecialchars($citizen['DOB']) . '</td></tr>';
    $html .= '<tr><td><strong>Address:</strong></td><td>' . htmlspecialchars($citizen['Address']) . '</td></tr>';
    $html .= '</table><br>';
    
    // Vaccination Records
    $html .= '<table cellpadding="6" border="1" style="font-size:12px; width:100%; border-collapse:collapse;">';
    $html .= '<tr style="background-color:#667eea; color:white;">';
    $html .= '<th><strong>Vaccine Name</strong></th>';
    $html .= '<th><strong>Dose</strong></th>';
    $html .= '<th><strong>Date</strong></th>';
    $html .= '<th><strong>Administered By</strong></th>';
    $html .= '<th><strong>Notes</strong></th>';
    $html .= '</tr>';
    
    if (empty($vaccinations)) {
        $html .= '<tr><td colspan="5" style="text-align:center; color:#666;">No vaccination records found</td></tr>';
    } else {
        foreach ($vaccinations as $vax) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($vax['VaccineName']) . '</td>';
            $html .= '<td>' . htmlspecialchars($vax['DoseNumber']) . '</td>';
            $html .= '<td>' . htmlspecialchars($vax['DateAdministered']) . '</td>';
            $html .= '<td>' . htmlspecialchars($vax['AdministeredBy'] ?? 'Not specified') . '</td>';
            $html .= '<td>' . htmlspecialchars($vax['Notes'] ?? '-') . '</td>';
            $html .= '</tr>';
        }
    }
    
    $html .= '</table><br>';
    $html .= '<p style="font-size:10px; color:#666; text-align:center;">Generated on ' . date('F j, Y \a\t g:i A') . ' | National Digital Management System</p>';
    
    $pdf->writeHTML($html, true, false, true, false, '');
    return $pdf;
}

/**
 * Generate Education Report PDF
 */
function generateEducationReport($citizenID) {
    global $conn;
    
    // Get citizen info
    $stmt = $conn->prepare("SELECT * FROM Citizens WHERE CitizenID = ?");
    $stmt->bind_param('i', $citizenID);
    $stmt->execute();
    $citizen = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$citizen) {
        return false;
    }
    
    // Get education records
    $education = getEducationRecords($citizenID);
    
    // Create PDF
    $pdf = new TCPDF();
    $pdf->SetCreator('NDMS');
    $pdf->SetAuthor('NDMS');
    $pdf->SetTitle('Education Report');
    $pdf->SetMargins(20, 20, 20);
    $pdf->AddPage();
    
    // Header
    $html = '<h2 style="color:#667eea; text-align:center;">Sri Lanka Education Report</h2>';
    $html .= '<h3 style="text-align:center; color:#666;">National Digital Management System</h3>';
    $html .= '<hr><br>';
    
    // Citizen Information
    $html .= '<table cellpadding="8" style="font-size:14px; width:100%;">';
    $html .= '<tr><td colspan="2" style="background-color:#f0f0f0;"><strong>CITIZEN INFORMATION</strong></td></tr>';
    $html .= '<tr><td width="30%"><strong>Full Name:</strong></td><td>' . htmlspecialchars($citizen['FirstName'] . ' ' . $citizen['LastName']) . '</td></tr>';
    $html .= '<tr><td><strong>NIC:</strong></td><td>' . htmlspecialchars($citizen['NIC'] ?? 'Not provided') . '</td></tr>';
    $html .= '<tr><td><strong>Date of Birth:</strong></td><td>' . htmlspecialchars($citizen['DOB']) . '</td></tr>';
    $html .= '</table><br>';
    
    // Education Records
    $html .= '<table cellpadding="6" border="1" style="font-size:12px; width:100%; border-collapse:collapse;">';
    $html .= '<tr style="background-color:#667eea; color:white;">';
    $html .= '<th><strong>School/Institution</strong></th>';
    $html .= '<th><strong>Grade/Level</strong></th>';
    $html .= '<th><strong>Exam/Qualification</strong></th>';
    $html .= '<th><strong>Result</strong></th>';
    $html .= '<th><strong>Date</strong></th>';
    $html .= '</tr>';
    
    if (empty($education)) {
        $html .= '<tr><td colspan="5" style="text-align:center; color:#666;">No education records found</td></tr>';
    } else {
        foreach ($education as $edu) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($edu['SchoolName'] ?? 'Not specified') . '</td>';
            $html .= '<td>' . htmlspecialchars($edu['GradeLevel'] ?? 'Not specified') . '</td>';
            $html .= '<td>' . htmlspecialchars($edu['ExamName'] ?? 'Not specified') . '</td>';
            $html .= '<td>' . htmlspecialchars($edu['Result'] ?? 'Not specified') . '</td>';
            $html .= '<td>' . htmlspecialchars($edu['RecordDate'] ?? 'Not specified') . '</td>';
            $html .= '</tr>';
        }
    }
    
    $html .= '</table><br>';
    $html .= '<p style="font-size:10px; color:#666; text-align:center;">Generated on ' . date('F j, Y \a\t g:i A') . ' | National Digital Management System</p>';
    
    $pdf->writeHTML($html, true, false, true, false, '');
    return $pdf;
}

/**
 * Generate Medical Report PDF
 */
function generateMedicalReport($citizenID) {
    global $conn;
    
    // Get citizen info
    $stmt = $conn->prepare("SELECT * FROM Citizens WHERE CitizenID = ?");
    $stmt->bind_param('i', $citizenID);
    $stmt->execute();
    $citizen = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$citizen) {
        return false;
    }
    
    // Get medical records
    $medical = getMedicalRecords($citizenID);
    
    // Create PDF
    $pdf = new TCPDF();
    $pdf->SetCreator('NDMS');
    $pdf->SetAuthor('NDMS');
    $pdf->SetTitle('Medical Report');
    $pdf->SetMargins(20, 20, 20);
    $pdf->AddPage();
    
    // Header
    $html = '<h2 style="color:#667eea; text-align:center;">Sri Lanka Medical Report</h2>';
    $html .= '<h3 style="text-align:center; color:#666;">National Digital Management System</h3>';
    $html .= '<hr><br>';
    
    // Citizen Information
    $html .= '<table cellpadding="8" style="font-size:14px; width:100%;">';
    $html .= '<tr><td colspan="2" style="background-color:#f0f0f0;"><strong>CITIZEN INFORMATION</strong></td></tr>';
    $html .= '<tr><td width="30%"><strong>Full Name:</strong></td><td>' . htmlspecialchars($citizen['FirstName'] . ' ' . $citizen['LastName']) . '</td></tr>';
    $html .= '<tr><td><strong>NIC:</strong></td><td>' . htmlspecialchars($citizen['NIC'] ?? 'Not provided') . '</td></tr>';
    $html .= '<tr><td><strong>Date of Birth:</strong></td><td>' . htmlspecialchars($citizen['DOB']) . '</td></tr>';
    $html .= '</table><br>';
    
    // Medical Records
    $html .= '<table cellpadding="6" border="1" style="font-size:11px; width:100%; border-collapse:collapse;">';
    $html .= '<tr style="background-color:#667eea; color:white;">';
    $html .= '<th width="15%"><strong>Date</strong></th>';
    $html .= '<th width="20%"><strong>Hospital</strong></th>';
    $html .= '<th width="25%"><strong>Diagnosis</strong></th>';
    $html .= '<th width="25%"><strong>Treatment</strong></th>';
    $html .= '<th width="15%"><strong>Doctor</strong></th>';
    $html .= '</tr>';
    
    if (empty($medical)) {
        $html .= '<tr><td colspan="5" style="text-align:center; color:#666;">No medical records found</td></tr>';
    } else {
        foreach ($medical as $med) {
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($med['recordDate'] ?? 'Not specified') . '</td>';
            $html .= '<td>' . htmlspecialchars($med['hospitalName'] ?? 'Not specified') . '</td>';
            $html .= '<td>' . htmlspecialchars($med['diagnosis'] ?? 'Not specified') . '</td>';
            $html .= '<td>' . htmlspecialchars($med['treatment'] ?? 'Not specified') . '</td>';
            $html .= '<td>' . htmlspecialchars($med['doctorName'] ?? 'Not specified') . '</td>';
            $html .= '</tr>';
        }
    }
    
    $html .= '</table><br>';
    $html .= '<p style="font-size:10px; color:#666; text-align:center;">Generated on ' . date('F j, Y \a\t g:i A') . ' | National Digital Management System</p>';
    
    $pdf->writeHTML($html, true, false, true, false, '');
    return $pdf;
}

/**
 * Generate Employment Report PDF
 */
function generateEmploymentReport($citizenID) {
    global $conn;
    
    // Get citizen info
    $stmt = $conn->prepare("SELECT * FROM Citizens WHERE CitizenID = ?");
    $stmt->bind_param('i', $citizenID);
    $stmt->execute();
    $citizen = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$citizen) {
        return false;
    }
    
    // Get employment records
    $employment = getEmploymentRecords($citizenID);
    
    // Create PDF
    $pdf = new TCPDF();
    $pdf->SetCreator('NDMS');
    $pdf->SetAuthor('NDMS');
    $pdf->SetTitle('Employment Report');
    $pdf->SetMargins(20, 20, 20);
    $pdf->AddPage();
    
    // Header
    $html = '<h2 style="color:#667eea; text-align:center;">Sri Lanka Employment Report</h2>';
    $html .= '<h3 style="text-align:center; color:#666;">National Digital Management System</h3>';
    $html .= '<hr><br>';
    
    // Citizen Information
    $html .= '<table cellpadding="8" style="font-size:14px; width:100%;">';
    $html .= '<tr><td colspan="2" style="background-color:#f0f0f0;"><strong>CITIZEN INFORMATION</strong></td></tr>';
    $html .= '<tr><td width="30%"><strong>Full Name:</strong></td><td>' . htmlspecialchars($citizen['FirstName'] . ' ' . $citizen['LastName']) . '</td></tr>';
    $html .= '<tr><td><strong>NIC:</strong></td><td>' . htmlspecialchars($citizen['NIC'] ?? 'Not provided') . '</td></tr>';
    $html .= '<tr><td><strong>Date of Birth:</strong></td><td>' . htmlspecialchars($citizen['DOB']) . '</td></tr>';
    $html .= '</table><br>';
    
    // Employment Records
    $html .= '<table cellpadding="6" border="1" style="font-size:11px; width:100%; border-collapse:collapse;">';
    $html .= '<tr style="background-color:#667eea; color:white;">';
    $html .= '<th width="20%"><strong>Company</strong></th>';
    $html .= '<th width="20%"><strong>Job Title</strong></th>';
    $html .= '<th width="15%"><strong>Start Date</strong></th>';
    $html .= '<th width="15%"><strong>End Date</strong></th>';
    $html .= '<th width="15%"><strong>Salary</strong></th>';
    $html .= '<th width="15%"><strong>Status</strong></th>';
    $html .= '</tr>';
    
    if (empty($employment)) {
        $html .= '<tr><td colspan="6" style="text-align:center; color:#666;">No employment records found</td></tr>';
    } else {
        foreach ($employment as $emp) {
            $status = $emp['EndDate'] ? 'Completed' : 'Current';
            $verified = $emp['Verified'] ? '✓ Verified' : '⚠ Unverified';
            
            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($emp['CompanyName'] ?? 'Not specified') . '</td>';
            $html .= '<td>' . htmlspecialchars($emp['JobTitle'] ?? 'Not specified') . '</td>';
            $html .= '<td>' . htmlspecialchars($emp['StartDate'] ?? 'Not specified') . '</td>';
            $html .= '<td>' . htmlspecialchars($emp['EndDate'] ?? 'Current') . '</td>';
            $html .= '<td>' . htmlspecialchars($emp['Salary'] ? '$' . number_format($emp['Salary'], 2) : 'Not disclosed') . '</td>';
            $html .= '<td>' . $status . '<br><small>' . $verified . '</small></td>';
            $html .= '</tr>';
        }
    }
    
    $html .= '</table><br>';
    $html .= '<p style="font-size:10px; color:#666; text-align:center;">Generated on ' . date('F j, Y \a\t g:i A') . ' | National Digital Management System</p>';
    
    $pdf->writeHTML($html, true, false, true, false, '');
    return $pdf;
}
?>
