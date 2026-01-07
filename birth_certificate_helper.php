<?php
// birth_certificate_helper.php
// Helper functions for birth certificate generation and verification

require_once 'config.php';
require_once 'server_config.php'; // Include server configuration helper
require_once 'includes/phpqrcode/phpqrcode.php'; // Proper QR code library

// --- CONFIG ---
define('CERT_SECRET_KEY', 'ndms_birth_cert_secret_key_2025_very_secure_and_long_random_string_for_verification');
define('QR_DIR', __DIR__ . '/uploads/qrcodes/');

// Ensure QR directory exists and is writable
if (!is_dir(QR_DIR)) {
    mkdir(QR_DIR, 0755, true);
}

/**
 * Generate and issue a birth certificate with QR code
 */
function generateBirthCertificate($citizenID, $childFullName, $dateOfBirth, $timeOfBirth = null, $sex = 'Male', 
                                  $placeOfBirth = null, $hospitalName = null, $fatherName = null, $motherName = null,
                                  $fatherNIC = null, $motherNIC = null, $motherMaidenName = null, $parentsAddress = null,
                                  $nationality = 'Sri Lankan', $birthWeightGrams = null, $birthLengthCm = null, 
                                  $deliveryType = null, $registrarOffice = null, $registeredBy = null) {
    global $conn;
    
    try {
        // Begin transaction
        $conn->begin_transaction();

        // Insert initial birth certificate record
        $insertSql = "INSERT INTO BirthCertificates 
            (CitizenID, ChildFullName, DateOfBirth, TimeOfBirth, Sex, PlaceOfBirth, HospitalName, 
             FatherName, MotherName, FatherNIC, MotherNIC, MotherMaidenName, ParentsAddress, 
             Nationality, BirthWeightGrams, BirthLengthCm, DeliveryType, RegistrarOffice, 
             RegisteredBy, RegisteredAt, Status, IssueDate, CreatedBy)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'Active', CURDATE(), ?)";
        
        $stmt = $conn->prepare($insertSql);
        $stmt->bind_param("isssssssssssssidssii", 
            $citizenID, $childFullName, $dateOfBirth, $timeOfBirth, $sex, $placeOfBirth, $hospitalName,
            $fatherName, $motherName, $fatherNIC, $motherNIC, $motherMaidenName, $parentsAddress,
            $nationality, $birthWeightGrams, $birthLengthCm, $deliveryType, $registrarOffice,
            $registeredBy, $registeredBy
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to insert birth certificate record");
        }

        // Get the newly inserted BirthCertID
        $birthCertID = $conn->insert_id;
        $stmt->close();

        // Generate CertificateNumber (format: BC-LK-YYYY-XXXXXX)
        $year = (int)date('Y');
        $certificateNumber = sprintf('BC-LK-%04d-%06d', $year, $birthCertID);

        // Compute VerificationHash (HMAC-SHA256)
        $verificationHash = hash_hmac('sha256', $certificateNumber . '|' . $citizenID, CERT_SECRET_KEY);

        // Generate verification URL using dynamic server configuration
        $serverUrl = getServerUrl();
        $verifyUrl = $serverUrl . '/verify_certificate.php?cert=' . urlencode($certificateNumber) . '&hash=' . urlencode($verificationHash);

        // Generate QR PNG file
        $safeFile = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $certificateNumber) . '.png';
        $qrFilePath = QR_DIR . $safeFile;

        // Ensure directory exists
        if (!is_dir(QR_DIR)) {
            if (!mkdir(QR_DIR, 0755, true)) {
                throw new Exception("Failed to create QR code directory");
            }
        }

        // Generate QR code using proper library
        try {
            QRcode::png($verifyUrl, $qrFilePath, QR_ECLEVEL_M, 8, 2);
        } catch (Exception $e) {
            error_log("QR Code generation failed: " . $e->getMessage());
            // Create a fallback QR code or continue without QR
            $qrFilePath = null;
        }

        // Update the DB record with generated values
        $updateSql = "UPDATE BirthCertificates
                      SET CertificateNumber = ?, VerificationHash = ?, QRCodePath = ?
                      WHERE BirthCertID = ?";
        $stmt = $conn->prepare($updateSql);
        $qrWebPath = $qrFilePath ? ('uploads/qrcodes/' . $safeFile) : null;
        $stmt->bind_param("sssi", $certificateNumber, $verificationHash, $qrWebPath, $birthCertID);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update certificate with generated values");
        }
        $stmt->close();

        // Get registrar name for display
        $registrarName = '';
        if ($registeredBy) {
            $userStmt = $conn->prepare("SELECT Username FROM Users WHERE UserID = ?");
            $userStmt->bind_param("i", $registeredBy);
            $userStmt->execute();
            $userResult = $userStmt->get_result();
            if ($userRow = $userResult->fetch_assoc()) {
                $registrarName = $userRow['Username'];
            }
            $userStmt->close();
            
            // Update registrar name in certificate
            $regNameStmt = $conn->prepare("UPDATE BirthCertificates SET RegistrarName = ? WHERE BirthCertID = ?");
            $regNameStmt->bind_param("si", $registrarName, $birthCertID);
            $regNameStmt->execute();
            $regNameStmt->close();
        }

        // Commit transaction
        $conn->commit();

        return [
            'success' => true,
            'message' => 'Birth certificate generated successfully',
            'BirthCertID' => $birthCertID,
            'CertificateNumber' => $certificateNumber,
            'VerificationHash' => $verificationHash,
            'QRCodePath' => $qrWebPath,
            'verifyUrl' => $verifyUrl
        ];

    } catch (Exception $ex) {
        // Rollback transaction and cleanup
        $conn->rollback();
        
        // Remove QR file if created
        if (!empty($qrFilePath) && file_exists($qrFilePath)) {
            @unlink($qrFilePath);
        }
        
        return [
            'success' => false,
            'message' => 'Error generating certificate: ' . $ex->getMessage()
        ];
    }
}

