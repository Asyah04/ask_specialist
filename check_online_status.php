<?php
session_start();
require_once 'config/database.php';

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    exit('Not logged in');
}

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if($user_id <= 0) {
    exit('Invalid user');
}

// Check if table exists
$table_exists = $conn->query("SHOW TABLES LIKE 'online_status'")->num_rows > 0;

if (!$table_exists) {
    // Create table if it doesn't exist
    $conn->query("
        CREATE TABLE IF NOT EXISTS online_status (
            user_id INT PRIMARY KEY,
            last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            is_online TINYINT(1) DEFAULT 0,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    
    // Create index
    $conn->query("
        CREATE INDEX IF NOT EXISTS idx_last_seen ON online_status(last_seen)
    ");
    
    // Insert initial status for the user
    $stmt = $conn->prepare("
        INSERT INTO online_status (user_id, is_online, last_seen) 
        VALUES (?, 1, CURRENT_TIMESTAMP)
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    echo json_encode([
        'is_online' => true,
        'last_seen' => date('H:i')
    ]);
    exit;
}

// Get user's online status
$stmt = $conn->prepare("
    SELECT is_online, last_seen 
    FROM online_status 
    WHERE user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$status = $result->fetch_assoc();

if($status) {
    echo json_encode([
        'is_online' => (bool)$status['is_online'],
        'last_seen' => date('H:i', strtotime($status['last_seen']))
    ]);
} else {
    // Insert status for new user
    $stmt = $conn->prepare("
        INSERT INTO online_status (user_id, is_online, last_seen) 
        VALUES (?, 1, CURRENT_TIMESTAMP)
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    echo json_encode([
        'is_online' => true,
        'last_seen' => date('H:i')
    ]);
}
?> 