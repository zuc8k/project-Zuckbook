<?php
require_once __DIR__ . "/backend/config.php";

echo "<h2>Adding Sample Followers Data...</h2>";

// First, make sure the table exists
$checkTable = $conn->query("SHOW TABLES LIKE 'followers'");
if ($checkTable->num_rows == 0) {
    echo "<p style='color: red;'>❌ Followers table doesn't exist. Creating it now...</p>";
    
    $createTable = "CREATE TABLE IF NOT EXISTS followers (
        id INT PRIMARY KEY AUTO_INCREMENT,
        follower_id INT NOT NULL,
        following_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_follow (follower_id, following_id),
        INDEX idx_follower (follower_id),
        INDEX idx_following (following_id)
    )";
    
    if ($conn->query($createTable)) {
        echo "<p style='color: green;'>✅ Table created successfully!</p>";
    } else {
        echo "<p style='color: red;'>❌ Error: " . $conn->error . "</p>";
        exit;
    }
}

// Get all users
$users = $conn->query("SELECT id, name FROM users ORDER BY id LIMIT 20");
$userIds = [];

while ($user = $users->fetch_assoc()) {
    $userIds[] = $user['id'];
}

if (count($userIds) < 2) {
    echo "<p style='color: red;'>❌ Not enough users in database. Need at least 2 users.</p>";
    exit;
}

echo "<p>Found " . count($userIds) . " users. Creating follow relationships...</p>";

$added = 0;
$errors = 0;

// Create some random follow relationships
for ($i = 0; $i < count($userIds); $i++) {
    for ($j = 0; $j < count($userIds); $j++) {
        // Skip if same user
        if ($i === $j) continue;
        
        // Random chance to follow (30%)
        if (rand(1, 100) <= 30) {
            $follower = $userIds[$i];
            $following = $userIds[$j];
            
            $stmt = $conn->prepare("INSERT IGNORE INTO followers (follower_id, following_id) VALUES (?, ?)");
            $stmt->bind_param("ii", $follower, $following);
            
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $added++;
            }
        }
    }
}

echo "<h3 style='color: green;'>✅ Done!</h3>";
echo "<p>Added <strong>$added</strong> follow relationships.</p>";

// Show statistics
$stats = $conn->query("
    SELECT 
        u.id,
        u.name,
        (SELECT COUNT(*) FROM followers WHERE following_id = u.id) as followers_count,
        (SELECT COUNT(*) FROM followers WHERE follower_id = u.id) as following_count
    FROM users u
    ORDER BY followers_count DESC
    LIMIT 10
");

echo "<h3>Top Users by Followers:</h3>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr style='background: #f0f2f5;'><th>User</th><th>Followers</th><th>Following</th></tr>";

while ($row = $stats->fetch_assoc()) {
    echo "<tr>";
    echo "<td><a href='/profile.php?id={$row['id']}'>{$row['name']}</a></td>";
    echo "<td><strong>{$row['followers_count']}</strong></td>";
    echo "<td><strong>{$row['following_count']}</strong></td>";
    echo "</tr>";
}

echo "</table>";

echo "<br><p><a href='/profile.php?id=1' style='padding: 10px 20px; background: #1877f2; color: white; text-decoration: none; border-radius: 6px;'>Go to Profile</a></p>";
?>
