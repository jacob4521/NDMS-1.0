<?php
require_once 'config.php';

// Check if user is logged in and is a citizen
if (!isset($_SESSION['UserID']) || $_SESSION['Role'] !== 'Citizen') {
    header("Location: login.php");
    exit();
}

// Get citizen information
$citizenInfo = null;
if (isset($_SESSION['UserID'])) {
    $citizenQuery = $conn->prepare("
        SELECT c.*, u.Username as LoginUsername
        FROM Citizens c 
        JOIN Users u ON c.Citizen_eID = u.Username
        WHERE u.UserID = ?
    ");
    $citizenQuery->bind_param("i", $_SESSION['UserID']);
    $citizenQuery->execute();
    $citizenResult = $citizenQuery->get_result();
    
    if ($citizenResult->num_rows > 0) {
        $citizenInfo = $citizenResult->fetch_assoc();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Help & Support - NDMS Citizen Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            color: #333;
        }

        .has-citizen-sidebar {
            margin-left: 280px;
        }

        .has-citizen-sidebar.citizen-sidebar-collapsed {
            margin-left: 60px;
        }

        .main-content {
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .help-header {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            text-align: center;
        }

        .help-header h1 {
            color: #1e3a8a;
            font-size: 2.5rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
        }

        .help-header p {
            color: #666;
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
        }

        .help-sections {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .help-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .help-section:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        }

        .help-section h2 {
            color: #1e3a8a;
            font-size: 1.5rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .help-section h3 {
            color: #3b82f6;
            font-size: 1.2rem;
            margin: 1.5rem 0 0.5rem 0;
        }

        .help-section p, .help-section li {
            color: #666;
            line-height: 1.6;
            margin-bottom: 0.5rem;
        }

        .help-section ul {
            padding-left: 1.5rem;
            margin-bottom: 1rem;
        }

        .feature-list {
            list-style: none;
            padding: 0;
        }

        .feature-list li {
            padding: 0.5rem 0;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .feature-list li:last-child {
            border-bottom: none;
        }

        .feature-list li i {
            color: #10b981;
            width: 20px;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .contact-item {
            background: #f8fafc;
            padding: 1.5rem;
            border-radius: 10px;
            text-align: center;
            border: 2px solid #e5e7eb;
            transition: all 0.3s ease;
        }

        .contact-item:hover {
            border-color: #3b82f6;
            background: #eff6ff;
        }

        .contact-item i {
            font-size: 2rem;
            color: #3b82f6;
            margin-bottom: 1rem;
        }

        .contact-item h3 {
            color: #1e3a8a;
            margin-bottom: 0.5rem;
        }

        .contact-item p {
            color: #666;
            margin: 0;
        }

        .back-button {
            position: fixed;
            top: 2rem;
            right: 2rem;
            background: #1e3a8a;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 15px rgba(30, 58, 138, 0.3);
            transition: all 0.3s ease;
            z-index: 100;
        }

        .back-button:hover {
            background: #1e40af;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(30, 58, 138, 0.4);
            text-decoration: none;
            color: white;
        }

        .quick-links {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .quick-links h2 {
            color: #1e3a8a;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .links-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .quick-link {
            background: linear-gradient(135deg, #3b82f6 0%, #1e3a8a 100%);
            color: white;
            padding: 1rem;
            border-radius: 10px;
            text-decoration: none;
            text-align: center;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
        }

        .quick-link:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
            text-decoration: none;
            color: white;
        }

        .quick-link i {
            font-size: 1.5rem;
        }

        .faq-section {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .faq-item {
            border-bottom: 1px solid #f0f0f0;
            padding: 1rem 0;
        }

        .faq-item:last-child {
            border-bottom: none;
        }

        .faq-question {
            font-weight: 600;
            color: #1e3a8a;
            margin-bottom: 0.5rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .faq-answer {
            color: #666;
            line-height: 1.6;
            display: none;
            padding-left: 1rem;
        }

        .faq-answer.active {
            display: block;
        }

        .faq-toggle {
            transition: transform 0.3s ease;
        }

        .faq-toggle.active {
            transform: rotate(180deg);
        }

        @media (max-width: 768px) {
            .has-citizen-sidebar {
                margin-left: 0;
            }

            .main-content {
                padding: 1rem;
            }

            .help-header h1 {
                font-size: 2rem;
            }

            .help-sections {
                grid-template-columns: 1fr;
            }

            .back-button {
                position: static;
                margin-bottom: 1rem;
                align-self: flex-start;
            }
        }
    </style>
</head>
<body class="has-citizen-sidebar">
    <!-- Include Citizen Sidebar -->
    <?php include 'includes/citizen_sidebar.php'; ?>

    <div class="main-content">
        <a href="citizen_dashboard.php" class="back-button">
            <i class="fas fa-arrow-left"></i>
            Back to Dashboard
        </a>

        <div class="help-header">
            <h1>
                <i class="fas fa-question-circle"></i>
                Help & Support
            </h1>
            <p>Welcome to the NDMS Citizen Portal Help Center. Find answers to common questions and learn how to make the most of your digital identity.</p>
        </div>

        <div class="quick-links">
            <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
            <div class="links-grid">
                <a href="citizen_dashboard.php#certificates" class="quick-link">
                    <i class="fas fa-certificate"></i>
                    <span>View Certificates</span>
                </a>
                <a href="citizen_dashboard.php#vaccination" class="quick-link">
                    <i class="fas fa-syringe"></i>
                    <span>Vaccination Records</span>
                </a>
                <a href="citizen_dashboard.php#education" class="quick-link">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Education History</span>
                </a>
                <a href="change_password.php" class="quick-link">
                    <i class="fas fa-key"></i>
                    <span>Change Password</span>
                </a>
            </div>
        </div>

        <div class="help-sections">
            <div class="help-section">
                <h2><i class="fas fa-home"></i> Getting Started</h2>
                <h3>Welcome to Your Digital Identity</h3>
                <p>Your NDMS account provides secure access to all your important documents and records in one place.</p>
                
                <h3>What You Can Do:</h3>
                <ul class="feature-list">
                    <li><i class="fas fa-check"></i> View your personal information and QR code</li>
                    <li><i class="fas fa-check"></i> Access vaccination and medical records</li>
                    <li><i class="fas fa-check"></i> Download birth certificates and reports</li>
                    <li><i class="fas fa-check"></i> Track your education and employment history</li>
                    <li><i class="fas fa-check"></i> Participate in career guidance programs</li>
                    <li><i class="fas fa-check"></i> Subscribe to government newsletters</li>
                    <li><i class="fas fa-check"></i> View activity logs and notifications</li>
                </ul>

                <h3>First Login Steps:</h3>
                <ol>
                    <li>Enter your username (National ID) and password</li>
                    <li>Review your personal information on the dashboard</li>
                    <li>Change your password if using a temporary one</li>
                    <li>Explore different tabs to familiarize yourself</li>
                    <li>Download any needed certificates</li>
                </ol>
            </div>

            <div class="help-section">
                <h2><i class="fas fa-shield-alt"></i> Account Security</h2>
                <h3>Keeping Your Account Safe</h3>
                <p>Your security is our top priority. Here's how to protect your account:</p>
                
                <ul>
                    <li><strong>Strong Password:</strong> Use a password with at least 8 characters, including numbers and symbols</li>
                    <li><strong>Regular Updates:</strong> Change your password every 6 months</li>
                    <li><strong>Secure Access:</strong> Always log out when using public computers</li>
                    <li><strong>Report Issues:</strong> Contact support immediately if you notice suspicious activity</li>
                    <li><strong>Browser Security:</strong> Keep your browser updated and avoid saving passwords on public devices</li>
                    <li><strong>Network Safety:</strong> Avoid accessing your account on public Wi-Fi networks</li>
                </ul>

                <h3>Password Requirements:</h3>
                <ul>
                    <li>Minimum 8 characters</li>
                    <li>At least one uppercase letter</li>
                    <li>At least one number</li>
                    <li>At least one special character (!@#$%^&*)</li>
                    <li>Cannot contain your name or username</li>
                    <li>Cannot be a common password</li>
                </ul>

                <h3>Security Tips:</h3>
                <ul>
                    <li>Never share your login credentials with anyone</li>
                    <li>Log out completely when finished</li>
                    <li>Check for HTTPS in the address bar</li>
                    <li>Be cautious of phishing emails</li>
                    <li>Report suspicious activities immediately</li>
                </ul>
            </div>

            <div class="help-section">
                <h2><i class="fas fa-file-alt"></i> Documents & Certificates</h2>
                <h3>Accessing Your Documents</h3>
                <p>All your official documents are securely stored and easily accessible:</p>
                
                <ul>
                    <li><strong>Birth Certificates:</strong> Download official PDF copies with QR verification</li>
                    <li><strong>Vaccination Records:</strong> Complete immunization history with dates and batch numbers</li>
                    <li><strong>Education Reports:</strong> Academic achievements, O/L and A/L results</li>
                    <li><strong>Medical Reports:</strong> Health records, treatments, and prescriptions</li>
                    <li><strong>Employment History:</strong> Work experience documentation and references</li>
                    <li><strong>Activity Reports:</strong> Your system usage and document access history</li>
                </ul>

                <h3>Document Download Instructions:</h3>
                <ol>
                    <li>Navigate to "Certificates & Reports" tab in your dashboard</li>
                    <li>Select the document type you need from the available sections</li>
                    <li>Click "Download PDF" or "Download Report" button</li>
                    <li>Choose your preferred location to save the file</li>
                    <li>Verify the download completed successfully</li>
                </ol>

                <h3>Document Verification:</h3>
                <ul>
                    <li>All documents include QR codes for verification</li>
                    <li>Digital signatures ensure authenticity</li>
                    <li>Timestamps show when documents were generated</li>
                    <li>Official government seals are embedded</li>
                </ul>

                <h3>Troubleshooting Downloads:</h3>
                <ul>
                    <li>Ensure pop-ups are enabled in your browser</li>
                    <li>Check your download folder permissions</li>
                    <li>Try using a different browser if issues persist</li>
                    <li>Contact support if documents fail to generate</li>
                </ul>
            </div>

            <div class="help-section">
                <h2><i class="fas fa-qrcode"></i> QR Code Usage</h2>
                <h3>Your Digital Identity Card</h3>
                <p>Your QR code serves as your digital identity card for quick verification:</p>
                
                <h3>How to Use Your QR Code:</h3>
                <ul>
                    <li>Show it to authorized personnel for identity verification</li>
                    <li>Use it at government offices for quick service</li>
                    <li>Present it during medical appointments</li>
                    <li>Use for educational institution verification</li>
                    <li>Employment verification processes</li>
                    <li>Bank account opening procedures</li>
                    <li>Travel document applications</li>
                </ul>

                <h3>QR Code Security:</h3>
                <ul>
                    <li>Only show to authorized personnel</li>
                    <li>Don't share screenshots on social media</li>
                    <li>Report immediately if compromised</li>
                    <li>Cover when not in use</li>
                    <li>Don't allow photos by unauthorized persons</li>
                </ul>

                <h3>QR Code Information Includes:</h3>
                <ul>
                    <li>Your full name and National ID</li>
                    <li>Date of birth and address</li>
                    <li>Verification timestamp</li>
                    <li>Digital signature for authenticity</li>
                    <li>Emergency contact information</li>
                </ul>

                <h3>Accessing Your QR Code:</h3>
                <ol>
                    <li>Go to your main dashboard</li>
                    <li>Click on the "Personal Information" tab</li>
                    <li>Your QR code is displayed prominently</li>
                    <li>You can also download it as a PDF</li>
                    <li>Print a copy for offline use if needed</li>
                </ol>
            </div>

            <div class="help-section">
                <h2><i class="fas fa-user-graduate"></i> Career Guidance</h2>
                <h3>Plan Your Future</h3>
                <p>Use our career guidance system to make informed decisions about your education and career path:</p>
                
                <h3>Features Available:</h3>
                <ul>
                    <li>O/L results analysis and interpretation</li>
                    <li>Career interest assessment questionnaire</li>
                    <li>Personalized career recommendations</li>
                    <li>Educational pathway suggestions</li>
                    <li>Job market insights and trends</li>
                    <li>Skill gap analysis</li>
                    <li>University course recommendations</li>
                    <li>Vocational training options</li>
                </ul>

                <h3>How to Get Started:</h3>
                <ol>
                    <li>Click "Career Guidance" in the sidebar</li>
                    <li>Fill in your O/L results accurately</li>
                    <li>Complete the comprehensive interest assessment</li>
                    <li>Answer questions about your preferences</li>
                    <li>Review your personalized recommendations</li>
                    <li>Explore different career paths</li>
                    <li>Save your assessment results</li>
                    <li>Schedule follow-up consultations if needed</li>
                </ol>

                <h3>Understanding Your Results:</h3>
                <ul>
                    <li><strong>Career Matches:</strong> Careers that align with your interests and abilities</li>
                    <li><strong>Education Requirements:</strong> Academic qualifications needed</li>
                    <li><strong>Skill Development:</strong> Areas where you can improve</li>
                    <li><strong>Job Market Outlook:</strong> Employment prospects and salary ranges</li>
                    <li><strong>Next Steps:</strong> Concrete actions to pursue your goals</li>
                </ul>

                <h3>Tips for Better Results:</h3>
                <ul>
                    <li>Be honest about your interests and abilities</li>
                    <li>Consider multiple career options</li>
                    <li>Research recommended careers thoroughly</li>
                    <li>Discuss with family and mentors</li>
                    <li>Consider practical factors like location and income</li>
                </ul>
            </div>

            <div class="help-section">
                <h2><i class="fas fa-mobile-alt"></i> Mobile Access</h2>
                <h3>Access on the Go</h3>
                <p>Your NDMS portal is fully responsive and works on all devices:</p>
                
                <h3>Mobile Features:</h3>
                <ul>
                    <li>Full access to all features</li>
                    <li>Touch-friendly navigation</li>
                    <li>Quick QR code access</li>
                    <li>Document downloads</li>
                    <li>Secure mobile login</li>
                    <li>Offline viewing of downloaded documents</li>
                    <li>Mobile notifications</li>
                    <li>GPS-based location services</li>
                </ul>

                <h3>Browser Recommendations:</h3>
                <ul>
                    <li><strong>Chrome (Latest version):</strong> Best performance and compatibility</li>
                    <li><strong>Safari (Latest version):</strong> Optimized for iOS devices</li>
                    <li><strong>Firefox (Latest version):</strong> Good privacy features</li>
                    <li><strong>Edge (Latest version):</strong> Integrated Windows experience</li>
                </ul>

                <h3>Mobile Optimization Tips:</h3>
                <ul>
                    <li>Use landscape mode for better viewing of tables</li>
                    <li>Enable notifications for important updates</li>
                    <li>Clear browser cache regularly</li>
                    <li>Ensure stable internet connection for downloads</li>
                    <li>Use mobile data wisely when downloading large files</li>
                </ul>

                <h3>Troubleshooting Mobile Issues:</h3>
                <ul>
                    <li>Clear browser cache and cookies</li>
                    <li>Update your browser to the latest version</li>
                    <li>Check internet connection stability</li>
                    <li>Try switching between Wi-Fi and mobile data</li>
                    <li>Restart your device if problems persist</li>
                </ul>
            </div>

            <div class="help-section">
                <h2><i class="fas fa-bell"></i> Notifications & Updates</h2>
                <h3>Stay Informed</h3>
                <p>Keep track of important updates and system notifications:</p>
                
                <h3>Notification Types:</h3>
                <ul>
                    <li><strong>System Updates:</strong> New features and maintenance announcements</li>
                    <li><strong>Document Updates:</strong> When new documents are available</li>
                    <li><strong>Security Alerts:</strong> Important security-related notifications</li>
                    <li><strong>Vaccination Reminders:</strong> Upcoming vaccination schedules</li>
                    <li><strong>Government Announcements:</strong> Important policy changes</li>
                    <li><strong>Newsletter Updates:</strong> Regular information bulletins</li>
                </ul>

                <h3>Managing Notifications:</h3>
                <ol>
                    <li>Check your dashboard regularly for new notifications</li>
                    <li>Click on notifications to read full details</li>
                    <li>Mark notifications as read after reviewing</li>
                    <li>Subscribe to newsletter for email updates</li>
                    <li>Enable browser notifications if desired</li>
                </ol>

                <h3>Newsletter Subscription:</h3>
                <ul>
                    <li>Stay updated with government news</li>
                    <li>Receive policy updates and announcements</li>
                    <li>Get reminders for important deadlines</li>
                    <li>Access to exclusive citizen resources</li>
                </ul>
            </div>

            <div class="help-section">
                <h2><i class="fas fa-chart-line"></i> Activity Tracking</h2>
                <h3>Monitor Your Account Usage</h3>
                <p>Keep track of your system usage and maintain account security:</p>
                
                <h3>Activity Logs Include:</h3>
                <ul>
                    <li>Login and logout times</li>
                    <li>Document downloads and views</li>
                    <li>Password changes</li>
                    <li>Profile updates</li>
                    <li>Career guidance submissions</li>
                    <li>QR code access attempts</li>
                </ul>

                <h3>Reviewing Your Activities:</h3>
                <ol>
                    <li>Navigate to "My Activities" in the sidebar</li>
                    <li>Review recent login attempts</li>
                    <li>Check document access history</li>
                    <li>Monitor any suspicious activities</li>
                    <li>Report unauthorized access immediately</li>
                </ol>

                <h3>Privacy and Data Protection:</h3>
                <ul>
                    <li>Your data is encrypted and secure</li>
                    <li>Activity logs help maintain security</li>
                    <li>Only you can access your activity history</li>
                    <li>Data is stored according to government regulations</li>
                    <li>You can request data deletion following proper procedures</li>
                </ul>
            </div>

            <div class="help-section">
                <h2><i class="fas fa-tools"></i> Troubleshooting</h2>
                <h3>Common Issues and Solutions</h3>
                <p>Quick fixes for the most common problems users encounter:</p>
                
                <h3>Login Problems:</h3>
                <ul>
                    <li><strong>Can't remember username:</strong> Your username is your National ID number</li>
                    <li><strong>Password not working:</strong> Check caps lock, try typing in a text editor first</li>
                    <li><strong>Account locked:</strong> Wait 30 minutes or contact support</li>
                    <li><strong>Page won't load:</strong> Check internet connection, try different browser</li>
                </ul>

                <h3>Download Issues:</h3>
                <ul>
                    <li><strong>PDF won't download:</strong> Enable pop-ups, check download folder permissions</li>
                    <li><strong>File appears corrupted:</strong> Try downloading again, ensure stable internet</li>
                    <li><strong>Can't open document:</strong> Install/update PDF reader (Adobe Reader recommended)</li>
                    <li><strong>Download is slow:</strong> Try during off-peak hours, check internet speed</li>
                </ul>

                <h3>Display Problems:</h3>
                <ul>
                    <li><strong>Page looks broken:</strong> Clear browser cache, disable ad blockers</li>
                    <li><strong>Text too small:</strong> Use browser zoom (Ctrl + plus key)</li>
                    <li><strong>Images not loading:</strong> Check internet connection, try refreshing</li>
                    <li><strong>Mobile view issues:</strong> Rotate device, update browser app</li>
                </ul>

                <h3>Performance Issues:</h3>
                <ul>
                    <li><strong>Slow loading:</strong> Close other browser tabs, restart browser</li>
                    <li><strong>Page freezing:</strong> Clear cache, disable browser extensions</li>
                    <li><strong>Timeout errors:</strong> Check internet stability, try again later</li>
                    <li><strong>Features not working:</strong> Enable JavaScript, update browser</li>
                </ul>

                <h3>When to Contact Support:</h3>
                <ul>
                    <li>Error messages you can't resolve</li>
                    <li>Incorrect information in your records</li>
                    <li>Suspected security breaches</li>
                    <li>Missing vaccination or medical records</li>
                    <li>Problems persisting after troubleshooting</li>
                </ul>

                <h3>Before Contacting Support:</h3>
                <ol>
                    <li>Try the basic troubleshooting steps above</li>
                    <li>Note down any error messages</li>
                    <li>Record what you were trying to do when the problem occurred</li>
                    <li>Check if the problem happens on different devices/browsers</li>
                    <li>Have your National ID and contact information ready</li>
                </ol>
            </div>

        <div class="faq-section">
            <h2><i class="fas fa-question-circle"></i> Frequently Asked Questions</h2>
            
            <div class="faq-item">
                <div class="faq-question">
                    <span>How do I download my birth certificate?</span>
                    <i class="fas fa-chevron-down faq-toggle"></i>
                </div>
                <div class="faq-answer">
                    Go to Dashboard → Certificates & Reports tab → Birth Certificates section → Click "Download PDF" next to your certificate. Make sure you have a PDF viewer installed on your device.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>Can I update my personal information?</span>
                    <i class="fas fa-chevron-down faq-toggle"></i>
                </div>
                <div class="faq-answer">
                    Personal information updates must be done through your local government office. Contact the office where you registered for assistance with updating your details. Some basic information like contact details may be updated through the system with proper verification.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>What if I forget my password?</span>
                    <i class="fas fa-chevron-down faq-toggle"></i>
                </div>
                <div class="faq-answer">
                    Contact your local government office or the system administrator to reset your password. You'll need to provide proper identification for security purposes. For security reasons, password resets cannot be done online.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>How do I add new vaccination records?</span>
                    <i class="fas fa-chevron-down faq-toggle"></i>
                </div>
                <div class="faq-answer">
                    Vaccination records are added by authorized medical officers during your vaccination appointments. If you notice missing records, contact the healthcare facility where you received the vaccination. They can update your records in the system.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>Is my data secure?</span>
                    <i class="fas fa-chevron-down faq-toggle"></i>
                </div>
                <div class="faq-answer">
                    Yes, your data is protected with advanced encryption and secure government servers. Access is strictly controlled and logged for security purposes. All data transmission uses HTTPS encryption, and we follow international cybersecurity standards.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>How do I access my account from a mobile device?</span>
                    <i class="fas fa-chevron-down faq-toggle"></i>
                </div>
                <div class="faq-answer">
                    Simply open your mobile browser and navigate to the NDMS portal website. The system is fully responsive and will automatically adapt to your mobile device. All features are available on mobile devices.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>Why can't I see all my vaccination records?</span>
                    <i class="fas fa-chevron-down faq-toggle"></i>
                </div>
                <div class="faq-answer">
                    Only vaccinations recorded in the digital system will appear. If you received vaccinations before the system was implemented or from facilities not yet connected, contact the healthcare provider to have them added to your digital record.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>How can I print my QR code?</span>
                    <i class="fas fa-chevron-down faq-toggle"></i>
                </div>
                <div class="faq-answer">
                    Go to your Personal Information tab, right-click on the QR code, and select "Print" or use your browser's print function (Ctrl+P). You can also download it as a PDF and print from there. Ensure high print quality for better scanning.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>What should I do if I notice incorrect information?</span>
                    <i class="fas fa-chevron-down faq-toggle"></i>
                </div>
                <div class="faq-answer">
                    Immediately contact the government office that manages your records. Provide documentation supporting the correct information. For urgent corrections, visit the office in person with proper identification and supporting documents.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>How often should I check my account?</span>
                    <i class="fas fa-chevron-down faq-toggle"></i>
                </div>
                <div class="faq-answer">
                    We recommend checking your account at least once a month to review any new notifications, updates to your records, or system announcements. Regular monitoring helps ensure the accuracy of your information and account security.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>Can I access the system from outside Sri Lanka?</span>
                    <i class="fas fa-chevron-down faq-toggle"></i>
                </div>
                <div class="faq-answer">
                    Yes, you can access your NDMS account from anywhere in the world with an internet connection. However, some features may be restricted based on your location for security purposes. Contact support if you experience access issues while abroad.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>What browsers work best with the NDMS portal?</span>
                    <i class="fas fa-chevron-down faq-toggle"></i>
                </div>
                <div class="faq-answer">
                    The portal works best with modern browsers: Chrome (latest), Firefox (latest), Safari (latest), and Edge (latest). Ensure JavaScript is enabled and cookies are allowed. Avoid using Internet Explorer as it's no longer supported.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>How do I subscribe to government newsletters?</span>
                    <i class="fas fa-chevron-down faq-toggle"></i>
                </div>
                <div class="faq-answer">
                    Look for the newsletter subscription option in your dashboard or check if there's a "Subscribe" link in the portal. Newsletters keep you informed about government updates, policy changes, and important announcements.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>What if the system is slow or not working?</span>
                    <i class="fas fa-chevron-down faq-toggle"></i>
                </div>
                <div class="faq-answer">
                    First, check your internet connection and try refreshing the page. Clear your browser cache and cookies. If problems persist, try a different browser or device. For ongoing issues, contact technical support with details about the problem you're experiencing.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span>Are there any fees for using the NDMS portal?</span>
                    <i class="fas fa-chevron-down faq-toggle"></i>
                </div>
                <div class="faq-answer">
                    No, the NDMS portal is a free government service for all citizens. There are no charges for accessing your records, downloading certificates, or using any of the portal's features. Beware of any websites that ask for payment for similar services.
                </div>
            </div>
        </div>

        <div class="help-section" id="contact-support">
            <h2><i class="fas fa-headset"></i> Contact Support</h2>
            <p>Need additional help? Our support team is here to assist you:</p>
            
            <div class="contact-grid">
                <div class="contact-item">
                    <i class="fas fa-phone"></i>
                    <h3>Phone Support</h3>
                    <p>+94 78 093 8755</p>
                    <p>Mon-Fri: 8AM-5PM</p>
                </div>
                
                <div class="contact-item">
                    <i class="fas fa-envelope"></i>
                    <h3>Email Support</h3>
                    <p>ndms@gov.lk</p>
                    <p>Response within 24 hours</p>
                </div>
                
                <div class="contact-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <h3>Visit Office</h3>
                    <p>Government Digital Hub</p>
                    <p>Galle, Sri Lanka</p>
                </div>
                
                <div class="contact-item">
                    <i class="fas fa-clock"></i>
                    <h3>Emergency Support</h3>
                    <p>Available 24/7</p>
                    <p>For urgent issues only</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // FAQ toggle functionality
        document.querySelectorAll('.faq-question').forEach(question => {
            question.addEventListener('click', () => {
                const answer = question.nextElementSibling;
                const toggle = question.querySelector('.faq-toggle');
                
                // Close all other FAQ items
                document.querySelectorAll('.faq-answer').forEach(ans => {
                    if (ans !== answer) {
                        ans.classList.remove('active');
                    }
                });
                
                document.querySelectorAll('.faq-toggle').forEach(tog => {
                    if (tog !== toggle) {
                        tog.classList.remove('active');
                    }
                });
                
                // Toggle current FAQ item
                answer.classList.toggle('active');
                toggle.classList.toggle('active');
            });
        });

        // Handle sidebar state
        document.addEventListener('DOMContentLoaded', function() {
            // Check if sidebar is collapsed
            const isCollapsed = localStorage.getItem('citizenSidebarCollapsed') === 'true';
            if (isCollapsed) {
                document.body.classList.add('citizen-sidebar-collapsed');
            }
        });
    </script>
</body>
</html>