/**
 * Verify a birth certificate using certificate number and hash
 */
function verifyCertificate($certificateNumber, $providedHash) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("
            SELECT bc.*, c.FirstName, c.LastName, c.NIC, c.Citizen_eID 
            FROM BirthCertificates bc 
            JOIN Citizens c ON bc.CitizenID = c.CitizenID 
            WHERE bc.CertificateNumber = ? AND bc.Status = 'Active'
        ");
        $stmt->bind_param("s", $certificateNumber);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return ['valid' => false, 'message' => 'Certificate not found or inactive'];
        }
        
        $certificate = $result->fetch_assoc();
        $stmt->close();
        
        // Verify hash
        $expectedHash = hash_hmac('sha256', $certificateNumber . '|' . $certificate['CitizenID'], CERT_SECRET_KEY);
        
        if (!hash_equals($expectedHash, $providedHash)) {
            return ['valid' => false, 'message' => 'Invalid verification hash'];
        }
        
        // Return public-safe information only
        return [
            'valid' => true,
            'message' => 'Certificate is valid',
            'data' => [
                'CertificateNumber' => $certificate['CertificateNumber'],
                'ChildFullName' => $certificate['ChildFullName'],
                'DateOfBirth' => $certificate['DateOfBirth'],
                'TimeOfBirth' => $certificate['TimeOfBirth'],
                'Sex' => $certificate['Sex'],
                'PlaceOfBirth' => $certificate['PlaceOfBirth'],
                'HospitalName' => $certificate['HospitalName'],
                'Nationality' => $certificate['Nationality'],
                'IssueDate' => $certificate['IssueDate'],
                'RegisteredAt' => $certificate['RegisteredAt'],
                'Status' => $certificate['Status'],
                // Additional comprehensive fields
                'FatherName' => $certificate['FatherName'],
                'MotherName' => $certificate['MotherName'],
                'FatherNIC' => $certificate['FatherNIC'],
                'MotherNIC' => $certificate['MotherNIC'],
                'MotherMaidenName' => $certificate['MotherMaidenName'],
                'ParentsAddress' => $certificate['ParentsAddress'],
                'BirthWeightGrams' => $certificate['BirthWeightGrams'],
                'BirthLengthCm' => $certificate['BirthLengthCm'],
                'DeliveryType' => $certificate['DeliveryType'],
                'RegistrarOffice' => $certificate['RegistrarOffice'],
                'RegisteredBy' => $certificate['RegisteredBy']
            ]
        ];
        
    } catch (Exception $ex) {
        return ['valid' => false, 'message' => 'Verification error: ' . $ex->getMessage()];
    }
}

