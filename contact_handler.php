<?php
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Enable error reporting for debugging (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Set to 0 in production

require_once 'config.php';

// CSRF Token validation function
function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Generate CSRF token if not exists
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Rate limiting function
function checkRateLimit($ip, $conn) {
    $maxSubmissions = 5; // Max 5 submissions per hour
    $timeWindow = 3600; // 1 hour in seconds
    $blockDuration = 3600; // Block for 1 hour
    
    // Check if IP is currently blocked
    $blockQuery = $conn->prepare("
        SELECT blocked_until 
        FROM contact_rate_limiting 
        WHERE ip_address = ? AND blocked_until > NOW()
    ");
    $blockQuery->bind_param("s", $ip);
    $blockQuery->execute();
    $blockResult = $blockQuery->get_result();
    
    if ($blockResult->num_rows > 0) {
        $block = $blockResult->fetch_assoc();
        return [
            'allowed' => false,
            'message' => 'Too many submissions. Please try again after ' . date('H:i', strtotime($block['blocked_until'])),
            'reset_time' => $block['blocked_until']
        ];
    }
    
    // Check current submission count
    $query = $conn->prepare("
        SELECT submission_count, last_submission 
        FROM contact_rate_limiting 
        WHERE ip_address = ? AND last_submission > DATE_SUB(NOW(), INTERVAL ? SECOND)
    ");
    $query->bind_param("si", $ip, $timeWindow);
    $query->execute();
    $result = $query->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $currentCount = $row['submission_count'];
        
        if ($currentCount >= $maxSubmissions) {
            // Block the IP
            $blockUntil = date('Y-m-d H:i:s', time() + $blockDuration);
            $updateQuery = $conn->prepare("
                UPDATE contact_rate_limiting 
                SET blocked_until = ?, submission_count = submission_count + 1 
                WHERE ip_address = ?
            ");
            $updateQuery->bind_param("ss", $blockUntil, $ip);
            $updateQuery->execute();
            
            return [
                'allowed' => false,
                'message' => 'Rate limit exceeded. Please try again after 1 hour.',
                'reset_time' => $blockUntil
            ];
        } else {
            // Increment count
            $updateQuery = $conn->prepare("
                UPDATE contact_rate_limiting 
                SET submission_count = submission_count + 1, last_submission = NOW() 
                WHERE ip_address = ?
            ");
            $updateQuery->bind_param("s", $ip);
            $updateQuery->execute();
        }
    } else {
        // First submission from this IP in the time window
        $insertQuery = $conn->prepare("
            INSERT INTO contact_rate_limiting (ip_address, submission_count, last_submission) 
            VALUES (?, 1, NOW()) 
            ON DUPLICATE KEY UPDATE 
            submission_count = 1, 
            last_submission = NOW(), 
            blocked_until = NULL
        ");
        $insertQuery->bind_param("s", $ip);
        $insertQuery->execute();
    }
    
    return ['allowed' => true];
}

// Input validation and sanitization
function validateInput($data) {
    $errors = [];
    
    // Name validation
    if (empty($data['name']) || strlen(trim($data['name'])) < 2) {
        $errors['name'] = 'Name must be at least 2 characters long';
    } elseif (strlen($data['name']) > 100) {
        $errors['name'] = 'Name must not exceed 100 characters';
    } elseif (!preg_match('/^[a-zA-Z\s\.\-\']+$/', $data['name'])) {
        $errors['name'] = 'Name contains invalid characters';
    }
    
    // Email validation
    if (empty($data['email'])) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    } elseif (strlen($data['email']) > 150) {
        $errors['email'] = 'Email address is too long';
    }
    
    // Message validation
    if (empty($data['message']) || strlen(trim($data['message'])) < 10) {
        $errors['message'] = 'Message must be at least 10 characters long';
    } elseif (strlen($data['message']) > 5000) {
        $errors['message'] = 'Message must not exceed 5000 characters';
    }
    
    // Check for spam patterns
    $spamPatterns = [
        '/\b(viagra|cialis|casino|lottery|winner|prize|click here|buy now)\b/i',
        '/\b\d{4}[\s\-]?\d{4}[\s\-]?\d{4}[\s\-]?\d{4}\b/', // Credit card pattern
        '/(http|https):\/\/[^\s]+/i' // URL in message
    ];
    
    foreach ($spamPatterns as $pattern) {
        if (preg_match($pattern, $data['message'])) {
            $errors['message'] = 'Message appears to contain spam content';
            break;
        }
    }
    
    return $errors;
}

// Send email notification to admin
function sendAdminNotification($contactData) {
    $to = 'ndms@gov.lk'; // Admin email
    $subject = 'New NDMS Contact Form Submission';
    
    $message = "
    <html>
    <head>
        <title>New Contact Form Submission</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #1e3a8a; color: white; padding: 20px; border-radius: 5px 5px 0 0; }
            .content { background: #f8f9fa; padding: 20px; border: 1px solid #dee2e6; }
            .footer { background: #6c757d; color: white; padding: 10px; border-radius: 0 0 5px 5px; font-size: 12px; }
            .field { margin-bottom: 15px; }
            .label { font-weight: bold; color: #495057; }
            .value { background: white; padding: 10px; border-radius: 3px; border: 1px solid #ced4da; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>🔔 New Contact Form Submission</h2>
                <p>You have received a new message through the NDMS website contact form.</p>
            </div>
            <div class='content'>
                <div class='field'>
                    <div class='label'>Name:</div>
                    <div class='value'>" . htmlspecialchars($contactData['name']) . "</div>
                </div>
                <div class='field'>
                    <div class='label'>Email:</div>
                    <div class='value'>" . htmlspecialchars($contactData['email']) . "</div>
                </div>
                <div class='field'>
                    <div class='label'>Message:</div>
                    <div class='value'>" . nl2br(htmlspecialchars($contactData['message'])) . "</div>
                </div>
                <div class='field'>
                    <div class='label'>Submitted At:</div>
                    <div class='value'>" . date('Y-m-d H:i:s') . "</div>
                </div>
                <div class='field'>
                    <div class='label'>IP Address:</div>
                    <div class='value'>" . htmlspecialchars($contactData['ip_address']) . "</div>
                </div>
            </div>
            <div class='footer'>
                <p>This message was sent from the NDMS Contact Form. Please reply directly to the sender's email address.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: NDMS System <noreply@ndms.gov.lk>',
        'Reply-To: ' . $contactData['email'],
        'X-Mailer: PHP/' . phpversion()
    ];
    
    return mail($to, $subject, $message, implode("\r\n", $headers));
}

// Send auto-reply to user
function sendAutoReply($contactData) {
    $to = $contactData['email'];
    $subject = 'Thank you for contacting NDMS - Message Received';
    
    $message = "
    <html>
    <head>
        <title>Thank you for contacting NDMS</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%); color: white; padding: 20px; border-radius: 5px 5px 0 0; text-align: center; }
            .content { background: #f8f9fa; padding: 20px; border: 1px solid #dee2e6; }
            .footer { background: #6c757d; color: white; padding: 15px; border-radius: 0 0 5px 5px; font-size: 12px; }
            .logo { font-size: 24px; font-weight: bold; margin-bottom: 10px; }
            .contact-info { background: white; padding: 15px; border-radius: 5px; margin: 15px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <div class='logo'>🇱🇰 NDMS</div>
                <h2>Thank You for Contacting Us!</h2>
            </div>
            <div class='content'>
                <p>Dear " . htmlspecialchars($contactData['name']) . ",</p>
                
                <p>Thank you for reaching out to the National Digital Management System (NDMS). We have successfully received your message and our team will review it promptly.</p>
                
                <div class='contact-info'>
                    <h3>📋 Your Message Summary:</h3>
                    <p><strong>Submitted:</strong> " . date('F j, Y \a\t g:i A') . "</p>
                    <p><strong>Reference ID:</strong> NDMS-" . date('Ymd') . "-" . substr(md5($contactData['email'] . time()), 0, 6) . "</p>
                </div>
                
                <h3>⏰ What happens next?</h3>
                <ul>
                    <li>Our support team will review your message within 24 hours</li>
                    <li>For urgent matters, we'll respond within 4 business hours</li>
                    <li>You'll receive a detailed response at this email address</li>
                    <li>Please keep this email for your records</li>
                </ul>
                
                <div class='contact-info'>
                    <h3>📞 Need Immediate Assistance?</h3>
                    <p><strong>Phone:</strong> +94 78 093 8755</p>
                    <p><strong>Email:</strong> ndms@gov.lk</p>
                    <p><strong>Office Hours:</strong> Monday - Friday: 8:00 AM - 5:00 PM</p>
                    <p><strong>Emergency Support:</strong> Available 24/7</p>
                </div>
                
                <p>Best regards,<br>
                <strong>NDMS Support Team</strong><br>
                National Digital Management System<br>
                Government of Sri Lanka</p>
            </div>
            <div class='footer'>
                <p>This is an automated response. Please do not reply to this email directly. For additional support, contact us at ndms@gov.lk</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: NDMS Support <noreply@ndms.gov.lk>',
        'X-Mailer: PHP/' . phpversion()
    ];
    
    return mail($to, $subject, $message, implode("\r\n", $headers));
}

// Main handler logic
try {
    // Only accept POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }
    
    // Get client IP address
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (strpos($ip, ',') !== false) {
        $ip = trim(explode(',', $ip)[0]);
    }
    
    // Get user agent
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    // Parse JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        // Fallback to POST data
        $input = $_POST;
    }
    
    // Validate CSRF token (if provided)
    if (isset($input['csrf_token'])) {
        if (!validateCSRFToken($input['csrf_token'])) {
            throw new Exception('Invalid security token. Please refresh the page and try again.');
        }
    }
    
    // Check rate limiting
    $rateLimitResult = checkRateLimit($ip, $conn);
    if (!$rateLimitResult['allowed']) {
        echo json_encode([
            'success' => false,
            'message' => $rateLimitResult['message'],
            'error_type' => 'rate_limit',
            'reset_time' => $rateLimitResult['reset_time'] ?? null
        ]);
        exit;
    }
    
    // Validate input data
    $validationErrors = validateInput($input);
    if (!empty($validationErrors)) {
        echo json_encode([
            'success' => false,
            'message' => 'Please correct the following errors:',
            'errors' => $validationErrors
        ]);
        exit;
    }
    
    // Sanitize input data
    $contactData = [
        'name' => trim(strip_tags($input['name'])),
        'email' => trim(strtolower($input['email'])),
        'message' => trim(strip_tags($input['message'])),
        'ip_address' => $ip,
        'user_agent' => $userAgent
    ];
    
    // Insert into database
    $insertQuery = $conn->prepare("
        INSERT INTO contact_messages (name, email, message, ip_address, user_agent, status, created_at) 
        VALUES (?, ?, ?, ?, ?, 'new', NOW())
    ");
    
    $insertQuery->bind_param(
        "sssss",
        $contactData['name'],
        $contactData['email'],
        $contactData['message'],
        $contactData['ip_address'],
        $contactData['user_agent']
    );
    
    if (!$insertQuery->execute()) {
        throw new Exception('Failed to save message. Please try again.');
    }
    
    $contactId = $conn->insert_id;
    
    // Send notifications (optional - can be disabled in production if email server is not configured)
    $emailSent = false;
    $autoReplySent = false;
    
    try {
        // Send admin notification
        $emailSent = sendAdminNotification($contactData);
        
        // Send auto-reply to user
        $autoReplySent = sendAutoReply($contactData);
    } catch (Exception $e) {
        // Log email error but don't fail the whole process
        error_log("Email sending failed: " . $e->getMessage());
    }
    
    // Success response
    echo json_encode([
        'success' => true,
        'message' => 'Thank you for your message! We have received your inquiry and will respond within 24 hours.',
        'contact_id' => $contactId,
        'email_sent' => $emailSent,
        'auto_reply_sent' => $autoReplySent,
        'reference' => 'NDMS-' . date('Ymd') . '-' . substr(md5($contactData['email'] . time()), 0, 6)
    ]);
    
} catch (Exception $e) {
    // Error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_type' => 'general'
    ]);
    
    // Log error for debugging
    error_log("Contact form error: " . $e->getMessage() . " | IP: " . ($ip ?? 'unknown'));
}

// Close database connection
if (isset($conn)) {
    $conn->close();
}
?>