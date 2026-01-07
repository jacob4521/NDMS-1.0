<?php
include "config.php";

// Check if user is logged in and is a citizen
if (!isset($_SESSION['UserID']) || $_SESSION['Role'] !== 'Citizen') {
    header("Location: login.php");
    exit;
}

$userID = $_SESSION['UserID'];
$username = $_SESSION['Username'] ?? '';

// Get citizen information using username (which is the eID)
$citizenQuery = $conn->prepare("
    SELECT c.*, u.Username as LoginUsername
    FROM Citizens c 
    JOIN Users u ON c.Citizen_eID = u.Username
    WHERE u.UserID = ?
");
$citizenQuery->bind_param("i", $userID);
$citizenQuery->execute();
$citizenResult = $citizenQuery->get_result();

if ($citizenResult->num_rows === 0) {
    die("Citizen profile not found.");
}

$citizen = $citizenResult->fetch_assoc();
$citizenID = $citizen['CitizenID'];

// Calculate age
$dob = new DateTime($citizen['DOB']);
$today = new DateTime();
$age = $today->diff($dob)->y;

// Fetch medical records with additional details
$medicalQuery = $conn->prepare("
    SELECT mr.*, 
           CASE 
               WHEN mr.recordDate >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) THEN 'Recent'
               WHEN mr.recordDate >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR) THEN 'This Year'
               ELSE 'Historical'
           END as RecordPeriod,
           DATEDIFF(CURDATE(), mr.recordDate) as DaysAgo
    FROM MedicalRecords mr 
    WHERE mr.citizenID = ? 
    ORDER BY mr.recordDate DESC
");
$medicalQuery->bind_param("i", $citizenID);
$medicalQuery->execute();
$medicalRecords = $medicalQuery->get_result();

// Fetch vaccination records with vaccine details
$vaccinationQuery = $conn->prepare("
    SELECT vr.*, v.Description as VaccineDescription,
           CASE 
               WHEN vr.DateAdministered >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR) THEN 'Recent'
               WHEN vr.DateAdministered >= DATE_SUB(CURDATE(), INTERVAL 5 YEAR) THEN 'Valid'
               ELSE 'Expired/Old'
           END as VaccineStatus,
           DATEDIFF(CURDATE(), vr.DateAdministered) as DaysAgo
    FROM VaccinationRecords vr 
    LEFT JOIN Vaccines v ON vr.VaccineName = v.VaccineName
    WHERE vr.CitizenID = ? 
    ORDER BY vr.DateAdministered DESC
");
$vaccinationQuery->bind_param("i", $citizenID);
$vaccinationQuery->execute();
$vaccinationRecords = $vaccinationQuery->get_result();

