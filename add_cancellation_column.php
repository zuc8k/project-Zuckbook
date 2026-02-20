<?php
require_once __DIR__ . "/backend/config.php";

echo "<h2>Adding Cancellation Column to Support Tickets...</h2>";

// Check if column exists
$checkColumn = $conn->query("SHOW COLUMNS FROM support_tickets LIKE 'cancellation_data'");

if ($checkColumn->num_rows == 0) {
    echo "<p>Adding cancellation_data column...</p>";
    $conn->query("ALTER TABLE support_tickets ADD COLUMN cancellation_data TEXT NULL AFTER message");
    echo "<p style='color: green;'>✓ cancellation_data column added</p>";
} else {
    echo "<p style='color: blue;'>✓ cancellation_data column already exists</p>";
}

echo "<h3 style='color: green;'>✅ Done!</h3>";
echo "<p><a href='/my_subscription.php'>Go to My Subscription</a></p>";
?>
