<?php
require_once 'config/database.php';

echo "=== Debug Dashboard Online Specialists ===\n";

// Test the exact query from dashboard.php
echo "\n1. Dashboard Query (with JOIN):\n";
$specialists_sql = "SELECT u.id, u.username, u.email, c.name as category_name,
        os.is_online, os.last_seen,
        (SELECT COUNT(*) FROM answers WHERE user_id = u.id) as total_answers
        FROM users u 
        JOIN specialist_applications sa ON u.id = sa.user_id 
        JOIN categories c ON sa.category_id = c.id 
        JOIN online_status os ON u.id = os.user_id
        WHERE sa.status = 'approved' 
        AND os.is_online = 1 
        AND os.last_seen >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ORDER BY os.last_seen DESC";

$result = mysqli_query($conn, $specialists_sql);
if (!$result) {
    echo "❌ Query error: " . mysqli_error($conn) . "\n";
} else {
    $count1 = 0;
    while ($row = mysqli_fetch_assoc($result)) {
        echo "✅ {$row['username']} (ID: {$row['id']}) - {$row['category_name']} - Last seen: {$row['last_seen']}\n";
        $count1++;
    }
    echo "Count: $count1\n";
}

// Test with LEFT JOIN like messages page
echo "\n2. Messages Style Query (with LEFT JOIN):\n";
$specialists_sql2 = "SELECT u.id, u.username, u.email, c.name as category_name,
        os.is_online, os.last_seen,
        (SELECT COUNT(*) FROM answers WHERE user_id = u.id) as total_answers
        FROM users u 
        JOIN specialist_applications sa ON u.id = sa.user_id 
        JOIN categories c ON sa.category_id = c.id 
        LEFT JOIN online_status os ON u.id = os.user_id
        WHERE sa.status = 'approved' 
        AND (os.is_online = 1 OR os.is_online IS NULL)
        AND (os.last_seen >= DATE_SUB(NOW(), INTERVAL 5 MINUTE) OR os.last_seen IS NULL)
        ORDER BY os.last_seen DESC";

$result2 = mysqli_query($conn, $specialists_sql2);
$count2 = 0;
while ($row = mysqli_fetch_assoc($result2)) {
    $status = ($row['is_online'] && $row['last_seen']) ? "🟢 Online" : "🔴 Offline";
    $last_seen = $row['last_seen'] ?: "Never";
    echo "$status {$row['username']} (ID: {$row['id']}) - {$row['category_name']} - Last seen: $last_seen\n";
    $count2++;
}
echo "Count: $count2\n";

// Check what's in online_status for specialists
echo "\n3. All Specialists and their online_status:\n";
$all_specialists = mysqli_query($conn, "
    SELECT u.id, u.username, sa.status, os.is_online, os.last_seen
    FROM users u 
    JOIN specialist_applications sa ON u.id = sa.user_id 
    LEFT JOIN online_status os ON u.id = os.user_id
    WHERE sa.status = 'approved'
    ORDER BY u.id
");

while ($row = mysqli_fetch_assoc($all_specialists)) {
    $online_status = $row['is_online'] ? "🟢 Online" : "🔴 Offline";
    $last_seen = $row['last_seen'] ?: "No record";
    echo "{$row['username']} (ID: {$row['id']}) - $online_status - Last seen: $last_seen\n";
}

// Check who's currently online right now
echo "\n4. All online_status records:\n";
$all_online = mysqli_query($conn, "SELECT user_id, is_online, last_seen FROM online_status WHERE is_online = 1 ORDER BY last_seen DESC");
while ($row = mysqli_fetch_assoc($all_online)) {
    echo "User ID: {$row['user_id']} - Online: {$row['is_online']} - Last seen: {$row['last_seen']}\n";
}
?>
