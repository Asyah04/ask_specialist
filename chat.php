<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// Try both possible session variables
$sender_id = isset($_SESSION['id']) ? $_SESSION['id'] : (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null);

if (!$sender_id) {
    die("Error: User ID not found in session. Please log in again.");
}

// Get receiver_id from URL
$receiver_id = isset($_GET['receiver_id']) ? (int)$_GET['receiver_id'] : 0;

if($receiver_id <= 0) {
    header("location: index.php");
    exit;
}

// Get receiver's username
$stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
$stmt->bind_param("i", $receiver_id);
$stmt->execute();
$result = $stmt->get_result();
$receiver = $result->fetch_assoc();

if(!$receiver) {
    header("location: index.php");
    exit;
}

$page_title = "Chat with " . htmlspecialchars($receiver['username']);
ob_start();
?>

<style>
    .chat-container {
        width: 100%;
        max-width: 100%;
        margin: 0;
        background: var(--card-background, #fff);
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        display: flex;
        flex-direction: column;
        height: calc(100vh - 200px);
        border: 1px solid var(--border-color, #eee);
    }

    .chat-header {
        padding: 15px;
        border-bottom: 1px solid #eee;
        display: flex;
        align-items: center;
        gap: 10px;
        background: linear-gradient(135deg, #2563EB 0%, #1E40AF 100%);
        color: white;
        border-radius: 10px 10px 0 0;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .profile-circle {
        width: 40px;
        height: 40px;
        background: rgba(255, 255, 255, 0.25);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.2rem;
        font-weight: bold;
        border: 2px solid rgba(255, 255, 255, 0.3);
    }

    .chat-info {
        flex: 1;
    }

    .username {
        font-weight: bold;
        color: white;
        margin: 0;
    }

    .online-status {
        font-size: 0.8rem;
        color: rgba(255, 255, 255, 0.9);
        font-weight: 500;
    }

    .online-status.online {
        color: #fff;
        font-weight: 600;
    }

    .online-status.online::before {
        content: '';
        display: inline-block;
        width: 8px;
        height: 8px;
        background: #28a745;
        border-radius: 50%;
        margin-right: 5px;
    }

    .messages-container {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        background: var(--background-color, #f8f9fa);
    }

    .message {
        max-width: 70%;
        padding: 10px 15px;
        border-radius: 15px;
        position: relative;
    }

    .message.sent {
        background: var(--primary-color);
        color: white;
        align-self: flex-end;
        border-bottom-right-radius: 5px;
    }

    .message.received {
        background: var(--card-background, white);
        color: var(--text-color, #333);
        align-self: flex-start;
        border-bottom-left-radius: 5px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        border: 1px solid var(--border-color, #eee);
    }

    .message .sender {
        font-size: 0.8rem;
        margin-bottom: 5px;
        opacity: 0.8;
    }

    .message .time {
        font-size: 0.7rem;
        opacity: 0.8;
        margin-top: 5px;
        text-align: right;
    }

    .input-container {
        padding: 15px;
        border-top: 1px solid var(--border-color, #eee);
        display: flex;
        gap: 10px;
        background: var(--card-background, white);
        border-radius: 0 0 10px 10px;
    }

    .message-input {
        flex: 1;
        padding: 10px 15px;
        border: 1px solid var(--border-color, #ddd);
        border-radius: 20px;
        outline: none;
        transition: border-color 0.3s;
        background: var(--card-background, white);
        color: var(--text-color, #333);
    }

    .message-input:focus {
        border-color: var(--primary-color);
    }

    .message-input::placeholder {
        color: var(--text-muted, #6c757d);
    }

    .send-button {
        padding: 10px 20px;
        background: var(--primary-color) !important;
        color: white !important;
        border: none;
        border-radius: 20px;
        cursor: pointer;
        transition: all 0.3s;
        font-weight: 500;
    }

    .send-button:hover {
        background: var(--primary-color) !important;
        color: white !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(37, 99, 235, 0.3);
    }

    .send-button:active {
        background: var(--primary-color) !important;
        color: white !important;
        transform: translateY(0);
    }

    .send-button:focus {
        background: var(--primary-color) !important;
        color: white !important;
        outline: none;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.2);
    }
</style>

<div class="chat-container">
    <div class="chat-header">
        <div class="profile-circle">
            <?= strtoupper(substr($receiver['username'], 0, 1)) ?>
        </div>
        <div class="chat-info">
            <h5 class="username"><?= htmlspecialchars($receiver['username']) ?></h5>
            <div class="online-status" id="onlineStatus">Checking status...</div>
        </div>
    </div>
    
    <div class="messages-container" id="messagesContainer">
        <!-- Messages will be loaded here -->
    </div>
    
    <div class="input-container">
        <input type="text" class="message-input" id="messageInput" placeholder="Type your message...">
        <button class="send-button" id="sendButton">Send</button>
    </div>
</div>

<script>
let lastMessageTime = '';
let isTyping = false;
let typingTimeout;
let lastActivity = Date.now();

// Update user's online status
function updateUserOnlineStatus() {
    fetch('update_online_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=update'
    });
}

function updateOnlineStatus() {
    fetch('check_online_status.php?user_id=<?= $receiver_id ?>')
        .then(response => response.json())
        .then(data => {
            const statusElement = document.getElementById('onlineStatus');
            if(data.is_online) {
                statusElement.textContent = 'Online';
                statusElement.classList.add('online');
            } else {
                statusElement.textContent = 'Last seen ' + data.last_seen;
                statusElement.classList.remove('online');
            }
        })
        .catch(error => console.log('Status check failed:', error));
}

function loadMessages() {
    fetch('fetch_messages.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'receiver_id=<?= $receiver_id ?>'
    })
    .then(response => response.json())
    .then(messages => {
        const container = document.getElementById('messagesContainer');
        const wasAtBottom = container.scrollHeight - container.clientHeight <= container.scrollTop + 1;
        
        // Clear and rebuild messages
        container.innerHTML = '';
        
        messages.forEach(msg => {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${msg.is_sent ? 'sent' : 'received'}`;
            messageDiv.setAttribute('data-message-id', msg.id);
            
            const senderDiv = document.createElement('div');
            senderDiv.className = 'sender';
            senderDiv.textContent = msg.sender_name;
            
            const messageText = document.createElement('div');
            messageText.textContent = msg.message;
            
            const timeDiv = document.createElement('div');
            timeDiv.className = 'time';
            timeDiv.textContent = msg.timestamp;
            
            messageDiv.appendChild(senderDiv);
            messageDiv.appendChild(messageText);
            messageDiv.appendChild(timeDiv);
            
            container.appendChild(messageDiv);
        });
        
        // Auto-scroll if user was at bottom or if there are new messages
        if(wasAtBottom || messages.length !== container.children.length) {
            container.scrollTop = container.scrollHeight;
        }
        
        if(messages.length > 0) {
            lastMessageTime = messages[messages.length - 1].timestamp;
        }
    })
    .catch(error => {
        console.log('Failed to load messages:', error);
    });
}

function sendMessage() {
    const input = document.getElementById('messageInput');
    const message = input.value.trim();
    
    if(message) {
        // Clear input immediately for better UX
        input.value = '';
        
        // Disable input and button while sending
        input.disabled = true;
        document.getElementById('sendButton').disabled = true;
        
        // Add message to UI immediately (optimistic update)
        addMessageToUI({
            message: message,
            sender_name: 'You',
            timestamp: new Date().toLocaleTimeString(),
            is_sent: true,
            id: 'temp-' + Date.now()
        });
        
        fetch('send_message.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `receiver_id=<?= $receiver_id ?>&message=${encodeURIComponent(message)}`
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                // Immediately load new messages to get the real message ID
                setTimeout(loadMessages, 100);
            } else {
                // Remove the optimistic message and show error
                removeTemporaryMessage();
                alert(data.error || 'Failed to send message. Please try again.');
                input.value = message; // Restore message
            }
        })
        .catch(error => {
            console.error('Error:', error);
            removeTemporaryMessage();
            alert('Failed to send message. Please try again.');
            input.value = message; // Restore message
        })
        .finally(() => {
            // Re-enable input and button
            input.disabled = false;
            document.getElementById('sendButton').disabled = false;
            input.focus();
        });
    }
}

function addMessageToUI(msg) {
    const container = document.getElementById('messagesContainer');
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${msg.is_sent ? 'sent' : 'received'}`;
    messageDiv.setAttribute('data-message-id', msg.id);
    
    const senderDiv = document.createElement('div');
    senderDiv.className = 'sender';
    senderDiv.textContent = msg.sender_name;
    
    const messageText = document.createElement('div');
    messageText.textContent = msg.message;
    
    const timeDiv = document.createElement('div');
    timeDiv.className = 'time';
    timeDiv.textContent = msg.timestamp;
    
    messageDiv.appendChild(senderDiv);
    messageDiv.appendChild(messageText);
    messageDiv.appendChild(timeDiv);
    
    container.appendChild(messageDiv);
    container.scrollTop = container.scrollHeight;
}

function removeTemporaryMessage() {
    const tempMessages = document.querySelectorAll('[data-message-id^="temp-"]');
    tempMessages.forEach(msg => msg.remove());
}

// Update user's own online status every 30 seconds
setInterval(updateUserOnlineStatus, 30000);
updateUserOnlineStatus();

// Update recipient's online status every 15 seconds
setInterval(updateOnlineStatus, 15000);
updateOnlineStatus();

// Load messages every 1 second for real-time chat
setInterval(loadMessages, 1000);
loadMessages();

// Track user activity for auto-updating online status
setInterval(() => {
    if(Date.now() - lastActivity < 60000) { // Active in last minute
        updateUserOnlineStatus();
    }
}, 10000);

// Send message on button click
document.getElementById('sendButton').addEventListener('click', sendMessage);

// Send message on Enter key
document.getElementById('messageInput').addEventListener('keypress', function(e) {
    if(e.key === 'Enter') {
        sendMessage();
    }
});

// Track typing activity
document.getElementById('messageInput').addEventListener('input', function() {
    lastActivity = Date.now();
});

// Track any user activity
document.addEventListener('mousemove', () => lastActivity = Date.now());
document.addEventListener('keypress', () => lastActivity = Date.now());
document.addEventListener('click', () => lastActivity = Date.now());

// Update online status when page becomes visible
document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
        updateUserOnlineStatus();
        loadMessages();
    }
});

// Update status when user leaves/closes page
window.addEventListener('beforeunload', function() {
    navigator.sendBeacon('update_online_status.php', 'action=offline');
});
</script>

<?php
$content = ob_get_clean();
require_once "includes/layout.php";
?>