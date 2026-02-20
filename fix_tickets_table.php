<?php
require_once __DIR__ . "/backend/config.php";

echo "<h2>Checking support_tickets table structure...</h2>";

// Check current structure
$result = $conn->query("DESCRIBE support_tickets");

echo "<h3>Current Columns:</h3>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";

$hasSubject = false;
$hasCancellationData = false;

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['Field']}</td>";
    echo "<td>{$row['Type']}</td>";
    echo "<td>{$row['Null']}</td>";
    echo "<td>{$row['Key']}</td>";
    echo "<td>{$row['Default']}</td>";
    echo "</tr>";
    
    if ($row['Field'] === 'subject') {
        $hasSubject = true;
    }
    if ($row['Field'] === 'cancellation_data') {
        $hasCancellationData = true;
    }
}
echo "</table>";

echo "<h3>Fixes Needed:</h3>";

// Add subject column if missing
if (!$hasSubject) {
    echo "<p style='color: orange;'>Adding 'subject' column...</p>";
    $conn->query("ALTER TABLE support_tickets ADD COLUMN subject VARCHAR(255) NOT NULL DEFAULT 'Support Request' AFTER user_id");
    echo "<p style='color: green;'>✓ Added 'subject' column</p>";
} else {
    echo "<p style='color: green;'>✓ 'subject' column exists</p>";
}

// Add cancellation_data column if missing
if (!$hasCancellationData) {
    echo "<p style='color: orange;'>Adding 'cancellation_data' column...</p>";
    $conn->query("ALTER TABLE support_tickets ADD COLUMN cancellation_data TEXT NULL AFTER message");
    echo "<p style='color: green;'>✓ Added 'cancellation_data' column</p>";
} else {
    echo "<p style='color: green;'>✓ 'cancellation_data' column exists</p>";
}

echo "<h3 style='color: green;'>✅ All Done!</h3>";
echo "<p><a href='/my_subscription.php'>Back to My Subscription</a></p>";
?>
