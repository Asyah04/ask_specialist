<?phpsession_start();
require_once 'config/database.php';

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    exit('Not logged in');
}

if(!isset($_SESSION['id'])){
    exit('User ID missing from session');
}

$user_id = $_SESSION['id'];

// Check user exists
$result = $conn->query("SELECT id FROM users WHERE id = $user_id");
if($result->num_rows === 0){
    exit("User with ID $user_id does not exist.");
}

// Create table if not exists
$conn->query("
    CREATE TABLE IF NOT EXISTS online_status (
        user_id INT PRIMARY KEY,
        last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        is_online TINYINT(1) DEFAULT 0,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )
");

sleep(1);

// Create index if not exists
$index_exists = $conn->query("
    SELECT COUNT(1) IndexIsThere 
    FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE table_schema=DATABASE() 
    AND table_name='online_status' 
    AND index_name='idx_last_seen'
")->fetch_row()[0];

if (!$index_exists) {
    try {
        $conn->query("CREATE INDEX idx_last_seen ON online_status(last_seen)");
    } catch (Exception $e) {
        error_log("Failed to create index: " . $e->getMessage());
    }
}

// Insert or update online status
$stmt = $conn->prepare("
    INSERT INTO online_status (user_id, is_online, last_seen) 
    VALUES (?, 1, CURRENT_TIMESTAMP)
    ON DUPLICATE KEY UPDATE 
    is_online = 1,
    last_seen = CURRENT_TIMESTAMP
");
$stmt->bind_param("i", $user_id);
$stmt->execute();

// Mark users offline if last seen more than 5 minutes ago
$stmt = $conn->prepare("
    UPDATE online_status 
    SET is_online = 0 
    WHERE last_seen < DATE_SUB(NOW(), INTERVAL 5 MINUTE)
");
$stmt->execute();

?> 