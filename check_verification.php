<?php
session_start();
require_once __DIR__ . "/backend/config.php";

if (!isset($_SESSION['user_id'])) {
    die("Please login first");
}

$user_id = intval($_SESSION['user_id']);

$stmt = $conn->prepare("SELECT id, name, is_verified, subscription_tier, subscription_expires FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

echo "<h2>Current User Data:</h2>";
echo "<pre>";
print_r($user);
echo "</pre>";

echo "<h3>Verification Status:</h3>";
echo "is_verified: " . ($user['is_verified'] ? 'YES (1)' : 'NO (0)') . "<br>";
echo "subscription_tier: " . ($user['subscription_tier'] ?? 'NULL') . "<br>";
echo "subscription_expires: " . ($user['subscription_expires'] ?? 'NULL') . "<br>";

if ($user['subscription_expires']) {
    $isActive = strtotime($user['subscription_expires']) > time();
    echo "Subscription Active: " . ($isActive ? 'YES' : 'NO (EXPIRED)') . "<br>";
    echo "Days remaining: " . ceil((strtotime($user['subscription_expires']) - time()) / 86400) . "<br>";
}

echo "<br><a href='/home.php'>Back to Home</a>";
?>
