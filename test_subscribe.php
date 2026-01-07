<?php
// Simple test script to check subscription functionality
require_once "config.php";

echo "Testing newsletter subscription...\n";

// Check database connection
if ($conn->connect_error) {
    echo "Database connection failed: " . $conn->connect_error . "\n";
    exit();
}
echo "Database connection: OK\n";

// Check if subscribers table exists
$result = $conn->query("SHOW TABLES LIKE 'subscribers'");
if ($result->num_rows > 0) {
    echo "Subscribers table: EXISTS\n";
    
    // Check table structure
    $structure = $conn->query("DESCRIBE subscribers");
    echo "Table structure:\n";
    while ($row = $structure->fetch_assoc()) {
        echo "  - " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
    
    // Test insert
    $test_email = "test_" . time() . "@example.com";
    $stmt = $conn->prepare("INSERT INTO subscribers (Email, SubscribedAt, IsActive) VALUES (?, NOW(), 1)");
    $stmt->bind_param("s", $test_email);
    
    if ($stmt->execute()) {
        echo "Test insert: SUCCESS\n";
        echo "Test email: " . $test_email . "\n";
        
        // Clean up test data
        $conn->query("DELETE FROM subscribers WHERE Email = '$test_email'");
        echo "Test cleanup: DONE\n";
    } else {
        echo "Test insert: FAILED - " . $stmt->error . "\n";
    }
    
} else {
    echo "Subscribers table: NOT FOUND\n";
    echo "Creating subscribers table...\n";
    
    $create_table = "CREATE TABLE subscribers (
        SubscriberID INT AUTO_INCREMENT PRIMARY KEY,
        Email VARCHAR(255) UNIQUE NOT NULL,
        SubscribedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        IsActive TINYINT(1) DEFAULT 1
    )";
    
    if ($conn->query($create_table)) {
        echo "Subscribers table created successfully!\n";
    } else {
        echo "Error creating table: " . $conn->error . "\n";
    }
}

$conn->close();
?>
