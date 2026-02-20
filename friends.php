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

$userName = htmlspecialchars($userData['name']);
$userImage = $userData['profile_image'] ? "/uploads/" . htmlspecialchars($userData['profile_image']) : "/assets/zuckuser.png";
$userCoins = $userData['coins'];

/* Get all friends */
$friendsStmt = $conn->prepare("
    SELECT u.id, u.name, u.profile_image, u.is_verified
    FROM friends f
    JOIN users u ON (
        (f.sender_id = u.id AND f.receiver_id = ?) OR 
        (f.receiver_id = u.id AND f.sender_id = ?)
    )
    WHERE f.status = 'accepted'
    ORDER BY u.name ASC
");
$friendsStmt->bind_param("ii", $user_id, $user_id);
$friendsStmt->execute();
$friends = $friendsStmt->get_result();

$friendCount = $friends->num_rows;
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Friends - ZuckBook</title>
<!-- Preload critical resources -->
<link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style">
<link rel="preload" href="/assets/dark-mode.css" as="style">
<link rel="preload" href="/assets/zuckuser.png" as="image">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="/assets/dark-mode.css">
<script src="/assets/avatar-placeholder.js" defer></script>
<script src="/assets/online-status.js" defer></script>
<script src="/assets/dark-mode.js" defer></script>
<style>
/* Prevent FOUC (Flash of Unstyled Content) */
html {
    visibility: hidden;
    opacity: 0;
}
html.loaded {
    visibility: visible;
    opacity: 1;
    transition: opacity 0.2s ease;
}
* { margin: 0; padding: 0; box-sizing: border-box; }
body { 
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
    background: #f0f2f5; 
    color: #050505;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

/* Loading optimization */
img {
    image-rendering: -webkit-optimize-contrast;
    image-rendering: crisp-edges;
}

img[loading="lazy"] {
    opacity: 0;
    transition: opacity 0.3s;
}

img[loading="lazy"].loaded,
img[loading="lazy"][src] {
    opacity: 1;
}

.header { 
    background: white; 
    border-bottom: 1px solid #e5e7eb; 
    padding: 0 16px; 
    position: sticky; 
    top: 0; 
    z-index: 100; 
    box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
}

.header-content { 
    max-width: 1400px; 
    margin: 0 auto; 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    height: 56px; 
    gap: 16px;
}

.header-left { 
    display: flex; 
    align-items: center; 
    gap: 8px; 
    flex: 1;
    max-width: 320px;
}

.logo { 
    font-size: 40px; 
    font-weight: bold; 
    color: #1877f2; 
    cursor: pointer; 
    user-select: none;
    line-height: 1;
}

.search-box {
    position: relative;
    flex: 1;
    max-width: 600px;
}

.search-box input {
    width: 100%;
    padding: 10px 16px 10px 40px;
    border: none;
    border-radius: 50px;
    background: #f0f2f5;
    font-size: 15px;
    outline: none;
    transition: all 0.2s;
}

.search-box input:focus {
    background: #e4e6eb;
}

.search-box i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #65676b;
    font-size: 16px;
}

/* Search Results */
.search-results { 
    display: none; 
    position: absolute; 
    top: 100%; 
    left: 0; 
    width: 100%; 
    max-width: 420px;
    background: white; 
    border-radius: 12px; 
    box-shadow: 0 8px 24px rgba(0,0,0,0.15); 
    z-index: 1000; 
    max-height: 500px; 
    overflow-y: auto; 
    margin-top: 8px;
    border: 1px solid #e4e6eb;
}
.search-results.show { display: block; }

.search-result-item { 
    padding: 14px 16px; 
    display: flex; 
    align-items: center; 
    gap: 14px; 
    cursor: pointer; 
    border-bottom: 1px solid #f0f2f5; 
    transition: all 0.2s ease;
    position: relative;
}
.search-result-item:hover { 
    background: #f0f2f5;
    transform: translateX(4px);
}
.search-result-item:active {
    background: #e4e6eb;
    transform: translateX(2px);
}
.search-result-item:last-child { border-bottom: none; }

.search-result-avatar { 
    width: 56px; 
    height: 56px; 
    border-radius: 50%; 
    object-fit: cover; 
    border: 3px solid #e4e6eb; 
    background: #f0f2f5; 
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: all 0.2s ease;
}
.search-result-item:hover .search-result-avatar {
    border-color: #1877f2;
    transform: scale(1.05);
}

.search-result-info { 
    flex: 1; 
    min-width: 0;
}

.search-result-name { 
    font-weight: 600; 
    font-size: 16px; 
    display: flex; 
    align-items: center; 
    gap: 6px; 
    color: #050505; 
    margin-bottom: 4px;
    line-height: 1.3;
}

.search-result-username {
    font-size: 14px;
    color: #65676b;
    font-weight: 400;
}

.search-result-btn { 
    background: #1877f2; 
    color: white; 
    border: none; 
    padding: 10px 24px; 
    border-radius: 8px; 
    font-weight: 600; 
    font-size: 15px; 
    cursor: pointer; 
    transition: all 0.2s ease; 
    flex-shrink: 0;
    box-shadow: 0 2px 4px rgba(24, 119, 242, 0.2);
}
.search-result-btn:hover { 
    background: #166fe5; 
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(24, 119, 242, 0.3);
}
.search-result-btn:active {
    transform: translateY(0);
}
.search-result-btn:disabled { 
    background: #65676b; 
    cursor: not-allowed; 
    transform: none;
    box-shadow: none;
}

.search-empty { 
    padding: 32px 24px; 
    text-align: center; 
    color: #65676b; 
    font-size: 15px;
    line-height: 1.6;
}
.search-empty i {
    display: block;
    font-size: 32px;
    margin-bottom: 12px;
    opacity: 0.5;
}

.verified {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    flex-shrink: 0;
}
.verified.basic { 
    background: #1877f2; 
    box-shadow: 0 0 8px rgba(24, 119, 242, 0.4);
}
.verified.premium { 
    background: #9333ea; 
    box-shadow: 0 0 8px rgba(147, 51, 234, 0.4);
}
.verified.elite { 
    background: #dc2626; 
    box-shadow: 0 0 8px rgba(220, 38, 38, 0.4);
}

.header-center {
    display: flex;
    gap: 8px;
    justify-content: center;
    flex: 1;
    max-width: 600px;
}

.nav-item {
    padding: 12px 40px;
    border-radius: 8px;
    color: #65676b;
    text-decoration: none;
    font-size: 20px;
    transition: background 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.nav-item:hover {
    background: #f0f2f5;
}

.nav-item.active {
    color: #1877f2;
}

.nav-item.active::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: #1877f2;
    border-radius: 3px 3px 0 0;
}

.header-right { 
    display: flex; 
    gap: 8px; 
    align-items: center; 
}

.header-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #e4e6eb;
    border: none;
    cursor: pointer;
    font-size: 20px;
    color: #050505;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s;
    position: relative;
}

