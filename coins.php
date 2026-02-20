<?php
session_start();
require_once __DIR__ . "/backend/config.php";
require_once __DIR__ . "/includes/helpers.php";

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

if ($userData['is_banned'] == 1 && ($userData['ban_expires_at'] === NULL || $userData['ban_expires_at'] > date("Y-m-d H:i:s"))) {
    die("Your account has been banned.");
}

$userName = htmlspecialchars($userData['name']);
$userImage = $userData['profile_image'] ? "/uploads/" . htmlspecialchars($userData['profile_image']) : "/assets/zuckuser.png";
$userCoins = $userData['coins'];

$notifStmt = $conn->prepare("SELECT COUNT(*) as total FROM notifications WHERE user_id = ? AND is_read = 0");
$notifStmt->bind_param("i", $user_id);
$notifStmt->execute();
$unread = $notifStmt->get_result()->fetch_assoc()['total'];

// Get all users with their coins (ordered by coins descending)
$coinsStmt = $conn->query("
    SELECT id, name, profile_image, coins, is_verified, role 
    FROM users 
    ORDER BY coins DESC 
    LIMIT 100
");
$allUsersCoins = $coinsStmt->fetch_all(MYSQLI_ASSOC);

// Get total coins in system
$totalCoinsStmt = $conn->query("SELECT SUM(coins) as total FROM users");
$totalCoins = $totalCoinsStmt->fetch_assoc()['total'] ?? 0;

// Get user rank
$rankStmt = $conn->prepare("
    SELECT COUNT(*) + 1 as rank 
    FROM users 
    WHERE coins > (SELECT coins FROM users WHERE id = ?)
");
$rankStmt->bind_param("i", $user_id);
$rankStmt->execute();
$userRank = $rankStmt->get_result()->fetch_assoc()['rank'];
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Coins - ZuckBook</title>
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

.content { background: white; border-radius: 8px; padding: 24px; }
.content-title { font-size: 32px; font-weight: bold; margin-bottom: 24px; }

.coins-display { background: linear-gradient(135deg, #1877f2 0%, #0a66c2 100%); color: white; padding: 32px; border-radius: 12px; text-align: center; margin-bottom: 24px; }
.coins-amount { font-size: 48px; font-weight: bold; margin: 16px 0; }
.coins-label { font-size: 18px; opacity: 0.9; }

.card { background: #f0f2f5; padding: 20px; border-radius: 12px; margin-bottom: 20px; }
.card h3 { margin-bottom: 12px; font-size: 18px; }
.card p { color: #65676b; margin-bottom: 16px; }

.video-container { background: #000; border-radius: 12px; overflow: hidden; margin-bottom: 16px; }
.video-container video { width: 100%; display: block; }

.timer { text-align: center; font-size: 18px; font-weight: bold; color: #1877f2; margin: 16px 0; }

.button-group { display: flex; gap: 12px; flex-wrap: wrap; }
.btn { padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 15px; transition: 0.2s; }
.btn-primary { background: #1877f2; color: white; }
.btn-primary:hover { background: #0a66c2; }
.btn-secondary { background: #e4e6eb; color: #050505; }
.btn-secondary:hover { background: #d0d2d7; }

.coins-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    margin-top: 24px;
}

.coins-table thead {
    background: #f0f2f5;
    border-bottom: 2px solid #e5e7eb;
}

.coins-table th {
    padding: 12px 16px;
    text-align: right;
    font-weight: 600;
    color: #050505;
    font-size: 14px;
}

.coins-table td {
    padding: 12px 16px;
    border-bottom: 1px solid #e5e7eb;
    font-size: 14px;
}

.coins-table tbody tr:hover {
    background: #f9f9f9;
}

.coins-table tbody tr:last-child td {
    border-bottom: none;
}

.coins-amount-cell {
    font-weight: 600;
    color: #ffc107;
    display: flex;
    align-items: center;
    gap: 6px;
}

.coins-status {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.status-available {
    background: #d4edda;
    color: #155724;
}

.status-claimed {
    background: #d1ecf1;
    color: #0c5460;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.success-message { background: #d4edda; color: #155724; padding: 16px; border-radius: 8px; margin-bottom: 16px; display: none; }

.leaderboard-section {
    margin-top: 40px;
}

.leaderboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.leaderboard-title {
    font-size: 24px;
    font-weight: 700;
    color: #050505;
}

.leaderboard-stats {
    display: flex;
    gap: 20px;
    font-size: 14px;
    color: #65676b;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 6px;
}

.users-coins-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

.users-coins-table thead {
    background: linear-gradient(135deg, #1877f2 0%, #0a66c2 100%);
    color: white;
}

.users-coins-table th {
    padding: 16px;
    text-align: right;
    font-weight: 600;
    font-size: 14px;
}

.users-coins-table td {
    padding: 12px 16px;
    border-bottom: 1px solid #e5e7eb;
    font-size: 14px;
}

.users-coins-table tbody tr:hover {
    background: #f9f9f9;
}

.users-coins-table tbody tr:last-child td {
    border-bottom: none;
}

.user-rank {
    font-weight: 700;
    font-size: 16px;
    color: #1877f2;
    min-width: 40px;
    text-align: center;
}

.rank-1 { color: #ffd700; }
.rank-2 { color: #c0c0c0; }
.rank-3 { color: #cd7f32; }

.user-info-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}

.user-mini-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #e4e6eb;
}

.user-name-wrapper {
    display: flex;
    flex-direction: column;
}

.user-name-link {
    font-weight: 600;
    color: #050505;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 6px;
}

.user-name-link:hover {
    color: #1877f2;
    text-decoration: underline;
}

.user-role-badge {
    font-size: 11px;
    color: #65676b;
    background: #f0f2f5;
    padding: 2px 8px;
    border-radius: 10px;
    display: inline-block;
}

.role-cofounder { background: #fff3cd; color: #856404; }
.role-mod { background: #d1ecf1; color: #0c5460; }
.role-sup { background: #d4edda; color: #155724; }

.coins-amount-large {
    font-weight: 700;
    font-size: 18px;
    color: #ffc107;
    display: flex;
    align-items: center;
    gap: 6px;
}

.current-user-row {
    background: #e7f3ff !important;
    border-left: 4px solid #1877f2;
}

.verified-badge {
    color: #1877f2;
    font-size: 14px;
}

.table-wrapper {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    margin-bottom: 20px;
}

.right-sidebar { background: white; border-radius: 8px; padding: 16px; height: fit-content; position: sticky; top: 60px; }
.right-sidebar-title { font-weight: 600; font-size: 15px; margin-bottom: 12px; }

/* ==================== MOBILE RESPONSIVE STYLES ==================== */

/* Tablets and below (1024px) */
@media (max-width: 1024px) {
    .container { 
        grid-template-columns: 1fr; 
        padding: 0 12px;
    }
    .sidebar, .right-sidebar { display: none; }
}

/* Mobile devices (768px and below) */
@media (max-width: 768px) {
    /* Header adjustments */
    .header { height: 50px; padding: 0 8px; }
    .header-content { padding: 0 8px; }
    .logo { font-size: 32px; }
    
    .search-box { display: none; } /* Hide search on mobile */
    
    .header-icons { gap: 4px; }
    .header-icon { 
        width: 36px; 
        height: 36px; 
        font-size: 16px; 
    }
    
    .user-avatar { 
        width: 32px; 
        height: 32px; 
    }
    
    /* Main container */
    .container { 
        padding: 0 8px; 
        margin-top: 60px;
    }
    
    /* Page title */
    .page-title { 
        font-size: 24px; 
        margin-bottom: 16px;
        padding: 0 8px;
    }
    
    /* Balance card */
    .balance-card { 
        padding: 24px 16px; 
        margin-bottom: 16px;
    }
    
    .balance-label { font-size: 14px; }
    .balance-amount { font-size: 42px; }
    .balance-text { font-size: 16px; }
    
    /* Earn section */
    .earn-section { 
        padding: 20px 16px; 
        margin-bottom: 16px;
    }
    
    .earn-title { 
        font-size: 18px; 
        margin-bottom: 8px;
    }
    
    .earn-description { font-size: 13px; }
    
    .earn-btn { 
        padding: 12px 20px; 
        font-size: 14px;
    }
    
    /* Tasks table */
    .tasks-section { 
        padding: 16px; 
        margin-bottom: 16px;
    }
    
    .section-title { 
        font-size: 18px; 
        margin-bottom: 16px;
    }
    
    .tasks-table { 
        display: block;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .tasks-table table {
        min-width: 500px; /* Prevent table from being too narrow */
    }
    
    .tasks-table th,
    .tasks-table td { 
        padding: 10px 8px; 
        font-size: 13px;
    }
    
    .task-icon { 
        width: 36px; 
        height: 36px; 
        font-size: 16px;
    }
    
    .task-title { font-size: 14px; }
    .task-frequency { font-size: 12px; }
    
    .coin-reward { font-size: 14px; }
    
    .claim-btn { 
        padding: 6px 12px; 
        font-size: 12px;
    }
    
    /* Video modal */
    .video-modal-content { 
        width: 95%; 
        max-width: 95%;
        padding: 0;
    }
    
    .video-modal-header { 
        padding: 12px 16px;
    }
    
    .video-modal-header h2 { font-size: 16px; }
    
    .video-modal-body { 
        padding: 16px;
    }
    
    .video-container { 
        height: 250px;
    }
    
    .video-info { 
        padding: 12px;
    }
    
    .video-title { font-size: 15px; }
    .video-description { font-size: 13px; }
    
    .video-actions { 
        padding: 12px 16px;
        flex-direction: column;
    }
    
    .video-actions button { 
        width: 100%;
        padding: 12px;
    }
}

/* Small mobile devices (575px and below) */
@media (max-width: 575px) {
    /* Header */
    .header { height: 48px; padding: 0 6px; }
    .header-content { padding: 0 6px; }
    .logo { font-size: 28px; }
    
    .header-icons { gap: 2px; }
    .header-icon { 
        width: 32px; 
        height: 32px; 
        font-size: 14px;
    }
    
    .user-avatar { 
        width: 28px; 
        height: 28px;
    }
    
    /* Container */
    .container { 
        padding: 0 6px; 
        margin-top: 56px;
    }
    
    /* Page title */
    .page-title { 
        font-size: 20px; 
        margin-bottom: 12px;
        padding: 0 6px;
    }
    
    /* Balance card */
    .balance-card { 
        padding: 20px 12px;
        margin-bottom: 12px;
    }
    
    .balance-label { font-size: 13px; }
    .balance-amount { font-size: 36px; }
    .balance-text { font-size: 14px; }
    
    /* Earn section */
    .earn-section { 
        padding: 16px 12px;
        margin-bottom: 12px;
    }
    
    .earn-title { 
        font-size: 16px;
        margin-bottom: 6px;
    }
    
    .earn-description { font-size: 12px; }
    
    .earn-btn { 
        padding: 10px 16px; 
        font-size: 13px;
    }
    
    /* Tasks section */
    .tasks-section { 
        padding: 12px;
        margin-bottom: 12px;
    }
    
    .section-title { 
        font-size: 16px;
        margin-bottom: 12px;
    }
    
    .tasks-table th,
    .tasks-table td { 
        padding: 8px 6px; 
        font-size: 12px;
    }
    
    .task-icon { 
        width: 32px; 
        height: 32px; 
        font-size: 14px;
    }
    
    .task-title { font-size: 13px; }
    .task-frequency { font-size: 11px; }
    
    .coin-reward { font-size: 13px; }
    
    .claim-btn { 
        padding: 5px 10px; 
        font-size: 11px;
    }
    
    /* Video modal */
    .video-modal-content { 
        width: 98%;
        max-width: 98%;
    }
    
    .video-modal-header { 
        padding: 10px 12px;
    }
    
    .video-modal-header h2 { font-size: 15px; }
    
    .video-modal-body { 
        padding: 12px;
    }
    
    .video-container { 
        height: 200px;
    }
    
    .video-info { 
        padding: 10px;
    }
    
    .video-title { font-size: 14px; }
    .video-description { font-size: 12px; }
    
    .video-actions { 
        padding: 10px 12px;
    }
    
    .video-actions button { 
        padding: 10px;
        font-size: 13px;
    }
}

/* Landscape mode adjustments */
@media (max-width: 768px) and (orientation: landscape) {
    .video-container { 
        height: 300px;
    }
    
    .video-modal-content {
        max-height: 90vh;
        overflow-y: auto;
    }
}

/* Extra adjustments for very small screens */
@media (max-width: 360px) {
    .balance-amount { font-size: 32px; }
    
    .tasks-table table {
        min-width: 450px;
    }
    
    .earn-btn {
        width: 100%;
        text-align: center;
    }
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
        <a href="/coins.php" class="sidebar-item active"><i class="fas fa-coins"></i> Coins (<?= formatCoins($userCoins) ?>)</a>
        <a href="/subscriptions.php" class="sidebar-item"><i class="fas fa-crown"></i> Subscriptions</a>
        <?php 
        // Check if user has active subscription
        $checkSub = $conn->query("SELECT subscription_tier, subscription_expires FROM users WHERE id = {$user_id}");
        $subData = $checkSub->fetch_assoc();
        $hasActiveSub = ($subData['subscription_expires'] && strtotime($subData['subscription_expires']) > time() && $subData['subscription_tier'] !== 'free');
        if($hasActiveSub): 
        ?>
        <a href="/my_subscription.php" class="sidebar-item"><i class="fas fa-id-card"></i> My Subscription</a>
        <?php endif; ?>
        <a href="/notifications.php" class="sidebar-item"><i class="fas fa-bell"></i> Notifications (<?= $unread ?>)</a>
        <a href="/settings.php" class="sidebar-item"><i class="fas fa-cog"></i> Settings</a>
        <a href="/backend/logout.php" class="sidebar-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
    
    <div class="content">
        <div class="content-title"><i class="fas fa-coins"></i> Earn Coins</div>
        
        <div class="coins-display">
            <div class="coins-label">Your Balance</div>
            <div class="coins-amount" id="balance"><?= formatCoins($userCoins) ?></div>
            <div class="coins-label">Coins</div>
        </div>

        <div id="successMessage" class="success-message">
            <i class="fas fa-check-circle"></i> 1 Coin has been added to your balance!
        </div>

        <!-- STEP 1 -->
        <div id="introCard" class="card">
            <h3><i class="fas fa-video"></i> Watch & Earn</h3>
            <p>Watch the full video to receive 1 coin reward. It's that simple!</p>
            <button class="btn btn-primary" onclick="nextStep()">Start Watching</button>
        </div>

        <!-- STEP 2 -->
        <div id="ratingCard" class="card" style="display: none;">
            <h3>How do you rate ZuckBook?</h3>
            <p>Your feedback helps us improve!</p>
            <div class="button-group">
                <button class="btn btn-secondary" onclick="showVideo()">⭐ Great</button>
                <button class="btn btn-secondary" onclick="showVideo()">⭐⭐ Awesome</button>
                <button class="btn btn-secondary" onclick="showVideo()">⭐⭐⭐ Amazing</button>
            </div>
        </div>

        <!-- VIDEO -->
        <div id="videoSection" class="card" style="display: none;">
            <h3>Watch the Video</h3>
            <div class="video-container">
                <video id="adVideo" width="100%" height="auto" controls crossorigin="anonymous">
                    <source src="/assets/coins-ad.mp4" type="video/mp4">
                    <p>Your browser does not support HTML5 video. Please use a modern browser.</p>
                </video>
            </div>
            <div class="timer" id="timer"></div>
            <button class="btn btn-primary" id="claimBtn" onclick="claimCoin()" style="display: none; margin-top: 16px;">Claim 1 Coin</button>
        </div>

        <!-- Coins History Table -->
        <div class="table-wrapper">
            <table class="coins-table">
                <thead>
                    <tr>
                        <th>الطريقة</th>
                        <th>الكوينز</th>
                        <th>التكرار</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
            <tbody>
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-video" style="color: #1877f2; font-size: 18px;"></i>
                            <span>مشاهدة الفيديو</span>
                        </div>
                    </td>
                    <td>
                        <div class="coins-amount-cell">
                            <i class="fas fa-coins"></i> 1
                        </div>
                    </td>
                    <td>يومياً</td>
                    <td>
                        <span class="coins-status status-available">
                            <i class="fas fa-check-circle"></i> متاح
                        </span>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-user-plus" style="color: #4caf50; font-size: 18px;"></i>
                            <span>دعوة صديق</span>
                        </div>
                    </td>
                    <td>
                        <div class="coins-amount-cell">
                            <i class="fas fa-coins"></i> 5
                        </div>
                    </td>
                    <td>لكل صديق</td>
                    <td>
                        <span class="coins-status status-available">
                            <i class="fas fa-check-circle"></i> متاح
                        </span>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-heart" style="color: #e74c3c; font-size: 18px;"></i>
                            <span>الإعجاب بالمنشورات</span>
                        </div>
                    </td>
                    <td>
                        <div class="coins-amount-cell">
                            <i class="fas fa-coins"></i> 0.5
                        </div>
                    </td>
                    <td>لكل إعجاب</td>
                    <td>
                        <span class="coins-status status-available">
                            <i class="fas fa-check-circle"></i> متاح
                        </span>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-comment" style="color: #9b59b6; font-size: 18px;"></i>
                            <span>التعليق على المنشورات</span>
                        </div>
                    </td>
                    <td>
                        <div class="coins-amount-cell">
                            <i class="fas fa-coins"></i> 1
                        </div>
                    </td>
                    <td>لكل تعليق</td>
                    <td>
                        <span class="coins-status status-available">
                            <i class="fas fa-check-circle"></i> متاح
                        </span>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-share" style="color: #3498db; font-size: 18px;"></i>
                            <span>مشاركة المنشورات</span>
                        </div>
                    </td>
                    <td>
                        <div class="coins-amount-cell">
                            <i class="fas fa-coins"></i> 2
                        </div>
                    </td>
                    <td>لكل مشاركة</td>
                    <td>
                        <span class="coins-status status-available">
                            <i class="fas fa-check-circle"></i> متاح
                        </span>
                    </td>
                </tr>
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-star" style="color: #f39c12; font-size: 18px;"></i>
                            <span>الاشتراك في الخطة المميزة</span>
                        </div>
                    </td>
                    <td>
                        <div class="coins-amount-cell">
                            <i class="fas fa-coins"></i> 10
                        </div>
                    </td>
                    <td>شهرياً</td>
                    <td>
                        <span class="coins-status status-pending">
                            <i class="fas fa-hourglass-half"></i> قريباً
                        </span>
                    </td>
                </tr>
            </tbody>
        </table>
        </div>

        <!-- Leaderboard Section -->
        <div class="leaderboard-section">
            <div class="leaderboard-header">
                <div class="leaderboard-title">
                    <i class="fas fa-trophy"></i> لوحة المتصدرين
                </div>
                <div class="leaderboard-stats">
                    <div class="stat-item">
                        <i class="fas fa-coins"></i>
                        <span>إجمالي العملات: <strong><?= formatCoins($totalCoins) ?></strong></span>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-medal"></i>
                        <span>ترتيبك: <strong>#<?= $userRank ?></strong></span>
                    </div>
                </div>
            </div>

            <div class="table-wrapper">
            <table class="users-coins-table">
                <thead>
                    <tr>
                        <th style="text-align: center;">الترتيب</th>
                        <th>المستخدم</th>
                        <th>الدور</th>
                        <th style="text-align: center;">العملات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $rank = 1;
                    foreach($allUsersCoins as $user): 
                        $isCurrentUser = ($user['id'] == $user_id);
                        $rowClass = $isCurrentUser ? 'current-user-row' : '';
                        $rankClass = '';
                        if ($rank == 1) $rankClass = 'rank-1';
                        elseif ($rank == 2) $rankClass = 'rank-2';
                        elseif ($rank == 3) $rankClass = 'rank-3';
                        
                        $userImg = $user['profile_image'] ? "/uploads/" . htmlspecialchars($user['profile_image']) : "/assets/zuckuser.png";
                        
                        $roleNames = [
                            'cofounder' => 'مؤسس مشارك',
                            'mod' => 'مشرف',
                            'sup' => 'دعم فني',
                            'user' => 'مستخدم'
                        ];
                        $roleName = $roleNames[$user['role']] ?? 'مستخدم';
                        $roleClass = 'role-' . $user['role'];
                    ?>
                    <tr class="<?= $rowClass ?>">
                        <td>
                            <div class="user-rank <?= $rankClass ?>">
                                <?php if ($rank <= 3): ?>
                                    <i class="fas fa-trophy"></i>
                                <?php endif; ?>
                                #<?= $rank ?>
                            </div>
                        </td>
                        <td>
                            <div class="user-info-cell">
                                <img src="<?= $userImg ?>" 
                                     class="user-mini-avatar" 
                                     alt="<?= htmlspecialchars($user['name']) ?>"
                                     onerror="this.onerror=null; this.src='/assets/zuckuser.png';">
                                <div class="user-name-wrapper">
                                    <a href="/profile.php?id=<?= $user['id'] ?>" class="user-name-link">
                                        <?= htmlspecialchars($user['name']) ?>
                                        <?php if ($user['is_verified']): ?>
                                            <i class="fas fa-check-circle verified-badge" title="موثق"></i>
                                        <?php endif; ?>
                                    </a>
                                    <?php if ($isCurrentUser): ?>
                                        <span style="font-size: 12px; color: #1877f2; font-weight: 600;">
                                            <i class="fas fa-user"></i> أنت
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="user-role-badge <?= $roleClass ?>">
                                <?= $roleName ?>
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <div class="coins-amount-large">
                                <i class="fas fa-coins"></i>
                                <?= formatCoins($user['coins']) ?>
                            </div>
                        </td>
                    </tr>
                    <?php 
                    $rank++;
                    endforeach; 
                    ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
    
    <div class="right-sidebar">
        <div class="right-sidebar-title"><i class="fas fa-lightbulb"></i> Tips</div>
        <div style="font-size: 14px; color: #65676b; line-height: 1.6;">
            <p>• Watch videos daily to earn coins</p>
            <p>• Use coins to unlock premium features</p>
            <p>• Share with friends to earn bonuses</p>
        </div>
    </div>
</div>

<script>
function nextStep() {
    document.getElementById("introCard").style.display = "none";
    document.getElementById("ratingCard").style.display = "block";
}

function showVideo() {
    document.getElementById("ratingCard").style.display = "none";
    document.getElementById("videoSection").style.display = "block";

    let video = document.getElementById("adVideo");
    let timerDiv = document.getElementById("timer");
    let claimBtn = document.getElementById("claimBtn");

    // Reset
    video.currentTime = 0;
    claimBtn.style.display = "none";
    timerDiv.innerText = "⏱️ Starting video...";

    // تشغيل الفيديو مباشرة
    setTimeout(() => {
        video.play().then(() => {
            console.log("✅ Video playing");
        }).catch(err => {
            console.error("❌ Play error:", err);
            timerDiv.innerText = "❌ Error: " + err.message;
        });
    }, 100);

    // تحديث الوقت المتبقي
    video.ontimeupdate = function() {
        if(video.duration) {
            let remaining = Math.floor(video.duration - video.currentTime);
            timerDiv.innerText = "⏱️ Remaining: " + remaining + " seconds";
        }
    };

    // انتهاء الفيديو
    video.onended = function() {
        timerDiv.innerText = "✅ Video completed! Click the button to claim your coin.";
        claimBtn.style.display = "block";
    };

    // معالجة الأخطاء
    video.onerror = function() {
        timerDiv.innerText = "❌ Error loading video file";
        console.error("Video error:", video.error);
    };
}

function claimCoin() {
    fetch("/backend/claim_coin.php", {
        method: "POST"
    })
    .then(res => res.json())
    .then(data => {
        if(data.status === "success") {
            document.getElementById("videoSection").style.display = "none";
            document.getElementById("successMessage").style.display = "block";
            document.getElementById("balance").innerText = data.new_balance;
            
            setTimeout(() => {
                document.getElementById("introCard").style.display = "block";
                document.getElementById("successMessage").style.display = "none";
            }, 3000);
        } else if(data.status === "cooldown") {
            alert("⏰ You already claimed today. Come back tomorrow!");
        } else {
            alert("❌ Something went wrong. Please try again.");
        }
    })
    .catch(err => alert("Error: " + err));
}

// Handle broken avatar images in leaderboard
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.user-mini-avatar').forEach(img => {
        img.addEventListener('error', function() {
            // Get user name from alt attribute
            const userName = this.alt || 'User';
            const firstLetter = userName.charAt(0).toUpperCase();
            
            // Create canvas for placeholder
            const canvas = document.createElement('canvas');
            canvas.width = 40;
            canvas.height = 40;
            const ctx = canvas.getContext('2d');
            
            // Draw background with random color based on name
            const colors = ['#1877f2', '#42b72a', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4'];
            const colorIndex = userName.charCodeAt(0) % colors.length;
            ctx.fillStyle = colors[colorIndex];
            ctx.fillRect(0, 0, 40, 40);
            
            // Draw letter
            ctx.fillStyle = '#ffffff';
            ctx.font = 'bold 18px Arial';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(firstLetter, 20, 20);
            
            // Set as image source
            this.src = canvas.toDataURL();
            this.onerror = null; // Prevent infinite loop
        });
    });
});
</script>

</body>
</html>