/**
 * Get birth certificate by ID with full details (admin/medical officer access)
 */
function getBirthCertificate($birthCertID) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT bc.*, c.FirstName, c.LastName, c.NIC, c.Citizen_eID,
               u1.Username as RegisteredByName, u2.Username as CreatedByName
        FROM BirthCertificates bc 
        JOIN Citizens c ON bc.CitizenID = c.CitizenID 
        LEFT JOIN Users u1 ON bc.RegisteredBy = u1.UserID
        LEFT JOIN Users u2 ON bc.CreatedBy = u2.UserID
        WHERE bc.BirthCertID = ?
    ");
    $stmt->bind_param("i", $birthCertID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return null;
    }
    
    $certificate = $result->fetch_assoc();
    $stmt->close();
    
    return $certificate;
}

/**
 * Get all birth certificates for a citizen
 */
function getCitizenBirthCertificates($citizenID) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT bc.*, u.Username as RegisteredByName
        FROM BirthCertificates bc 
        LEFT JOIN Users u ON bc.RegisteredBy = u.UserID
        WHERE bc.CitizenID = ?
        ORDER BY bc.RegisteredAt DESC
    ");
    $stmt->bind_param("i", $citizenID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $certificates = [];
    while ($row = $result->fetch_assoc()) {
        $certificates[] = $row;
    }
    $stmt->close();
    
    return $certificates;
}

/**
 * Get all birth certificates (admin view)
 */
function getAllBirthCertificates($limit = 50, $offset = 0, $search = '', $status = '') {
    global $conn;
    
    $sql = "
        SELECT bc.*, c.FirstName, c.LastName, c.NIC, c.Citizen_eID,
               u.Username as RegisteredByName
        FROM BirthCertificates bc 
        JOIN Citizens c ON bc.CitizenID = c.CitizenID 
        LEFT JOIN Users u ON bc.RegisteredBy = u.UserID
    ";
    
    $conditions = [];
    $params = [];
    $types = "";
    
    if ($search) {
        $conditions[] = "(bc.CertificateNumber LIKE ? OR bc.ChildFullName LIKE ? OR 
                         bc.FatherName LIKE ? OR bc.MotherName LIKE ? OR 
                         c.FirstName LIKE ? OR c.LastName LIKE ?)";
        $searchParam = "%$search%";
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
        $types .= "ssssss";
    }
    
    if ($status) {
        $conditions[] = "bc.Status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(' AND ', $conditions);
    }
    
    $sql .= " ORDER BY bc.RegisteredAt DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $certificates = [];
    while ($row = $result->fetch_assoc()) {
        $certificates[] = $row;
    }
    $stmt->close();
    
    return $certificates;
}

/**
 * Update birth certificate status (for amendments/revocation)
 */
function updateCertificateStatus($birthCertID, $status, $amendmentNote = null, $updatedBy = null) {
    global $conn;
    
    $sql = "UPDATE BirthCertificates SET Status = ?, AmendmentNote = ?, UpdatedBy = ? WHERE BirthCertID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssii", $status, $amendmentNote, $updatedBy, $birthCertID);
    
    $result = $stmt->execute();
    $stmt->close();
    
    return $result;
}

/**
 * Get birth certificate statistics
 */
