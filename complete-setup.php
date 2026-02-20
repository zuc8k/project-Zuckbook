<?php
// Complete ZuckBook Setup & Fix

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

    echo "<h1>🚀 ZuckBook Complete Setup</h1>";
    echo "<hr>";

    // 1. Drop problematic tables
    echo "<h2>Step 1: Cleaning Database</h2>";
    $drop_tables = ["group_verifications", "stories"];
    foreach ($drop_tables as $table) {
        $conn->query("DROP TABLE IF EXISTS $table");
        echo "✅ Cleaned: $table<br>";
    }

    // 2. Add missing columns
    echo "<h2>Step 2: Adding Missing Columns</h2>";
    
    $columns = [
        "users" => [
            "is_deleted" => "ALTER TABLE users ADD COLUMN IF NOT EXISTS is_deleted TINYINT(1) DEFAULT 0",
            "last_activity" => "ALTER TABLE users ADD COLUMN IF NOT EXISTS last_activity DATETIME",
            "language" => "ALTER TABLE users ADD COLUMN IF NOT EXISTS language VARCHAR(10) DEFAULT 'en'",
            "theme" => "ALTER TABLE users ADD COLUMN IF NOT EXISTS theme VARCHAR(10) DEFAULT 'dark'"
        ],
        "posts" => [
            "is_deleted" => "ALTER TABLE posts ADD COLUMN IF NOT EXISTS is_deleted TINYINT(1) DEFAULT 0"
        ],
        "groups" => [
            "is_deleted" => "ALTER TABLE groups ADD COLUMN IF NOT EXISTS is_deleted TINYINT(1) DEFAULT 0"
        ]
    ];

    foreach ($columns as $table => $cols) {
        foreach ($cols as $col => $sql) {
            if ($conn->query($sql)) {
                echo "✅ $table.$col<br>";
            }
        }
    }

    // 3. Create test user if not exists
    echo "<h2>Step 3: Creating Test User</h2>";
    $test_user = $conn->query("SELECT id FROM users WHERE email='test@zuckbook.com' LIMIT 1");
    
    if ($test_user->num_rows == 0) {
        $password = password_hash('123456', PASSWORD_DEFAULT);
        $conn->query("INSERT INTO users (name, username, email, password, coins) VALUES ('Test User', 'testuser', 'test@zuckbook.com', '$password', 1000)");
        echo "✅ Test user created (Email: test@zuckbook.com, Password: 123456)<br>";
    } else {
        echo "✅ Test user already exists<br>";
    }

    // 4. Verify all tables exist
    echo "<h2>Step 4: Verifying Tables</h2>";
    $required_tables = ['users', 'posts', 'friends', 'groups', 'group_members', 'messages', 'conversations', 'notifications', 'post_reactions', 'post_comments'];
    
    foreach ($required_tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows > 0) {
            echo "✅ $table<br>";
        } else {
            echo "❌ $table (MISSING)<br>";
        }
    }

    echo "<hr>";
    echo "<h2>✅ Setup Complete!</h2>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ol>";
    echo "<li><a href='http://localhost'>Login to ZuckBook</a> (test@zuckbook.com / 123456)</li>";
    echo "<li>Or <a href='http://localhost/index.php'>Register a new account</a></li>";
    echo "</ol>";

    $conn->close();

} catch (Exception $e) {
    echo "<h2>❌ Error: " . $e->getMessage() . "</h2>";
    echo "<p>Make sure MySQL is running on port 3306</p>";
}
?>
