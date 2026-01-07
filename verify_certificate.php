<?php
require_once 'config.php';
require_once 'birth_certificate_helper.php';

// Get verification parameters
$certificateNumber = $_GET['cert'] ?? '';
$providedHash = $_GET['hash'] ?? '';

$verificationResult = null;
$showForm = true;

// Handle form submission for manual verification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $certificateNumber = trim($_POST['certificate_number'] ?? '');
  $providedHash = trim($_POST['verification_hash'] ?? '');
}

// Perform verification if parameters are provided
if ($certificateNumber && $providedHash) {
  $verificationResult = verifyCertificate($certificateNumber, $providedHash);
  $showForm = false;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Birth Certificate Verification - NDMS</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .verification-container {
      max-width: 600px;
      margin: 50px auto;
      padding: 20px;
    }

    .verification-card {
      background: white;
      border-radius: 20px;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
      overflow: hidden;
    }

    .card-header {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 30px;
      text-align: center;
      border: none;
    }

    .card-body {
      padding: 40px;
    }

    .verification-form {
      margin-top: 20px;
    }

    .form-control {
      border-radius: 10px;
      border: 2px solid #e9ecef;
      padding: 12px 16px;
      margin-bottom: 20px;
    }

    .form-control:focus {
      border-color: #667eea;
      box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }

    .btn-verify {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border: none;
      border-radius: 10px;
      padding: 12px 30px;
      color: white;
      font-weight: 600;
      width: 100%;
    }

    .btn-verify:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
    }

    .verification-result {
      margin-top: 30px;
      padding: 25px;
      border-radius: 15px;
      animation: fadeIn 0.5s ease-in-out;
    }

    .result-valid {
      background: linear-gradient(135deg, #4CAF50 0%, #45a049 100%);
      color: white;
    }

    .result-invalid {
      background: linear-gradient(135deg, #f44336 0%, #d32f2f 100%);
      color: white;
    }

    .certificate-details {
      background: #f8f9fa;
      border-radius: 10px;
      padding: 20px;
      margin-top: 20px;
    }

    .detail-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 8px 0;
      border-bottom: 1px solid #e9ecef;
    }

    .detail-row:last-child {
      border-bottom: none;
    }

    .detail-label {
      font-weight: 600;
      color: #495057;
    }

    .detail-value {
      color: #212529;
      text-align: right;
    }

    .sri-lanka-emblem {
      width: 60px;
      height: 60px;
      margin: 0 auto 20px;
      background: white;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      color: #667eea;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(20px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .back-link {
      text-align: center;
      margin-top: 30px;
    }

    .back-link a {
      color: white;
      text-decoration: none;
      font-weight: 500;
    }

    .back-link a:hover {
      text-decoration: underline;
    }
  </style>
</head>

<body>
  <div class="container">
    <div class="verification-container">
      <div class="verification-card">
        <div class="card-header">
          <div class="sri-lanka-emblem">
            <i class="fas fa-certificate"></i>
          </div>
          <h2 class="mb-0">🇱🇰 Birth Certificate Verification</h2>
          <p class="mb-0 mt-2">National Digital Management System</p>
        </div>

        <div class="card-body">
          <?php if ($showForm): ?>
            <div class="text-center mb-4">
              <h5>Verify Birth Certificate Authenticity</h5>
              <p class="text-muted">Enter the certificate number and verification hash to validate the certificate</p>
            </div>

            <form method="POST" class="verification-form">
              <div class="mb-3">
                <label for="certificate_number" class="form-label">
                  <i class="fas fa-certificate"></i> Certificate Number
                </label>
                <input type="text"
                  class="form-control"
                  id="certificate_number"
                  name="certificate_number"
                  placeholder="BC-LK-YYYY-XXXXXX"
                  required>
              </div>

              <div class="mb-3">
                <label for="verification_hash" class="form-label">
                  <i class="fas fa-shield-alt"></i> Verification Hash
                </label>
                <input type="text"
                  class="form-control"
                  id="verification_hash"
                  name="verification_hash"
                  placeholder="Enter verification hash from QR code"
                  required>
              </div>

              <button type="submit" class="btn btn-verify">
                <i class="fas fa-search"></i> Verify Certificate
              </button>
            </form>

            <div class="text-center mt-4">
              <small class="text-muted">
                <i class="fas fa-info-circle"></i>
                Scan the QR code on the certificate or enter details manually
              </small>
            </div>
          <?php else: ?>
            <?php if ($verificationResult): ?>
              <div class="verification-result <?php echo $verificationResult['valid'] ? 'result-valid' : 'result-invalid'; ?>">
                <div class="text-center">
                  <i class="fas fa-<?php echo $verificationResult['valid'] ? 'check-circle' : 'times-circle'; ?> fa-3x mb-3"></i>
                  <h4><?php echo $verificationResult['valid'] ? 'Certificate Valid' : 'Certificate Invalid'; ?></h4>
                  <p class="mb-0"><?php echo htmlspecialchars($verificationResult['message']); ?></p>
                </div>

                <?php if ($verificationResult['valid'] && isset($verificationResult['data'])): ?>
                  <div class="certificate-details">
                    <h6 class="mb-3">
                      <i class="fas fa-file-alt"></i> Certificate Details
                    </h6>

                    <!-- Certificate Information -->
                    <div class="section-header" style="background: #e9ecef; padding: 8px 12px; margin: 15px -20px 10px -20px; font-weight: bold; color: #495057;">
                      <i class="fas fa-certificate"></i> CERTIFICATE INFORMATION
                    </div>
                    
                    <div class="detail-row">
                      <span class="detail-label">Certificate Number:</span>
                      <span class="detail-value"><?php echo htmlspecialchars($verificationResult['data']['CertificateNumber']); ?></span>
                    </div>

                    <?php if ($verificationResult['data']['IssueDate']): ?>
                      <div class="detail-row">
                        <span class="detail-label">Issue Date:</span>
                        <span class="detail-value">
                          <?php
                          $issueDate = new DateTime($verificationResult['data']['IssueDate']);
                          echo $issueDate->format('F j, Y');
                          ?>
                        </span>
                      </div>
                    <?php endif; ?>

                    <div class="detail-row">
                      <span class="detail-label">Status:</span>
                      <span class="detail-value">
                        <span class="badge bg-success"><?php echo htmlspecialchars($verificationResult['data']['Status']); ?></span>
                      </span>
                    </div>

                    <!-- Child Information -->
                    <div class="section-header" style="background: #e9ecef; padding: 8px 12px; margin: 15px -20px 10px -20px; font-weight: bold; color: #495057;">
                      <i class="fas fa-baby"></i> CHILD INFORMATION
                    </div>

                    <div class="detail-row">
                      <span class="detail-label">Child's Full Name:</span>
                      <span class="detail-value"><?php echo htmlspecialchars($verificationResult['data']['ChildFullName']); ?></span>
                    </div>

                    <div class="detail-row">
                      <span class="detail-label">Date of Birth:</span>
                      <span class="detail-value">
                        <?php
                        $dob = new DateTime($verificationResult['data']['DateOfBirth']);
                        echo $dob->format('F j, Y');
                        ?>
                      </span>
                    </div>

                    <?php if ($verificationResult['data']['TimeOfBirth']): ?>
                      <div class="detail-row">
                        <span class="detail-label">Time of Birth:</span>
                        <span class="detail-value">
                          <?php
                          $time = new DateTime($verificationResult['data']['TimeOfBirth']);
                          echo $time->format('h:i A');
                          ?>
                        </span>
                      </div>
                    <?php endif; ?>

                    <div class="detail-row">
                      <span class="detail-label">Sex:</span>
                      <span class="detail-value"><?php echo htmlspecialchars($verificationResult['data']['Sex']); ?></span>
                    </div>

                    <div class="detail-row">
                      <span class="detail-label">Nationality:</span>
                      <span class="detail-value"><?php echo htmlspecialchars($verificationResult['data']['Nationality']); ?></span>
                    </div>

                    <?php if (isset($verificationResult['data']['BirthWeightGrams']) && $verificationResult['data']['BirthWeightGrams']): ?>
                      <div class="detail-row">
                        <span class="detail-label">Birth Weight (grams):</span>
                        <span class="detail-value"><?php echo htmlspecialchars($verificationResult['data']['BirthWeightGrams']); ?></span>
                      </div>
                    <?php endif; ?>

                    <?php if (isset($verificationResult['data']['BirthLengthCm']) && $verificationResult['data']['BirthLengthCm']): ?>
                      <div class="detail-row">
                        <span class="detail-label">Birth Length (cm):</span>
                        <span class="detail-value"><?php echo htmlspecialchars($verificationResult['data']['BirthLengthCm']); ?></span>
                      </div>
                    <?php endif; ?>

                    <!-- Birth Location -->
                    <div class="section-header" style="background: #e9ecef; padding: 8px 12px; margin: 15px -20px 10px -20px; font-weight: bold; color: #495057;">
                      <i class="fas fa-map-marker-alt"></i> BIRTH LOCATION
                    </div>

                    <?php if ($verificationResult['data']['PlaceOfBirth']): ?>
                      <div class="detail-row">
                        <span class="detail-label">Place of Birth:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($verificationResult['data']['PlaceOfBirth']); ?></span>
                      </div>
                    <?php endif; ?>

                    <?php if ($verificationResult['data']['HospitalName']): ?>
                      <div class="detail-row">
                        <span class="detail-label">Hospital/Institution:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($verificationResult['data']['HospitalName']); ?></span>
                      </div>
                    <?php endif; ?>

                    <?php if (isset($verificationResult['data']['DeliveryType']) && $verificationResult['data']['DeliveryType']): ?>
                      <div class="detail-row">
                        <span class="detail-label">Delivery Type:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($verificationResult['data']['DeliveryType']); ?></span>
                      </div>
                    <?php endif; ?>

                    <!-- Parents Information -->
                    <div class="section-header" style="background: #e9ecef; padding: 8px 12px; margin: 15px -20px 10px -20px; font-weight: bold; color: #495057;">
                      <i class="fas fa-users"></i> PARENTS INFORMATION
                    </div>

                    <?php if (isset($verificationResult['data']['FatherName']) && $verificationResult['data']['FatherName']): ?>
                      <div class="detail-row">
                        <span class="detail-label">Father Name:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($verificationResult['data']['FatherName']); ?></span>
                      </div>
                    <?php endif; ?>

                    <?php if (isset($verificationResult['data']['FatherNIC']) && $verificationResult['data']['FatherNIC']): ?>
                      <div class="detail-row">
                        <span class="detail-label">Father NIC:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($verificationResult['data']['FatherNIC']); ?></span>
                      </div>
                    <?php endif; ?>

                    <?php if (isset($verificationResult['data']['MotherName']) && $verificationResult['data']['MotherName']): ?>
                      <div class="detail-row">
                        <span class="detail-label">Mother Name:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($verificationResult['data']['MotherName']); ?></span>
                      </div>
                    <?php endif; ?>

                    <?php if (isset($verificationResult['data']['MotherNIC']) && $verificationResult['data']['MotherNIC']): ?>
                      <div class="detail-row">
                        <span class="detail-label">Mother NIC:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($verificationResult['data']['MotherNIC']); ?></span>
                      </div>
                    <?php endif; ?>

                    <?php if (isset($verificationResult['data']['MotherMaidenName']) && $verificationResult['data']['MotherMaidenName']): ?>
                      <div class="detail-row">
                        <span class="detail-label">Mother Maiden Name:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($verificationResult['data']['MotherMaidenName']); ?></span>
                      </div>
                    <?php endif; ?>

                    <?php if (isset($verificationResult['data']['ParentsAddress']) && $verificationResult['data']['ParentsAddress']): ?>
                      <div class="detail-row">
                        <span class="detail-label">Parents Address:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($verificationResult['data']['ParentsAddress']); ?></span>
                      </div>
                    <?php endif; ?>

                    <!-- Registration Details -->
                    <div class="section-header" style="background: #e9ecef; padding: 8px 12px; margin: 15px -20px 10px -20px; font-weight: bold; color: #495057;">
                      <i class="fas fa-clipboard-check"></i> REGISTRATION DETAILS
                    </div>

                    <?php if (isset($verificationResult['data']['RegistrarOffice']) && $verificationResult['data']['RegistrarOffice']): ?>
                      <div class="detail-row">
                        <span class="detail-label">Registrar Office:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($verificationResult['data']['RegistrarOffice']); ?></span>
                      </div>
                    <?php endif; ?>

                    <?php if (isset($verificationResult['data']['RegisteredBy']) && $verificationResult['data']['RegisteredBy']): ?>
                      <div class="detail-row">
                        <span class="detail-label">Registered By:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($verificationResult['data']['RegisteredBy']); ?></span>
                      </div>
                    <?php endif; ?>

                    <div class="detail-row">
                      <span class="detail-label">Registration Date:</span>
                      <span class="detail-value">
                        <?php
                        $regDate = new DateTime($verificationResult['data']['RegisteredAt']);
                        echo $regDate->format('F j, Y \a\t g:i A');
                        ?>
                      </span>
                    </div>
                  </div>

                  <div class="text-center mt-3">
                    <small class="text-white">
                      <i class="fas fa-shield-alt"></i>
                      This certificate has been verified against the NDMS database
                    </small>
                  </div>
                <?php endif; ?>
              </div>

              <div class="text-center mt-4">
                <a href="verify_certificate.php" class="btn btn-outline-primary">
                  <i class="fas fa-search"></i> Verify Another Certificate
                </a>
              </div>
            <?php endif; ?>
          <?php endif; ?>
        </div>
      </div>

      <div class="back-link">
        <a href="index.php">
          <i class="fas fa-home"></i> Back to NDMS Homepage
        </a>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Auto-fill from URL parameters if available
    document.addEventListener('DOMContentLoaded', function() {
      const urlParams = new URLSearchParams(window.location.search);
      const cert = urlParams.get('cert');
      const hash = urlParams.get('hash');

      if (cert && hash && document.getElementById('certificate_number')) {
        document.getElementById('certificate_number').value = cert;
        document.getElementById('verification_hash').value = hash;

        // Auto-submit if both parameters are present
        setTimeout(() => {
          document.querySelector('form').submit();
        }, 500);
      }
    });
  </script>
</body>

</html>