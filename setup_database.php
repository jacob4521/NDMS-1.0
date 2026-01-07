<?php
// setup_database.php - Run this once to create the subscribers table
include "config.php";

$sql = "CREATE TABLE IF NOT EXISTS `subscribers` (
  `SubscriberID` int NOT NULL AUTO_INCREMENT,
  `Email` varchar(191) NOT NULL,
  `SubscribedAt` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `IsActive` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`SubscriberID`),
  UNIQUE KEY `Email` (`Email`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci";

if ($conn->query($sql) === TRUE) {
    echo "✅ Subscribers table created successfully (or already exists)!\n";
    echo "🚀 Your newsletter system is ready to use!\n";
    echo "\n📋 Next steps:\n";
    echo "1. Test subscription on your homepage\n";
    echo "2. Visit admin_subscribers.php to view subscribers\n";
    echo "3. Delete this setup file for security\n";
} else {
    echo "❌ Error creating table: " . $conn->error . "\n";
}

$conn->close();
?>
