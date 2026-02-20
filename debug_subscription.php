<?php
session_start();
require_once __DIR__ . "/backend/config.php";

if (!isset($_SESSION['user_id'])) {
    die("Please login first");
}

$user_id = intval($_SESSION['user_id']);

echo "<h2>Subscription Debug Tool</h2>";
echo "<style>body { font-family: Arial; padding: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; } pre { background: #f5f5f5; padding: 15px; border-radius: 8px; }</style>";

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

echo "<h3>Current User Data:</h3>";
echo "<pre>";
print_r($user);
echo "</pre>";

// Check subscriptions table
echo "<h3>Subscriptions Table Check:</h3>";
$checkTable = $conn->query("SHOW TABLES LIKE 'subscriptions'");
if ($checkTable->num_rows > 0) {
    echo "<p class='success'>✓ Subscriptions table exists</p>";
    
    // Get user subscriptions
    $subStmt = $conn->prepare("SELECT * FROM subscriptions WHERE user_id = ? ORDER BY started_at DESC");
    $subStmt->bind_param("i", $user_id);
    $subStmt->execute();
    $subscriptions = $subStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo "<h4>Your Subscriptions:</h4>";
    echo "<pre>";
    print_r($subscriptions);
    echo "</pre>";
} else {
    echo "<p class='error'>✗ Subscriptions table does not exist</p>";
}

// Check required columns in users table
echo "<h3>Users Table Columns Check:</h3>";
$requiredColumns = ['subscription_tier', 'subscription_expires', 'is_verified', 'coins'];
$result = $conn->query("SHOW COLUMNS FROM users");
$existingColumns = [];
while ($row = $result->fetch_assoc()) {
    $existingColumns[] = $row['Field'];
}

foreach ($requiredColumns as $col) {
    if (in_array($col, $existingColumns)) {
        echo "<p class='success'>✓ Column '{$col}' exists</p>";
    } else {
        echo "<p class='error'>✗ Column '{$col}' is missing</p>";
    }
}

// Test subscription purchase simulation
if (isset($_GET['test']) && $_GET['test'] === 'purchase') {
    echo "<hr><h3 class='info'>Testing Subscription Purchase...</h3>";
    
    $plan = 'basic';
    $billing = 'monthly';
    $price = 30;
    
    try {
        $conn->begin_transaction();
        
        // Get current coins
        $stmt = $conn->prepare("SELECT coins FROM users WHERE id = ? FOR UPDATE");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $userData = $result->fetch_assoc();
        
        $currentCoins = $userData['coins'];
        echo "<p>Current coins: {$currentCoins}</p>";
        
        if ($currentCoins < $price) {
            throw new Exception("Not enough coins");
        }
        
        $newCoins = $currentCoins - $price;
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 month'));
        
        // Update user
        $updateStmt = $conn->prepare("
            UPDATE users 
            SET coins = ?, 
                subscription_tier = ?, 
                subscription_expires = ?,
                is_verified = 1
            WHERE id = ?
        ");
        $updateStmt->bind_param("issi", $newCoins, $plan, $expiresAt, $user_id);
        
        if ($updateStmt->execute()) {
            echo "<p class='success'>✓ User updated successfully</p>";
        } else {
            throw new Exception("Failed to update user: " . $conn->error);
        }
        
        // Create subscription record
        $checkTable = $conn->query("SHOW TABLES LIKE 'subscriptions'");
        if ($checkTable->num_rows > 0) {
            $insertStmt = $conn->prepare("
                INSERT INTO subscriptions (user_id, plan, billing_type, price, expires_at) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $insertStmt->bind_param("issis", $user_id, $plan, $billing, $price, $expiresAt);
            
            if ($insertStmt->execute()) {
                echo "<p class='success'>✓ Subscription record created</p>";
            } else {
                throw new Exception("Failed to create subscription: " . $conn->error);
            }
        }
        
        $conn->commit();
        echo "<p class='success'><strong>✓ TEST PURCHASE SUCCESSFUL!</strong></p>";
        echo "<p>New coins: {$newCoins}</p>";
        echo "<p>Expires: {$expiresAt}</p>";
        echo "<p><a href='debug_subscription.php'>Refresh to see changes</a></p>";
        
    } catch (Exception $e) {
        $conn->rollback();
        echo "<p class='error'>✗ Error: " . $e->getMessage() . "</p>";
    }
}

echo "<hr>";
echo "<p><a href='?test=purchase' style='padding: 10px 20px; background: #1877f2; color: white; text-decoration: none; border-radius: 8px;'>Test Purchase (Basic Monthly - 30 coins)</a></p>";
echo "<p><a href='subscriptions.php' style='padding: 10px 20px; background: #10b981; color: white; text-decoration: none; border-radius: 8px;'>Go to Subscriptions Page</a></p>";
echo "<p><a href='home.php' style='padding: 10px 20px; background: #65676b; color: white; text-decoration: none; border-radius: 8px;'>Back to Home</a></p>";
?>
