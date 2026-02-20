<?php
require_once __DIR__ . "/backend/config.php";

// Check if followers table exists
$result = $conn->query("SHOW TABLES LIKE 'followers'");

if ($result->num_rows > 0) {
    echo "✅ Followers table exists!<br><br>";
    
    // Get table structure
    $structure = $conn->query("DESCRIBE followers");
    echo "<h3>Table Structure:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $structure->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table><br>";
    
    // Count followers
    $count = $conn->query("SELECT COUNT(*) as total FROM followers");
    $total = $count->fetch_assoc()['total'];
    echo "<p>Total follow relationships: <strong>$total</strong></p>";
    
} else {
    echo "❌ Followers table does NOT exist!<br>";
    echo "<p>Creating table now...</p>";
    
    $sql = "CREATE TABLE IF NOT EXISTS followers (
        id INT PRIMARY KEY AUTO_INCREMENT,
        follower_id INT NOT NULL,
        following_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_follow (follower_id, following_id),
        FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "✅ Followers table created successfully!";
    } else {
        echo "❌ Error creating table: " . $conn->error;
    }
}
?>
