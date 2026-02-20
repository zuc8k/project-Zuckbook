<?php
// Complete database fix

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

    // Drop problematic tables if they exist
    $drop_tables = [
        "group_verifications",
        "stories"
    ];

    foreach ($drop_tables as $table) {
        if ($conn->query("DROP TABLE IF EXISTS $table")) {
            echo "✅ Dropped table: $table<br>";
        }
    }

    // Add missing columns to users
    $columns_to_add = [
        "is_deleted" => "ALTER TABLE users ADD COLUMN IF NOT EXISTS is_deleted TINYINT(1) DEFAULT 0",
        "last_activity" => "ALTER TABLE users ADD COLUMN IF NOT EXISTS last_activity DATETIME",
        "language" => "ALTER TABLE users ADD COLUMN IF NOT EXISTS language VARCHAR(10) DEFAULT 'en'",
        "theme" => "ALTER TABLE users ADD COLUMN IF NOT EXISTS theme VARCHAR(10) DEFAULT 'dark'"
    ];

    foreach ($columns_to_add as $col_name => $sql) {
        if ($conn->query($sql)) {
            echo "✅ Added/verified column: $col_name<br>";
        } else {
            echo "⚠️ Column $col_name: " . $conn->error . "<br>";
        }
    }

    // Add missing columns to posts
    $post_columns = [
        "is_deleted" => "ALTER TABLE posts ADD COLUMN IF NOT EXISTS is_deleted TINYINT(1) DEFAULT 0"
    ];

    foreach ($post_columns as $col_name => $sql) {
        if ($conn->query($sql)) {
            echo "✅ Added/verified posts column: $col_name<br>";
        } else {
            echo "⚠️ Posts column $col_name: " . $conn->error . "<br>";
        }
    }

    // Add missing columns to groups
    $group_columns = [
        "is_deleted" => "ALTER TABLE groups ADD COLUMN IF NOT EXISTS is_deleted TINYINT(1) DEFAULT 0"
    ];

    foreach ($group_columns as $col_name => $sql) {
        if ($conn->query($sql)) {
            echo "✅ Added/verified groups column: $col_name<br>";
        } else {
            echo "⚠️ Groups column $col_name: " . $conn->error . "<br>";
        }
    }

    echo "<h2>✅ Database fixed!</h2>";
    echo "<p><a href='http://localhost/home.php'>Go to Home</a></p>";

    $conn->close();

} catch (Exception $e) {
    echo "<h2>❌ Error: " . $e->getMessage() . "</h2>";
    echo "<p>Make sure MySQL is running on port 3306</p>";
}
?>
