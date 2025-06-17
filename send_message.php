<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

$sender_id = $_SESSION['id'];
$receiver_id = $_POST['receiver_id'];
$message = trim($_POST['message']);

// Validate receiver_id
if (!$receiver_id || !is_numeric($receiver_id)) {
    exit('Invalid receiver');
}

// Validate message
if (empty($message)) {
    exit('Message cannot be empty');
}

// Check if receiver exists
$stmt = $conn->prepare("SELECT id FROM users WHERE id = ?");
$stmt->bind_param("i", $receiver_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    exit('Invalid receiver');
}

// Insert message
$stmt = $conn->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
$stmt->bind_param("iis", $sender_id, $receiver_id, $message);

if ($stmt->execute()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Failed to send message']);
}

$stmt->close();
?>
