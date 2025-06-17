<?php
session_start();
require_once 'config/database.php';

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    exit('Not logged in');
}

// Create online_status table if it doesn't exist
$conn->query("
    CREATE TABLE IF NOT EXISTS online_status (
        user_id INT PRIMARY KEY,
        last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        is_online TINYINT(1) DEFAULT 0,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )
");

// Wait a moment to ensure table is created
sleep(1);

// Check if index exists
$index_exists = $conn->query("
    SELECT COUNT(1) IndexIsThere 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE table_schema=DATABASE() 
    AND table_name='online_status' 
    AND index_name='idx_last_seen'
")->fetch_row()[0];

// Create index if it doesn't exist
if (!$index_exists) {
    try {
        $conn->query("CREATE INDEX idx_last_seen ON online_status(last_seen)");
    } catch (Exception $e) {
        // If index creation fails, continue without it
        error_log("Failed to create index: " . $e->getMessage());
    }
}

$user_id = $_SESSION['id'];

// Update or insert online status
$stmt = $conn->prepare("
    INSERT INTO online_status (user_id, is_online, last_seen) 
    VALUES (?, 1, CURRENT_TIMESTAMP)
    ON DUPLICATE KEY UPDATE 
    is_online = 1,
    last_seen = CURRENT_TIMESTAMP
");
$stmt->bind_param("i", $user_id);
$stmt->execute();

// Set users as offline if they haven't been seen in the last 5 minutes
$stmt = $conn->prepare("
    UPDATE online_status 
    SET is_online = 0 
    WHERE last_seen < DATE_SUB(NOW(), INTERVAL 5 MINUTE)
");
$stmt->execute();
?> 