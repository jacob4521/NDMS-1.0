<?php
require_once 'config.php';
require_once 'birth_certificate_helper.php';

// Role check - Only Medical Officers and Admins can register birth certificates
if (!isset($_SESSION['UserID']) || !canManageBirthCertificates($_SESSION['Role'])) {
    header("Location: login.php");
    exit();
}

$message = '';
$messageType = '';
$generatedCertificate = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input data
    $data = [
        'citizenID' => $_POST['citizenID'] ?? '',
        'childFullName' => trim($_POST['childFullName'] ?? ''),
        'dateOfBirth' => $_POST['dateOfBirth'] ?? '',
        'timeOfBirth' => $_POST['timeOfBirth'] ?? '',
        'sex' => $_POST['sex'] ?? '',
        'placeOfBirth' => trim($_POST['placeOfBirth'] ?? ''),
        'hospitalName' => trim($_POST['hospitalName'] ?? ''),
        'fatherName' => trim($_POST['fatherName'] ?? ''),
        'motherName' => trim($_POST['motherName'] ?? ''),
        'fatherNIC' => trim($_POST['fatherNIC'] ?? ''),
        'motherNIC' => trim($_POST['motherNIC'] ?? ''),
        'motherMaidenName' => trim($_POST['motherMaidenName'] ?? ''),
        'parentsAddress' => trim($_POST['parentsAddress'] ?? ''),
        'nationality' => $_POST['nationality'] ?? 'Sri Lankan',
        'birthWeightGrams' => $_POST['birthWeightGrams'] ? (int)$_POST['birthWeightGrams'] : null,
        'birthLengthCm' => $_POST['birthLengthCm'] ? (float)$_POST['birthLengthCm'] : null,
        'deliveryType' => $_POST['deliveryType'] ?? '',
        'registrarOffice' => trim($_POST['registrarOffice'] ?? '')
    ];
    
    // Validate the data
    $validationErrors = validateBirthCertificateData($data);
    
    if (empty($validationErrors)) {
        // Generate the birth certificate
        $result = generateBirthCertificate(
            $data['citizenID'],
            $data['childFullName'],
            $data['dateOfBirth'],
            $data['timeOfBirth'] ?: null,
            $data['sex'],
            $data['placeOfBirth'] ?: null,
            $data['hospitalName'] ?: null,
            $data['fatherName'] ?: null,
            $data['motherName'] ?: null,
            $data['fatherNIC'] ?: null,
            $data['motherNIC'] ?: null,
            $data['motherMaidenName'] ?: null,
            $data['parentsAddress'] ?: null,
            $data['nationality'],
            $data['birthWeightGrams'],
            $data['birthLengthCm'],
            $data['deliveryType'] ?: null,
            $data['registrarOffice'] ?: null,
            $_SESSION['UserID']
        );
        
        if ($result['success']) {
            $generatedCertificate = $result;
            $message = 'Birth certificate generated successfully!';
            $messageType = 'success';
        } else {
            $message = $result['message'];
            $messageType = 'danger';
        }
    } else {
        $message = 'Please correct the following errors: ' . implode(', ', $validationErrors);
        $messageType = 'danger';
    }
}

// Fetch citizens (newborns - last 2 years)
$citizensQuery = "SELECT CitizenID, FirstName, LastName, DOB, Citizen_eID FROM Citizens 
                  WHERE TIMESTAMPDIFF(YEAR, DOB, CURDATE()) <= 2 
                  ORDER BY DOB DESC";
$citizensResult = $conn->query($citizensQuery);

// Fetch recent birth certificates
$recentCertificates = getAllBirthCertificates(10, 0);

