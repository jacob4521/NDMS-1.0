<?php
// Quick test to check subscribers data
$host = "localhost"; 
$user = "root"; 
$pass = ""; 
$db   = "ndms"; 
$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>📊 Subscribers Database Check</h2>";

// Check if table exists
$table_check = $conn->query("SHOW TABLES LIKE 'subscribers'");
if ($table_check->num_rows > 0) {
    echo "✅ Table 'subscribers' exists<br><br>";
    
    // Count total records
    $count_result = $conn->query("SELECT COUNT(*) as total FROM subscribers");
    $total = $count_result->fetch_assoc()['total'];
    echo "📊 Total subscribers: <strong>$total</strong><br><br>";
    
    if ($total > 0) {
        // Show sample data
        echo "<h3>Sample Data:</h3>";
        $sample_result = $conn->query("SELECT * FROM subscribers ORDER BY SubscribedAt DESC LIMIT 5");
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Email</th><th>Subscribed At</th><th>Active</th></tr>";
        while ($row = $sample_result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['SubscriberID'] . "</td>";
            echo "<td>" . $row['Email'] . "</td>";
            echo "<td>" . $row['SubscribedAt'] . "</td>";
            echo "<td>" . ($row['IsActive'] ? 'Yes' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</table><br>";
        
        // Calculate pagination
        $records_per_page = 10;
        $total_pages = ceil($total / $records_per_page);
        echo "<h3>Pagination Calculation:</h3>";
        echo "Records per page: $records_per_page<br>";
        echo "Total pages: <strong>$total_pages</strong><br>";
        echo "Should show pagination? " . ($total_pages > 1 ? "<strong style='color: green;'>YES</strong>" : "<strong style='color: red;'>NO (need more than $records_per_page records)</strong>");
        
    } else {
        echo "❌ No subscribers found. Run the test data script first!<br>";
        echo "<strong>To create test data:</strong><br>";
        echo "1. Run the SQL script: test_pagination_data.sql<br>";
        echo "2. Or manually add more than 10 subscribers<br>";
    }
} else {
    echo "❌ Table 'subscribers' does not exist!<br>";
    echo "<strong>Create the table first:</strong><br>";
    echo "<pre>";
    echo "CREATE TABLE subscribers (
    SubscriberID INT AUTO_INCREMENT PRIMARY KEY,
    Email VARCHAR(255) NOT NULL UNIQUE,
    SubscribedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    IsActive TINYINT(1) DEFAULT 1
);";
    echo "</pre>";
}

$conn->close();
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { margin: 10px 0; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style>

<br><br>
<a href="admin_subscribers.php" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">← Back to Admin Subscribers</a>
