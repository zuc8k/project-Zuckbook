<?php
session_start();
require_once __DIR__ . "/backend/config.php";

echo "<h1>Remember Me System Test</h1>";
echo "<hr>";

// Check session
echo "<h2>Session Status:</h2>";
if (isset($_SESSION['user_id'])) {
    echo "✅ Logged in as User ID: " . $_SESSION['user_id'] . "<br>";
    echo "Username: " . $_SESSION['username'] . "<br>";
} else {
    echo "❌ Not logged in<br>";
}

echo "<hr>";

// Check cookie
echo "<h2>Cookie Status:</h2>";
if (isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    echo "✅ Remember token cookie exists<br>";
    echo "Token (first 20 chars): " . substr($token, 0, 20) . "...<br>";
    
    // Check in database
    $stmt = $conn->prepare("
        SELECT rt.*, u.username, u.email 
        FROM remember_tokens rt
        JOIN users u ON rt.user_id = u.id
        WHERE rt.token = ?
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result) {
        echo "<br><strong>Token found in database:</strong><br>";
        echo "User ID: " . $result['user_id'] . "<br>";
        echo "Username: " . $result['username'] . "<br>";
        echo "Email: " . $result['email'] . "<br>";
        echo "Expires: " . $result['expires_at'] . "<br>";
        echo "Created: " . $result['created_at'] . "<br>";
        
        $expires = strtotime($result['expires_at']);
        $now = time();
        if ($expires > $now) {
            $days_left = floor(($expires - $now) / 86400);
            echo "<span style='color: green;'>✅ Token is valid ({$days_left} days remaining)</span><br>";
        } else {
            echo "<span style='color: red;'>❌ Token has expired</span><br>";
        }
    } else {
        echo "<span style='color: red;'>❌ Token not found in database</span><br>";
    }
} else {
    echo "❌ No remember token cookie<br>";
}

echo "<hr>";

// Check table
echo "<h2>Database Table Status:</h2>";
$checkTable = $conn->query("SHOW TABLES LIKE 'remember_tokens'");
if ($checkTable->num_rows > 0) {
    echo "✅ remember_tokens table exists<br>";
    
    // Count tokens
    $count = $conn->query("SELECT COUNT(*) as total FROM remember_tokens")->fetch_assoc()['total'];
    echo "Total tokens in database: {$count}<br>";
    
    // Show all tokens
    echo "<br><strong>All tokens:</strong><br>";
    $tokens = $conn->query("
        SELECT rt.*, u.username 
        FROM remember_tokens rt
        JOIN users u ON rt.user_id = u.id
        ORDER BY rt.created_at DESC
    ");
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>User ID</th><th>Username</th><th>Token (first 20)</th><th>Expires</th><th>Status</th></tr>";
    while ($row = $tokens->fetch_assoc()) {
        $status = strtotime($row['expires_at']) > time() ? '✅ Valid' : '❌ Expired';
        echo "<tr>";
        echo "<td>{$row['user_id']}</td>";
        echo "<td>{$row['username']}</td>";
        echo "<td>" . substr($row['token'], 0, 20) . "...</td>";
        echo "<td>{$row['expires_at']}</td>";
        echo "<td>{$status}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ remember_tokens table does not exist<br>";
}

echo "<hr>";
echo "<h2>Actions:</h2>";
echo "<a href='/' style='padding: 10px 20px; background: #1877f2; color: white; text-decoration: none; border-radius: 8px; margin: 5px;'>Go to Login</a> ";
echo "<a href='/backend/logout.php' style='padding: 10px 20px; background: #dc2626; color: white; text-decoration: none; border-radius: 8px; margin: 5px;'>Logout</a> ";
echo "<a href='/home.php' style='padding: 10px 20px; background: #10b981; color: white; text-decoration: none; border-radius: 8px; margin: 5px;'>Go to Home</a>";

echo "<hr>";
echo "<p><small>Check your browser's error log (F12 → Console) for detailed debug messages</small></p>";
?>
