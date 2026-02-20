<?php
require_once __DIR__ . "/backend/config.php";

echo "<h2>Adding Subscription Columns to Users Table...</h2>";

// Check if columns exist
$checkColumns = $conn->query("SHOW COLUMNS FROM users LIKE 'subscription_tier'");

if ($checkColumns->num_rows == 0) {
    echo "<p>Adding subscription_tier column...</p>";
    $conn->query("ALTER TABLE users ADD COLUMN subscription_tier VARCHAR(50) DEFAULT 'free' AFTER role");
    echo "<p style='color: green;'>✓ subscription_tier column added</p>";
} else {
    echo "<p style='color: blue;'>✓ subscription_tier column already exists</p>";
}

$checkExpires = $conn->query("SHOW COLUMNS FROM users LIKE 'subscription_expires'");

if ($checkExpires->num_rows == 0) {
    echo "<p>Adding subscription_expires column...</p>";
    $conn->query("ALTER TABLE users ADD COLUMN subscription_expires DATETIME NULL AFTER subscription_tier");
    echo "<p style='color: green;'>✓ subscription_expires column added</p>";
} else {
    echo "<p style='color: blue;'>✓ subscription_expires column already exists</p>";
}

echo "<h3 style='color: green;'>✅ Done! Subscription system is ready.</h3>";
echo "<p><a href='/subscriptions.php'>Go to Subscriptions Page</a></p>";
?>
