<?php
session_start();
require_once __DIR__ . "/backend/config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: /");
    exit;
}

$user_id = intval($_SESSION['user_id']);

$userStmt = $conn->prepare("
    SELECT id, coins, is_verified, name, role, profile_image, is_banned, ban_expires_at
    FROM users
    WHERE id = ?
");
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$userData = $userStmt->get_result()->fetch_assoc();

if (!$userData) {
    session_destroy();
    exit;
}

if (
    $userData['is_banned'] == 1 &&
    (
        $userData['ban_expires_at'] === NULL ||
        $userData['ban_expires_at'] > date("Y-m-d H:i:s")
    )
) {
    die("Your account has been banned.");
}

$userCoins  = $userData['coins'];
$isVerified = $userData['is_verified'];
$userName = htmlspecialchars($userData['name']);
$userImage = $userData['profile_image'] ? "/uploads/" . htmlspecialchars($userData['profile_image']) : "/assets/zuckuser.png";