.header-icon:hover {
    background: #d8dadf;
}

.coins-display {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 12px;
    background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    border-radius: 20px;
    color: white;
    font-weight: 700;
    font-size: 14px;
    cursor: pointer;
    transition: transform 0.2s;
}

.coins-display:hover {
    transform: scale(1.05);
}

.user-avatar { 
    width: 40px; 
    height: 40px; 
    border-radius: 50%; 
    object-fit: cover; 
    cursor: pointer; 
    border: 2px solid #e4e6eb;
}

.user-avatar:hover {
    border-color: #1877f2;
}

.main-container { max-width: 1200px; margin: 16px auto; padding: 0 16px; }
.page-title { font-size: 32px; font-weight: bold; margin-bottom: 8px; }
.page-subtitle { color: #65676b; margin-bottom: 24px; }

.friends-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; }

.friends-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

.friends-table thead {
    background: #f0f2f5;
    border-bottom: 2px solid #e5e7eb;
}

.friends-table th {
    padding: 12px 16px;
    text-align: right;
    font-weight: 600;
    color: #050505;
    font-size: 14px;
}

.friends-table td {
    padding: 12px 16px;
    border-bottom: 1px solid #e5e7eb;
    font-size: 14px;
}

.friends-table tbody tr:hover {
    background: #f9f9f9;
}

