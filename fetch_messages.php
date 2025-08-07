<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    exit('Not logged in');
}

// Get receiver_id from POST data
$receiver_id = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;

if($receiver_id <= 0) {
    exit('Invalid receiver');
}

$sender_id = $_SESSION['id'];

// Mark messages as read
$update_stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0");
$update_stmt->bind_param("ii", $receiver_id, $sender_id);
$update_stmt->execute();

// Get messages
$stmt = $conn->prepare("
    SELECT m.id, m.message, m.timestamp, m.sender_id, u.username as sender_name 
    FROM messages m 
    JOIN users u ON m.sender_id = u.id 
    WHERE (m.sender_id = ? AND m.receiver_id = ?) 
    OR (m.sender_id = ? AND m.receiver_id = ?) 
    ORDER BY m.timestamp ASC
");
$stmt->bind_param("iiii", $sender_id, $receiver_id, $receiver_id, $sender_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while($row = $result->fetch_assoc()) {
    $messages[] = [
        'id' => $row['id'],
        'message' => htmlspecialchars($row['message']),
        'timestamp' => date('H:i', strtotime($row['timestamp'])),
        'is_sent' => $row['sender_id'] == $sender_id,
        'sender_name' => htmlspecialchars($row['sender_name'])
    ];
}

echo json_encode($messages);
?>
