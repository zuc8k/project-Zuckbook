<?php
require_once __DIR__ . "/backend/config.php";

try {
    // Check if column exists
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'profile_setup_completed'");
    
    if ($result->num_rows == 0) {
        // Add column
        $conn->query("ALTER TABLE users ADD COLUMN profile_setup_completed TINYINT(1) DEFAULT 0");
        echo "✓ Added profile_setup_completed column to users table\n";
    } else {
        echo "✓ profile_setup_completed column already exists\n";
    }
    
    echo "\nSetup complete! You can now delete this file.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
