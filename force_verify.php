<?php
session_start();
require_once __DIR__ . "/backend/config.php";

if (!isset($_SESSION['user_id'])) {
    die("Please login first");
}

$user_id = intval($_SESSION['user_id']);

// Get current user data
$stmt = $conn->prepare("SELECT id, name, is_verified, subscription_tier, subscription_expires FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

echo "<h2>Before Update:</h2>";
echo "<pre>";
print_r($user);
echo "</pre>";

// Check if user has active subscription
$hasActiveSubscription = false;
if ($user['subscription_tier'] && $user['subscription_tier'] !== 'free' && $user['subscription_expires']) {
    $hasActiveSubscription = strtotime($user['subscription_expires']) > time();
}

echo "<h3>Has Active Subscription: " . ($hasActiveSubscription ? 'YES' : 'NO') . "</h3>";

// If has active subscription but not verified, fix it
if ($hasActiveSubscription && $user['is_verified'] != 1) {
    echo "<h3 style='color: red;'>FIXING: User has active subscription but is_verified = 0</h3>";
    
    $updateStmt = $conn->prepare("UPDATE users SET is_verified = 1 WHERE id = ?");
    $updateStmt->bind_param("i", $user_id);
    
    if ($updateStmt->execute()) {
        echo "<h3 style='color: green;'>✓ Fixed! is_verified set to 1</h3>";
    } else {
        echo "<h3 style='color: red;'>✗ Failed to update</h3>";
    }
    
    // Get updated data
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    echo "<h2>After Update:</h2>";
    echo "<pre>";
    print_r($user);
    echo "</pre>";
}

// If subscription expired but still verified, remove verification
if (!$hasActiveSubscription && $user['is_verified'] == 1) {
    echo "<h3 style='color: orange;'>FIXING: Subscription expired but is_verified = 1</h3>";
    
    $updateStmt = $conn->prepare("UPDATE users SET is_verified = 0 WHERE id = ?");
    $updateStmt->bind_param("i", $user_id);
    
    if ($updateStmt->execute()) {
        echo "<h3 style='color: green;'>✓ Fixed! is_verified set to 0</h3>";
    } else {
        echo "<h3 style='color: red;'>✗ Failed to update</h3>";
    }
    
    // Get updated data
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    echo "<h2>After Update:</h2>";
    echo "<pre>";
    print_r($user);
    echo "</pre>";
}

echo "<br><br>";
echo "<a href='/home.php' style='padding: 10px 20px; background: #1877f2; color: white; text-decoration: none; border-radius: 8px;'>Go to Home (Press Ctrl+F5 to refresh)</a>";
?>
