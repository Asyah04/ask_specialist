<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is a specialist
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Check if user is a specialist
$user_id = $_SESSION['id'];
$stmt = $conn->prepare("SELECT * FROM specialist_applications WHERE user_id = ? AND status = 'approved'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows === 0) {
    header("location: index.php");
    exit;
}

// Get all conversations for this specialist
$stmt = $conn->prepare("
    SELECT DISTINCT 
        CASE 
            WHEN m.sender_id = ? THEN m.receiver_id 
            ELSE m.sender_id 
        END as other_user_id,
        u.username as other_username,
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
    WHERE m.sender_id = ? OR m.receiver_id = ?
    ORDER BY last_message_time DESC
");
$stmt->bind_param("iiiiiiiii", $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
$stmt->execute();
$conversations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$page_title = "My Messages";
ob_start();
?>

<style>
    .messages-container {
        max-width: 1000px;
        margin: 20px auto;
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .conversation-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .conversation-item {
        padding: 15px;
        border-bottom: 1px solid #eee;
        display: flex;
        align-items: center;
        gap: 15px;
        text-decoration: none;
        color: inherit;
        transition: background-color 0.2s;
    }

    .conversation-item:hover {
        background-color: #f8f9fa;
    }

    .conversation-item.active {
        background-color: #f0f7ff;
    }

    .profile-circle {
        width: 50px;
        height: 50px;
        background: linear-gradient(45deg, #4a90e2, #67b26f);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
        font-weight: bold;
    }

    .conversation-info {
        flex: 1;
    }

    .conversation-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 5px;
    }

    .username {
        font-weight: bold;
        color: #333;
    }

    .last-message-time {
        font-size: 0.8rem;
        color: #666;
    }

    .last-message {
        color: #666;
        font-size: 0.9rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 300px;
    }

    .unread-badge {
        background: #4a90e2;
        color: white;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 0.8rem;
    }

    .no-messages {
        text-align: center;
        padding: 40px;
        color: #666;
    }
</style>

<div class="messages-container">
    <h3 class="p-3 border-bottom">My Messages</h3>
    
    <?php if(empty($conversations)): ?>
        <div class="no-messages">
            <i class="fas fa-comments fa-3x mb-3"></i>
            <p>You don't have any messages yet.</p>
        </div>
    <?php else: ?>
        <ul class="conversation-list">
            <?php foreach($conversations as $conv): ?>
                <a href="chat.php?receiver_id=<?= $conv['other_user_id'] ?>" class="conversation-item">
                    <div class="profile-circle">
                        <?= strtoupper(substr($conv['other_username'], 0, 1)) ?>
                    </div>
                    <div class="conversation-info">
                        <div class="conversation-header">
                            <span class="username"><?= htmlspecialchars($conv['other_username']) ?></span>
                            <span class="last-message-time">
                                <?= date('H:i', strtotime($conv['last_message_time'])) ?>
                            </span>
                        </div>
                        <div class="last-message">
                            <?= htmlspecialchars($conv['last_message']) ?>
                            <?php if($conv['unread_count'] > 0): ?>
                                <span class="unread-badge"><?= $conv['unread_count'] ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require_once "includes/layout.php";
?> 