// Fetch education records with enhanced details
$educationQuery = $conn->prepare("
    SELECT er.*, u.Username as RegisteredByName,
           CASE 
               WHEN er.ExamName LIKE '%A/L%' OR er.ExamName LIKE '%Advanced Level%' THEN 'Advanced Level'
               WHEN er.ExamName LIKE '%O/L%' OR er.ExamName LIKE '%Ordinary Level%' THEN 'Ordinary Level'
               WHEN er.GradeLevel BETWEEN '1' AND '5' THEN 'Primary'
               WHEN er.GradeLevel BETWEEN '6' AND '11' THEN 'Secondary'
               WHEN er.GradeLevel IN ('12', '13') THEN 'Advanced Level'
               ELSE 'Other'
           END as EducationLevel,
           YEAR(CURDATE()) - YEAR(er.RecordDate) as YearsAgo
    FROM EducationRecords er 
    LEFT JOIN Users u ON er.RegisteredBy = u.UserID 
    WHERE er.CitizenID = ? 
    ORDER BY er.RecordDate DESC, er.GradeLevel DESC
");
$educationQuery->bind_param("i", $citizenID);
$educationQuery->execute();
$educationRecords = $educationQuery->get_result();

// Fetch employment records with career progression details
$employmentQuery = $conn->prepare("
    SELECT er.*, u.Username as RegisteredByName,
           CASE 
               WHEN er.EndDate IS NULL THEN 'Current'
               WHEN er.EndDate > DATE_SUB(CURDATE(), INTERVAL 1 YEAR) THEN 'Recent'
               ELSE 'Former'
           END as EmploymentStatus,
           CASE 
               WHEN er.EndDate IS NULL THEN DATEDIFF(CURDATE(), er.StartDate)
               ELSE DATEDIFF(er.EndDate, er.StartDate)
           END as DurationDays,
           CASE 
               WHEN er.EndDate IS NULL THEN 
                   CONCAT(FLOOR(DATEDIFF(CURDATE(), er.StartDate)/365), ' years, ', 
                         FLOOR((DATEDIFF(CURDATE(), er.StartDate) % 365)/30), ' months')
               ELSE 
                   CONCAT(FLOOR(DATEDIFF(er.EndDate, er.StartDate)/365), ' years, ', 
                         FLOOR((DATEDIFF(er.EndDate, er.StartDate) % 365)/30), ' months')
           END as Duration
    FROM EmploymentRecords er 
    LEFT JOIN Users u ON er.RegisteredBy = u.UserID 
    WHERE er.CitizenID = ? 
    ORDER BY er.StartDate DESC
");
$employmentQuery->bind_param("i", $citizenID);
$employmentQuery->execute();
$employmentRecords = $employmentQuery->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - NDMS</title>
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
            --citizen-gradient: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
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
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 50%, #f1f5f9 100%);
            background-attachment: fixed;
            min-height: 100vh;
            color: var(--text-primary);
            line-height: 1.6;
            padding: 0;
            margin: 0;
            transition: padding-left 0.3s ease;
            position: relative;
            animation: fadeIn 0.8s ease-out;
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

        /* Add subtle geometric pattern overlay */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                radial-gradient(circle at 25% 25%, rgba(59, 130, 246, 0.05) 0%, transparent 50%),
                radial-gradient(circle at 75% 75%, rgba(16, 185, 129, 0.05) 0%, transparent 50%),
                linear-gradient(45deg, transparent 25%, rgba(30, 58, 138, 0.02) 25%, rgba(30, 58, 138, 0.02) 50%, transparent 50%);
            background-size: 200px 200px, 300px 300px, 60px 60px;
            pointer-events: none;
            z-index: -1;
            animation: backgroundShift 20s ease-in-out infinite;
        }

        @keyframes backgroundShift {
            0%, 100% { 
                background-position: 0% 0%, 100% 100%, 0% 0%; 
            }
            50% { 
                background-position: 100% 100%, 0% 0%, 50% 50%; 
            }
        }

        .main-container { 
            max-width: 1400px; 
            margin: 0 auto; 
            padding: 24px;
            min-height: auto;
            animation: slideInUp 0.6s ease-out 0.2s both;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* When sidebar is active, override container behavior */
        body.has-citizen-sidebar .main-container { 
            max-width: none;
            margin: 0;
            min-height: auto;
            height: auto;
            padding: 24px 24px 24px 40px;
        }
        
        /* Page Header Section */
        .page-header {
            background: linear-gradient(145deg, #ffffff 0%, #fefefe 100%);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 32px;
            box-shadow: 
                0 8px 25px -5px rgba(0, 0, 0, 0.1),
                0 4px 6px -2px rgba(0, 0, 0, 0.05),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(20px);
            animation: slideInDown 0.6s ease-out 0.1s both;
            transition: all 0.3s ease;
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: var(--gradient-bg);
            border-radius: 20px 20px 0 0;
            animation: expandWidth 0.8s ease-out 0.5s both;
        }

        @keyframes expandWidth {
            from {
                width: 0;
            }
            to {
                width: 100%;
            }
        }

        /* Add decorative elements to page header */
        .page-header::after {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.05) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
            animation: pulseGlow 4s ease-in-out infinite;
        }

        @keyframes pulseGlow {
            0%, 100% {
                opacity: 0.5;
                transform: scale(1);
            }
            50% {
                opacity: 0.8;
                transform: scale(1.1);
            }
        }
        
        .header-content {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 24px;
            align-items: center;
        }
        
        .citizen-avatar {
            width: 80px;
            height: 80px;
            background: var(--gradient-bg);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: white;
            box-shadow: var(--shadow-md);
        }
        
        .header-info h1 { 
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--text-primary);
        }
        
        .header-info .citizen-meta {
            display: flex;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }
        
        .meta-item {
            background: var(--light-bg);
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 14px;
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
        }
        
        .header-actions {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        /* Quick Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
            margin-left: 32px;
            margin-right: 32px;
            padding: 32px;
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.9) 0%, rgba(255, 255, 255, 0.8) 100%);
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 
                0 4px 6px -1px rgba(0, 0, 0, 0.1),
                0 2px 4px -1px rgba(0, 0, 0, 0.06),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            position: relative;
            backdrop-filter: blur(20px);
            animation: fadeInUp 0.6s ease-out 0.3s both;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .stats-grid::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-bg);
            border-radius: 20px 20px 0 0;
        }
        
        .stats-grid::after {
            content: '📊 Quick Overview';
            position: absolute;
            top: -12px;
            left: 24px;
            background: linear-gradient(145deg, #ffffff 0%, #f8fafc 100%);
            padding: 0 12px;
            font-size: 14px;
            font-weight: 600;
            color: var(--primary-color);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.1);
            animation: bounceInDown 0.6s ease-out 0.8s both;
        }

        @keyframes bounceInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            60% {
                opacity: 1;
                transform: translateY(5px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .stat-card {
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.95) 0%, rgba(248, 250, 252, 0.9) 100%);
            border-radius: 16px;
            padding: 24px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 
                0 4px 6px -1px rgba(0, 0, 0, 0.1),
                0 2px 4px -1px rgba(0, 0, 0, 0.06),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--gradient-bg);
        }

        .stat-card::after {
            content: '';
            position: absolute;
            top: -20px;
            right: -20px;
            width: 60px;
            height: 60px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.1) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }
        
        .stat-card:hover {
            transform: translateY(-6px);
            box-shadow: 
                0 10px 15px -3px rgba(0, 0, 0, 0.1),
                0 4px 6px -2px rgba(0, 0, 0, 0.05),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }
        
        .stat-icon {
            width: 56px;
            height: 56px;
            background: var(--light-bg);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin: 0 auto 16px;
            color: var(--primary-color);
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: 800;
            color: var(--primary-color);
            margin-bottom: 8px;
            line-height: 1;
        }
        
        .stat-label {
            color: var(--text-secondary);
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Navigation Tabs */
        .tab-navigation {
            background: white;
            border-radius: 16px;
            padding: 8px 32px;
            margin-bottom: 40px;
            margin-left: 32px;
            margin-right: 32px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            overflow-x: auto;
            position: relative;
        }
        
        .tab-navigation::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-bg);
            border-radius: 16px 16px 0 0;
        }
        
        .tabs {
            display: flex;
            gap: 6px;
            min-width: fit-content;
            background: rgba(255, 255, 255, 0.8);
            padding: 8px;
            border-radius: 16px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            animation: slideInLeft 0.6s ease-out 0.4s both;
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .tab {
            padding: 16px 24px;
            background: transparent;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            color: var(--text-secondary);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            white-space: nowrap;
            min-width: fit-content;
            display: flex;
            align-items: center;
            gap: 8px;
            position: relative;
            overflow: hidden;
        }

        .tab::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.6);
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }

        .tab:hover::before {
            transform: translateX(0);
        }

        .tab:hover {
            background: transparent;
            color: var(--primary-color);
            transform: translateY(-2px) scale(1.02);
        }
        
        .tab.active {
            color: white;
            background: var(--gradient-bg);
            box-shadow: 
                0 4px 6px -1px rgba(0, 0, 0, 0.1),
                0 2px 4px -1px rgba(0, 0, 0, 0.06),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            transform: translateY(-2px) scale(1.05);
            animation: activateTab 0.3s ease-out;
        }

        @keyframes activateTab {
            0% {
                transform: translateY(0) scale(1);
            }
            50% {
                transform: translateY(-4px) scale(1.08);
            }
            100% {
                transform: translateY(-2px) scale(1.05);
            }
        }
        
        .tab:hover:not(.active) {
            background: var(--light-bg);
            color: var(--primary-color);
        }
        
        /* Content Sections */
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .content-section {
            background: linear-gradient(145deg, #ffffff 0%, #fefefe 100%);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 40px;
            margin-left: 32px;
            margin-right: 32px;
            box-shadow: 
                0 4px 6px -1px rgba(0, 0, 0, 0.1),
                0 2px 4px -1px rgba(0, 0, 0, 0.06),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            border: 1px solid var(--border-color);
            position: relative;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
            backdrop-filter: blur(10px);
            opacity: 0;
            transform: translateY(30px);
            animation: slideInContent 0.6s ease-out forwards;
        }

        @keyframes slideInContent {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Stagger animation delays for content sections */
        .content-section:nth-child(1) { animation-delay: 0.5s; }
        .content-section:nth-child(2) { animation-delay: 0.6s; }
        .content-section:nth-child(3) { animation-delay: 0.7s; }
        .content-section:nth-child(4) { animation-delay: 0.8s; }
        .content-section:nth-child(5) { animation-delay: 0.9s; }
        
        .content-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-bg);
            border-radius: 20px 20px 0 0;
            transform: scaleX(0);
            transform-origin: left;
            animation: expandBar 0.8s ease-out 1s forwards;
        }

        @keyframes expandBar {
            to {
                transform: scaleX(1);
            }
        }

        /* Add subtle inner pattern */
        .content-section::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 150px;
            height: 150px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.03) 0%, transparent 70%);
            border-radius: 50%;
            transform: translate(30%, -30%);
            pointer-events: none;
            animation: floatPattern 6s ease-in-out infinite;
        }

        @keyframes floatPattern {
            0%, 100% {
                transform: translate(30%, -30%) scale(1);
            }
            50% {
                transform: translate(35%, -25%) scale(1.1);
            }
        }
        
        .content-section:hover {
            transform: translateY(-6px) scale(1.01);
            box-shadow: 
                0 12px 20px -5px rgba(0, 0, 0, 0.15),
                0 4px 6px -2px rgba(0, 0, 0, 0.05),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
        }
        
        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid var(--light-bg);
        }
        
        .section-icon {
            width: 48px;
            height: 48px;
            background: var(--light-bg);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: var(--primary-color);
        }
        
        .section-header h3 {
            color: var(--primary-color);
            font-size: 22px;
            font-weight: 700;
            margin: 0;
        }
        
        /* Information Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
            margin-left: 32px;
            margin-right: 32px;
            padding: 32px;
            background: white;
            border-radius: 16px;
            border: 1px solid var(--border-color);
            box-shadow: var(--shadow-md);
            position: relative;
        }
        
        .info-grid::before {
            content: '📋 Personal Information';
            position: absolute;
            top: -35px;
            left: 0;
            color: var(--text-primary);
            font-weight: 600;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .info-card {
            background: var(--light-bg);
            padding: 24px;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            position: relative;
            border-top: 3px solid var(--primary-color);
        }
        
        .info-card::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: var(--accent-color);
            border-radius: 2px 0 0 2px;
        }
        
        .info-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            background: white;
        }
        
        .info-label {
            font-weight: 700;
            color: var(--text-secondary);
            margin-bottom: 8px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }
        
        .info-value {
            color: var(--text-primary);
            font-size: 18px;
            font-weight: 600;
            line-height: 1.4;
        }
        
        /* Record Items */
        .records-container {
            display: grid;
            gap: 20px;
        }
        
        .record-item {
            background: var(--light-bg);
            border-radius: 16px;
            padding: 24px;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            position: relative;
        }
        
        .record-item::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: var(--secondary-color);
            border-radius: 2px 0 0 2px;
        }
        
        .record-item:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
            background: white;
        }
        
        .record-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
            flex-wrap: wrap;
            gap: 12px;
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
        
        .record-content {
            display: grid;
            gap: 12px;
        }
        
        .record-field {
            display: flex;
            gap: 8px;
        }
        
        .record-field strong {
            color: var(--text-secondary);
            min-width: 120px;
            font-weight: 600;
        }
        
        /* Action Buttons */
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-top: 32px;
        }
        
        .action-btn {
            background: var(--gradient-bg);
            color: white;
            padding: 16px 24px;
            border: none;
            border-radius: 12px;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            cursor: pointer;
            box-shadow: var(--shadow-sm);
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            filter: brightness(1.1);
        }
        
        .action-btn.secondary {
            background: var(--light-bg);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }
        
        .action-btn.secondary:hover {
            background: white;
            border-color: var(--primary-color);
        }
        
        /* Badge System */
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        
        /* Empty States */
        .empty-state {
            text-align: center;
            padding: 64px 32px;
            color: var(--text-secondary);
            background: var(--light-bg);
            border-radius: 16px;
            border: 2px dashed var(--border-color);
        }
        
        .empty-state-icon {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin: 0 auto 24px;
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
        }
        
        .empty-state h4 {
            color: var(--text-primary);
            margin-bottom: 8px;
            font-size: 20px;
            font-weight: 600;
        }
        
        .empty-state p {
            font-size: 16px;
            opacity: 0.8;
            max-width: 400px;
            margin: 0 auto;
        }
        
        .download-section {
            background: linear-gradient(135deg, rgba(240, 249, 255, 0.9) 0%, rgba(224, 242, 254, 0.9) 100%);
            border: 2px solid var(--secondary-color);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            position: relative;
            backdrop-filter: blur(5px);
        }
        
        .download-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-bg);
            border-radius: 15px 15px 0 0;
        }
        
        .download-section h4 {
            color: var(--primary-color);
            margin-bottom: 20px;
            font-size: 18px;
            font-weight: 700;
        }
        
        .download-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .download-btn {
            background: var(--gradient-bg);
            color: white;
            padding: 15px 20px;
            border: none;
            border-radius: 12px;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            cursor: pointer;
            box-shadow: var(--shadow-sm);
        }
        
        .download-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            filter: brightness(1.1);
        }
        
        .download-btn:focus {
            outline: 3px solid var(--accent-color);
            outline-offset: 2px;
        }
        
        /* Autocomplete Styles */
        .autocomplete-container { 
            position: relative; 
            display: inline-block; 
            width: 100%; 
        }

        .suggestions-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 2px solid var(--secondary-color);
            border-top: none;
            border-radius: 0 0 12px 12px;
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: var(--shadow-lg);
            display: none;
        }

        .suggestion-item {
            padding: 12px 16px;
            cursor: pointer;
            border-bottom: 1px solid var(--border-color);
            transition: all 0.2s ease;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .suggestion-item:hover {
            background: #f0f9ff;
            border-left: 4px solid var(--secondary-color);
            transform: translateX(2px);
        }

        .suggestion-item:last-child {
            border-bottom: none;
        }

        .suggestion-eid {
            font-weight: 600;
            color: var(--primary-color);
            font-size: 14px;
        }

        .suggestion-name {
            color: var(--text-secondary);
            font-size: 13px;
        }

        /* Password change button styling */
        .password-change-btn {
            background: var(--warning-color);
            color: white;
            padding: 12px 24px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 20px;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
        }

        .password-change-btn:hover {
            background: #d97706;
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        /* Report and certificate styles */
        .report-section {
            margin-bottom: 30px;
        }

        .report-section h4 {
            color: var(--primary-color);
            margin-bottom: 20px;
            font-size: 18px;
            font-weight: 700;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--light-bg);
        }

        .report-card {
            background: var(--light-bg);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
            height: 100%;
        }

        .report-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
            background: white;
        }

        .report-icon {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .report-card h5 {
            color: var(--primary-color);
            margin-bottom: 10px;
            font-weight: 700;
        }

        .report-card p {
            color: var(--text-secondary);
            margin-bottom: 15px;
            font-size: 14px;
        }

        .stat-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(255, 255, 255, 0.7) 100%);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            border-left: 4px solid var(--accent-color);
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 10px;
            right: 10px;
            width: 30px;
            height: 30px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.3) 0%, transparent 70%);
            border-radius: 50%;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .stat-number {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
        }

        .table th, .table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .table th {
            background: var(--gradient-bg);
            font-weight: 600;
            color: white;
            text-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }

        .table-striped tbody tr:nth-child(odd) {
            background: var(--light-bg);
        }

        .table-bordered {
            border: 1px solid var(--border-color);
        }

        .btn {
            background: var(--gradient-bg);
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 12px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-md);
            filter: brightness(1.1);
        }

        .btn-primary { background: var(--primary-color); }
        .btn-success { background: var(--accent-color); }
        .btn-info { background: var(--secondary-color); }
        .btn-danger { background: var(--danger-color); }
        .btn-warning { background: var(--warning-color); }

        .btn-sm {
            padding: 6px 12px;
            font-size: 11px;
        }

        .row {
            display: flex;
            flex-wrap: wrap;
            margin: -10px;
        }

        /* Certificates & Reports Styling */
        .certificates-grid {
            margin-bottom: 40px;
        }
        
        .certificate-category {
            background: white;
            border-radius: 16px;
            padding: 32px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            margin-bottom: 32px;
        }
        
        .category-header {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid var(--light-bg);
        }
        
        .category-icon {
            width: 48px;
            height: 48px;
            background: var(--light-bg);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        .category-header h4 {
            color: var(--primary-color);
            font-size: 20px;
            font-weight: 700;
            margin: 0;
        }
        
        .certificates-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        
        .certificate-item {
            background: var(--light-bg);
            border-radius: 12px;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }
        
        .certificate-item:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            background: white;
        }
        
        .certificate-info {
            flex: 1;
        }
        
        .certificate-title {
            font-weight: 700;
            color: var(--text-primary);
            font-size: 16px;
            margin-bottom: 8px;
        }
        
        .certificate-meta {
            display: flex;
            gap: 16px;
            font-size: 14px;
            color: var(--text-secondary);
        }
        
        .cert-number, .cert-date {
            background: white;
            padding: 4px 8px;
            border-radius: 6px;
            border: 1px solid var(--border-color);
        }
        
        .certificate-actions {
            margin-left: 20px;
        }
        
        .cert-btn {
            background: var(--gradient-bg);
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .cert-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            text-decoration: none;
            color: white;
        }
        
        .empty-certificates {
            text-align: center;
            padding: 40px;
            color: var(--text-secondary);
        }
        
        .empty-icon {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }
        
        .reports-section {
            margin-bottom: 40px;
        }
        
        .section-subheader {
            margin-bottom: 32px;
            text-align: center;
        }
        
        .subheader-icon {
            width: 64px;
            height: 64px;
            background: var(--gradient-bg);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            margin: 0 auto 16px;
            color: white;
        }
        
        .section-subheader h4 {
            color: var(--primary-color);
            font-size: 24px;
            font-weight: 700;
            margin: 0 0 8px 0;
        }
        
        .section-subheader p {
            color: var(--text-secondary);
            font-size: 16px;
            margin: 0;
        }
        
        .reports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }
        
        .report-card.modern {
            background: white;
            border-radius: 20px;
            padding: 0;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            overflow: hidden;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .report-card.modern:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-xl);
        }
        
        .report-header {
            padding: 24px 24px 0;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        
        .report-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            margin-bottom: 16px;
        }
        
        .report-icon.vaccination {
            background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
        }
        
        .report-icon.education {
            background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
        }
        
        .report-icon.medical {
            background: linear-gradient(135deg, #ef4444 0%, #f87171 100%);
        }
        
        .report-icon.employment {
            background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
        }
        
        .report-badge {
            background: var(--light-bg);
            color: var(--text-secondary);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .report-content {
            padding: 0 24px 24px;
        }
        
        .report-content h5 {
            color: var(--text-primary);
            font-size: 18px;
            font-weight: 700;
            margin: 0 0 12px 0;
        }
        
        .report-content p {
            color: var(--text-secondary);
            font-size: 14px;
            line-height: 1.6;
            margin: 0;
        }
        
        .report-footer {
            padding: 20px 24px;
            background: var(--light-bg);
            border-top: 1px solid var(--border-color);
        }
        
        .report-btn {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .report-btn.success {
            background: linear-gradient(135deg, #10b981 0%, #34d399 100%);
            color: white;
        }
        
        .report-btn.info {
            background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
            color: white;
        }
        
        .report-btn.danger {
            background: linear-gradient(135deg, #ef4444 0%, #f87171 100%);
            color: white;
        }
        
        .report-btn.warning {
            background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
            color: white;
        }
        
        .report-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            text-decoration: none;
            color: white;
        }
        
        .btn-icon {
            font-size: 16px;
        }
        
        .stats-overview {
            margin-bottom: 40px;
        }
        
        .stats-mini-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .mini-stat {
            background: white;
            border-radius: 16px;
            padding: 24px;
            text-align: center;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }
        
        .mini-stat:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
        }
        
        .mini-stat-icon {
            font-size: 32px;
            margin-bottom: 12px;
        }
        
        .mini-stat-number {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 8px;
        }
        
        .mini-stat-label {
            color: var(--text-secondary);
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Modern Card Styling for All Sections */
        .info-card.modern {
            background: white;
            border-radius: 16px;
            padding: 0;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            overflow: hidden;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .info-card.modern:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-xl);
        }
        
        .card-header {
            padding: 20px 20px 0;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        
        .card-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
            margin-bottom: 12px;
        }
        
        .card-icon.personal { background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); }
        .card-icon.identity { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); }
        .card-icon.document { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .card-icon.birth { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        .card-icon.gender { background: linear-gradient(135deg, #ec4899 0%, #be185d 100%); }
        .card-icon.address { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
        
        .card-badge {
            background: var(--light-bg);
            color: var(--text-secondary);
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .card-content {
            padding: 0 20px 20px;
        }
        
        .card-content .info-label {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        
        .card-content .info-value {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
            line-height: 1.4;
        }
        
        /* QR Section Styling */
        .qr-section {
            margin-bottom: 40px;
        }
        
        .qr-card {
            background: white;
            border-radius: 20px;
            padding: 32px;
            box-shadow: var(--shadow-lg);
            border: 1px solid var(--border-color);
            display: flex;
            gap: 32px;
            align-items: center;
        }
        
        .qr-image {
            flex-shrink: 0;
        }
        
        .qr-image img {
            width: 160px;
            height: 160px;
            border: 2px solid var(--primary-color);
            border-radius: 16px;
            padding: 16px;
            background: white;
            box-shadow: var(--shadow-md);
        }
        
        .qr-info {
            flex: 1;
        }
        
        .qr-info h5 {
            color: var(--primary-color);
            font-size: 20px;
            font-weight: 700;
            margin: 0 0 12px 0;
        }
        
        .qr-info p {
            color: var(--text-secondary);
            line-height: 1.6;
            margin: 0 0 20px 0;
        }
        
        .qr-actions {
            display: flex;
            gap: 12px;
            opacity: 0;
            transform: translateY(15px);
            animation: slideInActions 0.5s ease-out 0.6s forwards;
        }

        @keyframes slideInActions {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .qr-btn {
            background: var(--gradient-bg);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            transform: scale(1);
            position: relative;
            overflow: hidden;
        }

        .qr-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .qr-btn:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .qr-btn:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: var(--shadow-md);
        }

        .qr-btn:active {
            transform: translateY(-1px) scale(0.98);
            transition: all 0.1s ease;
        }
        
        .qr-btn.secondary {
            background: var(--light-bg);
            color: var(--primary-color);
        }

        .qr-btn.secondary:hover {
            background: var(--primary-color);
            color: white;
        }
        
        /* Quick Actions Section */
        .quick-actions-section {
            margin-bottom: 40px;
        }
        
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .action-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            display: flex;
            align-items: center;
            gap: 16px;
            text-decoration: none;
            color: var(--text-primary);
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            opacity: 0;
            transform: translateY(20px) scale(0.95);
            animation: slideInAction 0.5s ease-out forwards;
        }

        @keyframes slideInAction {
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Stagger action card animations */
        .action-card:nth-child(1) { animation-delay: 0.1s; }
        .action-card:nth-child(2) { animation-delay: 0.2s; }
        .action-card:nth-child(3) { animation-delay: 0.3s; }
        .action-card:nth-child(4) { animation-delay: 0.4s; }
        
        .action-card:hover {
            transform: translateY(-6px) scale(1.02);
            box-shadow: var(--shadow-lg);
            text-decoration: none;
            color: var(--text-primary);
        }

        .action-card:hover .action-icon {
            transform: scale(1.15) rotate(10deg);
        }

        .action-card:hover .action-title {
            color: var(--primary-color);
        }
        
        .action-card.secondary {
            background: var(--light-bg);
        }
        
        .action-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: white;
            flex-shrink: 0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .action-icon.activities { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        .action-icon.vaccination { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .action-icon.career { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); }
        .action-icon.profile { background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%); }
        
        .action-content {
            flex: 1;
        }
        
        .action-content h5 {
            color: var(--primary-color);
            font-size: 16px;
            font-weight: 700;
            margin: 0 0 6px 0;
        }
        
        .action-content p {
            color: var(--text-secondary);
            font-size: 14px;
            margin: 0;
        }
        
        .action-arrow {
            color: var(--primary-color);
            font-size: 18px;
            font-weight: bold;
            opacity: 0.7;
            transition: all 0.3s ease;
        }
        
        .action-card:hover .action-arrow {
            transform: translateX(4px);
            opacity: 1;
        }

        /* Loading state animation */
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(59, 130, 246, 0.3);
            border-radius: 50%;
            border-top-color: var(--primary-color);
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* Page entrance animation */
        .page-enter {
            animation: pageEnter 0.8s ease-out;
        }

        @keyframes pageEnter {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }

        /* Animation utilities */
        .animate-on-scroll {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease-out;
        }

        .animate-on-scroll.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* Reduce motion for accessibility */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
                scroll-behavior: auto !important;
            }
        }
        
        /* Records Grid Styling */
        .records-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 24px;
            margin-bottom: 40px;
        }
        
        .record-card {
            background: white;
            border-radius: 16px;
            padding: 0;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            opacity: 0;
            transform: translateY(30px) scale(0.98);
            animation: slideInRecord 0.6s ease-out forwards;
        }

        @keyframes slideInRecord {
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        /* Stagger record card animations */
        .record-card:nth-child(1) { animation-delay: 0.2s; }
        .record-card:nth-child(2) { animation-delay: 0.3s; }
        .record-card:nth-child(3) { animation-delay: 0.4s; }
        .record-card:nth-child(4) { animation-delay: 0.5s; }
        
        .record-card:hover {
            transform: translateY(-6px) scale(1.02);
            box-shadow: var(--shadow-xl);
        }

        .record-card:hover .record-icon {
            transform: scale(1.1) rotate(5deg);
        }

        .record-card:hover .record-badge {
            transform: scale(1.05);
        }
        
        .record-header {
            padding: 20px 20px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .record-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: white;
            flex-shrink: 0;
        }
        
        .record-icon.medical { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
        .record-icon.vaccination { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .record-icon.education { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); }
        .record-icon.employment { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        
        .record-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .record-badge.medical { background: #fee2e2; color: #991b1b; }
        .record-badge.vaccination { background: #d1fae5; color: #065f46; }
        .record-badge.education { background: #dbeafe; color: #1e40af; }
        .record-badge.employment { background: #fef3c7; color: #92400e; }
        .record-badge.success { background: #d1fae5; color: #065f46; }
        .record-badge.warning { background: #fef3c7; color: #92400e; }
        
        .record-content {
            padding: 0 20px 20px;
        }
        
        .record-date {
            color: #64748b;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 8px;
            display: block;
            background: #f1f5f9;
            padding: 6px 12px;
            border-radius: 8px;
            border-left: 3px solid var(--primary-color);
        }
        
        .record-content h5 {
            color: var(--primary-color);
            font-size: 16px;
            font-weight: 700;
            margin: 0 0 16px 0;
        }
        
        .record-details {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .detail-item {
            display: flex;
            gap: 8px;
            align-items: flex-start;
        }
        
        .detail-label {
            color: var(--text-secondary);
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            min-width: 80px;
            flex-shrink: 0;
        }
        
        .detail-value {
            color: var(--text-primary);
            font-size: 14px;
            font-weight: 500;
            flex: 1;
        }
        
        .record-footer {
            padding: 16px 20px;
            background: var(--light-bg);
            border-top: 1px solid var(--border-color);
        }
        
        .record-btn {
            width: 100%;
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.3s ease;
            color: white;
        }
        
        .record-btn.medical { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
        .record-btn.vaccination { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .record-btn.education { background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%); }
        .record-btn.employment { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        
        .record-btn:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-sm);
        }
        
        /* Summary Cards */
        .medical-summary, .vaccination-summary, .education-summary, .employment-summary {
            margin-bottom: 32px;
        }
        
        .summary-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .summary-icon {
            width: 56px;
            height: 56px;
            background: var(--gradient-bg);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }
        
        .summary-content {
            flex: 1;
        }
        
        .summary-content h4 {
            color: var(--primary-color);
            font-size: 18px;
            font-weight: 700;
            margin: 0 0 6px 0;
        }
        
        .summary-content p {
            color: var(--text-secondary);
            font-size: 14px;
            margin: 0;
        }
        
        .summary-badge {
            padding: 8px 16px;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .summary-badge.success { background: #d1fae5; color: #065f46; }
        .summary-badge.medical { background: #fee2e2; color: #991b1b; }
        .summary-badge.vaccination { background: #d1fae5; color: #065f46; }
        .summary-badge.education { background: #dbeafe; color: #1e40af; }
        .summary-badge.employment { background: #fef3c7; color: #92400e; }
        
        /* Modern Empty States */
        .empty-state-modern {
            background: white;
            border-radius: 20px;
            padding: 48px 32px;
            text-align: center;
            border: 2px dashed var(--border-color);
            box-shadow: var(--shadow-sm);
        }
        
        .empty-icon {
            width: 80px;
            height: 80px;
            background: var(--light-bg);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin: 0 auto 20px;
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
        }
        
        .empty-state-modern h4 {
            color: var(--primary-color);
            font-size: 20px;
            font-weight: 700;
            margin: 0 0 12px 0;
        }
        
        .empty-state-modern p {
            color: var(--text-secondary);
            font-size: 16px;
            line-height: 1.6;
            margin: 0 0 24px 0;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .empty-actions {
            display: flex;
            justify-content: center;
        }
        
        .empty-btn {
            background: var(--gradient-bg);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .empty-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .col-md-3, .col-md-6 {
            padding: 10px;
        }

        .col-md-3 { flex: 0 0 25%; }
        .col-md-6 { flex: 0 0 50%; }

        /* Modal Styles for Detailed Views */
        .detail-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            animation: modalFadeIn 0.3s ease;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }

        .modal-content {
            background: white;
            border-radius: 20px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow: hidden;
            box-shadow: var(--shadow-xl);
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            padding: 24px 32px;
            background: var(--gradient-bg);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-content.medical .modal-header {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }

        .modal-content.vaccination .modal-header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .modal-content.education .modal-header {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        }

        .modal-content.employment .modal-header {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }

        .modal-header h3 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
        }

        .close-btn {
            background: none;
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            padding: 0;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .close-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: rotate(90deg);
        }

        .modal-body {
            padding: 32px;
            font-size: 16px;
            line-height: 1.6;
            color: var(--text-primary);
        }

        .modal-body p {
            margin-bottom: 16px;
        }

        .modal-body strong {
            color: var(--primary-color);
        }

        .modal-footer {
            padding: 24px 32px;
            background: var(--light-bg);
            border-top: 1px solid var(--border-color);
            display: flex;
            gap: 16px;
            justify-content: flex-end;
        }

        .btn-primary, .btn-secondary {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--gradient-bg);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-secondary {
            background: white;
            color: var(--primary-color);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background: var(--light-bg);
        }

        /* Notification System */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border-radius: 12px;
            padding: 16px 20px;
            box-shadow: var(--shadow-lg);
            border-left: 4px solid var(--primary-color);
            z-index: 10001;
            transform: translateX(400px);
            transition: all 0.3s ease;
            max-width: 350px;
        }

        .notification.show {
            transform: translateX(0);
        }

        .notification.success {
            border-left-color: var(--accent-color);
        }

        .notification-content {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .notification-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            flex-shrink: 0;
        }

        .notification.success .notification-icon {
            background: var(--accent-color);
        }

        .notification-message {
            color: var(--text-primary);
            font-weight: 600;
            font-size: 14px;
        }

        /* Share Modal */
        .share-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            animation: modalFadeIn 0.3s ease;
        }

        .modal-content.share {
            background: white;
            border-radius: 16px;
            width: 90%;
            max-width: 500px;
            box-shadow: var(--shadow-xl);
        }

        .modal-content.share .modal-header {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
        }

        @media (max-width: 768px) {
            .col-md-3, .col-md-6 {
                flex: 0 0 100%;
            }
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .main-container {
                padding: 20px;
            }
            
            body.has-citizen-sidebar .main-container {
                padding: 20px 20px 20px 32px;
            }
            
            .page-header, .stats-grid, .content-section, .info-grid {
                padding: 24px;
            }
            
            .stats-grid, .tab-navigation, .content-section, .info-grid {
                margin-left: 24px;
                margin-right: 24px;
            }
            
            .tab-navigation {
                padding: 8px 24px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
            
            .info-grid {
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            }
            
            .reports-grid {
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            }
            
            .stats-mini-grid {
                grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            }
        }
        
        @media (max-width: 768px) {
            body.has-citizen-sidebar .main-container {
                padding: 16px 16px 16px 24px;
            }
            
            .page-header, .stats-grid, .content-section, .info-grid {
                padding: 20px;
            }
            
            .stats-grid, .tab-navigation, .content-section, .info-grid {
                margin-left: 16px;
                margin-right: 16px;
            }
            
            .tab-navigation {
                padding: 8px 20px;
            }
            
            .reports-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            
            .stats-mini-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 16px;
            }
            
            .certificate-category {
                padding: 20px;
            }
            
            .certificate-item {
                flex-direction: column;
                gap: 16px;
                text-align: center;
            }
            
            .certificate-actions {
                margin-left: 0;
            }
            
            .header-content {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 20px;
            }
            
            .citizen-avatar {
                width: 64px;
                height: 64px;
                font-size: 28px;
                margin: 0 auto;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 16px;
            }
            
            .tab-navigation {
                padding: 4px;
            }
            
            .tabs {
                flex-direction: column;
                gap: 2px;
            }
            
            .tab {
                text-align: center;
                padding: 12px 16px;
            }
            
            .content-section {
                padding: 24px 20px;
            }
            
            .section-header {
                flex-direction: column;
                text-align: center;
                gap: 8px;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            
            .action-grid {
                grid-template-columns: 1fr;
            }
            
            .record-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }
        }

        /* Sidebar Integration Styles */
        body.has-citizen-sidebar {
            padding-left: 280px;
            transition: padding-left 0.3s ease;
        }
        
        body.citizen-sidebar-collapsed {
            padding-left: 60px;
        }
        
        body.has-citizen-sidebar .main-container {
            margin: 0;
            padding: 24px 24px 24px 40px; /* Added left padding for gap from sidebar */
            max-width: none;
            height: auto;
            min-height: auto;
        }
        
        /* Mobile adjustments */
        @media (max-width: 768px) {
            body.has-citizen-sidebar {
                padding-left: 0 !important;
                padding-top: 70px;
            }
            
            body.has-citizen-sidebar .main-container {
                padding: 16px;
                margin: 0;
                height: auto;
                min-height: auto;
            }
        }

        
    </style>
</head>
<body>
    <?php include 'includes/citizen_sidebar.php'; ?>
    
    <div class="main-container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-content">
                <div class="citizen-avatar">
                    👤
                </div>
                <div class="header-info">
                    <h1><?= htmlspecialchars($citizen['FirstName'] . ' ' . $citizen['LastName']) ?></h1>
                    <div class="citizen-meta">
                        <span class="meta-item">📧 eID: <?= htmlspecialchars($citizen['Citizen_eID']) ?></span>
                        <span class="meta-item">� Age: <?= $age ?> years</span>
                        <span class="meta-item">👤 <?= htmlspecialchars($citizen['Gender']) ?></span>
                    </div>
                    <div class="badge badge-info">Active Citizen</div>
                </div>
                <div class="header-actions">
                    <?php include 'notification_component.php'; ?>
                </div>
            </div>
        </div>

        <!-- Quick Statistics -->
        <div class="stats-grid">
            <?php
            // Get counts for each type of record
            $vacCount = $conn->query("SELECT COUNT(*) as count FROM VaccinationRecords WHERE CitizenID = $citizenID")->fetch_assoc()['count'];
            $eduCount = $conn->query("SELECT COUNT(*) as count FROM EducationRecords WHERE CitizenID = $citizenID")->fetch_assoc()['count'];
            $medCount = $conn->query("SELECT COUNT(*) as count FROM MedicalRecords WHERE citizenID = $citizenID")->fetch_assoc()['count'];
            $empCount = $conn->query("SELECT COUNT(*) as count FROM EmploymentRecords WHERE CitizenID = $citizenID")->fetch_assoc()['count'];
            ?>
            <div class="stat-card">
                <div class="stat-icon">💉</div>
                <div class="stat-number"><?= $vacCount ?></div>
                <div class="stat-label">Vaccinations</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🏥</div>
                <div class="stat-number"><?= $medCount ?></div>
                <div class="stat-label">Medical Records</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🎓</div>
                <div class="stat-number"><?= $eduCount ?></div>
                <div class="stat-label">Education Records</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💼</div>
                <div class="stat-number"><?= $empCount ?></div>
                <div class="stat-label">Employment Records</div>
            </div>
        </div>

        <!-- Tab Navigation -->
        <div class="tab-navigation">
            <div class="tabs">
                <button class="tab" onclick="showTab('basic')">📋 Basic Information</button>
                <button class="tab" onclick="showTab('medical')">🏥 Medical Records</button>
                <button class="tab" onclick="showTab('vaccination')">💉 Vaccination History</button>
                <button class="tab" onclick="showTab('education')">🎓 Education Records</button>
                <button class="tab" onclick="showTab('employment')">💼 Employment History</button>
                <button class="tab" onclick="showTab('certificates')">📜 Certificates & Reports</button>
            </div>
        </div>
        <!-- Certificates & Reports Tab -->
        <div id="certificates" class="tab-content">
            <div class="content-section">
                <div class="section-header">
                    <div class="section-icon">📜</div>
                    <h3>My Certificates & Reports</h3>
                </div>
                
                <!-- Birth Certificates Section -->
                <div class="certificates-grid">
                    <div class="certificate-category">
                        <div class="category-header">
                            <div class="category-icon">🆔</div>
                            <h4>Birth Certificates</h4>
                        </div>
                        <?php
                        $certStmt = $conn->prepare("SELECT BirthCertID, CertificateNumber, ChildFullName, DateOfBirth FROM BirthCertificates WHERE CitizenID = ?");
                        $certStmt->bind_param('i', $citizenID);
                        $certStmt->execute();
                        $certs = $certStmt->get_result();
                        ?>
                        <?php if ($certs->num_rows > 0): ?>
                            <div class="certificates-list">
                                <?php while ($row = $certs->fetch_assoc()): ?>
                                    <div class="certificate-item">
                                        <div class="certificate-info">
                                            <div class="certificate-title"><?= htmlspecialchars($row['ChildFullName']) ?></div>
                                            <div class="certificate-meta">
                                                <span class="cert-number">Certificate: <?= htmlspecialchars($row['CertificateNumber']) ?></span>
                                                <span class="cert-date">DOB: <?= htmlspecialchars($row['DateOfBirth']) ?></span>
                                            </div>
                                        </div>
                                        <div class="certificate-actions">
                                            <a href="download_certificate.php?cert_id=<?= $row['BirthCertID'] ?>" class="cert-btn primary">
                                                📥 Download PDF
                                            </a>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-certificates">
                                <div class="empty-icon">📄</div>
                                <p>No birth certificates found</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Comprehensive Reports Section -->
                <div class="reports-section">
                    <div class="section-subheader">
                        <div class="subheader-icon">📊</div>
                        <h4>Comprehensive Reports</h4>
                        <p>Download detailed reports of your records</p>
                    </div>
                    
                    <div class="reports-grid">
                        <div class="report-card modern">
                            <div class="report-header">
                                <div class="report-icon vaccination">💉</div>
                                <div class="report-badge">Health</div>
                            </div>
                            <div class="report-content">
                                <h5>Vaccination Report</h5>
                                <p>Complete vaccination history and immunization records with dates and locations</p>
                            </div>
                            <div class="report-footer">
                                <a href="download_report.php?type=vaccination&citizen_id=<?= $citizenID ?>" class="report-btn success">
                                    <span class="btn-icon">📥</span>
                                    <span class="btn-text">Download Report</span>
                                </a>
                            </div>
                        </div>
                        
                        <div class="report-card modern">
                            <div class="report-header">
                                <div class="report-icon education">🎓</div>
                                <div class="report-badge">Education</div>
                            </div>
                            <div class="report-content">
                                <h5>Education Report</h5>
                                <p>Academic records, qualifications, degrees, and complete educational history</p>
                            </div>
                            <div class="report-footer">
                                <a href="download_report.php?type=education&citizen_id=<?= $citizenID ?>" class="report-btn info">
                                    <span class="btn-icon">📥</span>
                                    <span class="btn-text">Download Report</span>
                                </a>
                            </div>
                        </div>
                        
                        <div class="report-card modern">
                            <div class="report-header">
                                <div class="report-icon medical">🏥</div>
                                <div class="report-badge">Medical</div>
                            </div>
                            <div class="report-content">
                                <h5>Medical Report</h5>
                                <p>Complete medical history, diagnoses, treatments, and healthcare records</p>
                            </div>
                            <div class="report-footer">
                                <a href="download_report.php?type=medical&citizen_id=<?= $citizenID ?>" class="report-btn danger">
                                    <span class="btn-icon">📥</span>
                                    <span class="btn-text">Download Report</span>
                                </a>
                            </div>
                        </div>
                        
                        <div class="report-card modern">
                            <div class="report-header">
                                <div class="report-icon employment">💼</div>
                                <div class="report-badge">Career</div>
                            </div>
                            <div class="report-content">
                                <h5>Employment Report</h5>
                                <p>Employment history, job positions, work experience, and career timeline</p>
                            </div>
                            <div class="report-footer">
                                <a href="download_report.php?type=employment&citizen_id=<?= $citizenID ?>" class="report-btn warning">
                                    <span class="btn-icon">📥</span>
                                    <span class="btn-text">Download Report</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Statistics Section -->
                <div class="stats-overview">
                    <div class="section-subheader">
                        <div class="subheader-icon">📈</div>
                        <h4>Records Overview</h4>
                        <p>Quick summary of your records</p>
                    </div>
                    
                    <div class="stats-mini-grid">
                        <?php
                        // Get counts for each type of record
                        $vacCount = $conn->query("SELECT COUNT(*) as count FROM VaccinationRecords WHERE CitizenID = $citizenID")->fetch_assoc()['count'];
                        $eduCount = $conn->query("SELECT COUNT(*) as count FROM EducationRecords WHERE CitizenID = $citizenID")->fetch_assoc()['count'];
                        $medCount = $conn->query("SELECT COUNT(*) as count FROM MedicalRecords WHERE citizenID = $citizenID")->fetch_assoc()['count'];
                        $empCount = $conn->query("SELECT COUNT(*) as count FROM EmploymentRecords WHERE CitizenID = $citizenID")->fetch_assoc()['count'];
                        ?>
                        <div class="mini-stat">
                            <div class="mini-stat-icon">💉</div>
                            <div class="mini-stat-number"><?= $vacCount ?></div>
                            <div class="mini-stat-label">Vaccinations</div>
                        </div>
                        <div class="mini-stat">
                            <div class="mini-stat-icon">🎓</div>
                            <div class="mini-stat-number"><?= $eduCount ?></div>
                            <div class="mini-stat-label">Education Records</div>
                        </div>
                        <div class="mini-stat">
                            <div class="mini-stat-icon">🏥</div>
                            <div class="mini-stat-number"><?= $medCount ?></div>
                            <div class="mini-stat-label">Medical Records</div>
                        </div>
                        <div class="mini-stat">
                            <div class="mini-stat-icon">💼</div>
                            <div class="mini-stat-number"><?= $empCount ?></div>
                            <div class="mini-stat-label">Job Records</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Basic Information Tab -->
        <div id="basic" class="tab-content">
            <div class="content-section">
                <div class="section-header">
                    <div class="section-icon">�</div>
                    <h3>Personal Information</h3>
                </div>
                
                <div class="info-grid">
                    <div class="info-card modern">
                        <div class="card-header">
                            <div class="card-icon personal">👤</div>
                            <div class="card-badge">Identity</div>
                        </div>
                        <div class="card-content">
                            <div class="info-label">Full Name</div>
                            <div class="info-value"><?= htmlspecialchars($citizen['FirstName'] . ' ' . $citizen['LastName']) ?></div>
                        </div>
                    </div>
                    
                    <div class="info-card modern">
                        <div class="card-header">
                            <div class="card-icon identity">🆔</div>
                            <div class="card-badge">eID</div>
                        </div>
                        <div class="card-content">
                            <div class="info-label">Citizen eID</div>
                            <div class="info-value"><?= htmlspecialchars($citizen['Citizen_eID']) ?></div>
                        </div>
                    </div>
                    
                    <div class="info-card modern">
                        <div class="card-header">
                            <div class="card-icon document">📄</div>
                            <div class="card-badge">Official</div>
                        </div>
                        <div class="card-content">
                            <div class="info-label">National ID (NIC)</div>
                            <div class="info-value"><?= htmlspecialchars($citizen['NIC'] ?? 'Not Set') ?></div>
                        </div>
                    </div>
                    
                    <div class="info-card modern">
                        <div class="card-header">
                            <div class="card-icon birth">🎂</div>
                            <div class="card-badge">Birth</div>
                        </div>
                        <div class="card-content">
                            <div class="info-label">Date of Birth</div>
                            <div class="info-value"><?= $citizen['DOB'] ?> (<?= $age ?> years old)</div>
                        </div>
                    </div>
                    
                    <div class="info-card modern">
                        <div class="card-header">
                            <div class="card-icon gender">⚧</div>
                            <div class="card-badge">Gender</div>
                        </div>
                        <div class="card-content">
                            <div class="info-label">Gender</div>
                            <div class="info-value"><?= htmlspecialchars($citizen['Gender']) ?></div>
                        </div>
                    </div>
                    
                    <div class="info-card modern">
                        <div class="card-header">
                            <div class="card-icon address">📍</div>
                            <div class="card-badge">Location</div>
                        </div>
                        <div class="card-content">
                            <div class="info-label">Address</div>
                            <div class="info-value"><?= htmlspecialchars($citizen['Address']) ?></div>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($citizen['QRCodePath']) && file_exists($citizen['QRCodePath'])): ?>
                <div class="qr-section">
                    <div class="section-subheader">
                        <div class="subheader-icon">📱</div>
                        <h4>My Digital Identity</h4>
                        <p>Your unique QR code for easy identification</p>
                    </div>
                    
                    <div class="qr-card">
                        <div class="qr-image">
                            <img src="<?= htmlspecialchars($citizen['QRCodePath']) ?>" alt="My QR Code">
                        </div>
                        <div class="qr-info">
                            <h5>Personal QR Code</h5>
                            <p>This QR code links to your public profile and can be used for quick identification by authorized personnel.</p>
                            <div class="qr-actions">
                                <button class="qr-btn" onclick="downloadQRCode()">📥 Download</button>
                                <button class="qr-btn secondary" onclick="shareQRCode()">📤 Share</button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="quick-actions-section">
                    <div class="section-subheader">
                        <div class="subheader-icon">⚡</div>
                        <h4>Quick Actions</h4>
                        <p>Access your services and information</p>
                    </div>
                    
                    <div class="actions-grid">
                        <a href="citizen_activities_citizen.php" class="action-card">
                            <div class="action-icon activities">🏆</div>
                            <div class="action-content">
                                <h5>My Activities</h5>
                                <p>View your participated activities and achievements</p>
                            </div>
                            <div class="action-arrow">→</div>
                        </a>
                        
                        <a href="citizen_vaccination.php" class="action-card">
                            <div class="action-icon vaccination">💉</div>
                            <div class="action-content">
                                <h5>My Vaccinations</h5>
                                <p>Check your vaccination history and schedule</p>
                            </div>
                            <div class="action-arrow">→</div>
                        </a>
                        
                        <a href="career_guidance_form.php" class="action-card">
                            <div class="action-icon career">🎯</div>
                            <div class="action-content">
                                <h5>Career Guidance</h5>
                                <p>Get professional career counseling and advice</p>
                            </div>
                            <div class="action-arrow">→</div>
                        </a>
                        
                        <a href="view_citizen.php?citizen_id=<?= $citizenID ?>" class="action-card secondary">
                            <div class="action-icon profile">📋</div>
                            <div class="action-content">
                                <h5>Full Profile</h5>
                                <p>View your complete citizen profile details</p>
                            </div>
                            <div class="action-arrow">→</div>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Medical Records Tab -->
        <div id="medical" class="tab-content">
            <div class="content-section">
                <div class="section-header">
                    <div class="section-icon">🏥</div>
                    <h3>Medical History</h3>
                </div>
                
                <?php if ($medicalRecords && $medicalRecords->num_rows > 0): ?>
                    <div class="medical-summary">
                        <div class="summary-card">
                            <div class="summary-icon">📊</div>
                            <div class="summary-content">
                                <h4>Medical Summary</h4>
                                <p>Total Records: <?= $medicalRecords->num_rows ?> medical entries</p>
                            </div>
                            <div class="summary-badge medical">Health Record</div>
                        </div>
                    </div>
                    
                    <div class="records-grid">
                        <?php while($record = $medicalRecords->fetch_assoc()): ?>
                            <div class="record-card medical">
                                <div class="record-header">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <div class="record-icon medical">🏥</div>
                                        <div class="record-date">
                                            <?= htmlspecialchars($record['recordDate']) ?>
                                            <span style="color: #64748b; font-size: 11px; margin-left: 8px;">
                                                (<?= $record['DaysAgo'] ?> days ago)
                                            </span>
                                        </div>
                                    </div>
                                    <div class="record-badge <?= $record['RecordPeriod'] == 'Recent' ? 'success' : ($record['RecordPeriod'] == 'This Year' ? 'warning' : 'medical') ?>">
                                        <?= htmlspecialchars($record['RecordPeriod']) ?>
                                    </div>
                                </div>
                                <div class="record-content">
                                    <h5><?= htmlspecialchars($record['diagnosis'] ?? 'Medical Record') ?></h5>
                                    <div class="record-details">
                                        <div class="detail-item">
                                            <span class="detail-label">Hospital:</span>
                                            <span class="detail-value"><?= htmlspecialchars($record['hospitalName'] ?? 'Not specified') ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Treatment:</span>
                                            <span class="detail-value"><?= htmlspecialchars($record['treatment'] ?? 'No treatment recorded') ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Doctor:</span>
                                            <span class="detail-value"><?= htmlspecialchars($record['doctorName'] ?? 'Unknown') ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Record Age:</span>
                                            <span class="detail-value">
                                                <?php 
                                                $days = $record['DaysAgo'];
                                                if ($days < 30) {
                                                    echo "$days days";
                                                } elseif ($days < 365) {
                                                    $months = floor($days / 30);
                                                    echo "$months month" . ($months > 1 ? 's' : '');
                                                } else {
                                                    $years = floor($days / 365);
                                                    echo "$years year" . ($years > 1 ? 's' : '');
                                                }
                                                ?>
                                            </span>
                                        </div>
                                        <?php if ($record['diagnosis']): ?>
                                        <div class="detail-item">
                                            <span class="detail-label">Diagnosis Details:</span>
                                            <span class="detail-value"><?= htmlspecialchars(substr($record['diagnosis'], 0, 100)) ?><?= strlen($record['diagnosis']) > 100 ? '...' : '' ?></span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state-modern">
                        <div class="empty-icon">🏥</div>
                        <h4>No Medical Records</h4>
                        <p>No medical history has been recorded yet. Your medical records will appear here when healthcare providers add them to the system.</p>
                        <div class="empty-actions">
                            <button class="empty-btn" onclick="alert('Connect with healthcare provider feature coming soon!')">Connect with Healthcare Provider</button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Vaccination Records Tab -->
        <div id="vaccination" class="tab-content">
            <div class="content-section">
                <div class="section-header">
                    <div class="section-icon">💉</div>
                    <h3>Vaccination History</h3>
                </div>
                
                <?php if ($vaccinationRecords && $vaccinationRecords->num_rows > 0): ?>
                    <div class="vaccination-summary">
                        <div class="summary-card vaccination">
                            <div class="summary-icon">📊</div>
                            <div class="summary-content">
                                <h4>Vaccination Summary</h4>
                                <p>Total Vaccinations: <?= $vaccinationRecords->num_rows ?> doses received</p>
                            </div>
                            <div class="summary-badge success">Immunized</div>
                        </div>
                    </div>
                    
                    <div class="records-grid">
                        <?php 
                        $vaccinationRecords->data_seek(0);
                        while($record = $vaccinationRecords->fetch_assoc()): 
                        ?>
                            <div class="record-card vaccination">
                                <div class="record-header">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <div class="record-icon vaccination">💉</div>
                                        <div class="record-date">
                                            <?= htmlspecialchars($record['DateAdministered']) ?>
                                            <span style="color: #64748b; font-size: 11px; margin-left: 8px;">
                                                (<?= $record['DaysAgo'] ?> days ago)
                                            </span>
                                        </div>
                                    </div>
                                    <div class="record-badge <?= $record['VaccineStatus'] == 'Recent' ? 'success' : ($record['VaccineStatus'] == 'Valid' ? 'vaccination' : 'warning') ?>">
                                        Dose <?= $record['DoseNumber'] ?> - <?= $record['VaccineStatus'] ?>
                                    </div>
                                </div>
                                <div class="record-content">
                                    <h5><?= htmlspecialchars($record['VaccineName']) ?></h5>
                                    <div class="record-details">
                                        <?php if ($record['VaccineDescription']): ?>
                                        <div class="detail-item">
                                            <span class="detail-label">Description:</span>
                                            <span class="detail-value"><?= htmlspecialchars($record['VaccineDescription']) ?></span>
                                        </div>
                                        <?php endif; ?>
                                        <div class="detail-item">
                                            <span class="detail-label">Administered By:</span>
                                            <span class="detail-value"><?= htmlspecialchars($record['AdministeredBy'] ?? 'Not specified') ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Dose Number:</span>
                                            <span class="detail-value"><?= htmlspecialchars($record['DoseNumber']) ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Status:</span>
                                            <span class="detail-value"><?= htmlspecialchars($record['VaccineStatus']) ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Time Since:</span>
                                            <span class="detail-value">
                                                <?php 
                                                $days = $record['DaysAgo'];
                                                if ($days < 30) {
                                                    echo "$days days";
                                                } elseif ($days < 365) {
                                                    $months = floor($days / 30);
                                                    echo "$months month" . ($months > 1 ? 's' : '');
                                                } else {
                                                    $years = floor($days / 365);
                                                    echo "$years year" . ($years > 1 ? 's' : '');
                                                }
                                                ?>
                                            </span>
                                        </div>
                                        <?php if ($record['Notes']): ?>
                                        <div class="detail-item">
                                            <span class="detail-label">Notes:</span>
                                            <span class="detail-value"><?= htmlspecialchars($record['Notes']) ?></span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state-modern">
                        <div class="empty-icon">💉</div>
                        <h4>No Vaccination Records</h4>
                        <p>No vaccination history has been recorded yet. Your vaccination records will appear here when healthcare providers update them.</p>
                        <div class="empty-actions">
                            <button class="empty-btn" onclick="alert('Schedule vaccination feature coming soon!')">Schedule Vaccination</button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Education Records Tab -->
        <div id="education" class="tab-content">
            <div class="content-section">
                <div class="section-header">
                    <div class="section-icon">🎓</div>
                    <h3>Education History</h3>
                </div>
                
                <?php if ($educationRecords && $educationRecords->num_rows > 0): ?>
                    <div class="education-summary">
                        <div class="summary-card education">
                            <div class="summary-icon">📊</div>
                            <div class="summary-content">
                                <h4>Education Summary</h4>
                                <p>Total Records: <?= $educationRecords->num_rows ?> academic entries</p>
                            </div>
                            <div class="summary-badge education">Academic</div>
                        </div>
                    </div>
                    
                    <div class="records-grid">
                        <?php while($record = $educationRecords->fetch_assoc()): ?>
                            <div class="record-card education">
                                <div class="record-header">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <div class="record-icon education">🎓</div>
                                        <div class="record-date">
                                            <?= htmlspecialchars($record['RecordDate'] ?? 'Date not set') ?>
                                            <?php if ($record['YearsAgo'] > 0): ?>
                                            <span style="color: #64748b; font-size: 11px; margin-left: 8px;">
                                                (<?= $record['YearsAgo'] ?> years ago)
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="record-badge <?= $record['EducationLevel'] == 'Advanced Level' ? 'success' : ($record['EducationLevel'] == 'Ordinary Level' ? 'education' : 'warning') ?>">
                                        <?= htmlspecialchars($record['EducationLevel']) ?>
                                    </div>
                                </div>
                                <div class="record-content">
                                    <h5><?= htmlspecialchars($record['SchoolName'] ?? 'Academic Record') ?></h5>
                                    <div class="record-details">
                                        <div class="detail-item">
                                            <span class="detail-label">Grade/Level:</span>
                                            <span class="detail-value"><?= htmlspecialchars($record['GradeLevel'] ?? 'Not specified') ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Education Level:</span>
                                            <span class="detail-value"><?= htmlspecialchars($record['EducationLevel']) ?></span>
                                        </div>
                                        <?php if ($record['ExamName']): ?>
                                        <div class="detail-item">
                                            <span class="detail-label">Exam:</span>
                                            <span class="detail-value"><?= htmlspecialchars($record['ExamName']) ?></span>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($record['Result']): ?>
                                        <div class="detail-item">
                                            <span class="detail-label">Result:</span>
                                            <span class="detail-value">
                                                <strong style="color: var(--accent-color);"><?= htmlspecialchars($record['Result']) ?></strong>
                                            </span>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($record['MarksObtained']): ?>
                                        <div class="detail-item">
                                            <span class="detail-label">Marks:</span>
                                            <span class="detail-value"><?= htmlspecialchars($record['MarksObtained']) ?></span>
                                        </div>
                                        <?php endif; ?>
                                        <div class="detail-item">
                                            <span class="detail-label">Record Age:</span>
                                            <span class="detail-value">
                                                <?= $record['YearsAgo'] > 0 ? $record['YearsAgo'] . ' year' . ($record['YearsAgo'] > 1 ? 's' : '') : 'Current year' ?>
                                            </span>
                                        </div>
                                        <?php if ($record['RegisteredByName']): ?>
                                        <div class="detail-item">
                                            <span class="detail-label">Registered By:</span>
                                            <span class="detail-value"><?= htmlspecialchars($record['RegisteredByName']) ?></span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state-modern">
                        <div class="empty-icon">🎓</div>
                        <h4>No Education Records</h4>
                        <p>No education history has been recorded yet. Your academic records will appear here when educational institutions add them to the system.</p>
                        <div class="empty-actions">
                            <button class="empty-btn" onclick="alert('Add education record feature coming soon!')">Add Education Record</button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Employment Records Tab -->
        <div id="employment" class="tab-content">
            <div class="content-section">
                <div class="section-header">
                    <div class="section-icon">💼</div>
                    <h3>Employment History</h3>
                </div>
                
                <?php if ($employmentRecords && $employmentRecords->num_rows > 0): ?>
                    <div class="employment-summary">
                        <div class="summary-card employment">
                            <div class="summary-icon">📊</div>
                            <div class="summary-content">
                                <h4>Employment Summary</h4>
                                <p>Total Records: <?= $employmentRecords->num_rows ?> job positions</p>
                            </div>
                            <div class="summary-badge employment">Career</div>
                        </div>
                    </div>
                    
                    <div class="records-grid">
                        <?php while($record = $employmentRecords->fetch_assoc()): ?>
                            <div class="record-card employment">
                                <div class="record-header">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <div class="record-icon employment">💼</div>
                                        <div class="record-date">
                                            <?= htmlspecialchars($record['StartDate']) ?> 
                                            <?= $record['EndDate'] ? '- ' . htmlspecialchars($record['EndDate']) : '- Present' ?>
                                        </div>
                                    </div>
                                    <div class="record-badge <?= $record['EmploymentStatus'] == 'Current' ? 'success' : ($record['EmploymentStatus'] == 'Recent' ? 'warning' : 'employment') ?>">
                                        <?= htmlspecialchars($record['EmploymentStatus']) ?>
                                    </div>
                                </div>
                                <div class="record-content">
                                    <h5><?= htmlspecialchars($record['JobTitle'] ?? 'Job Position') ?></h5>
                                    <div class="record-details">
                                        <div class="detail-item">
                                            <span class="detail-label">Company:</span>
                                            <span class="detail-value"><?= htmlspecialchars($record['CompanyName'] ?? 'Not specified') ?></span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Employment Status:</span>
                                            <span class="detail-value">
                                                <strong style="color: <?= $record['EmploymentStatus'] == 'Current' ? 'var(--accent-color)' : 'var(--text-secondary)' ?>;">
                                                    <?= htmlspecialchars($record['EmploymentStatus']) ?>
                                                </strong>
                                            </span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Duration:</span>
                                            <span class="detail-value"><?= htmlspecialchars($record['Duration']) ?></span>
                                        </div>
                                        <?php if ($record['Salary']): ?>
                                        <div class="detail-item">
                                            <span class="detail-label">Salary:</span>
                                            <span class="detail-value">
                                                <strong style="color: var(--accent-color);">LKR <?= number_format($record['Salary'], 2) ?></strong>
                                            </span>
                                        </div>
                                        <?php endif; ?>
                                        <div class="detail-item">
                                            <span class="detail-label">Experience:</span>
                                            <span class="detail-value">
                                                <?php 
                                                $totalDays = $record['DurationDays'];
                                                if ($totalDays < 30) {
                                                    echo "$totalDays days";
                                                } elseif ($totalDays < 365) {
                                                    $months = floor($totalDays / 30);
                                                    echo "$months month" . ($months > 1 ? 's' : '');
                                                } else {
                                                    $years = floor($totalDays / 365);
                                                    $remainingMonths = floor(($totalDays % 365) / 30);
                                                    echo "$years year" . ($years > 1 ? 's' : '');
                                                    if ($remainingMonths > 0) {
                                                        echo ", $remainingMonths month" . ($remainingMonths > 1 ? 's' : '');
                                                    }
                                                }
                                                ?>
                                            </span>
                                        </div>
                                        <?php if ($record['Verified']): ?>
                                        <div class="detail-item">
                                            <span class="detail-label">Verification:</span>
                                            <span class="detail-value">
                                                <span style="color: var(--accent-color); font-weight: 600;">✓ Verified</span>
                                            </span>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($record['RegisteredByName']): ?>
                                        <div class="detail-item">
                                            <span class="detail-label">Registered By:</span>
                                            <span class="detail-value"><?= htmlspecialchars($record['RegisteredByName']) ?></span>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state-modern">
                        <div class="empty-icon">💼</div>
                        <h4>No Employment Records</h4>
                        <p>No employment history has been recorded yet. Your work experience will appear here when employers add employment records.</p>
                        <div class="empty-actions">
                            <button class="empty-btn" onclick="alert('Job search feature coming soon!')">Find Jobs</button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tab contents
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => {
                content.classList.remove('active');
            });
            // Show selected tab content
            document.getElementById(tabName).classList.add('active');

            // Remove active class from all tabs
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Add active class to the corresponding tab button
            tabs.forEach(tab => {
                const btnTabName = tab.getAttribute('onclick').match(/showTab\('([^']+)'\)/)[1];
                if (btnTabName === tabName) {
                    tab.classList.add('active');
                }
            });
        }

        // Attach click handlers to tabs
        document.addEventListener('DOMContentLoaded', function() {
            const tabButtons = document.querySelectorAll('.tab');
            tabButtons.forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    const tabName = btn.getAttribute('onclick').match(/showTab\('([^']+)'\)/)[1];
                    showTab(tabName);
                    
                    // Update URL hash without reloading page
                    window.history.replaceState(null, null, '#' + tabName);
                });
            });
            
            // Handle initial tab selection based on URL hash or localStorage
            let initialTab = null;
            
            // Check URL hash first
            if (window.location.hash) {
                const hashTab = window.location.hash.substring(1);
                if (['basic', 'medical', 'vaccination', 'education', 'employment', 'certificates'].includes(hashTab)) {
                    initialTab = hashTab;
                }
            }
            
            // Check localStorage if no hash
            if (!initialTab) {
                const targetTab = localStorage.getItem('targetTab');
                if (targetTab && ['basic', 'medical', 'vaccination', 'education', 'employment', 'certificates'].includes(targetTab)) {
                    initialTab = targetTab;
                    localStorage.removeItem('targetTab'); // Clear after use
                }
            }
            
            // Default to basic if no specific tab requested
            if (!initialTab) {
                initialTab = 'basic';
            }
            
            // Show the initial tab (this will also handle the button highlighting)
            showTab(initialTab);
            
            // Update URL hash if not already set
            if (!window.location.hash || window.location.hash.substring(1) !== initialTab) {
                window.history.replaceState(null, null, '#' + initialTab);
            }
        });

        // QR Code functionality
        function downloadQRCode() {
            const qrImage = document.querySelector('.qr-image img');
            if (!qrImage) {
                alert('QR Code not found!');
                return;
            }

            // Create a canvas to draw the QR code
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            
            // Set canvas size to match the QR code image
            canvas.width = qrImage.naturalWidth || 300;
            canvas.height = qrImage.naturalHeight || 300;
            
            // Create a new image to ensure it's loaded
            const img = new Image();
            img.crossOrigin = 'anonymous';
            
            img.onload = function() {
                // Draw white background
                ctx.fillStyle = 'white';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                
                // Draw the QR code without any text overlay
                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                
                // Convert to blob and download
                canvas.toBlob(function(blob) {
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'NDMS_QR_Code_<?= htmlspecialchars($citizen["Citizen_eID"] ?? $citizen["CitizenID"]) ?>.png';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    window.URL.revokeObjectURL(url);
                    
                    // Show success message
                    showNotification('QR Code downloaded successfully!', 'success');
                }, 'image/png');
            };
            
            img.onerror = function() {
                // Fallback: create a simple download link
                const a = document.createElement('a');
                a.href = qrImage.src;
                a.download = 'NDMS_QR_Code_<?= htmlspecialchars($citizen["Citizen_eID"] ?? $citizen["CitizenID"]) ?>.png';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                
                showNotification('QR Code download initiated!', 'success');
            };
            
            img.src = qrImage.src;
        }

        function shareQRCode() {
            const qrImage = document.querySelector('.qr-image img');
            if (!qrImage) {
                alert('QR Code not found!');
                return;
            }

            const citizenName = '<?= htmlspecialchars($citizen["FirstName"] . " " . $citizen["LastName"]) ?>';
            const citizenID = '<?= htmlspecialchars($citizen["Citizen_eID"] ?? $citizen["CitizenID"]) ?>';
            
            // Check if Web Share API is supported
            if (navigator.share) {
                // Create a canvas to get the clean QR code image data
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                canvas.width = qrImage.naturalWidth || 300;
                canvas.height = qrImage.naturalHeight || 300;
                
                const img = new Image();
                img.crossOrigin = 'anonymous';
                
                img.onload = function() {
                    // Draw clean QR code without text
                    ctx.fillStyle = 'white';
                    ctx.fillRect(0, 0, canvas.width, canvas.height);
                    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                    
                    canvas.toBlob(async function(blob) {
                        try {
                            const file = new File([blob], `NDMS_QR_${citizenID}.png`, { type: 'image/png' });
                            await navigator.share({
                                title: 'NDMS Citizen QR Code',
                                text: `QR Code for ${citizenName} (ID: ${citizenID}) - National Digital Management System`,
                                files: [file]
                            });
                            showNotification('QR Code shared successfully!', 'success');
                        } catch (error) {
                            console.error('Error sharing:', error);
                            fallbackShare();
                        }
                    }, 'image/png');
                };
                
                img.onerror = fallbackShare;
                img.src = qrImage.src;
            } else {
                fallbackShare();
            }
            
            function fallbackShare() {
                // Fallback: Copy QR code info to clipboard
                const shareText = `NDMS Citizen QR Code\n${citizenName}\nID: ${citizenID}\nNational Digital Management System`;
                
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(shareText).then(() => {
                        showNotification('QR Code information copied to clipboard!', 'success');
                    }).catch(() => {
                        showBasicShareDialog(shareText);
                    });
                } else {
                    showBasicShareDialog(shareText);
                }
            }
            
            function showBasicShareDialog(text) {
                const modal = document.createElement('div');
                modal.className = 'share-modal';
                modal.innerHTML = `
                    <div class="modal-content share">
                        <div class="modal-header">
                            <h3>📤 Share QR Code</h3>
                            <button class="close-btn" onclick="this.closest('.share-modal').remove()">&times;</button>
                        </div>
                        <div class="modal-body">
                            <p>Copy this information to share:</p>
                            <textarea readonly style="width: 100%; height: 100px; padding: 10px; border: 1px solid #ccc; border-radius: 8px; font-family: monospace;">${text}</textarea>
                            <div style="margin-top: 15px;">
                                <button onclick="copyShareText(this)" class="btn-primary">📋 Copy Text</button>
                                <button onclick="downloadQRCode()" class="btn-secondary">📥 Download QR</button>
                            </div>
                        </div>
                    </div>
                `;
                document.body.appendChild(modal);
            }
        }

        function copyShareText(button) {
            const textarea = button.closest('.modal-body').querySelector('textarea');
            textarea.select();
            document.execCommand('copy');
            
            const originalText = button.textContent;
            button.textContent = '✓ Copied!';
            button.style.background = '#10b981';
            
            setTimeout(() => {
                button.textContent = originalText;
                button.style.background = '';
            }, 2000);
        }

        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.innerHTML = `
                <div class="notification-content">
                    <span class="notification-icon">${type === 'success' ? '✓' : 'ℹ'}</span>
                    <span class="notification-message">${message}</span>
                </div>
            `;
            
            // Add to page
            document.body.appendChild(notification);
            
            // Animate in
            setTimeout(() => notification.classList.add('show'), 100);
            
            // Remove after delay
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
    </script>
</body>
</html>
