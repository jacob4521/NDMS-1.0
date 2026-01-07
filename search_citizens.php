<?php
include "config.php";

// Check if user is logged in
if (!isset($_SESSION['UserID'])) {
    header("Location: login.php");
    exit;
}

$searchTerm = $_GET['search'] ?? '';
$results = null;

if (!empty($searchTerm)) {
    $searchPattern = "%$searchTerm%";
    $stmt = $conn->prepare("
        SELECT CitizenID, FirstName, LastName, DOB, Gender, NIC, Address, QRCodePath, Citizen_eID
        FROM Citizens 
        WHERE FirstName LIKE ? OR LastName LIKE ? OR CitizenID LIKE ? OR NIC LIKE ? OR Citizen_eID LIKE ?
        ORDER BY FirstName, LastName
        LIMIT 20
    ");
    $stmt->bind_param("sssss", $searchPattern, $searchPattern, $searchPattern, $searchPattern, $searchPattern);
    $stmt->execute();
    $results = $stmt->get_result();
}

// Get recent citizens (increased limit to show more citizens)
$recentCitizens = $conn->query("
    SELECT CitizenID, FirstName, LastName, DOB, Gender, QRCodePath, CreatedAt
    FROM Citizens 
    ORDER BY CreatedAt DESC 
    LIMIT 50
");

// Get statistics
$totalCitizens = $conn->query("SELECT COUNT(*) as count FROM Citizens")->fetch_assoc()['count'];
$withBirthCerts = $conn->query("SELECT COUNT(DISTINCT CitizenID) as count FROM BirthCertificates")->fetch_assoc()['count'];
$withEducation = $conn->query("SELECT COUNT(DISTINCT CitizenID) as count FROM EducationRecords")->fetch_assoc()['count'];
$withEmployment = $conn->query("SELECT COUNT(DISTINCT CitizenID) as count FROM EmploymentRecords")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Citizen Directory - NDMS</title>
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
            padding: 20px;
        }
        
        .container { 
            max-width: 1400px; 
            margin: 0 auto; 
        }
        
        .nav {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .nav a {
            color: var(--primary-color);
            text-decoration: none;
            padding: 12px 20px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            border: 1px solid transparent;
            display: flex;
            align-items: center;
            gap: 8px;
            background: var(--gradient-bg);
            color: white;
        }
        
        .nav a:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            filter: brightness(1.1);
        }
        
        .header { 
            background: var(--gradient-bg);
            color: white;
            padding: 30px;
            border-radius: 20px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-xl);
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
        
        .header h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 8px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
            z-index: 1;
        }
        
        .header p {
            font-size: 18px;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }
        
        .stats-section { 
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        .stat-box { 
            background: var(--card-bg);
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            border-left: 4px solid var(--accent-color);
            box-shadow: var(--shadow-md);
            transition: all 0.3s ease;
            position: relative;
        }
        
        .stat-box:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            border-left-color: var(--primary-color);
        }
        
        .stat-box h3 {
            font-size: 32px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 8px;
        }
        
        .stat-box p {
            color: var(--text-secondary);
            font-size: 14px;
            font-weight: 500;
        }
        
        .search-section { 
            background: var(--card-bg);
            padding: 30px;
            border-radius: 20px;
            margin: 25px 0;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            position: relative;
        }
        
        .search-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-bg);
            border-radius: 20px 20px 0 0;
        }
        
        .search-section h3 {
            color: var(--primary-color);
            margin-bottom: 20px;
            font-size: 22px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .form-row { 
            display: flex; 
            gap: 20px; 
            align-items: end;
            flex-wrap: wrap;
        }
        
        .form-group { 
            flex: 1;
            min-width: 200px;
        }
        
        .form-group label {
            display: block;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .form-group input { 
            padding: 12px 16px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            width: 100%;
            font-size: 14px;
            transition: all 0.3s ease;
            background: white;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: var(--secondary-color);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .btn { 
            padding: 12px 24px;
            background: var(--gradient-bg);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
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
        
        .results-section { 
            margin-top: 30px; 
        }
        
        .citizen-card { 
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 15px;
            padding: 20px;
            margin: 15px 0;
            display: flex;
            align-items: center;
            gap: 20px;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
        }
        
        .citizen-card:hover { 
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            border-color: var(--secondary-color);
        }
        
        .qr-thumbnail { 
            width: 70px;
            height: 70px;
            background: var(--light-bg);
            border-radius: 10px;
            overflow: hidden;
            border: 2px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: var(--text-secondary);
        }
        
        .qr-thumbnail img { 
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .citizen-info { 
            flex: 1; 
        }
        
        .citizen-info h4 { 
            margin: 0 0 8px 0;
            color: var(--primary-color);
            font-size: 18px;
            font-weight: 700;
        }
        
        .citizen-info p { 
            margin: 0 0 4px 0;
            color: var(--text-secondary);
            font-size: 14px;
        }
        
        .citizen-meta {
            display: flex;
            gap: 10px;
            margin-top: 8px;
            flex-wrap: wrap;
        }
        
        .citizen-actions { 
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .no-results { 
            text-align: center;
            padding: 60px 40px;
            color: var(--text-secondary);
            background: var(--light-bg);
            border-radius: 15px;
            border: 2px dashed var(--border-color);
        }
        
        .no-results h4 {
            color: var(--text-primary);
            margin-bottom: 15px;
            font-size: 20px;
        }
        
        .no-results p {
            font-size: 16px;
            opacity: 0.8;
        }
        
        .age-badge { 
            background: var(--secondary-color);
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .gender-badge {
            background: var(--accent-color);
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .id-badge {
            background: var(--warning-color);
            color: white;
            padding: 4px 12px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }
            
            .header h1 {
                font-size: 24px;
            }
            
            .stats-section {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                flex-direction: column;
                align-items: stretch;
            }
            
            .citizen-card {
                flex-direction: column;
                text-align: center;
            }
            
            .citizen-actions {
                justify-content: center;
            }
            
            .nav {
                flex-direction: column;
            }
        }
        
        /* Loading Animation */
        .citizen-card {
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
        <div class="container">
        <div class="header">
            <h1>🇱🇰 NDMS Citizen Directory</h1>
            <p>Search and view comprehensive citizen profiles</p>
        </div>

        <div class="stats-section">
            <div class="stat-box">
                <h3><?= $totalCitizens ?></h3>
                <p>Total Citizens</p>
            </div>
            <div class="stat-box">
                <h3><?= $withBirthCerts ?></h3>
                <p>With Birth Certificates</p>
            </div>
            <div class="stat-box">
                <h3><?= $withEducation ?></h3>
                <p>With Education Records</p>
            </div>
            <div class="stat-box">
                <h3><?= $withEmployment ?></h3>
                <p>With Employment Records</p>
            </div>
        </div>

        <div class="search-section">
            <h3>🔍 Search Citizens</h3>
            <form method="GET">
                <div class="form-row">
                    <div class="form-group">
                        <label for="search">Search by Name, ID, NIC, or eID:</label>
                        <input type="text" name="search" id="search" value="<?= htmlspecialchars($searchTerm) ?>" 
                               placeholder="Enter name, citizen ID, NIC number, or eID...">
                    </div>
                    <div>
                        <button type="submit" class="btn">🔍 Search</button>
                        <?php if ($searchTerm): ?>
                            <a href="search_citizens.php" class="btn btn-secondary">Clear</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <div class="results-section">
            <?php if (!empty($searchTerm)): ?>
                <h3 style="color: var(--primary-color); font-size: 24px; font-weight: 700; margin-bottom: 20px;">🔎 Search Results for "<?= htmlspecialchars($searchTerm) ?>"</h3>
                
                <?php if ($results && $results->num_rows > 0): ?>
                    <?php while($citizen = $results->fetch_assoc()): ?>
                        <?php
                        $dob = new DateTime($citizen['DOB']);
                        $today = new DateTime();
                        $age = $today->diff($dob)->y;
                        ?>
                        <div class="citizen-card">
                            <div class="qr-thumbnail">
                                <?php if (!empty($citizen['QRCodePath']) && file_exists($citizen['QRCodePath'])): ?>
                                    <img src="<?= htmlspecialchars($citizen['QRCodePath']) ?>" alt="QR Code">
                                <?php else: ?>
                                    <div style="background: #ddd; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 10px;">No QR</div>
                                <?php endif; ?>
                            </div>
                            <div class="citizen-info">
                                <h4><?= htmlspecialchars($citizen['FirstName'] . ' ' . $citizen['LastName']) ?></h4>
                                <p><strong>Address:</strong> <?= htmlspecialchars(substr($citizen['Address'], 0, 60)) ?><?= strlen($citizen['Address']) > 60 ? '...' : '' ?></p>
                                <div class="citizen-meta">
                                    <span class="id-badge">ID: <?= $citizen['CitizenID'] ?></span>
                                    <?php if (!empty($citizen['Citizen_eID'])): ?>
                                        <span class="id-badge">eID: <?= htmlspecialchars($citizen['Citizen_eID']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($citizen['NIC'])): ?>
                                        <span class="id-badge">NIC: <?= htmlspecialchars($citizen['NIC']) ?></span>
                                    <?php endif; ?>
                                    <span class="age-badge"><?= $age ?> years old</span>
                                    <span class="gender-badge"><?= htmlspecialchars($citizen['Gender']) ?></span>
                                </div>
                            </div>
                            <div class="citizen-actions">
                                <a href="view_citizen.php?citizen_id=<?= $citizen['CitizenID'] ?>" class="btn">View Full Profile</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-results">
                        <h4>🔍 No citizens found</h4>
                        <p>Try adjusting your search criteria or <a href="register.php" style="color: var(--secondary-color); text-decoration: none; font-weight: 600;">register a new citizen</a>.</p>
                    </div>
                <?php endif; ?>

            <?php else: ?>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
                    <h3 style="color: var(--primary-color); font-size: 24px; font-weight: 700; margin: 0;">📊 Complete Citizen Directory</h3>
                    <p style="color: var(--text-secondary); margin: 0; font-size: 14px; background: var(--light-bg); padding: 8px 16px; border-radius: 20px;">Showing latest 50 citizens</p>
                </div>
                
                <?php if ($recentCitizens->num_rows > 0): ?>
                    <?php while($citizen = $recentCitizens->fetch_assoc()): ?>
                        <?php
                        $dob = new DateTime($citizen['DOB']);
                        $today = new DateTime();
                        $age = $today->diff($dob)->y;
                        ?>
                        <div class="citizen-card">
                            <div class="qr-thumbnail">
                                <?php if (!empty($citizen['QRCodePath']) && file_exists($citizen['QRCodePath'])): ?>
                                    <img src="<?= htmlspecialchars($citizen['QRCodePath']) ?>" alt="QR Code">
                                <?php else: ?>
                                    <div style="background: #ddd; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 10px;">No QR</div>
                                <?php endif; ?>
                            </div>
                            <div class="citizen-info">
                                <h4><?= htmlspecialchars($citizen['FirstName'] . ' ' . $citizen['LastName']) ?></h4>
                                <p><strong>Date of Birth:</strong> <?= $citizen['DOB'] ?></p>
                                <div class="citizen-meta">
                                    <span class="id-badge">ID: <?= $citizen['CitizenID'] ?></span>
                                    <span class="age-badge"><?= $age ?> years old</span>
                                    <span class="gender-badge"><?= htmlspecialchars($citizen['Gender']) ?></span>
                                </div>
                            </div>
                            <div class="citizen-actions">
                                <a href="view_citizen.php?citizen_id=<?= $citizen['CitizenID'] ?>" class="btn">View Full Profile</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-results">
                        <h4>👤 No citizens registered yet</h4>
                        <p><a href="register.php" style="color: var(--secondary-color); text-decoration: none; font-weight: 600;">Register the first citizen</a> to get started.</p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        </div> <!-- End container -->
    </div> <!-- End main-content -->
    
    <!-- Include Sidebar JavaScript -->
    <script src="includes/sidebar.js"></script>
</body>
</html>