.friends-table tbody tr:last-child td {
    border-bottom: none;
}

.friend-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}

.friend-avatar-small {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
    cursor: pointer;
    border: 2px solid #e4e6eb;
    background: #f0f2f5;
    transition: all 0.2s ease;
}
.friend-avatar-small:hover {
    border-color: #1877f2;
    transform: scale(1.05);
}
.friend-avatar-small[src="/assets/zuckuser.png"],
.friend-avatar-small[src*="zuckuser.png"] {
    background: #f0f2f5;
    padding: 2px;
}
    border-radius: 50%;
    object-fit: cover;
    cursor: pointer;
}

.friend-name-cell {
    display: flex;
    align-items: center;
    gap: 6px;
    cursor: pointer;
    color: #1877f2;
    text-decoration: none;
}

.friend-name-cell:hover {
    text-decoration: underline;
}

.table-actions {
    display: flex;
    gap: 8px;
}

.table-btn {
    padding: 6px 12px;
    border-radius: 6px;
    border: none;
    font-weight: 600;
    font-size: 12px;
    cursor: pointer;
    transition: 0.2s;
    display: flex;
    align-items: center;
    gap: 4px;
}

.table-btn-primary {
    background: #1877f2;
    color: white;
}

.table-btn-primary:hover {
    background: #166fe5;
}

.table-btn-secondary {
    background: #e4e6eb;
    color: #050505;
}

.table-btn-secondary:hover {
    background: #d8dadf;
}

.friend-card { background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,0.1); transition: 0.2s; cursor: pointer; }
.friend-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.15); }

