<?php
session_start();
require_once "config/database.php";

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
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

$page_title = "Chat History";
ob_start();
?>

<div class="messages-container">

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
                            <div class="time-with-status">
                                <span class="last-message-time">
                                    <?= date('H:i', strtotime($conv['last_message_time'])) ?>
                                </span>
                                <div class="online-status-indicator <?= $conv['is_online'] ? 'online' : 'offline' ?>">
                                    <?php if($conv['is_online']): ?>
                                        <i class="fas fa-circle text-success"></i>
                                        <small class="status-text">Online</small>
                                    <?php else: ?>
                                        <i class="fas fa-circle text-muted"></i>
                                        <small class="status-text">
                                            <?php if($conv['last_seen']): ?>
                                                Last seen <?= date('H:i', strtotime($conv['last_seen'])) ?>
                                            <?php else: ?>
                                                Offline
                                            <?php endif; ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
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
        background: linear-gradient(45deg, var(--primary-color), var(--accent-color));
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
        align-items: flex-start;
        margin-bottom: 5px;
    }

    .time-with-status {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 2px;
    }

    .username {
        font-weight: bold;
        color: #333;
    }

    .online-status-indicator {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .online-status-indicator.online .status-text {
        color: #28a745;
        font-weight: 500;
    }

    .online-status-indicator.offline .status-text {
        color: #6c757d;
    }

    .online-status-indicator i {
        font-size: 0.6rem;
    }

    .online-status-indicator.online i {
        animation: pulse 2s infinite;
    }

    .status-text {
        font-size: 0.7rem;
        margin: 0;
    }

    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.5; }
        100% { opacity: 1; }
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
        background: var(--primary-color);
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

<script>
let updateInterval;

function updateConversations() {
    fetch('fetch_conversations.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        }
    })
    .then(response => response.json())
    .then(conversations => {
        if(conversations && !conversations.error) {
            updateConversationList(conversations);
        }
    })
    .catch(error => {
        console.log('Failed to update conversations:', error);
    });
}

function updateConversationList(conversations) {
    const conversationList = document.querySelector('.conversation-list');
    if(!conversationList) return;
    
    conversationList.innerHTML = '';
    
    conversations.forEach(conv => {
        const conversationItem = document.createElement('a');
        conversationItem.href = `chat.php?receiver_id=${conv.other_user_id}`;
        conversationItem.className = 'conversation-item';
        
        conversationItem.innerHTML = `
            <div class="profile-circle">
                ${conv.other_username.charAt(0).toUpperCase()}
            </div>
            <div class="conversation-info">
                <div class="conversation-header">
                    <span class="username">${escapeHtml(conv.other_username)}</span>
                    <div class="time-with-status">
                        <span class="last-message-time">
                            ${formatTime(conv.last_message_time)}
                        </span>
                        <div class="online-status-indicator ${conv.is_online ? 'online' : 'offline'}">
                            ${conv.is_online ? 
                                '<i class="fas fa-circle text-success"></i><small class="status-text">Online</small>' : 
                                `<i class="fas fa-circle text-muted"></i><small class="status-text">${conv.last_seen ? 'Last seen ' + formatTime(conv.last_seen) : 'Offline'}</small>`
                            }
                        </div>
                    </div>
                </div>
                <div class="last-message">
                    ${escapeHtml(conv.last_message)}
                    ${conv.unread_count > 0 ? `<span class="unread-badge">${conv.unread_count}</span>` : ''}
                </div>
            </div>
        `;
        
        conversationList.appendChild(conversationItem);
    });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatTime(timestamp) {
    if(!timestamp) return '';
    const date = new Date(timestamp);
    return date.toLocaleTimeString('en-US', { 
        hour: '2-digit', 
        minute: '2-digit',
        hour12: false 
    });
}

// Start real-time updates when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Update conversations every 3 seconds
    updateInterval = setInterval(updateConversations, 3000);
    
    // Initial update
    updateConversations();
});

// Update when page becomes visible
document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
        updateConversations();
    }
});

// Clean up interval when page unloads
window.addEventListener('beforeunload', function() {
    if(updateInterval) {
        clearInterval(updateInterval);
    }
});
</script>

<?php
$content = ob_get_clean();
require_once "includes/layout.php";
?> 