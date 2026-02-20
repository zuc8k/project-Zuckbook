<?php
// Fix missing columns in users table

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '197520error';
$db_name = 'zuckbook';
$db_port = 3306;

try {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);
    
    if ($conn->connect_error) {
        throw new Exception($conn->connect_error);
    }

    echo "<h2>🔧 Fixing Database...</h2>";

    // Check and add missing columns
    $columns_to_add = [
        "is_deleted" => "ALTER TABLE users ADD COLUMN is_deleted TINYINT(1) DEFAULT 0 AFTER timeout_expires_at",
        "last_activity" => "ALTER TABLE users ADD COLUMN last_activity DATETIME AFTER last_seen",
        "language" => "ALTER TABLE users ADD COLUMN language VARCHAR(10) DEFAULT 'en' AFTER coins",
        "theme" => "ALTER TABLE users ADD COLUMN theme VARCHAR(10) DEFAULT 'dark' AFTER language"
    ];

    foreach ($columns_to_add as $col_name => $sql) {
        // Check if column exists
        $result = $conn->query("SHOW COLUMNS FROM users LIKE '$col_name'");
        
        if ($result->num_rows == 0) {
            if ($conn->query($sql)) {
                echo "✅ Added column: $col_name<br>";
            } else {
                echo "❌ Error adding $col_name: " . $conn->error . "<br>";
            }
        } else {
            echo "✓ Column already exists: $col_name<br>";
        }
    }

    echo "<h2>✅ Database fixed!</h2>";
    echo "<p><a href='http://localhost/profile.php?id=1'>Go to Profile</a></p>";

    $conn->close();

} catch (Exception $e) {
    echo "<h2>❌ Error: " . $e->getMessage() . "</h2>";
    echo "<p>Make sure MySQL is running on port 3306</p>";
}
?>