.friend-avatar { width: 100%; height: 200px; object-fit: cover; background: #f0f2f5; }

.friend-info { padding: 12px; }
.friend-name { font-weight: 600; font-size: 15px; display: flex; align-items: center; gap: 4px; margin-bottom: 8px; }
.verify-badge { color: #1877f2; font-size: 14px; }

.friend-actions { display: flex; gap: 8px; }
.btn { flex: 1; padding: 8px; border-radius: 6px; border: none; font-weight: 600; font-size: 13px; cursor: pointer; transition: 0.2s; display: flex; align-items: center; justify-content: center; gap: 4px; }
.btn-primary { background: #1877f2; color: white; }
.btn-primary:hover { background: #166fe5; }
.btn-secondary { background: #e4e6eb; color: #050505; }
.btn-secondary:hover { background: #d8dadf; }

.empty-state { text-align: center; padding: 60px 20px; color: #65676b; }
.empty-state i { font-size: 64px; margin-bottom: 16px; opacity: 0.5; }
.empty-state p { font-size: 18px; margin-bottom: 8px; }

@media (max-width: 768px) {
    .friends-grid { grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); }
    .page-title { font-size: 24px; }
}

/* ==================== MOBILE RESPONSIVE STYLES ==================== */

/* Tablets and below (1024px) */
@media (max-width: 1024px) {
    .header-center {
        display: none;
    }
}

/* Mobile devices (768px and below) */
@media (max-width: 768px) {
    .header {
        padding: 0 8px;
    }

    .header-content {
        height: 50px;
        gap: 8px;
    }

    .header-left {
        max-width: 200px;
    }

    .logo {
        font-size: 32px;
    }

    .search-box input {
        padding: 8px 12px 8px 36px;
        font-size: 14px;
    }

    .search-box i {
        left: 12px;
        font-size: 14px;
    }

    .header-center {
        display: none;
    }

    .header-right {
        gap: 4px;
    }

    .header-icon {
        width: 36px;
        height: 36px;
        font-size: 18px;
    }

    .coins-display {
        padding: 6px 10px;
        font-size: 13px;
        gap: 4px;
    }

    .user-avatar {
        width: 36px;
        height: 36px;
    }

    .main-container {
        padding: 0 10px;
    }

    .page-title {
        font-size: 22px;
    }

    .page-subtitle {
        font-size: 14px;
        margin-bottom: 20px;
    }

    .friends-table {
        font-size: 13px;
    }

    .friends-table th,
    .friends-table td {
        padding: 10px 12px;
    }

    .friend-avatar-small {
        width: 36px;
        height: 36px;
    }

    .friend-name-cell {
        font-size: 14px;
    }

    .table-btn {
        padding: 5px 10px;
        font-size: 11px;
    }
}

/* Small mobile devices (575px and below) */
@media (max-width: 575px) {
    .header {
        padding: 0 6px;
    }

    .header-content {
        height: 48px;
        gap: 6px;
    }

    .header-left {
        max-width: 160px;
    }

    .logo {
        font-size: 28px;
    }

    .search-box input {
        padding: 7px 10px 7px 32px;
        font-size: 13px;
    }

    .search-box i {
        left: 10px;
        font-size: 13px;
    }

    .header-right {
        gap: 3px;
    }

    .header-icon {
        width: 32px;
        height: 32px;
        font-size: 16px;
    }

    .coins-display {
        padding: 5px 8px;
        font-size: 12px;
        gap: 3px;
    }

    .user-avatar {
        width: 32px;
        height: 32px;
    }

    .main-container {
        padding: 0 8px;
        margin: 12px auto;
    }

    .page-title {
        font-size: 20px;
    }

    .page-subtitle {
        font-size: 13px;
        margin-bottom: 16px;
    }

    .friends-table {
        font-size: 12px;
    }

    .friends-table th,
    .friends-table td {
        padding: 8px 10px;
    }

    .friend-avatar-small {
        width: 32px;
        height: 32px;
    }

    .friend-name-cell {
        font-size: 13px;
    }

    .table-actions {
        flex-direction: column;
        gap: 6px;
    }

    .table-btn {
        padding: 4px 8px;
        font-size: 10px;
        width: 100%;
    }
}

/* Extra small screens (360px and below) */
@media (max-width: 360px) {
    .search-box {
        display: none;
    }

    .header-left {
        max-width: auto;
    }
}
</style>
</head>
<body>

<!-- Header -->
<div class="header">
    <div class="header-content">
        <div class="header-left">
            <div class="logo" onclick="window.location.href='/home.php'">f</div>
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="friendSearchInput" placeholder="Search ZuckBook" oninput="searchFriends(this.value)">
                <div id="friendSearchResults" class="search-results"></div>
            </div>
        </div>
        
        <div class="header-center">
            <a href="/home.php" class="nav-item" title="Home"><i class="fas fa-home"></i></a>
            <a href="/friends.php" class="nav-item active" title="Friends"><i class="fas fa-user-friends"></i></a>
            <a href="/videos.php" class="nav-item" title="Watch"><i class="fas fa-play-circle"></i></a>
            <a href="/groups.php" class="nav-item" title="Groups"><i class="fas fa-users"></i></a>
            <a href="/profile.php?id=<?= $user_id ?>" class="nav-item" title="Profile"><i class="fas fa-user"></i></a>
        </div>
        
        <div class="header-right">
            <button class="header-icon" onclick="window.location.href='/home.php'" title="Home"><i class="fas fa-home"></i></button>
            <button class="header-icon" onclick="window.location.href='/friends.php'" title="Friends"><i class="fas fa-user-friends"></i></button>
            <div class="coins-display" onclick="window.location.href='/coins.php'" title="Coins">
                <i class="fas fa-coins"></i>
                <span><?= formatCoins($userCoins) ?></span>
            </div>
            <button class="header-icon" onclick="window.location.href='/groups.php'" title="Groups"><i class="fas fa-users"></i></button>
            <button class="header-icon" onclick="window.location.href='/notifications.php'" title="Notifications"><i class="fas fa-bell"></i></button>
            <img src="<?= $userImage ?>" 
                 class="user-avatar" 
                 alt="<?= $userName ?>"
                 data-name="<?= $userName ?>"
                 onclick="window.location.href='/profile.php?id=<?= $user_id ?>'">
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="main-container">
    <h1 class="page-title">الأصدقاء</h1>
    <p class="page-subtitle"><?= $friendCount ?> صديق</p>
    
    <?php if ($friendCount > 0): ?>
        <table class="friends-table">
            <thead>
                <tr>
                    <th>الصديق</th>
                    <th>الحالة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($friend = $friends->fetch_assoc()): ?>
                <tr>
                    <td>
                        <div class="friend-cell">
                            <?php 
                            $friendImage = '/assets/zuckuser.png';
                            if ($friend['profile_image']) {
                                $imagePath = '/uploads/' . htmlspecialchars($friend['profile_image']);
                                // Check if file exists
                                if (file_exists(__DIR__ . $imagePath)) {
                                    $friendImage = $imagePath;
                                }
                            }
                            ?>
                            <img src="<?= $friendImage ?>" 
                                 class="friend-avatar-small" 
                                 alt="<?= htmlspecialchars($friend['name']) ?>"
                                 data-name="<?= htmlspecialchars($friend['name']) ?>"
                                 loading="lazy"
                                 onerror="this.onerror=null; this.src='/assets/zuckuser.png';"
                                 onclick="window.location.href='/profile.php?id=<?= $friend['id'] ?>'">
                            <a href="/profile.php?id=<?= $friend['id'] ?>" class="friend-name-cell">
                                <?= htmlspecialchars($friend['name']) ?>
                                <?php if ($friend['is_verified'] == 1): ?>
                                    <i class="fas fa-check-circle" style="color: #1877f2; font-size: 12px;"></i>
                                <?php endif; ?>
                            </a>
                        </div>
                    </td>
                    <td>
                        <span style="color: #4caf50; font-weight: 600;">
                            <i class="fas fa-check-circle"></i> صديق
                        </span>
                    </td>
                    <td>
                        <div class="table-actions">
                            <button class="table-btn table-btn-primary" onclick="window.location.href='/chat.php?user=<?= $friend['id'] ?>'">
                                <i class="fas fa-comment"></i> رسالة
                            </button>
                            <button class="table-btn table-btn-secondary" onclick="removeFriend(<?= $friend['id'] ?>, this)">
                                <i class="fas fa-user-minus"></i> حذف
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-user-friends"></i>
            <p>لا توجد أصدقاء حتى الآن</p>
            <p style="font-size: 14px; margin-top: 8px;">ابدأ بإضافة أصدقاء لتوسيع شبكتك</p>
        </div>
    <?php endif; ?>
</div>

<script>
// Remove the old createDefaultAvatar code since it's now in avatar-placeholder.js

function removeFriend(friendId, btn) {
    if (confirm('هل تريد حقاً حذف هذا الصديق؟')) {
        fetch("/backend/remove_friend.php?id=" + friendId, {
            method: "GET"
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === "success") {
                btn.closest('tr').remove();
                
                // Check if table is empty
                const tbody = document.querySelector('.friends-table tbody');
                if (tbody && tbody.children.length === 0) {
                    location.reload();
                }
            } else {
                alert("Error: " + (data.message || "Unknown error"));
            }
        })
        .catch(err => console.error("Error:", err));
    }
}
</script>

<script>
// Search functionality
function searchFriends(query) {
    const resultsDiv = document.getElementById("friendSearchResults");
    
    if (query.length < 2) {
        resultsDiv.classList.remove("show");
        return;
    }

    fetch("/backend/search_users.php?q=" + encodeURIComponent(query))
    .then(res => res.json())
    .then(data => {
        if (!Array.isArray(data) || data.length === 0) {
            resultsDiv.innerHTML = '<div class="search-empty"><i class="fas fa-search"></i>No users found<br><span style="font-size: 13px; opacity: 0.7;">Try searching with a different name</span></div>';
            resultsDiv.classList.add("show");
            return;
        }

        let html = '';
        data.forEach(user => {
            let badgeClass = 'basic';
            if (user.subscription_tier === 'premium') badgeClass = 'premium';
            else if (user.subscription_tier === 'elite') badgeClass = 'elite';
            
            const verified = user.is_verified == 1 ? `<i class="fas fa-check-circle" style="color: #1877f2; font-size: 14px; margin-left: 4px;"></i>` : '';
            
            const userName = user.name || 'User';
            
            html += `
                <div class="search-result-item">
                    <img src="${user.profile_image}" 
                         class="search-result-avatar" 
                         onclick="window.location.href='/profile.php?id=${user.id}'"
                         onerror="this.onerror=null; this.src='/assets/zuckuser.png';"
                         alt="${userName}"
                         data-name="${userName}">
                    <div class="search-result-info">
                        <div class="search-result-name" onclick="window.location.href='/profile.php?id=${user.id}'" style="cursor: pointer;">
                            ${user.name}
                            ${verified}
                        </div>
                        <div class="search-result-username">@${user.name.toLowerCase().replace(/\s+/g, '')}</div>
                    </div>
                    <button class="search-result-btn" onclick="addFriendFromSearch(${user.id}, this)">
                        <i class="fas fa-user-plus" style="margin-right: 6px;"></i>Add
                    </button>
                </div>
            `;
        });

        resultsDiv.innerHTML = html;
        resultsDiv.classList.add("show");
        
        // Apply avatar placeholder to all images
        if (typeof generateAvatarPlaceholder === 'function') {
            document.querySelectorAll('.search-result-avatar').forEach(img => {
                generateAvatarPlaceholder(img);
            });
        }
    })
    .catch(err => console.error("Search error:", err));
}

function addFriendFromSearch(userId, btn) {
    btn.disabled = true;
    btn.innerText = "Sending...";

    fetch("/backend/add_friend.php?id=" + userId, {
        method: "GET"
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === "success") {
            btn.innerText = "Sent";
            btn.style.background = "#65676b";
        } else if (data.status === "already_friends") {
            btn.innerText = "Friends";
            btn.style.background = "#65676b";
        } else {
            btn.innerText = "Error";
            btn.style.background = "#e41e3a";
        }
    })
    .catch(err => {
        console.error("Error:", err);
        btn.innerText = "Error";
        btn.disabled = false;
    });
}

// Close search results when clicking outside
document.addEventListener("click", (e) => {
    if (!e.target.closest(".search-box")) {
        document.getElementById("friendSearchResults").classList.remove("show");
    }
});

// Remove loading state when page is fully loaded
window.addEventListener('DOMContentLoaded', function() {
    document.documentElement.classList.add('loaded');
    
    // Mark lazy loaded images as loaded when they load
    document.querySelectorAll('img[loading="lazy"]').forEach(img => {
        if (img.complete) {
            img.classList.add('loaded');
        } else {
            img.addEventListener('load', function() {
                this.classList.add('loaded');
            });
        }
    });
});

// Fallback in case DOMContentLoaded already fired
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        document.documentElement.classList.add('loaded');
    });
} else {
    document.documentElement.classList.add('loaded');
}

// Optimize image loading
document.addEventListener('DOMContentLoaded', function() {
    // Initialize avatar placeholders
    if (typeof initAvatarPlaceholders === 'function') {
        initAvatarPlaceholders();
    }
    
    // Add error handling for all images
    document.querySelectorAll('img').forEach(img => {
        if (!img.hasAttribute('onerror')) {
            img.onerror = function() {
                this.onerror = null;
                if (this.classList.contains('friend-avatar-small') || 
                    this.classList.contains('user-avatar') ||
                    this.classList.contains('search-result-avatar')) {
                    this.src = '/assets/zuckuser.png';
                }
            };
        }
    });
});
</script>

</body>
</html>
