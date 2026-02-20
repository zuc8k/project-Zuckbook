<?php
session_start();
require_once __DIR__ . "/backend/config.php";
require_once __DIR__ . "/includes/helpers.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: /");
    exit;
}

if (!isset($_GET['id'])) {
    die("User not found");
}

$profile_id   = intval($_GET['id']);
$current_user = intval($_SESSION['user_id']);
$isOwner      = ($profile_id === $current_user);

/* ================= CURRENT USER DATA ================= */

$userStmt = $conn->prepare("SELECT id, coins, is_verified, name, profile_image, is_banned, ban_expires_at FROM users WHERE id = ?");
$userStmt->bind_param("i", $current_user);
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
$notifStmt->bind_param("i", $current_user);
$notifStmt->execute();
$unread = $notifStmt->get_result()->fetch_assoc()['total'];

/* ================= PROFILE USER DATA ================= */

$stmt = $conn->prepare("SELECT id, name, profile_image, cover_image, cover_position, is_verified, bio, tagline, job_title, city, from_city, relationship_status, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $profile_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if(!$user){
    die("User not found");
}

$profileName = htmlspecialchars($user['name']);
$profileImage = $user['profile_image'] ? "/uploads/" . htmlspecialchars($user['profile_image']) : "/assets/zuckuser.png";
$coverImage = $user['cover_image'] ? "/uploads/" . htmlspecialchars($user['cover_image']) : "";
$coverPos = $user['cover_position'] ?? 50;
$isVerified = $user['is_verified']; // شرط العلامة الزرقاء
$profileBio = htmlspecialchars($user['bio'] ?? '');
$profileTagline = htmlspecialchars($user['tagline'] ?? '');
$profileJobTitle = htmlspecialchars($user['job_title'] ?? '');
$profileCity = htmlspecialchars($user['city'] ?? '');
$profileFromCity = htmlspecialchars($user['from_city'] ?? '');
$profileRelationship = htmlspecialchars($user['relationship_status'] ?? '');
$joinDate = $user['created_at'] ? date('F Y', strtotime($user['created_at'])) : '';

/* ================= COUNTS ================= */

$postCountStmt = $conn->prepare("SELECT COUNT(*) as total FROM posts WHERE user_id = ?");
$postCountStmt->bind_param("i", $profile_id);
$postCountStmt->execute();
$postCount = $postCountStmt->get_result()->fetch_assoc()['total'] ?? 0;

$friendCountStmt = $conn->prepare("SELECT COUNT(*) as total FROM friends WHERE (sender_id=? OR receiver_id=?) AND status='accepted'");
$friendCountStmt->bind_param("ii", $profile_id, $profile_id);
$friendCountStmt->execute();
$friendCount = $friendCountStmt->get_result()->fetch_assoc()['total'] ?? 0;

// Check if followers table exists, create if not
$checkTable = $conn->query("SHOW TABLES LIKE 'followers'");
if ($checkTable->num_rows == 0) {
    $createTable = "CREATE TABLE IF NOT EXISTS followers (
        id INT PRIMARY KEY AUTO_INCREMENT,
        follower_id INT NOT NULL,
        following_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_follow (follower_id, following_id),
        INDEX idx_follower (follower_id),
        INDEX idx_following (following_id)
    )";
    $conn->query($createTable);
}

// Get Followers count (people following this profile)
$followersCount = 0;
$followersCountStmt = $conn->prepare("SELECT COUNT(*) as total FROM followers WHERE following_id = ?");
if ($followersCountStmt) {
    $followersCountStmt->bind_param("i", $profile_id);
    $followersCountStmt->execute();
    $followersCount = $followersCountStmt->get_result()->fetch_assoc()['total'] ?? 0;
}

// Get Following count (people this profile is following)
$followingCount = 0;
$followingCountStmt = $conn->prepare("SELECT COUNT(*) as total FROM followers WHERE follower_id = ?");
if ($followingCountStmt) {
    $followingCountStmt->bind_param("i", $profile_id);
    $followingCountStmt->execute();
    $followingCount = $followingCountStmt->get_result()->fetch_assoc()['total'] ?? 0;
}

