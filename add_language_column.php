<?php
// Script to add language column to users table
require_once __DIR__ . "/backend/config.php";

// Check if column exists
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'language'");

if ($result->num_rows == 0) {
    // Add language column
    $sql = "ALTER TABLE users ADD COLUMN language VARCHAR(5) DEFAULT 'en' AFTER role";
    
    if ($conn->query($sql)) {
        echo "✅ Language column added successfully!<br>";
        echo "Default language set to 'en' (English)<br>";
    } else {
        echo "❌ Error adding language column: " . $conn->error . "<br>";
    }
} else {
    echo "ℹ️ Language column already exists!<br>";
}

$conn->close();

echo "<br><a href='/settings.php'>Go to Settings</a>";
?>
