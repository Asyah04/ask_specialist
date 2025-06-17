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
        max-width: 800px;
        margin: 20px auto;
        background: #fff;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        display: flex;
        flex-direction: column;
        height: calc(100vh - 200px);
    }

    .chat-header {
        padding: 15px;
        border-bottom: 1px solid #eee;
        display: flex;
        align-items: center;
        gap: 10px;
        background: linear-gradient(to right, var(--primary-color), var(--accent-color));
        color: white;
        border-radius: 10px 10px 0 0;
    }

    .profile-circle {
        width: 40px;
        height: 40px;
        background: rgba(255, 255, 255, 0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.2rem;
        font-weight: bold;
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
        color: rgba(255, 255, 255, 0.8);
    }

    .online-status.online {
        color: #fff;
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
        background: #f8f9fa;
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
        background: white;
        color: #333;
        align-self: flex-start;
        border-bottom-left-radius: 5px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
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
        border-top: 1px solid #eee;
        display: flex;
        gap: 10px;
        background: white;
        border-radius: 0 0 10px 10px;
    }

    .message-input {
        flex: 1;
        padding: 10px 15px;
        border: 1px solid #ddd;
        border-radius: 20px;
        outline: none;
        transition: border-color 0.3s;
    }

    .message-input:focus {
        border-color: var(--primary-color);
    }

    .send-button {
        padding: 10px 20px;
        background: var(--primary-color);
        color: white;
        border: none;
        border-radius: 20px;
        cursor: pointer;
        transition: all 0.3s;
    }

    .send-button:hover {
        background: var(--accent-color);
        transform: translateY(-2px);
    }

    .send-button:active {
        transform: translateY(0);
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
        });
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
        container.innerHTML = '';
        
        messages.forEach(msg => {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${msg.is_sent ? 'sent' : 'received'}`;
            
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
        
        container.scrollTop = container.scrollHeight;
        
        if(messages.length > 0) {
            lastMessageTime = messages[messages.length - 1].timestamp;
        }
    });
}

function sendMessage() {
    const input = document.getElementById('messageInput');
    const message = input.value.trim();
    
    if(message) {
        // Disable input and button while sending
        input.disabled = true;
        document.getElementById('sendButton').disabled = true;
        
        fetch('send_message.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `receiver_id=<?= $receiver_id ?>&message=${encodeURIComponent(message)}`
        })
        .then(response => response.json())
        .then(data => {
            // Clear input and reload messages regardless of success
            input.value = '';
            loadMessages();
            
            if(!data.success) {
                alert(data.error || 'Failed to send message. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Failed to send message. Please try again.');
        })
        .finally(() => {
            // Re-enable input and button
            input.disabled = false;
            document.getElementById('sendButton').disabled = false;
            input.focus();
        });
    }
}

// Update online status every 30 seconds
setInterval(updateOnlineStatus, 30000);
updateOnlineStatus();

// Load messages every 5 seconds
setInterval(loadMessages, 5000);
loadMessages();

// Send message on button click
document.getElementById('sendButton').addEventListener('click', sendMessage);

// Send message on Enter key
document.getElementById('messageInput').addEventListener('keypress', function(e) {
    if(e.key === 'Enter') {
        sendMessage();
    }
});
</script>

<?php
$content = ob_get_clean();
require_once "includes/layout.php";
?>