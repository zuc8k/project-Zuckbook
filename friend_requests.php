<?php
session_start();
require_once __DIR__ . "/backend/config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: /");
    exit;
}

$user_id = intval($_SESSION['user_id']);

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

$notifStmt = $conn->prepare("SELECT COUNT(*) as total FROM notifications WHERE user_id = ? AND is_read = 0");
$notifStmt->bind_param("i", $user_id);
$notifStmt->execute();
$unread = $notifStmt->get_result()->fetch_assoc()['total'];

/* Get pending friend requests */
$requestsStmt = $conn->prepare("
    SELECT u.id, u.name, u.profile_image, u.is_verified
    FROM friends f
    JOIN users u ON f.sender_id = u.id
    WHERE f.receiver_id = ? AND f.status = 'pending'
    ORDER BY f.created_at DESC
");
$requestsStmt->bind_param("i", $user_id);
$requestsStmt->execute();
$requests = $requestsStmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Friend Requests - ZuckBook</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f0f2f5; color: #050505; }

.header { background: white; border-bottom: 1px solid #e5e7eb; padding: 0 16px; position: sticky; top: 0; z-index: 100; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.header-content { max-width: 1400px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; height: 56px; }
.header-left { display: flex; align-items: center; gap: 8px; }
.logo { font-size: 28px; font-weight: bold; color: #1877f2; }
.header-right { display: flex; gap: 8px; align-items: center; }
.header-icon { width: 40px; height: 40px; border-radius: 50%; background: #f0f2f5; border: none; cursor: pointer; font-size: 18px; color: #1877f2; transition: 0.2s; display: flex; align-items: center; justify-content: center; }
.header-icon:hover { background: #e4e6eb; }
.user-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; cursor: pointer; }

.main-container { max-width: 940px; margin: 16px auto; padding: 0 16px; }
.page-title { font-size: 32px; font-weight: bold; margin-bottom: 16px; }

.request-card { background: white; border-radius: 8px; padding: 16px; margin-bottom: 12px; display: flex; align-items: center; gap: 12px; box-shadow: 0 1px 2px rgba(0,0,0,0.1); }
.request-avatar { width: 56px; height: 56px; border-radius: 50%; object-fit: cover; cursor: pointer; }
.request-info { flex: 1; }
.request-name { font-weight: 600; font-size: 15px; display: flex; align-items: center; gap: 4px; cursor: pointer; }
.verify-badge { color: #1877f2; font-size: 14px; }
.request-actions { display: flex; gap: 8px; }
.btn { padding: 8px 16px; border-radius: 6px; border: none; font-weight: 600; font-size: 14px; cursor: pointer; transition: 0.2s; }
.btn-primary { background: #1877f2; color: white; }
.btn-primary:hover { background: #166fe5; }
.btn-secondary { background: #e4e6eb; color: #050505; }
.btn-secondary:hover { background: #d8dadf; }

.empty-state { text-align: center; padding: 40px; color: #65676b; }
.empty-state i { font-size: 48px; margin-bottom: 16px; opacity: 0.5; }
</style>
</head>
<body>

<!-- Header -->
<div class="header">
    <div class="header-content">
        <div class="header-left">
            <div class="logo" onclick="window.location.href='/home.php'" style="cursor: pointer;">f</div>
        </div>
        <div class="header-right">
            <img src="<?= $userImage ?>" class="user-avatar" onclick="window.location.href='/profile.php?id=<?= $user_id ?>'">
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="main-container">
    <h1 class="page-title">Friend Requests</h1>
    
    <div id="requestsList">
        <?php if ($requests->num_rows > 0): ?>
            <?php while ($request = $requests->fetch_assoc()): ?>
                <div class="request-card" data-request-id="<?= $request['id'] ?>">
                    <img src="<?= $request['profile_image'] ? '/uploads/' . htmlspecialchars($request['profile_image']) : '/assets/zuckuser.png' ?>" class="request-avatar" onclick="window.location.href='/profile.php?id=<?= $request['id'] ?>'">
                    <div class="request-info">
                        <div class="request-name" onclick="window.location.href='/profile.php?id=<?= $request['id'] ?>'">
                            <?= htmlspecialchars($request['name']) ?>
                            <?php if ($request['is_verified'] == 1): ?>
                                <span class="verify-badge"><i class="fas fa-check-circle"></i></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="request-actions">
                        <button class="btn btn-primary" onclick="acceptFriendRequest(<?= $request['id'] ?>, this)">Confirm</button>
                        <button class="btn btn-secondary" onclick="rejectFriendRequest(<?= $request['id'] ?>, this)">Delete</button>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-user-friends"></i>
                <p>No friend requests</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function acceptFriendRequest(userId, btn) {
    fetch("/backend/accept_friend_request.php?id=" + userId, {
        method: "GET"
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === "success") {
            btn.closest('.request-card').remove();
            if (document.querySelectorAll('.request-card').length === 0) {
                document.getElementById('requestsList').innerHTML = '<div class="empty-state"><i class="fas fa-user-friends"></i><p>No friend requests</p></div>';
            }
        } else {
            alert("Error: " + (data.message || "Unknown error"));
        }
    })
    .catch(err => console.error("Error:", err));
}

function rejectFriendRequest(userId, btn) {
    fetch("/backend/reject_friend_request.php?id=" + userId, {
        method: "GET"
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === "success") {
            btn.closest('.request-card').remove();
            if (document.querySelectorAll('.request-card').length === 0) {
                document.getElementById('requestsList').innerHTML = '<div class="empty-state"><i class="fas fa-user-friends"></i><p>No friend requests</p></div>';
            }
        } else {
            alert("Error: " + (data.message || "Unknown error"));
        }
    })
    .catch(err => console.error("Error:", err));
}
</script>

</body>
</html>
