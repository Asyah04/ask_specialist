<?php
session_start();
require_once 'config/database.php';

// Get user_id from session or POST data
$user_id = null;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} elseif (isset($_POST['user_id'])) {
    $user_id = (int)$_POST['user_id'];
}

// Check if user is identified
if (!$user_id) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

$action = $_POST['action'] ?? 'online';

try {
    // Clean up old online status (users offline for more than 5 minutes)
    $cleanup_stmt = $conn->prepare("UPDATE online_status SET is_online = 0 WHERE last_seen < DATE_SUB(NOW(), INTERVAL 5 MINUTE) AND is_online = 1");
    $cleanup_stmt->execute();
    
    // Check if table exists and create if not
    $table_exists = $conn->query("SHOW TABLES LIKE 'online_status'")->num_rows > 0;
    
    if (!$table_exists) {
        $conn->query("
            CREATE TABLE IF NOT EXISTS online_status (
                user_id INT PRIMARY KEY,
                last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                is_online TINYINT(1) DEFAULT 0,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
        $conn->query("CREATE INDEX IF NOT EXISTS idx_last_seen ON online_status(last_seen)");
    }
    
    if ($action === 'offline') {
        // Set user offline
        $stmt = $conn->prepare("
            INSERT INTO online_status (user_id, is_online, last_seen) 
            VALUES (?, 0, NOW()) 
            ON DUPLICATE KEY UPDATE is_online = 0, last_seen = NOW()
        ");
        $stmt->bind_param("i", $user_id);
    } else {
        // Set user online
        $stmt = $conn->prepare("
            INSERT INTO online_status (user_id, is_online, last_seen) 
            VALUES (?, 1, NOW()) 
            ON DUPLICATE KEY UPDATE is_online = 1, last_seen = NOW()
        ");
        $stmt->bind_param("i", $user_id);
    }
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'status' => $action]);
    } else {
        throw new Exception('Failed to update status');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>