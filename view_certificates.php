<?php
require_once 'config.php';
require_once 'birth_certificate_helper.php';

// Role check - Only Medical Officers and Admins can view all certificates
if (!isset($_SESSION['UserID']) || !canManageBirthCertificates($_SESSION['Role'])) {
    header("Location: login.php");
    exit();
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Get certificates with search and filters
$certificates = getAllBirthCertificates($limit, $offset, $search, $status);

// Get total count for pagination
$totalCerts = getTotalBirthCertificatesCount($search, $status);
$totalPages = ceil($totalCerts / $limit);

// Get statistics
$stats = getBirthCertificateStats();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Birth Certificates - NDMS</title>
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
        
        .container { 
            max-width: 1400px; 
            margin: 0 auto; 
            padding: 20px;
        }
        
        .header {
            background: var(--gradient-bg);
            color: white;
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-xl);
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 200px;
            height: 200px;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="2"/><circle cx="50" cy="50" r="30" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/><circle cx="50" cy="50" r="20" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></svg>') no-repeat center;
            opacity: 0.3;
        }
        
        .header h1 { 
            font-size: 36px;
            font-weight: 700;
            margin: 0 0 8px 0;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
            z-index: 1;
        }
        
        .header p {
            font-size: 18px;
            opacity: 0.9;
            margin: 0;
            position: relative;
            z-index: 1;
        }
        
        .header-buttons {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            position: relative;
            z-index: 1;
        }
        
        .stats-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-box {
            background: var(--card-bg);
            padding: 30px;
            border-radius: 20px;
            text-align: center;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease;
        }
        
        .stat-box::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-bg);
        }
        
        .stat-box:hover {
            transform: translateY(-5px);
        }
        
        .stat-box h3 {
            font-size: 42px;
            font-weight: 800;
            color: var(--primary-color);
            margin: 0 0 8px 0;
            background: var(--gradient-bg);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .stat-box p {
            color: var(--text-secondary);
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
        }
        
        .content-section {
            background: var(--card-bg);
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            position: relative;
            margin-bottom: 30px;
        }
        
        .content-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-bg);
            border-radius: 20px 20px 0 0;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .section-header h2 {
            margin: 0;
            color: var(--primary-color);
            font-size: 24px;
            font-weight: 700;
        }
        
        .btn {
            background: var(--gradient-bg);
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: var(--shadow-sm);
            cursor: pointer;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            filter: brightness(1.1);
            color: white;
        }
        
        .btn-outline-light {
            background: transparent;
            color: white;
            border: 2px solid white;
        }
        
        .btn-outline-light:hover {
            background: white;
            color: var(--primary-color);
        }
        
        .btn-outline-primary {
            background: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }
        
        .btn-outline-primary:hover {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-outline-secondary {
            background: transparent;
            color: var(--text-secondary);
            border: 2px solid var(--text-secondary);
        }
        
        .btn-outline-secondary:hover {
            background: var(--text-secondary);
            color: white;
        }
        
        .btn-outline-info {
            background: transparent;
            color: var(--secondary-color);
            border: 2px solid var(--secondary-color);
        }
        
        .btn-outline-info:hover {
            background: var(--secondary-color);
            color: white;
        }
        
        .btn-sm {
            padding: 8px 16px;
            font-size: 12px;
            border-radius: 8px;
        }
        
        .form-control, .form-select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            font-size: 14px;
            transition: border-color 0.3s ease;
            background: var(--card-bg);
        }
        
        .form-control:focus, .form-select:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-primary);
            font-weight: 600;
            font-size: 14px;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: var(--card-bg);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
        }
        
        .table th,
        .table td {
            padding: 15px 12px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }
        
        .table th {
            background: var(--primary-color);
            color: white;
            font-weight: 600;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .table tr:hover {
            background: var(--light-bg);
        }
        
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .bg-success {
            background: var(--accent-color);
            color: white;
        }
        
        .bg-warning {
            background: var(--warning-color);
            color: white;
        }
        
        .row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .col-md-3 {
            flex: 1;
            min-width: 200px;
        }
        
        .col-md-4 {
            flex: 1;
            min-width: 250px;
        }
        
        .col-md-2 {
            flex: 0 0 auto;
            min-width: 150px;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .page-item {
            list-style: none;
        }
        
        .page-link {
            padding: 10px 15px;
            background: var(--card-bg);
            border: 2px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-primary);
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .page-link:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .page-item.active .page-link {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-muted {
            color: var(--text-secondary);
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }
        
        .modal.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-dialog {
            background: var(--card-bg);
            border-radius: 15px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-title {
            margin: 0;
            color: var(--primary-color);
            font-size: 20px;
            font-weight: 700;
        }
        
        .btn-close {
            background: transparent;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--text-secondary);
        }
        
        .modal-body {
            padding: 20px;
            text-align: center;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .header {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }
            
            .header h1 {
                font-size: 28px;
            }
            
            .stats-section {
                grid-template-columns: 1fr;
            }
            
            .section-header {
                flex-direction: column;
                text-align: center;
            }
            
            .table {
                font-size: 14px;
            }
            
            .table th,
            .table td {
                padding: 10px 8px;
            }
            
            .row {
                flex-direction: column;
            }
            
            .pagination {
                gap: 5px;
            }
            
            .page-link {
                padding: 8px 12px;
                font-size: 12px;
            }
        }
        
        /* Loading Animation */
        .stat-box, .content-section {
            animation: slideUp 0.3s ease-out;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>📋 All Birth Certificates</h1>
                <p>National Digital Management System - Medical Officer Portal</p>
            </div>
            <div class="header-buttons">
                <a href="birth_certificate.php" class="btn btn-outline-light">
                    ➕ Add New Certificate
                </a>
                <a href="dashboard.php" class="btn btn-outline-light">
                    🏠 Dashboard
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-section">
            <div class="stat-box">
                <h3><?php echo $stats['total']; ?></h3>
                <p>Total Certificates</p>
            </div>
            <div class="stat-box">
                <h3><?php echo $stats['by_status']['Active'] ?? 0; ?></h3>
                <p>Active Certificates</p>
            </div>
            <div class="stat-box">
                <h3><?php echo $stats['recent_30_days']; ?></h3>
                <p>This Month</p>
            </div>
            <div class="stat-box">
                <h3><?php echo count($certificates); ?></h3>
                <p>On This Page</p>
            </div>
        </div>

        <!-- Search and Filter -->
        <div class="content-section">
            <div class="section-header">
                <h2>🔍 Search & Filter</h2>
                <p class="text-muted">Find specific birth certificates</p>
            </div>
            <form method="GET" class="row">
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Certificate number, child name, parents...">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="Active" <?php echo $status === 'Active' ? 'selected' : ''; ?>>Active</option>
                        <option value="Cancelled" <?php echo $status === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        <option value="Suspended" <?php echo $status === 'Suspended' ? 'selected' : ''; ?>>Suspended</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Page</label>
                    <input type="number" name="page" class="form-control" 
                           value="<?php echo $page; ?>" min="1" max="<?php echo $totalPages; ?>">
                </div>
                <div class="col-md-3" style="display: flex; align-items: end; gap: 10px;">
                    <button type="submit" class="btn">
                        🔍 Search
                    </button>
                    <a href="view_certificates.php" class="btn btn-outline-secondary">
                        🔄 Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Certificates Table -->
        <div class="content-section">
            <div class="section-header">
                <h2>📜 Birth Certificates</h2>
                <span class="text-muted">
                    Showing <?php echo $offset + 1; ?>-<?php echo min($offset + $limit, $totalCerts); ?> 
                    of <?php echo $totalCerts; ?> certificates
                </span>
            </div>
            
            <?php if (empty($certificates)): ?>
                <div class="text-center" style="padding: 60px 20px;">
                    <h3 style="color: var(--text-secondary); margin-bottom: 15px;">📄 No Certificates Found</h3>
                    <?php if ($search || $status): ?>
                        <p class="text-muted">
                            Try adjusting your search criteria or 
                            <a href="view_certificates.php">view all certificates</a>.
                        </p>
                    <?php else: ?>
                        <p class="text-muted">No birth certificates have been registered yet.</p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div style="overflow-x: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Cert #</th>
                                <th>Child Name</th>
                                <th>Date of Birth</th>
                                <th>Parents</th>
                                <th>Place of Birth</th>
                                <th>Status</th>
                                <th>Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($certificates as $cert): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($cert['CertificateNumber']); ?></strong>
                                        <br><small class="text-muted">ID: <?php echo $cert['BirthCertID']; ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($cert['ChildFullName']); ?></strong>
                                        <br><small class="text-muted"><?php echo $cert['Sex']; ?></small>
                                    </td>
                                    <td>
                                        <?php echo date('M j, Y', strtotime($cert['DateOfBirth'])); ?>
                                        <?php if ($cert['TimeOfBirth']): ?>
                                            <br><small class="text-muted"><?php echo date('g:i A', strtotime($cert['TimeOfBirth'])); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($cert['FatherName']): ?>
                                            <strong>F:</strong> <?php echo htmlspecialchars($cert['FatherName']); ?><br>
                                        <?php endif; ?>
                                        <?php if ($cert['MotherName']): ?>
                                            <strong>M:</strong> <?php echo htmlspecialchars($cert['MotherName']); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($cert['PlaceOfBirth'] ?: 'Not specified'); ?>
                                        <?php if ($cert['HospitalName']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($cert['HospitalName']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $cert['Status'] === 'Active' ? 'bg-success' : 'bg-warning'; ?>">
                                            <?php echo $cert['Status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo date('M j, Y', strtotime($cert['RegisteredAt'])); ?>
                                        <?php if ($cert['RegistrarName']): ?>
                                            <br><small class="text-muted">by <?php echo htmlspecialchars($cert['RegistrarName']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div style="display: flex; flex-direction: column; gap: 5px;">
                                            <?php if ($cert['VerificationHash']): ?>
                                                <a href="verify_certificate.php?cert=<?php echo urlencode($cert['CertificateNumber']); ?>&hash=<?php echo urlencode($cert['VerificationHash']); ?>" 
                                                   target="_blank" class="btn btn-sm" title="Verify Certificate">
                                                    ✅ Verify
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($cert['QRCodePath']): ?>
                                                <button class="btn btn-outline-primary btn-sm" 
                                                        onclick="showQR('<?php echo htmlspecialchars($cert['QRCodePath']); ?>')" 
                                                        title="View QR Code">
                                                    📱 QR Code
                                                </button>
                                            <?php endif; ?>
                                            <a href="view_citizen.php?id=<?php echo $cert['CitizenID']; ?>" 
                                               class="btn btn-outline-info btn-sm" title="View Citizen">
                                                👤 Citizen
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Certificates pagination">
                        <ul class="pagination">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>">
                                        ◀ Previous
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>">
                                        Next ▶
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- QR Code Modal -->
    <div class="modal" id="qrModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">📱 Certificate QR Code</h5>
                    <button type="button" class="btn-close" onclick="closeModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <img id="qrImage" src="" alt="QR Code" style="max-width: 300px; width: 100%; height: auto;">
                    <p style="margin-top: 20px;" class="text-muted">Scan this QR code to verify the certificate</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Enhanced interactions and animations
        document.addEventListener('DOMContentLoaded', function() {
            // Add hover effects to table rows
            const tableRows = document.querySelectorAll('.table tbody tr');
            tableRows.forEach(row => {
                row.addEventListener('mouseenter', function() {
                    this.style.transform = 'scale(1.01)';
                    this.style.transition = 'transform 0.2s ease';
                });
                
                row.addEventListener('mouseleave', function() {
                    this.style.transform = 'scale(1)';
                });
            });

            // Add smooth transitions for buttons
            document.querySelectorAll('.btn').forEach(button => {
                button.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                    this.style.transition = 'all 0.3s ease';
                });
                
                button.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });

            // Auto-submit search form on status change
            document.querySelector('select[name="status"]')?.addEventListener('change', function() {
                this.closest('form').submit();
            });
        });

        // Modal functions
        function showQR(qrPath) {
            document.getElementById('qrImage').src = qrPath;
            document.getElementById('qrModal').classList.add('show');
        }
        
        function closeModal() {
            document.querySelector('.modal.show').classList.remove('show');
        }

        // Close modal when clicking outside
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                closeModal();
            }
        });
    </script>
</body>
</html>
