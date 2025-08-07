<?php
session_start();
require_once "config/database.php";

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

// Get all conversations for this user with online status
$stmt = $conn->prepare("
    SELECT DISTINCT 
        CASE 
            WHEN m.sender_id = ? THEN m.receiver_id 
            ELSE m.sender_id 
        END as other_user_id,
        u.username as other_username,
        os.is_online,
        os.last_seen,
        (SELECT message FROM messages 
         WHERE ((sender_id = ? AND receiver_id = other_user_id) 
                OR (sender_id = other_user_id AND receiver_id = ?))
         ORDER BY timestamp DESC LIMIT 1) as last_message,
        (SELECT timestamp FROM messages 
         WHERE ((sender_id = ? AND receiver_id = other_user_id) 
                OR (sender_id = other_user_id AND receiver_id = ?))
         ORDER BY timestamp DESC LIMIT 1) as last_message_time,
        (SELECT COUNT(*) FROM messages 
         WHERE sender_id = other_user_id 
         AND receiver_id = ? 
         AND is_read = 0) as unread_count
    FROM messages m
    JOIN users u ON u.id = CASE 
        WHEN m.sender_id = ? THEN m.receiver_id 
        ELSE m.sender_id 
    END
    LEFT JOIN online_status os ON u.id = os.user_id
    WHERE m.sender_id = ? OR m.receiver_id = ?
    ORDER BY last_message_time DESC
");
$stmt->bind_param("iiiiiiiii", $_SESSION["id"], $_SESSION["id"], $_SESSION["id"], $_SESSION["id"], $_SESSION["id"], $_SESSION["id"], $_SESSION["id"], $_SESSION["id"], $_SESSION["id"]);
$stmt->execute();
$conversations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

header('Content-Type: application/json');
echo json_encode($conversations);
?>
