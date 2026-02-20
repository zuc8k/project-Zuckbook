<?php
// ============================================
// ZuckBook Database Setup
// ============================================

$db_host = 'localhost';
$db_user = 'root';
$db_pass = '197520error';
$db_port = 3306; // XAMPP uses 3306

// Try to connect with error handling
try {
    $conn = @new mysqli($db_host, $db_user, $db_pass, '', $db_port);
    
    if ($conn->connect_error) {
        throw new Exception($conn->connect_error);
    }
} catch (Exception $e) {
    die("<h2>❌ MySQL Connection Error</h2>
    <p><strong>Error:</strong> " . $e->getMessage() . "</p>
    <p><strong>Solution:</strong></p>
    <ul>
        <li>Make sure MySQL is running in XAMPP Control Panel</li>
        <li>Click 'Start' button next to MySQL</li>
        <li>Wait 5 seconds and refresh this page</li>
    </ul>");
}

// Read SQL file
$sql_file = __DIR__ . '/ZuckBook/database/zuckbook_database.sql';

if (!file_exists($sql_file)) {
    die("<h2>❌ SQL file not found</h2><p>" . $sql_file . "</p>");
}

$sql = file_get_contents($sql_file);

// Execute SQL
if ($conn->multi_query($sql)) {
    echo "<h2>✅ Database setup completed successfully!</h2>";
    echo "<p>Redirecting to application...</p>";
    echo "<script>setTimeout(() => window.location.href = 'http://localhost', 2000);</script>";
    
    // Consume all results
    do {
        if ($result = $conn->store_result()) {
            $result->free();
        }
    } while ($conn->next_result());
} else {
    echo "<h2>❌ Error setting up database</h2>";
    echo "<p>" . $conn->error . "</p>";
}

$conn->close();
?>