$postsStmt = $conn->prepare("
    SELECT 
        p.id,
        p.content,
        p.created_at,
        (SELECT COUNT(*) FROM post_reactions WHERE post_id = p.id) AS like_count
    FROM posts p
    WHERE p.user_id = ? 
    ORDER BY p.created_at DESC 
    LIMIT 20
");
$postsStmt->bind_param("i", $profile_id);
$postsStmt->execute();
$posts = $postsStmt->get_result();

/* ================= FRIEND STATUS ================= */
$friendStatus = "none"; // none, pending, friends
if (!$isOwner) {
    $statusStmt = $conn->prepare("
        SELECT status FROM friends
        WHERE (sender_id = ? AND receiver_id = ?) 
        OR (sender_id = ? AND receiver_id = ?)
        LIMIT 1
    ");
    $statusStmt->bind_param("iiii", $current_user, $profile_id, $profile_id, $current_user);
    $statusStmt->execute();
    $statusResult = $statusStmt->get_result();
    if ($statusResult->num_rows > 0) {
        $friendStatus = $statusResult->fetch_assoc()['status'];
    }
}

/* ================= FOLLOWING STATUS ================= */
$isFollowing = false;
if (!$isOwner) {
    // Check if followers table exists, if not set isFollowing to false
    $checkTable = $conn->query("SHOW TABLES LIKE 'followers'");
    if ($checkTable && $checkTable->num_rows > 0) {
        $followStmt = $conn->prepare("
            SELECT id FROM followers
            WHERE follower_id = ? AND following_id = ?
        ");
        if ($followStmt) {
            $followStmt->bind_param("ii", $current_user, $profile_id);
            $followStmt->execute();
            $isFollowing = $followStmt->get_result()->num_rows > 0;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $profileName ?> - ZuckBook</title>
<!-- استخدام Font Awesome 6 -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="/assets/dark-mode.css">
<script src="/assets/avatar-placeholder.js"></script>
<script src="/assets/online-status.js"></script>
<script src="/assets/dark-mode.js"></script>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Segoe UI', Helvetica, Arial, sans-serif; background: #f0f2f5; color: #050505; }

/* --- Header Styles (Fb Style) --- */
.header { background: #ffffff; height: 56px; border-bottom: none; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 0 16px; position: fixed; top: 0; left: 0; right: 0; z-index: 300; display: flex; align-items: center; }
.header-content { width: 100%; display: flex; justify-content: space-between; align-items: center; max-width: 100%; padding: 0 16px; }
.logo-container { display: flex; align-items: center; gap: 8px; }
.logo { font-size: 40px; font-weight: bold; color: #1877f2; line-height: 1; cursor: pointer; }
.search-box { position: relative; margin-left: 8px; }
.search-box input { width: 240px; height: 40px; border-radius: 20px; background: #f0f2f5; border: none; padding: 0 16px 0 40px; font-size: 15px; transition: 0.2s; }
.search-box input:focus { background: #fff; box-shadow: 0 0 0 2px #e7f3ff; outline: none; }
.search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #65676b; }

.center-nav { display: flex; gap: 8px; margin-left: 5%; }
.nav-item { width: 112px; height: 56px; display: flex; align-items: center; justify-content: center; border-bottom: 3px solid transparent; color: #65676b; font-size: 24px; cursor: pointer; transition: 0.1s; text-decoration: none; }
.nav-item:hover { background: #f2f2f2; border-radius: 8px; }
.nav-item.active { border-bottom: 3px solid #1877f2; color: #1877f2; border-radius: 0; }
.nav-item.active:hover { background: transparent; }

.right-nav { display: flex; gap: 8px; align-items: center; }
.topbar-btn { width: 40px; height: 40px; border-radius: 50%; background: #e4e6eb; display: flex; align-items: center; justify-content: center; color: #050505; font-size: 18px; cursor: pointer; border: none; transition: 0.2s; position: relative; }
.topbar-btn:hover { background: #d8dadf; }
.topbar-avatar { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; cursor: pointer; }

/* --- Cover & Profile Layout --- */
.main-body { padding-top: 56px; background: #f0f2f5; }
.cover-container { position: relative; height: 350px; background: #fff; }
.cover-inner { max-width: 940px; margin: 0 auto; height: 100%; position: relative; }
.cover-photo { width: 100%; height: 350px; object-fit: cover; border-bottom-left-radius: 8px; border-bottom-right-radius: 8px; }

.profile-header { max-width: 940px; margin: -80px auto 0; padding: 0 16px; position: relative; display: flex; gap: 16px; align-items: flex-end; background: transparent; }
.profile-avatar-wrapper { position: relative; flex-shrink: 0; }
.profile-avatar { width: 168px; height: 168px; border-radius: 50%; border: 4px solid #fff; object-fit: cover; background: #f0f2f5; box-shadow: 0 1px 2px rgba(0,0,0,0.1); }
.profile-info { flex: 1; padding-bottom: 12px; min-width: 0; }
.profile-name-container { display: flex; flex-direction: column; }
.profile-name { font-size: 32px; font-weight: bold; line-height: 1.2; display: flex; align-items: center; gap: 6px; }

/* Facebook-style verification badge - simple and clean */
.verify-badge { 
    width: 20px; 
    height: 20px; 
    border-radius: 50%; 
    background: #1877f2;
    display: inline-flex; 
    align-items: center; 
    justify-content: center; 
    color: white; 
    font-size: 11px;
    flex-shrink: 0;
    margin-left: 4px;
}
.verify-badge i {
    color: white;
}

.profile-stats { font-size: 15px; color: #65676b; margin-top: 4px; }

.profile-actions { position: absolute; bottom: 12px; right: 16px; display: flex; gap: 8px; }
.btn { padding: 0 16px; height: 36px; border-radius: 6px; border: none; font-weight: 600; font-size: 15px; cursor: pointer; display: flex; align-items: center; gap: 6px; transition: 0.2s; }
.btn-primary { background: #1877f2; color: white; }
.btn-primary:hover { background: #166fe5; }
.btn-secondary { background: #e4e6eb; color: #050505; }
.btn-secondary:hover { background: #d8dadf; }

/* --- Content Grid (Facebook 3-Column Layout) --- */
/* About on left, Posts on right - Facebook style */
.container { max-width: 940px; margin: 16px auto; display: grid; grid-template-columns: 360px 1fr; gap: 16px; padding: 0 16px; }

.left-col { display: flex; flex-direction: column; gap: 12px; }
.right-col { display: flex; flex-direction: column; gap: 12px; }

.card { background: #fff; border-radius: 8px; padding: 12px; box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1); }

.intro-title { font-weight: 700; font-size: 17px; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
.intro-item { display: flex; align-items: center; gap: 12px; padding: 8px 0; color: #050505; font-size: 15px; }
.intro-icon { width: 28px; text-align: center; color: #65676b; font-size: 18px; }

.post-card { border-radius: 8px; }
.post-header { padding: 12px; display: flex; align-items: center; gap: 8px; }
.post-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; cursor: pointer; }
.post-author { font-weight: 600; font-size: 15px; display: flex; align-items: center; gap: 4px; }
.post-meta { font-size: 13px; color: #65676b; }
.post-content { padding: 0 12px 12px; font-size: 15px; line-height: 1.5; white-space: pre-wrap; }
.post-image { width: 100%; margin-top: 8px; }

.post-stats { padding: 8px 12px; display: flex; justify-content: space-between; color: #65676b; font-size: 14px; border-bottom: 1px solid #ced0d4; margin-bottom: 4px; }
.reactions-icon { color: #1877f2; margin-right: 4px; }
.comments-link { color: #65676b; cursor: pointer; }
.comments-link:hover { text-decoration: underline; }

.post-actions { display: flex; padding: 0 8px; margin-top: 4px; }
.action-btn { flex: 1; background: transparent; border: none; padding: 10px 0; font-weight: 600; color: #65676b; border-radius: 4px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 15px; }
.action-btn:hover { background: #f2f2f2; }
.action-btn i { font-size: 18px; }

.no-posts { text-align: center; padding: 20px; background: white; border-radius: 8px; color: #65676b; }

/* --- Edit Profile Modal --- */
.edit-modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 1000; align-items: center; justify-content: center; }
.edit-modal.show { display: flex; }
.edit-modal-content { background: white; border-radius: 12px; width: 90%; max-width: 500px; max-height: 90vh; overflow-y: auto; }
.edit-modal-header { display: flex; justify-content: space-between; align-items: center; padding: 16px; border-bottom: 1px solid #e5e7eb; }
.edit-modal-header h2 { font-size: 20px; font-weight: 600; }
.edit-modal-close { background: none; border: none; font-size: 24px; cursor: pointer; color: #65676b; }
.edit-modal-close:hover { color: #050505; }

.edit-section { padding: 16px; border-bottom: 1px solid #e5e7eb; }
.edit-section-title { font-weight: 600; font-size: 15px; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
.edit-section-title i { color: #1877f2; }

.image-preview { width: 100%; max-height: 300px; object-fit: cover; border-radius: 8px; margin-bottom: 12px; }
.avatar-preview { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; margin: 0 auto 12px; border: 3px solid #1877f2; }

.file-input-wrapper { position: relative; overflow: hidden; display: inline-block; width: 100%; }
.file-input-wrapper input[type=file] { position: absolute; left: -9999px; }
.file-input-label { display: block; padding: 10px 16px; background: #e4e6eb; border-radius: 6px; cursor: pointer; text-align: center; font-weight: 600; color: #050505; transition: 0.2s; }
.file-input-label:hover { background: #d8dadf; }

.edit-modal-actions { display: flex; gap: 8px; padding: 16px; }
.edit-modal-actions button { flex: 1; padding: 10px; border-radius: 6px; border: none; font-weight: 600; cursor: pointer; transition: 0.2s; }
.edit-modal-actions .cancel { background: #e4e6eb; color: #050505; }
.edit-modal-actions .cancel:hover { background: #d8dadf; }
.edit-modal-actions .save { background: #1877f2; color: white; }
.edit-modal-actions .save:hover { background: #166fe5; }

/* --- Top Bar Modals --- */
.top-modal { display: none; position: fixed; top: 56px; right: 16px; width: 360px; background: white; border-radius: 8px; box-shadow: 0 2px 12px rgba(0,0,0,0.15); z-index: 999; max-height: 500px; overflow-y: auto; }
.top-modal.show { display: block; }
.modal-header-bar { padding: 12px 16px; border-bottom: 1px solid #e5e7eb; font-weight: 600; font-size: 15px; display: flex; justify-content: space-between; align-items: center; }
.modal-close-btn { background: none; border: none; font-size: 20px; cursor: pointer; color: #65676b; }
.modal-close-btn:hover { color: #050505; }
.modal-item { padding: 12px 16px; border-bottom: 1px solid #f0f2f5; cursor: pointer; transition: 0.2s; display: flex; align-items: center; gap: 12px; }
.modal-item:hover { background: #f0f2f5; }
.modal-item-icon { width: 40px; height: 40px; border-radius: 50%; background: #e4e6eb; display: flex; align-items: center; justify-content: center; color: #1877f2; font-size: 18px; }
.modal-item-content { flex: 1; }
.modal-item-title { font-weight: 600; font-size: 14px; }
.modal-item-desc { font-size: 12px; color: #65676b; margin-top: 2px; }
.modal-empty { padding: 24px; text-align: center; color: #65676b; }

/* --- Lightbox Modal --- */
.lightbox { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.9); z-index: 2000; align-items: center; justify-content: center; }
.lightbox.show { display: flex; }
.lightbox-content { position: relative; max-width: 90vw; max-height: 90vh; }
.lightbox-img { width: 100%; height: 100%; object-fit: contain; }
.lightbox-close { position: absolute; top: 20px; right: 20px; background: white; border: none; width: 40px; height: 40px; border-radius: 50%; font-size: 24px; cursor: pointer; display: flex; align-items: center; justify-content: center; color: #050505; }
.lightbox-close:hover { background: #f0f2f5; }
</style>
</head>
<body>

<!-- Header -->
<div class="header">
    <div class="header-content">
        <div class="logo-container">
            <div class="logo" onclick="window.location.href='/home.php'">Z</div>
            <div class="search-box">
                <i class="fas fa-search search-icon"></i>
                <input type="text" placeholder="Search ZuckBook">
            </div>
        </div>
        
        <div class="center-nav">
            <a href="/home.php" class="nav-item"><i class="fas fa-home"></i></a>
            <a href="/friends.php" class="nav-item"><i class="fas fa-user-friends"></i></a>
            <a href="/videos.php" class="nav-item"><i class="fas fa-tv"></i></a>
            <a href="/groups.php" class="nav-item"><i class="fas fa-store"></i></a>
            <a href="/groups.php" class="nav-item"><i class="fas fa-users"></i></a>
        </div>
        
        <div class="right-nav">
            <button class="topbar-btn" onclick="window.location.href='/home.php'" title="Home"><i class="fas fa-home"></i></button>
            <button class="topbar-btn" onclick="window.location.href='/friends.php'" title="Friends"><i class="fas fa-user-friends"></i></button>
            <button class="topbar-btn" onclick="openMessengerModal()" title="Messenger"><i class="fab fa-facebook-messenger"></i></button>
            <button class="topbar-btn" onclick="window.location.href='/notifications.php'" title="Notifications"><i class="fas fa-bell"></i></button>
            <button class="topbar-btn" onclick="window.location.href='/groups.php'" title="Groups"><i class="fas fa-users"></i></button>
            <img src="<?= $userImage ?>" class="topbar-avatar user-avatar" alt="<?= $userName ?>" data-name="<?= $userName ?>" onclick="openAccountModal()" title="Account">
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="main-body">
    <!-- Cover Section -->
    <div class="cover-container">
        <div class="cover-inner">
            <?php if($coverImage): ?>
                <img src="<?= $coverImage ?>" class="cover-photo" style="object-position: center <?= $coverPos ?>%; cursor: pointer;" onclick="openLightbox('<?= $coverImage ?>')" title="Click to view">
            <?php else: ?>
                <div class="cover-photo" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden;">
                    <div style="font-size: 120px; font-weight: bold; color: rgba(255,255,255,0.3); letter-spacing: 20px;">
                        <?= strtoupper(substr($profileName, 0, 1)) ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Profile Header -->
    <div class="profile-header">
        <div class="profile-avatar-wrapper">
            <img src="<?= $profileImage ?>" 
                 class="profile-avatar" 
                 alt="<?= $profileUserName ?>"
                 data-name="<?= $profileUserName ?>"
                 style="cursor: pointer;" 
                 onclick="openLightbox('<?= $profileImage ?>')" 
                 title="Click to view">
        </div>
        <div class="profile-info">
            <div class="profile-name-container">
                <div class="profile-name">
                    <?= $profileName ?>
                    <?php if($isVerified == 1): ?>
                        <span class="verify-badge" title="Verified Account">
                            <i class="fas fa-check"></i>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="profile-stats"><?= formatCount($postCount) ?> Posts · <span style="color: #65676b; cursor: pointer;"><?= formatCount($followersCount) ?> Followers · <?= formatCount($followingCount) ?> Following</span></div>
            </div>
            <?php if(isset($isMobile) && $isMobile): // دعم للجوال ?>
                <div class="profile-actions">
                    <?php if(!$isOwner): ?>
                        <button class="btn btn-primary"><i class="fas fa-user-plus"></i> Add Friend</button>
                        <button class="btn btn-secondary"><i class="fas fa-comment"></i> Message</button>
                    <?php else: ?>
                        <button class="btn btn-secondary"><i class="fas fa-camera"></i> Edit Cover</button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <!-- أزرار سطح المكتب في النسخة الكاملة -->
        <div class="profile-actions" style="display: flex;">
            <?php if(!$isOwner): ?>
                <?php if($friendStatus === "friends"): ?>
                    <button class="btn btn-secondary" style="background: #65676b; color: white;">
                        <i class="fas fa-user-check"></i> Friends
                    </button>
                <?php elseif($friendStatus === "pending"): ?>
                    <button class="btn btn-secondary" onclick="cancelFriendRequest(<?= $profile_id ?>)">
                        <i class="fas fa-user-times"></i> Cancel Request
                    </button>
                <?php else: ?>
                    <button class="btn btn-primary" onclick="addFriend(<?= $profile_id ?>)">
                        <i class="fas fa-user-plus"></i> Add Friend
                    </button>
                <?php endif; ?>
                
                <button class="btn btn-secondary" onclick="window.location.href='/chat.php?user=<?= $profile_id ?>'">
                    <i class="fas fa-comment"></i> Message
                </button>
                
                <?php if($isFollowing): ?>
                    <button class="btn btn-secondary" onclick="unfollowUser(<?= $profile_id ?>)">
                        <i class="fas fa-user-minus"></i> Following
                    </button>
                <?php else: ?>
                    <button class="btn btn-secondary" onclick="followUser(<?= $profile_id ?>)">
                        <i class="fas fa-user-plus"></i> Follow
                    </button>
                <?php endif; ?>
            <?php else: ?>
                <button class="btn btn-secondary" onclick="openEditModal()" style="background: #e4e6eb; color: #050505; font-weight: 600;">
                    <i class="fas fa-pencil-alt"></i> Edit Profile
                </button>
                <button class="btn btn-primary" onclick="boostFollowers()" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
                    <i class="fas fa-rocket"></i> Boost Followers
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Content Layout -->
    <div class="container">
        <!-- Left Sidebar (About & Navigation) -->
        <div class="left-col">
            <div class="card">
                <div class="intro-title"><i class="fas fa-globe-americas"></i> About</div>
                
                <?php if($profileBio): ?>
                    <div class="intro-item" style="flex-direction: column; align-items: flex-start; padding: 12px 0;">
                        <div style="font-size: 14px; color: #65676b; margin-bottom: 4px;">Bio</div>
                        <div style="font-size: 15px; line-height: 1.4;"><?= nl2br($profileBio) ?></div>
                    </div>
                <?php endif; ?>
                
                <?php if($profileTagline): ?>
                    <div class="intro-item">
                        <i class="fas fa-quote-left intro-icon"></i>
                        <div><?= $profileTagline ?></div>
                    </div>
                <?php endif; ?>
                
                <?php if($profileJobTitle): ?>
                    <div class="intro-item">
                        <i class="fas fa-briefcase intro-icon"></i>
                        <div><?= $profileJobTitle ?></div>
                    </div>
                <?php endif; ?>
                
                <?php if($profileCity): ?>
                    <div class="intro-item">
                        <i class="fas fa-map-marker-alt intro-icon"></i>
                        <div>Lives in <?= $profileCity ?></div>
                    </div>
                <?php endif; ?>
                
                <?php if($profileFromCity): ?>
                    <div class="intro-item">
                        <i class="fas fa-home intro-icon"></i>
                        <div>From <?= $profileFromCity ?></div>
                    </div>
                <?php endif; ?>
                
                <?php if($profileRelationship): ?>
                    <div class="intro-item">
                        <i class="fas fa-heart intro-icon"></i>
                        <div><?= $profileRelationship ?></div>
                    </div>
                <?php endif; ?>
                
                <?php if($joinDate): ?>
                    <div class="intro-item">
                        <i class="fas fa-calendar-alt intro-icon"></i>
                        <div>Joined <?= $joinDate ?></div>
                    </div>
                <?php endif; ?>
                
                <div class="intro-item">
                    <i class="fas fa-user-friends intro-icon"></i>
                    <div><b><?= formatCount($followersCount) ?></b> Followers · <b><?= formatCount($followingCount) ?></b> Following</div>
                </div>
            </div>
            
            <!-- Sidebar Menu Mimic -->
            <div class="card" style="padding: 8px;">
                <a href="/profile.php?id=<?= $current_user ?>" style="text-decoration: none; color: inherit;">
                    <div class="intro-item" style="border-radius: 8px; cursor: pointer; position: relative;">
                        <img src="<?= $userImage ?>" style="width: 28px; height: 28px; border-radius: 50%;">
                        <div style="font-weight: 500; display: flex; align-items: center; gap: 4px;">
                            <?= $userName ?>
                            <?php if($userData['is_verified'] == 1): ?>
                                <i class="fas fa-check-circle" style="color: #1877f2; font-size: 10px;"></i>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
                <a href="/friends.php" style="text-decoration: none; color: inherit;">
                    <div class="intro-item" style="border-radius: 8px; cursor: pointer; padding: 12px 8px;">
                        <i class="fas fa-user-friends intro-icon" style="color: #1877f2;"></i>
                        <div>Friends</div>
                    </div>
                </a>
                <a href="/coins.php" style="text-decoration: none; color: inherit;">
                    <div class="intro-item" style="border-radius: 8px; cursor: pointer;">
                        <i class="fas fa-coins intro-icon" style="color: #ffc107;"></i>
                        <div>Coins (<?= formatCoins($userCoins) ?>)</div>
                    </div>
                </a>
                <a href="/backend/logout.php" style="text-decoration: none; color: inherit;">
                    <div class="intro-item" style="border-radius: 8px; cursor: pointer;">
                        <i class="fas fa-sign-out-alt intro-icon"></i>
                        <div>Logout</div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Main Feed (Posts) -->
        <div class="right-col">
            <?php if($posts->num_rows == 0): ?>
                <div class="no-posts">
                    <h3>No posts yet</h3>
                    <p style="margin-top: 8px; color: #65676b;">When <?= $profileName ?> posts something, it'll appear here.</p>
                </div>
            <?php else: ?>
                <?php while($post = $posts->fetch_assoc()): ?>
                    <div class="card post-card">
                        <div class="post-header">
                            <div style="position: relative; display: inline-block;">
                                <img src="<?= $profileImage ?>" class="post-avatar" alt="<?= $profileName ?>" data-name="<?= $profileName ?>">
                            </div>
                            <div>
                                <div class="post-author" style="cursor: pointer;" onclick="window.location.href='/profile.php?id=<?= $profile_id ?>'">
                                    <?= $profileName ?>
                                    <?php if($isVerified == 1): ?>
                                        <span class="verify-badge" style="width: 16px; height: 16px; font-size: 9px; margin-left: 4px;">
                                            <i class="fas fa-check"></i>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="post-meta">
                                    <span><?= date("F j", strtotime($post['created_at'])) ?> at <?= date("g:i a", strtotime($post['created_at'])) ?></span>
                                    <span style="margin: 0 4px;">·</span>
                                    <i class="fas fa-globe-americas" style="font-size: 12px;"></i>
                                </div>
                            </div>
                        </div>
                        
                        <?php if(!empty($post['content'])): ?>
                            <div class="post-content"><?= nl2br($post['content']) ?></div>
                        <?php endif; ?>
                        
                        <?php if(!empty($post['image'])): ?>
                            <img src="/uploads/<?= $post['image'] ?>" class="post-image" onclick="openLightbox('/uploads/<?= $post['image'] ?>')">
                        <?php endif; ?>
                        
                        <div class="post-stats">
                            <div class="reactions-count">
                                <i class="fas fa-thumbs-up reactions-icon"></i>
                                <span><?= $post['like_count'] ?? 0 ?></span>
                            </div>
                            <div class="comments-link">0 comments</div>
                        </div>
                        
                        <div class="post-actions">
                            <button class="action-btn">
                                <i class="far fa-thumbs-up"></i> Like
                            </button>
                            <button class="action-btn">
                                <i class="far fa-comment"></i> Comment
                            </button>
                            <button class="action-btn">
                                <i class="far fa-share-square"></i> Share
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Lightbox Modal -->
<div id="lightbox" class="lightbox">
    <div class="lightbox-content">
        <button class="lightbox-close" onclick="closeLightbox()"><i class="fas fa-times"></i></button>
        <img id="lightboxImg" src="" class="lightbox-img">
    </div>
</div>

<!-- Edit Profile Modal -->
<div id="editModal" class="edit-modal">
    <div class="edit-modal-content">
        <div class="edit-modal-header">
            <h2>Edit Profile</h2>
            <button class="edit-modal-close" onclick="closeEditModal()"><i class="fas fa-times"></i></button>
        </div>

        <!-- Cover Photo Section -->
        <div class="edit-section">
            <div class="edit-section-title"><i class="fas fa-image"></i> Cover Photo</div>
            <img id="coverPreview" src="<?= $coverImage ?: 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22500%22 height=%22200%22%3E%3Crect fill=%22%23667eea%22 width=%22500%22 height=%22100%22/%3E%3Crect fill=%22%23764ba2%22 y=%22100%22 width=%22500%22 height=%22100%22/%3E%3C/svg%3E' ?>" class="image-preview">
            <div class="file-input-wrapper">
                <input type="file" id="coverInput" accept="image/*" onchange="previewCover(this)">
                <label for="coverInput" class="file-input-label"><i class="fas fa-camera"></i> Change Cover Photo</label>
            </div>
        </div>

        <!-- Profile Photo Section -->
        <div class="edit-section">
            <div class="edit-section-title"><i class="fas fa-user-circle"></i> Profile Picture</div>
            <img id="avatarPreview" src="<?= $profileImage ?>" class="avatar-preview profile-avatar" alt="<?= $profileUserName ?>" data-name="<?= $profileUserName ?>">
            <div class="file-input-wrapper">
                <input type="file" id="avatarInput" accept="image/*" onchange="previewAvatar(this)">
                <label for="avatarInput" class="file-input-label"><i class="fas fa-camera"></i> Change Profile Picture</label>
            </div>
        </div>

        <!-- Cover Position Slider -->
        <div class="edit-section">
            <div class="edit-section-title"><i class="fas fa-sliders-h"></i> Cover Position</div>
            <input type="range" id="coverPosition" min="0" max="100" value="<?= $coverPos ?>" style="width: 100%; cursor: pointer;">
            <small style="color: #65676b; display: block; margin-top: 8px;">Drag to adjust image position</small>
        </div>

        <!-- Name Section -->
        <div class="edit-section">
            <div class="edit-section-title"><i class="fas fa-user"></i> Name</div>
            <input type="text" id="nameInput" value="<?= $profileName ?>" placeholder="Enter your name" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 15px;">
        </div>

        <!-- Tagline Section -->
        <div class="edit-section">
            <div class="edit-section-title"><i class="fas fa-quote-left"></i> Tagline</div>
            <input type="text" id="taglineInput" value="<?= $profileTagline ?>" placeholder="Example: Digital Content Creator" maxlength="255" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 15px;">
            <small style="color: #65676b; display: block; margin-top: 4px;">Maximum 255 characters</small>
        </div>

        <!-- Bio Section -->
        <div class="edit-section">
            <div class="edit-section-title"><i class="fas fa-align-left"></i> Bio</div>
            <textarea id="bioInput" placeholder="Tell us about yourself..." maxlength="1000" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 15px; min-height: 100px; resize: vertical;"><?= $profileBio ?></textarea>
            <small style="color: #65676b; display: block; margin-top: 4px;">Maximum 1000 characters</small>
        </div>

        <!-- Job Title Section -->
        <div class="edit-section">
            <div class="edit-section-title"><i class="fas fa-briefcase"></i> Job Title</div>
            <input type="text" id="jobTitleInput" value="<?= $profileJobTitle ?>" placeholder="Example: Certified Developer" maxlength="100" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 15px;">
        </div>

        <!-- City Section -->
        <div class="edit-section">
            <div class="edit-section-title"><i class="fas fa-map-marker-alt"></i> Current City</div>
            <input type="text" id="cityInput" value="<?= $profileCity ?>" placeholder="Example: New York" maxlength="100" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 15px;">
        </div>

        <!-- From City Section -->
        <div class="edit-section">
            <div class="edit-section-title"><i class="fas fa-home"></i> From City</div>
            <input type="text" id="fromCityInput" value="<?= $profileFromCity ?>" placeholder="Example: Los Angeles" maxlength="100" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 15px;">
        </div>

        <!-- Relationship Status Section -->
        <div class="edit-section">
            <div class="edit-section-title"><i class="fas fa-heart"></i> Relationship Status</div>
            <select id="relationshipInput" style="width: 100%; padding: 10px; border: 1px solid #e5e7eb; border-radius: 6px; font-size: 15px;">
                <option value="">Not specified</option>
                <option value="Single" <?= $profileRelationship === 'Single' ? 'selected' : '' ?>>Single</option>
                <option value="In a relationship" <?= $profileRelationship === 'In a relationship' ? 'selected' : '' ?>>In a relationship</option>
                <option value="Engaged" <?= $profileRelationship === 'Engaged' ? 'selected' : '' ?>>Engaged</option>
                <option value="Married" <?= $profileRelationship === 'Married' ? 'selected' : '' ?>>Married</option>
                <option value="It's complicated" <?= $profileRelationship === "It's complicated" ? 'selected' : '' ?>>It's complicated</option>
            </select>
        </div>

        <!-- Action Buttons -->
        <div class="edit-modal-actions">
            <button class="cancel" onclick="closeEditModal()">Cancel</button>
            <button class="save" onclick="saveProfileChanges()">Save Changes</button>
        </div>
    </div>
</div>

<!-- Apps Modal -->
<div id="appsModal" class="top-modal">
    <div class="modal-header-bar">
        <span>التطبيقات</span>
        <button class="modal-close-btn" onclick="closeAppsModal()"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-item" onclick="window.location.href='/games.php'">
        <div class="modal-item-icon"><i class="fas fa-gamepad"></i></div>
        <div class="modal-item-content">
            <div class="modal-item-title">الألعاب</div>
            <div class="modal-item-desc">العب مع أصدقائك</div>
        </div>
    </div>
    <div class="modal-item" onclick="window.location.href='/marketplace.php'">
        <div class="modal-item-icon"><i class="fas fa-store"></i></div>
        <div class="modal-item-content">
            <div class="modal-item-title">السوق</div>
            <div class="modal-item-desc">اشتري وبع الأشياء</div>
        </div>
    </div>
    <div class="modal-item" onclick="window.location.href='/events.php'">
        <div class="modal-item-icon"><i class="fas fa-calendar"></i></div>
        <div class="modal-item-content">
            <div class="modal-item-title">الأحداث</div>
            <div class="modal-item-desc">اكتشف الأحداث القادمة</div>
        </div>
    </div>
</div>

<!-- Messenger Modal -->
<div id="messengerModal" class="top-modal">
    <div class="modal-header-bar">
        <span>الرسائل</span>
        <button class="modal-close-btn" onclick="closeMessengerModal()"><i class="fas fa-times"></i></button>
    </div>
    <div id="messengerList"></div>
</div>

<!-- Notifications Modal -->
<div id="notificationsModal" class="top-modal">
    <div class="modal-header-bar">
        <span>الإشعارات</span>
        <button class="modal-close-btn" onclick="closeNotificationsModal()"><i class="fas fa-times"></i></button>
    </div>
    <div id="notificationsList"></div>
</div>

<!-- Account Modal -->
<div id="accountModal" class="top-modal">
    <div class="modal-header-bar">
        <span>الحساب</span>
        <button class="modal-close-btn" onclick="closeAccountModal()"><i class="fas fa-times"></i></button>
    </div>
    
    <!-- User Info -->
    <div style="padding: 16px; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; gap: 12px; background: #f8f9fa;">
        <img src="<?= $userImage ?>" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 3px solid #1877f2;">
        <div style="flex: 1;">
            <div style="font-weight: 700; font-size: 16px; color: #050505; display: flex; align-items: center; gap: 6px;">
                <?= $userName ?>
                <?php if($userData['is_verified'] == 1): ?>
                    <i class="fas fa-check-circle" style="color: #1877f2; font-size: 14px;"></i>
                <?php endif; ?>
            </div>
            <div style="font-size: 13px; color: #65676b; margin-top: 2px;">
                <i class="fas fa-coins" style="color: #ffc107;"></i> <?= formatCoins($userCoins) ?> Coins
            </div>
        </div>
    </div>
    
    <!-- Menu Items -->
    <div class="modal-item" onclick="window.location.href='/profile.php?id=<?= $current_user ?>'">
        <div class="modal-item-icon" style="background: #e7f3ff;"><i class="fas fa-user" style="color: #1877f2;"></i></div>
        <div class="modal-item-content">
            <div class="modal-item-title">My Profile</div>
            <div class="modal-item-desc">View your profile</div>
        </div>
    </div>
    
    <div class="modal-item" onclick="window.location.href='/settings.php'">
        <div class="modal-item-icon" style="background: #f3e8ff;"><i class="fas fa-cog" style="color: #9333ea;"></i></div>
        <div class="modal-item-content">
            <div class="modal-item-title">Settings</div>
            <div class="modal-item-desc">Account settings</div>
        </div>
    </div>
    
    <div class="modal-item" onclick="window.location.href='/coins.php'">
        <div class="modal-item-icon" style="background: #fef3c7;"><i class="fas fa-coins" style="color: #f59e0b;"></i></div>
        <div class="modal-item-content">
            <div class="modal-item-title">Coins</div>
            <div class="modal-item-desc"><?= formatCoins($userCoins) ?> available</div>
        </div>
    </div>
    
    <div class="modal-item" onclick="window.location.href='/subscriptions.php'">
        <div class="modal-item-icon" style="background: #dbeafe;"><i class="fas fa-crown" style="color: #3b82f6;"></i></div>
        <div class="modal-item-content">
            <div class="modal-item-title">Subscriptions</div>
            <div class="modal-item-desc">Upgrade your account</div>
        </div>
    </div>
    
    <div class="modal-item" onclick="window.location.href='/my_subscription.php'">
        <div class="modal-item-icon" style="background: #dcfce7;"><i class="fas fa-star" style="color: #10b981;"></i></div>
        <div class="modal-item-content">
            <div class="modal-item-title">My Subscription</div>
            <div class="modal-item-desc">Manage subscription</div>
        </div>
    </div>
    
    <div class="modal-item" onclick="window.location.href='/friends.php'">
        <div class="modal-item-icon" style="background: #e0f2fe;"><i class="fas fa-user-friends" style="color: #0284c7;"></i></div>
        <div class="modal-item-content">
            <div class="modal-item-title">Friends</div>
            <div class="modal-item-desc">Manage your friends</div>
        </div>
    </div>
    
    <div class="modal-item" onclick="window.location.href='/change_password.php'">
        <div class="modal-item-icon" style="background: #fef2f2;"><i class="fas fa-key" style="color: #ef4444;"></i></div>
        <div class="modal-item-content">
            <div class="modal-item-title">Change Password</div>
            <div class="modal-item-desc">Update your password</div>
        </div>
    </div>
    
    <div style="border-top: 1px solid #e5e7eb; margin-top: 8px;"></div>
    
    <div class="modal-item" onclick="if(confirm('Are you sure you want to logout?')) window.location.href='/backend/logout.php'">
        <div class="modal-item-icon" style="background: #fee2e2;"><i class="fas fa-sign-out-alt" style="color: #dc2626;"></i></div>
        <div class="modal-item-content">
            <div class="modal-item-title" style="color: #dc2626; font-weight: 700;">Logout</div>
            <div class="modal-item-desc">Sign out of your account</div>
        </div>
    </div>
</div>

<script>
// Lightbox Functions
function openLightbox(imageSrc) {
    document.getElementById('lightboxImg').src = imageSrc;
    document.getElementById('lightbox').classList.add('show');
}

function closeLightbox() {
    document.getElementById('lightbox').classList.remove('show');
}

// Close lightbox when clicking outside the image
document.addEventListener('DOMContentLoaded', () => {
    const lightbox = document.getElementById('lightbox');
    lightbox.addEventListener('click', (e) => {
        if (e.target === lightbox) {
            closeLightbox();
        }
    });
    
    // Close with Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeLightbox();
        }
    });
});

// Load Messenger Conversations
function loadMessengerList() {
    fetch("/backend/get_conversations.php")
    .then(res => res.json())
    .then(data => {
        let html = "";
        if (Array.isArray(data) && data.length > 0) {
            data.forEach(conv => {
                html += `
                    <div class="modal-item" onclick="window.location.href='/chat.php?user=${conv.user_id}'">
                        <img src="${conv.profile_image || '/assets/zuckuser.png'}" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                        <div class="modal-item-content">
                            <div class="modal-item-title">${conv.name}</div>
                            <div class="modal-item-desc">${conv.last_message || 'No messages'}</div>
                        </div>
                    </div>
                `;
            });
        } else {
            html = '<div class="modal-empty">No conversations</div>';
        }
        document.getElementById("messengerList").innerHTML = html;
    });
}

// Load Notifications
function loadNotificationsList() {
    fetch("/backend/get_unread_notifications.php")
    .then(res => res.json())
    .then(data => {
        let html = "";
        if (Array.isArray(data) && data.length > 0) {
            data.forEach(notif => {
                html += `
                    <div class="modal-item" onclick="markNotificationRead(${notif.id})">
                        <div class="modal-item-icon"><i class="fas fa-bell"></i></div>
                        <div class="modal-item-content">
                            <div class="modal-item-title">${notif.title}</div>
                            <div class="modal-item-desc">${notif.message}</div>
                        </div>
                    </div>
                `;
            });
        } else {
            html = '<div class="modal-empty">No new notifications</div>';
        }
        document.getElementById("notificationsList").innerHTML = html;
    });
}

function markNotificationRead(notifId) {
    fetch("/backend/mark_seen.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "notification_id=" + notifId
    })
    .then(() => loadNotificationsList());
}

// Edit Profile Modal Functions
function openEditModal() {
    document.getElementById("editModal").classList.add("show");
}

function closeEditModal() {
    document.getElementById("editModal").classList.remove("show");
}

function previewCover(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = (e) => {
            document.getElementById("coverPreview").src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = (e) => {
            document.getElementById("avatarPreview").src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function saveProfileChanges() {
    const coverInput = document.getElementById("coverInput");
    const avatarInput = document.getElementById("avatarInput");
    const coverPosition = document.getElementById("coverPosition").value;
    const name = document.getElementById("nameInput").value.trim();
    const tagline = document.getElementById("taglineInput").value.trim();
    const bio = document.getElementById("bioInput").value.trim();
    const jobTitle = document.getElementById("jobTitleInput").value.trim();
    const city = document.getElementById("cityInput").value.trim();
    const fromCity = document.getElementById("fromCityInput").value.trim();
    const relationship = document.getElementById("relationshipInput").value;

    if (!name) {
        alert("Name is required!");
        return;
    }

    const formData = new FormData();
    
    if (coverInput.files.length > 0) {
        const coverFile = coverInput.files[0];
        if (coverFile.size > 5 * 1024 * 1024) {
            alert("Cover image is too large (max 5MB)");
            return;
        }
        formData.append("coverImage", coverFile);
    }
    
    if (avatarInput.files.length > 0) {
        const avatarFile = avatarInput.files[0];
        if (avatarFile.size > 5 * 1024 * 1024) {
            alert("Profile image is too large (max 5MB)");
            return;
        }
        formData.append("profileImage", avatarFile);
    }
    
    formData.append("coverPosition", coverPosition);
    formData.append("name", name);
    formData.append("tagline", tagline);
    formData.append("bio", bio);
    formData.append("jobTitle", jobTitle);
    formData.append("city", city);
    formData.append("fromCity", fromCity);
    formData.append("relationship", relationship);

    fetch("/backend/update_profile.php", {
        method: "POST",
        body: formData
    })
    .then(res => {
        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }
        return res.json();
    })
    .then(data => {
        if (data.status === "success") {
            alert("Changes saved successfully!");
            closeEditModal();
            location.reload();
        } else {
            alert("Error: " + (data.error || "Failed to save changes"));
        }
    })
    .catch(err => {
        console.error("Error:", err);
        alert("Connection error: " + err.message);
    });
}

// Close modal when clicking outside
document.getElementById("editModal").addEventListener("click", (e) => {
    if (e.target.id === "editModal") {
        closeEditModal();
    }
});

// Top Bar Modals Functions
function openAppsModal() {
    closeMessengerModal();
    closeNotificationsModal();
    document.getElementById("appsModal").classList.toggle("show");
}

function closeAppsModal() {
    document.getElementById("appsModal").classList.remove("show");
}

function openMessengerModal() {
    closeAppsModal();
    closeNotificationsModal();
    document.getElementById("messengerModal").classList.toggle("show");
}

function closeMessengerModal() {
    document.getElementById("messengerModal").classList.remove("show");
}

function openNotificationsModal() {
    closeAppsModal();
    closeMessengerModal();
    document.getElementById("notificationsModal").classList.toggle("show");
}

function closeNotificationsModal() {
    document.getElementById("notificationsModal").classList.remove("show");
}

// Account Modal Functions
function openAccountModal() {
    closeAppsModal();
    closeMessengerModal();
    closeNotificationsModal();
    document.getElementById("accountModal").classList.toggle("show");
}

function closeAccountModal() {
    document.getElementById("accountModal").classList.remove("show");
}

// Close modals when clicking outside
document.addEventListener("click", (e) => {
    if (!e.target.closest(".topbar-btn") && !e.target.closest(".top-modal") && !e.target.closest(".topbar-avatar")) {
        closeAppsModal();
        closeMessengerModal();
        closeNotificationsModal();
        closeAccountModal();
    }
});

// Check friend status on page load
document.addEventListener("DOMContentLoaded", () => {
    const profileId = new URLSearchParams(window.location.search).get('id');
    const currentUserId = <?= $current_user ?>;
    
    if (profileId && profileId != currentUserId) {
        checkFriendStatus(profileId);
    }
});

// Check and update friend status dynamically
function checkFriendStatus(userId) {
    fetch("/backend/check_friend_status.php?id=" + userId, {
        method: "GET"
    })
    .then(res => res.json())
    .then(data => {
        updateFriendButton(userId, data.status);
    })
    .catch(err => console.error("Error checking friend status:", err));
}

// Update friend button based on status
function updateFriendButton(userId, status) {
    const buttons = document.querySelectorAll('.profile-actions .btn');
    let friendBtn = null;
    
    buttons.forEach(btn => {
        if (btn.innerText.includes('Add Friend') || btn.innerText.includes('Cancel Request') || btn.innerText.includes('Friends')) {
            friendBtn = btn;
        }
    });
    
    if (!friendBtn) return;
    
    if (status === "friends") {
        friendBtn.innerText = "Friends";
        friendBtn.style.background = "#65676b";
        friendBtn.style.color = "white";
        friendBtn.disabled = true;
        friendBtn.onclick = null;
    } else if (status === "pending") {
        friendBtn.innerText = "Cancel Request";
        friendBtn.style.background = "#e4e6eb";
        friendBtn.style.color = "#050505";
        friendBtn.disabled = false;
        friendBtn.onclick = function() { cancelFriendRequest(userId); };
    } else {
        friendBtn.innerText = "Add Friend";
        friendBtn.style.background = "#1877f2";
        friendBtn.style.color = "white";
        friendBtn.disabled = false;
        friendBtn.onclick = function() { addFriend(userId); };
    }
}

// Add Friend Function
function addFriend(userId) {
    const btn = event.target.closest('.btn');
    
    fetch("/backend/add_friend.php?id=" + userId, {
        method: "GET"
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === "success") {
            btn.innerText = "Cancel Request";
            btn.style.background = "#e4e6eb";
            btn.style.color = "#050505";
            btn.onclick = function() { cancelFriendRequest(userId); };
        } else if (data.status === "already_friends") {
            btn.innerText = "Friends";
            btn.disabled = true;
            btn.style.background = "#65676b";
            btn.style.color = "white";
        } else {
            alert("Error: " + (data.message || "Unknown error"));
        }
    })
    .catch(err => {
        console.error("Error:", err);
        alert("Error sending friend request");
    });
}

// Cancel Friend Request Function
function cancelFriendRequest(userId) {
    const btn = event.target.closest('.btn');
    
    fetch("/backend/cancel_friend_request.php?id=" + userId, {
        method: "GET"
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === "success") {
            btn.innerText = "Add Friend";
            btn.style.background = "#1877f2";
            btn.style.color = "white";
            btn.onclick = function() { addFriend(userId); };
        } else {
            alert("Error: " + (data.message || "Unknown error"));
        }
    })
    .catch(err => {
        console.error("Error:", err);
        alert("Error canceling friend request");
    });
}

// Refresh friend status
function refreshFriendStatus() {
    location.reload();
}

// Follow User Function
function followUser(userId) {
    const btn = event.target.closest('.btn');
    
    fetch("/backend/follow_user.php?id=" + userId, {
        method: "GET"
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === "success") {
            btn.innerText = "Following";
            btn.style.background = "#65676b";
            btn.onclick = function() { unfollowUser(userId); };
        } else {
            alert("Error: " + (data.message || "Unknown error"));
        }
    })
    .catch(err => {
        console.error("Error:", err);
        alert("Error following user");
    });
}

// Unfollow User Function
function unfollowUser(userId) {
    const btn = event.target.closest('.btn');
    
    fetch("/backend/unfollow_user.php?id=" + userId, {
        method: "GET"
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === "success") {
            btn.innerText = "Follow";
            btn.style.background = "#e4e6eb";
            btn.style.color = "#050505";
            btn.onclick = function() { followUser(userId); };
        } else {
            alert("Error: " + (data.message || "Unknown error"));
        }
    })
    .catch(err => {
        console.error("Error:", err);
        alert("Error unfollowing user");
    });
}

// Boost Followers Function
function boostFollowers() {
    document.getElementById('boostModal').style.display = 'flex';
    updateBoostPrice();
}

// Handle broken images - add placeholder
document.addEventListener('DOMContentLoaded', function() {
    const avatarImg = document.querySelector('.profile-avatar');
    if (avatarImg) {
        avatarImg.addEventListener('error', function() {
            // Create placeholder with first letter of name
            const name = '<?= $profileName ?>';
            const firstLetter = name.charAt(0).toUpperCase();
            
            // Create canvas for placeholder
            const canvas = document.createElement('canvas');
            canvas.width = 168;
            canvas.height = 168;
            const ctx = canvas.getContext('2d');
            
            // Draw background
            ctx.fillStyle = '#1877f2';
            ctx.fillRect(0, 0, 168, 168);
            
            // Draw letter
            ctx.fillStyle = '#ffffff';
            ctx.font = 'bold 72px Arial';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(firstLetter, 84, 84);
            
            // Set as image source
            this.src = canvas.toDataURL();
            this.onerror = null; // Prevent infinite loop
        });
    }
    
    // Handle all avatar images in posts
    document.querySelectorAll('.post-avatar, .search-result-avatar, .topbar-avatar').forEach(img => {
        img.addEventListener('error', function() {
            this.src = '/assets/zuckuser.png';
            this.onerror = null;
        });
    });
});
</script>

<!-- Boost Followers Modal -->
<div id="boostModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.85); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 24px; max-width: 500px; width: 90%; overflow: hidden; animation: slideUp 0.4s ease;">
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 30px; text-align: center; color: white; position: relative;">
            <div style="width: 90px; height: 90px; background: rgba(255,255,255,0.25); backdrop-filter: blur(10px); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;">
                <i class="fas fa-rocket" style="font-size: 45px;"></i>
            </div>
            <h3 style="font-size: 26px; font-weight: 800; margin-bottom: 8px;">شراء متابعين</h3>
            <p style="font-size: 15px; opacity: 0.95;">اختر عدد المتابعين الذي تريد إضافته</p>
        </div>
        
        <div style="padding: 35px 30px;">
            <div style="background: linear-gradient(135deg, #f0f2f5 0%, #e4e6eb 100%); border-radius: 16px; padding: 25px; margin-bottom: 25px;">
                <label style="display: block; font-weight: 700; font-size: 15px; color: #050505; margin-bottom: 12px;">
                    <i class="fas fa-users"></i> عدد المتابعين
                </label>
                <input type="number" id="followersCount" min="100" max="10000" step="100" value="1000" 
                       style="width: 100%; padding: 14px; border: 2px solid #e4e6eb; border-radius: 12px; font-size: 18px; font-weight: 700; text-align: center;"
                       oninput="updateBoostPrice()">
                <p style="font-size: 13px; color: #65676b; margin-top: 8px; text-align: center;">
                    الحد الأدنى: 100 | الحد الأقصى: 10,000
                </p>
            </div>

            <div style="background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); color: white; padding: 20px; border-radius: 16px; text-align: center; margin-bottom: 25px;">
                <div style="font-size: 14px; opacity: 0.9; margin-bottom: 5px;">السعر</div>
                <div style="font-size: 36px; font-weight: 800;" id="boostPrice">50</div>
                <div style="font-size: 14px; opacity: 0.9;"><i class="fas fa-coins"></i> كوين</div>
            </div>

            <div style="background: #e0f2fe; border-right: 4px solid #0284c7; padding: 15px; border-radius: 8px; margin-bottom: 25px;">
                <p style="font-size: 14px; color: #0c4a6e; margin: 0;">
                    <i class="fas fa-info-circle"></i> <strong>السعر:</strong> 100 متابع = 5 كوين
                </p>
            </div>

            <div style="display: flex; gap: 12px;">
                <button onclick="closeBoostModal()" style="flex: 1; padding: 16px; background: #e4e6eb; color: #050505; border: none; border-radius: 12px; font-size: 16px; font-weight: 700; cursor: pointer;">
                    <i class="fas fa-times"></i> إلغاء
                </button>
                <button onclick="confirmBoost()" style="flex: 1; padding: 16px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 12px; font-size: 16px; font-weight: 700; cursor: pointer;">
                    <i class="fas fa-check"></i> تأكيد
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Subscription Badge Modal -->
<div id="subscriptionBadgeModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 9999; align-items: center; justify-content: center;" onclick="closeSubscriptionBadge()">
    <div style="background: white; border-radius: 16px; padding: 30px; max-width: 320px; width: 90%; text-align: center; animation: scaleIn 0.3s ease;" onclick="event.stopPropagation()">
        <div id="badgeIcon" style="width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 40px; color: white;"></div>
        <h3 id="badgeTitle" style="font-size: 22px; font-weight: 800; margin-bottom: 10px; color: #050505;"></h3>
        <p style="font-size: 14px; color: #65676b; margin-bottom: 20px;">This user has an active subscription</p>
        <button onclick="closeSubscriptionBadge()" style="width: 100%; padding: 12px; background: #e4e6eb; color: #050505; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
            Close
        </button>
    </div>
</div>

<style>
@keyframes slideUp {
    from { opacity: 0; transform: translateY(50px) scale(0.9); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
@keyframes scaleIn {
    from { opacity: 0; transform: scale(0.8); }
    to { opacity: 1; transform: scale(1); }
}

/* ==================== MOBILE RESPONSIVE STYLES ==================== */
@media (max-width: 768px) {
    /* Header adjustments */
    .header-content { padding: 0 8px; }
    .logo { font-size: 32px; }
    .search-box { display: none; } /* Hide search on mobile */
    
    .center-nav { display: none; } /* Hide center nav on mobile */
    
    .right-nav { gap: 4px; }
    .topbar-btn { width: 36px; height: 36px; font-size: 16px; }
    .topbar-avatar { width: 28px; height: 28px; }
    
    /* Cover & Profile */
    .cover-container { height: 200px; }
    .cover-photo { height: 200px; }
    .cover-photo > div {
        font-size: 80px !important;
        letter-spacing: 10px !important;
    }
    
    .profile-header {
        flex-direction: column;
        align-items: center;
        margin: -60px auto 0;
        padding: 0 12px;
        text-align: center;
    }
    
    .profile-avatar { width: 120px; height: 120px; }
    
    .profile-info { padding-bottom: 0; }
    .profile-name { font-size: 24px; justify-content: center; }
    .profile-stats { font-size: 14px; text-align: center; }
    
    .profile-actions {
        position: static;
        width: 100%;
        flex-direction: column;
        margin-top: 16px;
        gap: 8px;
    }
    
    .profile-actions .btn {
        width: 100%;
        justify-content: center;
        height: 40px;
    }
    
    /* Content Grid - Stack on mobile */
    .container {
        grid-template-columns: 1fr;
        gap: 12px;
        padding: 0 12px;
        margin: 12px auto;
    }
    
    .left-col { order: 1; } /* About first on mobile */
    .right-col { order: 2; } /* Posts below About on mobile */
    
    /* Cards */
    .card { padding: 12px; border-radius: 8px; }
    
    .intro-title { font-size: 16px; }
    .intro-item { font-size: 14px; padding: 6px 0; }
    .intro-icon { width: 24px; font-size: 16px; }
    
    /* Posts */
    .post-card { border-radius: 8px; }
    .post-header { padding: 10px; }
    .post-avatar { width: 36px; height: 36px; }
    .post-author { font-size: 14px; }
    .post-meta { font-size: 12px; }
    .post-content { padding: 0 10px 10px; font-size: 14px; }
    
    .post-stats { padding: 6px 10px; font-size: 13px; }
    .post-actions { padding: 0 6px; }
    .action-btn { font-size: 14px; padding: 8px 0; }
    .action-btn i { font-size: 16px; }
    
    /* Edit Modal */
    .edit-modal-content {
        width: 95%;
        max-height: 95vh;
    }
    
    .edit-modal-header { padding: 12px; }
    .edit-modal-header h2 { font-size: 18px; }
    
    .edit-section { padding: 12px; }
    .edit-section-title { font-size: 14px; }
    
    .image-preview { max-height: 200px; }
    .avatar-preview { width: 100px; height: 100px; }
    
    .edit-modal-actions { padding: 12px; flex-direction: column; }
    .edit-modal-actions button { padding: 12px; }
    
    /* Top Modals */
    .top-modal {
        width: 100%;
        max-width: 100%;
        right: 0;
        left: 0;
        border-radius: 0;
        max-height: 70vh;
    }
    
    .modal-item { padding: 10px 12px; }
    .modal-item-icon { width: 36px; height: 36px; font-size: 16px; }
    .modal-item-title { font-size: 13px; }
    .modal-item-desc { font-size: 11px; }
    
    /* Lightbox */
    .lightbox-content { max-width: 95vw; max-height: 95vh; }
    .lightbox-close {
        top: 10px;
        right: 10px;
        width: 36px;
        height: 36px;
        font-size: 20px;
    }
    
    /* Boost Modal */
    #boostModal > div {
        width: 95%;
        max-width: 95%;
    }
    
    #boostModal > div > div:first-child {
        padding: 30px 20px;
    }
    
    #boostModal > div > div:first-child > div:first-child {
        width: 70px;
        height: 70px;
    }
    
    #boostModal > div > div:first-child > div:first-child i {
        font-size: 35px;
    }
    
    #boostModal > div > div:first-child h3 {
        font-size: 22px;
    }
    
    #boostModal > div > div:last-child {
        padding: 25px 20px;
    }
    
    #followersCount {
        font-size: 16px;
        padding: 12px;
    }
    
    #boostPrice {
        font-size: 30px;
    }
    
    /* Subscription Badge Modal */
    #subscriptionBadgeModal > div {
        width: 95%;
        padding: 25px;
    }
    
    #badgeIcon {
        width: 70px;
        height: 70px;
        font-size: 35px;
    }
    
    #badgeTitle {
        font-size: 20px;
    }
    
    /* Verify Badge - Mobile */
    .verify-badge {
        width: 16px;
        height: 16px;
        font-size: 9px;
    }
    
    /* No posts message */
    .no-posts {
        padding: 16px;
        font-size: 14px;
    }
    
    .no-posts h3 {
        font-size: 16px;
    }
    
    .no-posts p {
        font-size: 13px;
    }
}

/* Extra small devices (phones in portrait, less than 576px) */
@media (max-width: 575px) {
    .header { height: 50px; padding: 0 6px; }
    .header-content { padding: 0 6px; }
    .logo { font-size: 28px; }
    
    .right-nav { gap: 2px; }
    .topbar-btn { width: 32px; height: 32px; font-size: 14px; }
    .topbar-avatar { width: 26px; height: 26px; }
    
    .main-body { padding-top: 50px; }
    
    .cover-container { height: 150px; }
    .cover-photo { height: 150px; }
    
    .profile-header { margin: -50px auto 0; }
    .profile-avatar { width: 100px; height: 100px; border-width: 3px; }
    
    .profile-name { font-size: 20px; }
    .profile-stats { font-size: 13px; }
    
    .verify-badge {
        width: 14px;
        height: 14px;
        font-size: 8px;
    }
    
    .container { padding: 0 8px; margin: 8px auto; }
    
    .card { padding: 10px; }
    
    .intro-title { font-size: 15px; margin-bottom: 10px; }
    .intro-item { font-size: 13px; }
    
    .post-header { padding: 8px; }
    .post-avatar { width: 32px; height: 32px; }
    .post-author { font-size: 13px; }
    .post-meta { font-size: 11px; }
    .post-content { padding: 0 8px 8px; font-size: 13px; }
    
    .post-stats { padding: 5px 8px; font-size: 12px; }
    .post-actions { padding: 0 4px; }
    .action-btn { font-size: 13px; padding: 6px 0; gap: 4px; }
    .action-btn i { font-size: 14px; }
    
    .btn { padding: 0 12px; height: 34px; font-size: 14px; }
    
    .edit-modal-header h2 { font-size: 16px; }
    .edit-section-title { font-size: 13px; }
    
    input[type="text"],
    input[type="number"],
    textarea,
    select {
        font-size: 14px !important;
        padding: 8px !important;
    }
    
    .file-input-label {
        padding: 8px 12px;
        font-size: 14px;
    }
}

/* Landscape mode adjustments */
@media (max-width: 768px) and (orientation: landscape) {
    .cover-container { height: 150px; }
    .cover-photo { height: 150px; }
    
    .profile-header { margin: -50px auto 0; }
    .profile-avatar { width: 100px; height: 100px; }
    
    .edit-modal-content { max-height: 90vh; }
    .top-modal { max-height: 80vh; }
}

/* Tablet adjustments (768px - 991px) */
@media (min-width: 769px) and (max-width: 991px) {
    .container {
        grid-template-columns: 300px 1fr;
        gap: 12px;
    }
    
    .profile-avatar { width: 140px; height: 140px; }
    .profile-name { font-size: 28px; }
    
    .center-nav .nav-item { width: 90px; }
}
</style>

<script>
function updateBoostPrice() {
    const count = parseInt(document.getElementById('followersCount').value) || 100;
    const price = Math.ceil(count / 100) * 5;
    document.getElementById('boostPrice').textContent = price;
}

function closeBoostModal() {
    document.getElementById('boostModal').style.display = 'none';
}

function showSubscriptionBadge(tierName, subTier) {
    const modal = document.getElementById('subscriptionBadgeModal');
    const icon = document.getElementById('badgeIcon');
    const title = document.getElementById('badgeTitle');
    
    // Set badge colors based on tier
    const colors = {
        'basic': '#1877f2',
        'premium': '#9333ea',
        'elite': '#dc2626'
    };
    
    const badgeColor = colors[subTier] || '#1877f2';
    
    icon.style.background = badgeColor;
    icon.style.color = badgeColor;
    icon.style.boxShadow = `0 0 15px ${badgeColor}, 0 0 30px ${badgeColor}, inset 0 0 10px rgba(255,255,255,0.3)`;
    icon.innerHTML = '<i class="fas fa-check" style="color: white; position: relative; z-index: 1;"></i>';
    title.textContent = tierName;
    
    modal.style.display = 'flex';
}

function closeSubscriptionBadge() {
    document.getElementById('subscriptionBadgeModal').style.display = 'none';
}

function confirmBoost() {
    const count = parseInt(document.getElementById('followersCount').value) || 100;
    const price = Math.ceil(count / 100) * 5;
    
    const modal = document.getElementById('boostModal');
    const confirmBtn = event.target;
    const originalHTML = confirmBtn.innerHTML;
    
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري المعالجة...';
    
    fetch('/backend/boost_followers.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `count=${count}`
    })
    .then(res => res.json())
    .then(data => {
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = originalHTML;
        
        if (data.status === 'success') {
            closeBoostModal();
            alert(`✅ تم بنجاح!\n\n` +
                  `تم إضافة: ${data.added} متابع\n` +
                  `تم تخطي: ${data.skipped} (متابعين بالفعل)\n` +
                  `إجمالي المتابعين: ${data.total}\n\n` +
                  `الكوينات المدفوعة: ${data.coins_spent}\n` +
                  `الكوينات المتبقية: ${data.remaining_coins}`);
            location.reload();
        } else {
            alert("❌ خطأ: " + (data.message || "حدث خطأ غير متوقع"));
        }
    })
    .catch(err => {
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = originalHTML;
        console.error('Error:', err);
        alert("❌ حدث خطأ في الاتصال");
    });
}
</script>

</body>
</html>