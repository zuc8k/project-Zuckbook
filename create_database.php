<?php
// Script to create zuckbook database if it doesn't exist

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '197520error';
$db_name = 'zuckbook';
$db_port = 3306;

// Connect without database name first
try {
    $conn = new mysqli($db_host, $db_user, $db_pass, '', $db_port);
    $conn->set_charset("utf8mb4");
    
    echo "<h2>Database Setup</h2>";
    
    // Check if database exists
    $result = $conn->query("SHOW DATABASES LIKE '$db_name'");
    
    if ($result->num_rows > 0) {
        echo "✅ Database '$db_name' already exists!<br>";
    } else {
        // Create database
        $sql = "CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        
        if ($conn->query($sql)) {
            echo "✅ Database '$db_name' created successfully!<br>";
        } else {
            echo "❌ Error creating database: " . $conn->error . "<br>";
            exit;
        }
    }
    
    // Select the database
    $conn->select_db($db_name);
    
    // Check if users table exists
    $result = $conn->query("SHOW TABLES LIKE 'users'");
    
    if ($result->num_rows > 0) {
        echo "✅ Tables already exist!<br>";
        
        // Check if language column exists
        $result = $conn->query("SHOW COLUMNS FROM users LIKE 'language'");
        if ($result->num_rows == 0) {
            $sql = "ALTER TABLE users ADD COLUMN language VARCHAR(5) DEFAULT 'en' AFTER role";
            if ($conn->query($sql)) {
                echo "✅ Language column added to users table!<br>";
            }
        } else {
            echo "✅ Language column already exists!<br>";
        }
        
    } else {
        echo "⚠️ Database exists but tables are missing!<br>";
        echo "Please import the SQL file from ZuckBook/database/zuckbook_database.sql<br>";
        echo "<br><strong>Steps:</strong><br>";
        echo "1. Open phpMyAdmin (http://localhost/phpmyadmin)<br>";
        echo "2. Select 'zuckbook' database<br>";
        echo "3. Click 'Import' tab<br>";
        echo "4. Choose file: ZuckBook/database/zuckbook_database.sql<br>";
        echo "5. Click 'Go'<br>";
    }
    
    $conn->close();
    
    echo "<br><br>";
    echo "<a href='/backend/login.php' style='padding: 10px 20px; background: #1877f2; color: white; text-decoration: none; border-radius: 6px;'>Go to Login</a> ";
    echo "<a href='/settings.php' style='padding: 10px 20px; background: #42b72a; color: white; text-decoration: none; border-radius: 6px; margin-left: 10px;'>Go to Settings</a>";
    
} catch (mysqli_sql_exception $e) {
    echo "<h2>❌ Connection Error</h2>";
    echo "<p>Could not connect to MySQL server.</p>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<br>";
    echo "<p><strong>Please check:</strong></p>";
    echo "<ul>";
    echo "<li>MySQL/XAMPP is running</li>";
    echo "<li>Username: $db_user</li>";
    echo "<li>Password: $db_pass</li>";
    echo "<li>Host: $db_host</li>";
    echo "<li>Port: $db_port</li>";
    echo "</ul>";
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Database Setup - ZuckBook</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f0f2f5;
            padding: 40px;
            max-width: 800px;
            margin: 0 auto;
        }
        h2 {
            color: #1877f2;
        }
        ul {
            background: white;
            padding: 20px 40px;
            border-radius: 8px;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }
        li {
            margin: 10px 0;
        }
    </style>
</head>
<body>
</body>
</html>
