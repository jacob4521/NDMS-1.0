<?php
// subscribe.php - Handle newsletter subscription requests
header('Content-Type: application/json');

// Database connection only (avoid session conflicts)
$host = "localhost";
$user = "root";   
$pass = "";       
$db   = "ndms";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit();
}

// Enable CORS for AJAX requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get email from POST data (JSON or form data)
    $input = json_decode(file_get_contents('php://input'), true);
    $email = isset($input['email']) ? trim($input['email']) : (isset($_POST['email']) ? trim($_POST['email']) : '');

    // Validate email
    if (empty($email)) {
        $response['message'] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Please enter a valid email address.';
    } else {
        try {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT SubscriberID FROM subscribers WHERE Email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $existing = $result->fetch_assoc();

            if ($existing) {
                $response['message'] = 'You are already subscribed to our newsletter!';
                $response['success'] = true; // Still consider it success
            } else {
                // Insert new subscriber
                $stmt = $conn->prepare("INSERT INTO subscribers (Email, SubscribedAt, IsActive) VALUES (?, NOW(), 1)");
                $stmt->bind_param("s", $email);
                
                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'Thank you! You have been successfully subscribed to our newsletter.';
                    
                    // Log successful subscription (optional)
                    error_log("New subscriber: " . $email);
                } else {
                    $response['message'] = 'Sorry, there was an error processing your subscription. Please try again.';
                }
            }
        } catch (Exception $e) {
            $response['message'] = 'Sorry, there was an error processing your subscription. Please try again.';
            error_log("Subscription error: " . $e->getMessage());
        }
    }
} else {
    $response['message'] = 'Invalid request method.';
}

echo json_encode($response);
?>
