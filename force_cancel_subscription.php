<?php
session_start();
require_once __DIR__ . "/backend/config.php";

if (!isset($_SESSION['user_id'])) {
    die("Please login first");
}

$user_id = intval($_SESSION['user_id']);

echo "<!DOCTYPE html>
<html lang='ar' dir='rtl'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Force Cancel Subscription</title>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f2f5; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .card { background: white; border-radius: 12px; padding: 25px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h1 { color: #1877f2; margin-bottom: 20px; }
        h2 { color: #050505; margin: 20px 0 15px; border-bottom: 2px solid #1877f2; padding-bottom: 10px; }
        .success { color: #10b981; font-weight: bold; }
        .error { color: #ef4444; font-weight: bold; }
        .warning { color: #f59e0b; font-weight: bold; }
        .info { color: #3b82f6; font-weight: bold; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 8px; overflow-x: auto; font-size: 13px; }
        .btn { display: inline-block; padding: 12px 24px; background: #ef4444; color: white; text-decoration: none; border-radius: 8px; margin: 5px; font-weight: 600; border: none; cursor: pointer; }
        .btn:hover { background: #dc2626; }
        .btn-success { background: #10b981; }
        .btn-success:hover { background: #059669; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        table th, table td { padding: 12px; text-align: right; border-bottom: 1px solid #e5e7eb; }
        table th { background: #f9fafb; font-weight: 600; }
    </style>
</head>
<body>
<div class='container'>
    <h1><i class='fas fa-tools'></i> Force Cancel Subscription</h1>";

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

echo "<div class='card'>
    <h2>Current User Data</h2>
    <table>
        <tr><th>Field</th><th>Value</th></tr>
        <tr><td>ID</td><td>{$user['id']}</td></tr>
        <tr><td>Name</td><td>{$user['name']}</td></tr>
        <tr><td>Coins</td><td>{$user['coins']}</td></tr>
        <tr><td>Subscription Tier</td><td><strong>{$user['subscription_tier']}</strong></td></tr>
        <tr><td>Subscription Expires</td><td>{$user['subscription_expires']}</td></tr>
        <tr><td>Is Verified</td><td><strong>" . ($user['is_verified'] ? 'YES (1)' : 'NO (0)') . "</strong></td></tr>
    </table>
</div>";

// Check if user has active subscription
$hasActiveSubscription = false;
if ($user['subscription_tier'] && $user['subscription_tier'] !== 'free' && $user['subscription_expires']) {
    $hasActiveSubscription = strtotime($user['subscription_expires']) > time();
}

if (!$hasActiveSubscription) {
    echo "<div class='card'>
        <p class='warning'><i class='fas fa-exclamation-triangle'></i> No active subscription found!</p>
    </div>";
} else {
    echo "<div class='card'>
        <p class='info'><i class='fas fa-check-circle'></i> Active subscription found!</p>
    </div>";
}

// Process cancellation if requested
if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    echo "<div class='card'>
        <h2 class='error'>Processing Cancellation...</h2>";
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        echo "<p>Starting transaction...</p>";
        
        // Get current data
        $beforeStmt = $conn->prepare("SELECT subscription_tier, subscription_expires, is_verified, coins FROM users WHERE id = ?");
        $beforeStmt->bind_param("i", $user_id);
        $beforeStmt->execute();
        $before = $beforeStmt->get_result()->fetch_assoc();
        
        echo "<p><strong>Before:</strong></p>";
        echo "<pre>" . print_r($before, true) . "</pre>";
        
        // Cancel subscription
        $updateStmt = $conn->prepare("
            UPDATE users 
            SET subscription_tier = 'free',
                subscription_expires = NULL,
                is_verified = 0
            WHERE id = ?
        ");
        $updateStmt->bind_param("i", $user_id);
        
        if ($updateStmt->execute()) {
            echo "<p class='success'><i class='fas fa-check'></i> User updated successfully</p>";
            echo "<p>Affected rows: " . $updateStmt->affected_rows . "</p>";
        } else {
            throw new Exception("Failed to update user: " . $conn->error);
        }
        
        // Get data after update
        $afterStmt = $conn->prepare("SELECT subscription_tier, subscription_expires, is_verified, coins FROM users WHERE id = ?");
        $afterStmt->bind_param("i", $user_id);
        $afterStmt->execute();
        $after = $afterStmt->get_result()->fetch_assoc();
        
        echo "<p><strong>After:</strong></p>";
        echo "<pre>" . print_r($after, true) . "</pre>";
        
        // Update subscription record
        $checkSubTable = $conn->query("SHOW TABLES LIKE 'subscriptions'");
        if ($checkSubTable->num_rows > 0) {
            $updateSubStmt = $conn->prepare("
                UPDATE subscriptions 
                SET status = 'cancelled' 
                WHERE user_id = ? 
                AND status = 'active'
            ");
            $updateSubStmt->bind_param("i", $user_id);
            
            if ($updateSubStmt->execute()) {
                echo "<p class='success'><i class='fas fa-check'></i> Subscription record updated</p>";
                echo "<p>Affected rows: " . $updateSubStmt->affected_rows . "</p>";
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        echo "<h3 class='success'><i class='fas fa-check-circle'></i> CANCELLATION COMPLETED SUCCESSFULLY!</h3>";
        echo "<p><a href='force_cancel_subscription.php' class='btn btn-success'>Refresh Page</a></p>";
        echo "<p><a href='home.php' class='btn btn-success'>Go to Home (Press Ctrl+F5)</a></p>";
        
    } catch (Exception $e) {
        $conn->rollback();
        echo "<p class='error'><i class='fas fa-times-circle'></i> Error: " . $e->getMessage() . "</p>";
    }
    
    echo "</div>";
} else {
    if ($hasActiveSubscription) {
        echo "<div class='card'>
            <h2>Ready to Cancel?</h2>
            <p class='warning'><i class='fas fa-exclamation-triangle'></i> This will:</p>
            <ul style='margin: 15px 0 15px 30px; line-height: 2;'>
                <li>Set subscription_tier to 'free'</li>
                <li>Set subscription_expires to NULL</li>
                <li>Set is_verified to 0</li>
                <li>Update subscription status to 'cancelled'</li>
            </ul>
            <p><a href='?confirm=yes' class='btn'>YES, CANCEL MY SUBSCRIPTION NOW</a></p>
        </div>";
    }
}

echo "<div class='card'>
    <h2>Quick Links</h2>
    <a href='home.php' class='btn btn-success'>Home</a>
    <a href='my_subscription.php' class='btn btn-success'>My Subscription</a>
    <a href='test_system.php' class='btn btn-success'>System Test</a>
</div>";

echo "</div>
</body>
</html>";
?>
