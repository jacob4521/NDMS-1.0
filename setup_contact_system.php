<?php
require_once 'config.php';

echo "<h2>🚀 Setting up Contact System Database...</h2>";

try {
    // Read and execute the SQL file
    $sql = file_get_contents('contact_setup.sql');
    
    // Split SQL statements (simple split by semicolon)
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue; // Skip empty statements and comments
        }
        
        if ($conn->query($statement)) {
            echo "<p style='color: green;'>✅ Executed: " . substr($statement, 0, 50) . "...</p>";
        } else {
            echo "<p style='color: red;'>❌ Error: " . $conn->error . "</p>";
            echo "<p style='color: red;'>Statement: " . substr($statement, 0, 100) . "...</p>";
        }
    }
    
    echo "<h3 style='color: green;'>🎉 Contact system database setup completed!</h3>";
    echo "<p><a href='homepage.php'>Test Contact Form</a> | <a href='admin_contacts.php'>View Admin Panel</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Setup failed: " . $e->getMessage() . "</p>";
}

$conn->close();
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 800px;
    margin: 2rem auto;
    padding: 2rem;
    background: #f8fafc;
}

h2 {
    color: #1e3a8a;
    border-bottom: 2px solid #3b82f6;
    padding-bottom: 0.5rem;
}

p {
    margin: 0.5rem 0;
    padding: 0.5rem;
    border-radius: 0.25rem;
}

a {
    color: #3b82f6;
    text-decoration: none;
    font-weight: 600;
    margin-right: 1rem;
}

a:hover {
    text-decoration: underline;
}
</style>
