<?php
require_once __DIR__ . "/backend/config.php";

try {
    // Check if last_seen column exists
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'last_seen'");
    
    if ($result->num_rows == 0) {
        // Add last_seen column
        $conn->query("ALTER TABLE users ADD COLUMN last_seen DATETIME DEFAULT CURRENT_TIMESTAMP");
        echo "✓ Added last_seen column to users table\n";
    } else {
        echo "✓ last_seen column already exists\n";
    }
    
    // Update all users to have current timestamp
    $conn->query("UPDATE users SET last_seen = NOW() WHERE last_seen IS NULL");
    echo "✓ Updated existing users with current timestamp\n";
    
    echo "\nSetup complete! You can now delete this file.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