function getBirthCertificateStats() {
    global $conn;
    
    $stats = [];
    
    // Total certificates
    $result = $conn->query("SELECT COUNT(*) as total FROM BirthCertificates");
    $stats['total'] = $result->fetch_assoc()['total'];
    
    // Certificates by status
    $result = $conn->query("SELECT Status, COUNT(*) as count FROM BirthCertificates GROUP BY Status");
    $stats['by_status'] = [];
    while ($row = $result->fetch_assoc()) {
        $stats['by_status'][$row['Status']] = $row['count'];
    }
    
    // Certificates by year
    $result = $conn->query("SELECT YEAR(RegisteredAt) as year, COUNT(*) as count FROM BirthCertificates GROUP BY YEAR(RegisteredAt) ORDER BY year DESC LIMIT 5");
    $stats['by_year'] = [];
    while ($row = $result->fetch_assoc()) {
        $stats['by_year'][$row['year']] = $row['count'];
    }
    
    // Recent certificates (last 30 days)
    $result = $conn->query("SELECT COUNT(*) as recent FROM BirthCertificates WHERE RegisteredAt >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stats['recent_30_days'] = $result->fetch_assoc()['recent'];
    
    return $stats;
}

/**
 * Check if user can access birth certificate functions
 */
function canManageBirthCertificates($userRole) {
    return in_array($userRole, ['Admin', 'MedicalOfficer']);
}

/**
 * Validate birth certificate data before creation
 */
function validateBirthCertificateData($data) {
    $errors = [];
    
    // Required fields
    if (empty($data['citizenID'])) {
        $errors[] = 'Citizen ID is required';
    }
    
    if (empty($data['childFullName'])) {
        $errors[] = 'Child full name is required';
    }
    
    if (empty($data['dateOfBirth'])) {
        $errors[] = 'Date of birth is required';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['dateOfBirth'])) {
        $errors[] = 'Invalid date of birth format (YYYY-MM-DD required)';
    } elseif (strtotime($data['dateOfBirth']) > time()) {
        $errors[] = 'Date of birth cannot be in the future';
    }
    
    if (empty($data['sex']) || !in_array($data['sex'], ['Male', 'Female', 'Other'])) {
        $errors[] = 'Valid sex is required (Male, Female, or Other)';
    }
    
    // Optional field validations
    if (!empty($data['timeOfBirth']) && !preg_match('/^\d{2}:\d{2}$/', $data['timeOfBirth'])) {
        $errors[] = 'Invalid time of birth format (HH:MM required)';
    }
    
    if (!empty($data['birthWeightGrams']) && (!is_numeric($data['birthWeightGrams']) || $data['birthWeightGrams'] <= 0)) {
        $errors[] = 'Birth weight must be a positive number';
    }
    
    if (!empty($data['birthLengthCm']) && (!is_numeric($data['birthLengthCm']) || $data['birthLengthCm'] <= 0)) {
        $errors[] = 'Birth length must be a positive number';
    }
    
    return $errors;
}

/**
 * Get total count of birth certificates with optional search and status filter
 */
function getTotalBirthCertificatesCount($search = '', $status = '') {
    global $conn;
    
    $sql = "SELECT COUNT(*) as total FROM BirthCertificates bc 
            LEFT JOIN Citizens c ON bc.CitizenID = c.CitizenID";
    
    $conditions = [];
    $params = [];
    $types = '';
    
    if (!empty($search)) {
        $conditions[] = "(bc.CertificateNumber LIKE ? OR bc.ChildFullName LIKE ? OR 
                         bc.FatherName LIKE ? OR bc.MotherName LIKE ? OR 
                         c.FirstName LIKE ? OR c.LastName LIKE ?)";
        $searchParam = '%' . $search . '%';
        $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
        $types .= 'ssssss';
    }
    
    if (!empty($status)) {
        $conditions[] = "bc.Status = ?";
        $params[] = $status;
        $types .= 's';
    }
    
    if (!empty($conditions)) {
        $sql .= " WHERE " . implode(' AND ', $conditions);
    }
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return (int)$row['total'];
}
?>