// Get statistics
$stats = getBirthCertificateStats();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Birth Certificate Registration - NDMS</title>
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
        }
        
        .main-header {
            background: var(--gradient-bg);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-xl);
        }
        
        .main-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 300px;
            height: 300px;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="2"/><circle cx="50" cy="50" r="30" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/><circle cx="50" cy="50" r="20" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></svg>') no-repeat center;
            opacity: 0.3;
        }
        
        .main-header h1 {
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 10px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
            z-index: 1;
        }
        
        .main-header p {
            font-size: 18px;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stats-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-bg);
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-xl);
        }
        
        .stats-number {
            font-size: 42px;
            font-weight: 800;
            color: var(--primary-color);
            margin-bottom: 8px;
            background: var(--gradient-bg);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .stats-label {
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .alert {
            padding: 20px 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            border: none;
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
        }
        
        .alert-success {
            background: var(--accent-color);
            color: white;
        }
        
        .alert-danger {
            background: var(--danger-color);
            color: white;
        }
        
        .alert::before {
            content: '✅';
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 24px;
            opacity: 0.7;
        }
        
        .alert-danger::before {
            content: '❌';
        }
        
        .certificate-result {
            background: var(--accent-color);
            color: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-xl);
            position: relative;
            overflow: hidden;
        }
        
        .certificate-result::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 200px;
            height: 200px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }
        
        .form-card {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 35px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            margin-bottom: 30px;
            position: relative;
        }
        
        .form-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-bg);
            border-radius: 20px 20px 0 0;
        }
        
        .form-card h4 {
            color: var(--primary-color);
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .form-section {
            border-left: 4px solid var(--secondary-color);
            padding-left: 20px;
            margin-bottom: 30px;
            position: relative;
        }
        
        .form-section h6 {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 20px;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 700;
            color: var(--text-primary);
            font-size: 14px;
        }
        
        .form-control, .form-select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s ease;
            background: white;
            font-family: inherit;
            margin-bottom: 15px;
        }
        
        .form-control:focus, .form-select:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .row {
            display: grid;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .row.mt-3 {
            margin-top: 20px;
        }
        
        .col-md-4 {
            grid-column: span 1;
        }
        
        .col-md-6 {
            grid-column: span 1;
        }
        
        .col-md-8 {
            grid-column: span 2;
        }
        
        .row {
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        }
        
        .btn-generate {
            background: var(--gradient-bg);
            border: none;
            border-radius: 12px;
            padding: 16px 40px;
            color: white;
            font-weight: 700;
            font-size: 16px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: var(--shadow-md);
        }
        
        .btn-generate:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-xl);
            filter: brightness(1.1);
            color: white;
        }
        
        .btn-outline-light {
            border: 2px solid rgba(255,255,255,0.3);
            color: white;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-outline-light:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            transform: translateY(-2px);
        }
        
        .recent-certificates {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            position: relative;
        }
        
        .recent-certificates::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-bg);
            border-radius: 20px 20px 0 0;
        }
        
        .recent-certificates h5 {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .certificate-item {
            border-left: 4px solid var(--accent-color);
            padding: 20px;
            margin-bottom: 20px;
            background: var(--light-bg);
            border-radius: 0 15px 15px 0;
            transition: all 0.3s ease;
        }
        
        .certificate-item:hover {
            background: #f0f9ff;
            transform: translateX(5px);
        }
        
        .badge {
            background: var(--accent-color) !important;
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .qr-preview {
            max-width: 150px;
            border-radius: 15px;
            border: 3px solid white;
            box-shadow: var(--shadow-md);
        }
        
        .btn-close {
            background: none;
            border: none;
            color: white;
            font-size: 20px;
            opacity: 0.8;
        }
        
        .btn-outline-primary {
            border: 2px solid var(--secondary-color);
            color: var(--secondary-color);
            background: transparent;
            padding: 8px 16px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .btn-outline-primary:hover {
            background: var(--secondary-color);
            color: white;
            transform: translateY(-2px);
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 0 15px;
            }
            
            .main-header h1 {
                font-size: 28px;
            }
            
            .main-header p {
                font-size: 16px;
            }
            
            .stats-row {
                grid-template-columns: 1fr;
            }
            
            .row {
                grid-template-columns: 1fr;
            }
            
            .form-card {
                padding: 25px;
            }
        }
        
        /* Loading Animation */
        .form-card, .stats-card, .recent-certificates {
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
        <div class="main-header">
            <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1>🏥 Birth Certificate Registration</h1>
                    <p class="mb-0">National Digital Management System - Official Medical Documentation Portal</p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="dashboard.php" class="btn-outline-light">
                        🏠 Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Statistics Cards -->
        <div class="stats-row">
            <div class="stats-card">
                <div class="stats-number"><?php echo $stats['total']; ?></div>
                <div class="stats-label">Total Certificates</div>
            </div>
            <div class="stats-card">
                <div class="stats-number"><?php echo $stats['by_status']['Active'] ?? 0; ?></div>
                <div class="stats-label">Active Certificates</div>
            </div>
            <div class="stats-card">
                <div class="stats-number"><?php echo $stats['recent_30_days']; ?></div>
                <div class="stats-label">This Month</div>
            </div>
            <div class="stats-card">
                <div class="stats-number"><?php echo date('Y'); ?></div>
                <div class="stats-label">Current Year</div>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Generated Certificate Result -->
        <?php if ($generatedCertificate): ?>
            <div class="certificate-result">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h4><i class="fas fa-check-circle"></i> Certificate Generated Successfully!</h4>
                        <p class="mb-2"><strong>Certificate Number:</strong> <?php echo htmlspecialchars($generatedCertificate['CertificateNumber']); ?></p>
                        <p class="mb-2"><strong>Birth Certificate ID:</strong> <?php echo $generatedCertificate['BirthCertID']; ?></p>
                        <div class="mt-3">
                            <a href="<?php echo htmlspecialchars($generatedCertificate['verifyUrl']); ?>" 
                               target="_blank" class="btn btn-outline-light">
                                <i class="fas fa-external-link-alt"></i> Verify Certificate
                            </a>
                            <a href="view_citizen.php?id=<?php echo $_POST['citizenID'] ?? ''; ?>" 
                               class="btn btn-outline-light">
                                <i class="fas fa-user"></i> View Citizen Profile
                            </a>
                        </div>
                    </div>
                    <div class="col-md-4 text-center">
                        <?php if ($generatedCertificate['QRCodePath']): ?>
                            <img src="<?php echo htmlspecialchars($generatedCertificate['QRCodePath']); ?>" 
                                 alt="QR Code" class="qr-preview">
                            <p class="mt-2 mb-0"><small>QR Code for Verification</small></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Registration Form -->
            <div class="col-md-8">
                <div class="form-card">
                    <h4 class="mb-4">➕ Register New Birth Certificate</h4>
                    
                    <form method="POST">
                        <!-- Child Information -->
                        <div class="form-section">
                            <h6>👶 Child Information</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">Select Citizen *</label>
                                    <select name="citizenID" class="form-select" required>
                                        <option value="">Choose citizen...</option>
                                        <?php while ($citizen = $citizensResult->fetch_assoc()): ?>
                                            <option value="<?php echo $citizen['CitizenID']; ?>">
                                                <?php echo htmlspecialchars($citizen['FirstName'] . ' ' . $citizen['LastName']); ?>
                                                <?php if (!empty($citizen['Citizen_eID'])): ?>
                                                    - eID: <?php echo htmlspecialchars($citizen['Citizen_eID']); ?>
                                                <?php endif; ?>
                                                (DOB: <?php echo $citizen['DOB']; ?>)
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Child Full Name *</label>
                                    <input type="text" name="childFullName" class="form-control" 
                                           placeholder="Enter full name" required>
                                </div>
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-md-4">
                                    <label class="form-label">Date of Birth *</label>
                                    <input type="date" name="dateOfBirth" class="form-control" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Time of Birth</label>
                                    <input type="time" name="timeOfBirth" class="form-control">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Sex *</label>
                                    <select name="sex" class="form-select" required>
                                        <option value="">Select...</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Birth Details -->
                        <div class="form-section">
                            <h6>🏥 Birth Details</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">Place of Birth</label>
                                    <input type="text" name="placeOfBirth" class="form-control" 
                                           placeholder="City, District">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Hospital/Institution</label>
                                    <input type="text" name="hospitalName" class="form-control" 
                                           placeholder="Hospital name or 'Home'">
                                </div>
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-md-4">
                                    <label class="form-label">Birth Weight (grams)</label>
                                    <input type="number" name="birthWeightGrams" class="form-control" 
                                           placeholder="e.g., 3500">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Birth Length (cm)</label>
                                    <input type="number" step="0.1" name="birthLengthCm" class="form-control" 
                                           placeholder="e.g., 50.5">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Delivery Type</label>
                                    <select name="deliveryType" class="form-select">
                                        <option value="">Select...</option>
                                        <option value="Normal">Normal</option>
                                        <option value="C-Section">C-Section</option>
                                        <option value="Instrumental">Instrumental</option>
                                        <option value="Home">Home Birth</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Parents Information -->
                        <div class="form-section">
                            <h6>👨‍👩‍👧‍👦 Parents Information</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">Father's Full Name</label>
                                    <input type="text" name="fatherName" class="form-control" 
                                           placeholder="Father's full name">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Father's NIC</label>
                                    <input type="text" name="fatherNIC" class="form-control" 
                                           placeholder="NIC number">
                                </div>
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">Mother's Full Name</label>
                                    <input type="text" name="motherName" class="form-control" 
                                           placeholder="Mother's full name">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Mother's NIC</label>
                                    <input type="text" name="motherNIC" class="form-control" 
                                           placeholder="NIC number">
                                </div>
                            </div>
                            
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    <label class="form-label">Mother's Maiden Name</label>
                                    <input type="text" name="motherMaidenName" class="form-control" 
                                           placeholder="Maiden name">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Parents' Address</label>
                                    <textarea name="parentsAddress" class="form-control" rows="2" 
                                              placeholder="Full address"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Registration Details -->
                        <div class="form-section">
                            <h6>📋 Registration Details</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">Nationality</label>
                                    <input type="text" name="nationality" class="form-control" 
                                           value="Sri Lankan" placeholder="Nationality">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Registrar Office</label>
                                    <input type="text" name="registrarOffice" class="form-control" 
                                           placeholder="Registration office">
                                </div>
                            </div>
                        </div>

                        <div class="text-center">
                            <button type="submit" class="btn-generate btn-lg">
                                🏥 Generate Birth Certificate
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Recent Certificates Sidebar -->
            <div class="col-md-4">
                <div class="recent-certificates">
                    <h5 class="mb-4">📚 Recent Certificates</h5>
                    
                    <?php if (empty($recentCertificates)): ?>
                        <p class="text-muted text-center">No certificates registered yet</p>
                    <?php else: ?>
                        <?php foreach ($recentCertificates as $cert): ?>
                            <div class="certificate-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <strong><?php echo htmlspecialchars($cert['ChildFullName']); ?></strong>
                                        <br>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($cert['CertificateNumber']); ?>
                                        </small>
                                        <br>
                                        <small class="text-muted">
                                            <?php echo date('M j, Y', strtotime($cert['RegisteredAt'])); ?>
                                        </small>
                                    </div>
                                    <span class="badge bg-success"><?php echo $cert['Status']; ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="text-center mt-3">
                            <a href="view_certificates.php" class="btn-outline-primary btn-sm">
                                📋 View All Certificates
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-fill child name when citizen is selected
        document.querySelector('select[name="citizenID"]').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                const citizenName = selectedOption.text.split('(')[0].trim();
                document.querySelector('input[name="childFullName"]').value = citizenName;
            }
        });
        
        // Enhanced form validation with modern NDMS feedback
        document.querySelector('form').addEventListener('submit', function(e) {
            const requiredFields = ['citizenID', 'childFullName', 'dateOfBirth', 'sex'];
            let isValid = true;
            
            requiredFields.forEach(field => {
                const input = document.querySelector(`[name="${field}"]`);
                if (!input.value.trim()) {
                    input.style.borderColor = '#ef4444';
                    input.style.backgroundColor = '#fef2f2';
                    input.style.animation = 'shake 0.5s ease-in-out';
                    isValid = false;
                } else {
                    input.style.borderColor = '#10b981';
                    input.style.backgroundColor = '#f0fdf4';
                    setTimeout(() => {
                        input.style.borderColor = '#e5e7eb';
                        input.style.backgroundColor = 'white';
                    }, 1000);
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert('🚨 Please fill in all required fields to proceed.');
            }
        });
        
        // Add smooth transitions for form elements
        document.querySelectorAll('input, select, textarea').forEach(element => {
            element.addEventListener('focus', function() {
                this.style.transform = 'scale(1.02)';
                this.style.boxShadow = '0 0 0 3px rgba(59, 130, 246, 0.1)';
                this.style.borderColor = '#3b82f6';
            });
            
            element.addEventListener('blur', function() {
                this.style.transform = 'scale(1)';
                this.style.boxShadow = 'none';
                if (this.value) {
                    this.style.borderColor = '#10b981';
                } else {
                    this.style.borderColor = '#e5e7eb';
                }
            });
        });
        
        // Auto-hide success messages
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert-success');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s, transform 0.5s';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);

        // Add alert close functionality
        document.querySelectorAll('.btn-close').forEach(button => {
            button.addEventListener('click', function() {
                const alert = this.closest('.alert');
                alert.style.transition = 'opacity 0.3s, transform 0.3s';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => alert.remove(), 300);
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
    </div> <!-- End main-content -->
    
    <!-- Include Sidebar JavaScript -->
    <script src="includes/sidebar.js"></script>
</body>
</html>
