<?php
session_start();
require_once __DIR__ . "/backend/config.php";
require_once __DIR__ . "/includes/helpers.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: /");
    exit;
}

$user_id = intval($_SESSION['user_id']);

/* ================= CURRENT USER DATA ================= */

$userStmt = $conn->prepare("SELECT id, coins, is_verified, name, profile_image, is_banned, ban_expires_at FROM users WHERE id = ?");
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$userData = $userStmt->get_result()->fetch_assoc();

if (!$userData) {
    session_destroy();
    exit;
}

$userName = htmlspecialchars($userData['name']);
$userImage = $userData['profile_image'] ? "/uploads/" . htmlspecialchars($userData['profile_image']) : "/assets/zuckuser.png";
$userCoins = $userData['coins'];

/* ================= GET NOTIFICATIONS ================= */

$stmt = $conn->prepare("
    SELECT id, message, type, is_read, created_at
    FROM notifications
    WHERE user_id = ?
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$notifications = $stmt->get_result();

/* ================= MARK ALL AS READ ================= */

$updateStmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
$updateStmt->bind_param("i", $user_id);
$updateStmt->execute();

$notifStmt = $conn->prepare("SELECT COUNT(*) as total FROM notifications WHERE user_id = ? AND is_read = 0");
$notifStmt->bind_param("i", $user_id);
$notifStmt->execute();
$unread = $notifStmt->get_result()->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notifications - ZuckBook</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f0f2f5; }

.header { background: white; border-bottom: 1px solid #ccc; padding: 8px 16px; position: sticky; top: 0; z-index: 100; }
.header-content { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; gap: 16px; }
.logo { font-size: 28px; font-weight: bold; color: #1877f2; cursor: pointer; }
.search-box input { padding: 8px 16px; border: 1px solid #ccc; border-radius: 20px; background: #f0f2f5; width: 240px; }
.header-icons { display: flex; gap: 8px; }
.header-icon { width: 36px; height: 36px; border-radius: 50%; background: #f0f2f5; border: none; cursor: pointer; font-size: 18px; }
.user-avatar { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; cursor: pointer; }

.container { max-width: 1200px; margin: 16px auto; display: grid; grid-template-columns: 280px 1fr 360px; gap: 16px; padding: 0 16px; }

.sidebar { background: white; border-radius: 8px; padding: 8px 0; height: fit-content; position: sticky; top: 60px; }
.sidebar-item { padding: 8px 16px; display: flex; align-items: center; gap: 12px; cursor: pointer; text-decoration: none; color: #050505; font-size: 15px; }
.sidebar-item:hover { background: #f2f2f2; }
.sidebar-item.active { background: #f0f2f5; font-weight: 600; }

.content { background: white; border-radius: 8px; overflow: hidden; }
.content-header { padding: 16px; border-bottom: 1px solid #e5e7eb; font-size: 20px; font-weight: bold; }

.notification { padding: 12px 16px; border-bottom: 1px solid #e5e7eb; display: flex; gap: 12px; align-items: flex-start; cursor: pointer; transition: 0.2s; }
.notification:hover { background: #f0f2f5; }
.notification.unread { background: #f0f2f5; }

.notif-icon { width: 40px; height: 40px; border-radius: 50%; background: #e7f3ff; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }

.notif-content { flex: 1; }
.notif-message { font-size: 14px; color: #050505; margin-bottom: 4px; }
.notif-time { font-size: 12px; color: #65676b; }

.empty { text-align: center; padding: 60px 20px; color: #65676b; }
.empty-icon { font-size: 48px; margin-bottom: 12px; }

.right-sidebar { background: white; border-radius: 8px; padding: 16px; height: fit-content; position: sticky; top: 60px; }
.right-sidebar-title { font-weight: 600; font-size: 15px; margin-bottom: 12px; }

@media (max-width: 1024px) {
    .container { grid-template-columns: 1fr; }
    .sidebar, .right-sidebar { display: none; }
}
</style>
</head>
<body>

<div class="header">
    <div class="header-content">
        <div class="logo" onclick="window.location.href='/home.php'">f</div>
        <div class="search-box">
            <input type="text" placeholder="<i class="fas fa-search"></i> Search ZuckBook">
        </div>
        <div class="header-icons">
            <button class="header-icon" onclick="window.location.href='/home.php'"><i class="fas fa-home"></i></button>
            <button class="header-icon"><i class="fas fa-user-friends"></i></button>
            <button class="header-icon"><i class="fas fa-gamepad"></i></button>
        </div>
        <div style="display: flex; gap: 8px; align-items: center;">
            <button class="header-icon" onclick="window.location.href='/notifications.php'"><i class="fas fa-bell"></i></button>
            <img src="<?= $userImage ?>" class="user-avatar" onclick="window.location.href='/profile.php?id=<?= $user_id ?>'">
        </div>
    </div>
</div>

<div class="container">
    <div class="sidebar">
        <a href="/profile.php?id=<?= $user_id ?>" class="sidebar-item"><i class="fas fa-user"></i> <?= $userName ?></a>
        <a href="/friend_requests.php" class="sidebar-item"><i class="fas fa-user-friends"></i> Friends</a>
        <a href="/coins.php" class="sidebar-item"><i class="fas fa-coins"></i> Coins (<?= formatCoins($userCoins) ?>)</a>
        <a href="/notifications.php" class="sidebar-item active"><i class="fas fa-bell"></i> Notifications</a>
        <a href="/settings.php" class="sidebar-item"><i class="fas fa-cog"></i> Settings</a>
        <a href="/backend/logout.php" class="sidebar-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
    
    <div class="content">
        <div class="content-header"><i class="fas fa-bell"></i> Notifications</div>
        
        <?php if ($notifications->num_rows == 0): ?>
            <div class="empty">
                <div class="empty-icon"><i class="fas fa-bell"></i></div>
                <div>No notifications yet</div>
            </div>
        <?php else: ?>
            <?php while($n = $notifications->fetch_assoc()): 
                $time = strtotime($n['created_at']);
                $diff = time() - $time;

                if ($diff < 60) {
                    $timeText = $diff . "s ago";
                } elseif ($diff < 3600) {
                    $timeText = floor($diff/60) . "m ago";
                } elseif ($diff < 86400) {
                    $timeText = floor($diff/3600) . "h ago";
                } else {
                    $timeText = floor($diff/86400) . "d ago";
                }

                $icon = '<i class="fas fa-bell"></i>';
                if($n['type'] == 'friend') $icon = '<i class="fas fa-user-friends"></i>';
                if($n['type'] == 'message') $icon = '<i class="fas fa-comment"></i>';
                if($n['type'] == 'like') $icon = '<i class="fas fa-thumbs-up"></i>';
                if($n['type'] == 'comment') $icon = '<i class="fas fa-comment-dots"></i>';
            ?>
            <div class="notification <?= $n['is_read'] ? '' : 'unread' ?>">
                <div class="notif-icon"><?= $icon ?></div>
                <div class="notif-content">
                    <div class="notif-message"><?= htmlspecialchars($n['message']) ?></div>
                    <div class="notif-time"><?= $timeText ?></div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
    
    <div class="right-sidebar">
        <div class="right-sidebar-title"><i class="fas fa-lightbulb"></i> Tips</div>
        <div style="font-size: 14px; color: #65676b; line-height: 1.6;">
            <p>• Stay updated with notifications</p>
            <p>• Never miss friend requests</p>
            <p>• Get alerts on new messages</p>
        </div>
    </div>
</div>

</body>
</html>