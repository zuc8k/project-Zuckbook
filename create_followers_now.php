<?php
require_once __DIR__ . "/backend/config.php";

echo "<h2>Creating Followers Table...</h2>";

$sql = "CREATE TABLE IF NOT EXISTS followers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    follower_id INT NOT NULL,
    following_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_follow (follower_id, following_id),
    INDEX idx_follower (follower_id),
    INDEX idx_following (following_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "<p style='color: green; font-size: 18px;'>✅ Followers table created successfully!</p>";
    
    // Show table structure
    $structure = $conn->query("DESCRIBE followers");
    echo "<h3>Table Structure:</h3>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr style='background: #f0f2f5;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $structure->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<br><p style='color: blue;'>✅ You can now go back to your profile page!</p>";
    echo "<p><a href='/profile.php?id=" . ($_SESSION['user_id'] ?? 1) . "' style='padding: 10px 20px; background: #1877f2; color: white; text-decoration: none; border-radius: 6px;'>Go to Profile</a></p>";
    
} else {
    echo "<p style='color: red; font-size: 18px;'>❌ Error creating table: " . $conn->error . "</p>";
}
?>
