<?php
require_once __DIR__ . "/backend/config.php";

echo "<h2>Support Tickets Table Structure</h2>";

// Check if table exists
$tableExists = $conn->query("SHOW TABLES LIKE 'support_tickets'");

if ($tableExists->num_rows == 0) {
    echo "<p style='color: red;'>Table 'support_tickets' does not exist!</p>";
    echo "<p>Creating table...</p>";
    
    $createTable = "CREATE TABLE support_tickets (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        subject VARCHAR(255) NOT NULL DEFAULT 'Support Request',
        message TEXT NOT NULL,
        status VARCHAR(50) DEFAULT 'open',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        cancellation_data TEXT NULL,
        INDEX idx_user (user_id),
        INDEX idx_status (status)
    )";
    
    if ($conn->query($createTable)) {
        echo "<p style='color: green;'>✓ Table created successfully!</p>";
    } else {
        echo "<p style='color: red;'>✗ Failed to create table: " . $conn->error . "</p>";
    }
} else {
    echo "<p style='color: green;'>✓ Table exists</p>";
}

// Show current structure
$result = $conn->query("DESCRIBE support_tickets");

echo "<h3>Current Columns:</h3>";
echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr style='background: #f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";

$columns = [];
while ($row = $result->fetch_assoc()) {
    $columns[] = $row['Field'];
    echo "<tr>";
    echo "<td><strong>{$row['Field']}</strong></td>";
    echo "<td>{$row['Type']}</td>";
    echo "<td>{$row['Null']}</td>";
    echo "<td>{$row['Key']}</td>";
    echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>Required Columns Check:</h3>";

$requiredColumns = [
    'id' => 'INT PRIMARY KEY AUTO_INCREMENT',
    'user_id' => 'INT NOT NULL',
    'subject' => 'VARCHAR(255) NOT NULL',
    'message' => 'TEXT NOT NULL',
    'status' => 'VARCHAR(50) DEFAULT \'open\'',
    'created_at' => 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP',
    'cancellation_data' => 'TEXT NULL'
];

foreach ($requiredColumns as $col => $type) {
    if (in_array($col, $columns)) {
        echo "<p style='color: green;'>✓ Column '$col' exists</p>";
    } else {
        echo "<p style='color: orange;'>⚠ Column '$col' is missing - Adding it...</p>";
        
        // Add missing column
        $alterQuery = "ALTER TABLE support_tickets ADD COLUMN $col $type";
        if ($conn->query($alterQuery)) {
            echo "<p style='color: green;'>✓ Added column '$col'</p>";
        } else {
            echo "<p style='color: red;'>✗ Failed to add '$col': " . $conn->error . "</p>";
        }
    }
}

echo "<h3 style='color: green;'>✅ Structure Check Complete!</h3>";
echo "<p><a href='/my_subscription.php' style='padding: 10px 20px; background: #1877f2; color: white; text-decoration: none; border-radius: 8px; display: inline-block;'>Back to My Subscription</a></p>";
?>
