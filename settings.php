<?php
session_start();
require_once __DIR__ . "/backend/config.php";
require_once __DIR__ . "/lang/translations.php";
require_once __DIR__ . "/includes/helpers.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: /");
    exit;
}

$user_id = intval($_SESSION['user_id']);

// Check if language column exists, if not add it
$checkColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'language'");
if ($checkColumn->num_rows == 0) {
    $conn->query("ALTER TABLE users ADD COLUMN language VARCHAR(5) DEFAULT 'en' AFTER email");
}

$userStmt = $conn->prepare("SELECT id, coins, is_verified, name, profile_image, is_banned, ban_expires_at, language FROM users WHERE id = ?");
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$userData = $userStmt->get_result()->fetch_assoc();

if (!$userData) {
    session_destroy();
    exit;
}

// Set language from database or default to English
$_SESSION['lang'] = $userData['language'] ?? 'en';
$lang = getCurrentLang();
$dir = getDir();

$userName = htmlspecialchars($userData['name']);
$userImage = $userData['profile_image'] ? "/uploads/" . htmlspecialchars($userData['profile_image']) : "/assets/zuckuser.png";
$userCoins = $userData['coins'];

$notifStmt = $conn->prepare("SELECT COUNT(*) as total FROM notifications WHERE user_id = ? AND is_read = 0");
$notifStmt->bind_param("i", $user_id);
$notifStmt->execute();
$unread = $notifStmt->get_result()->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>" dir="<?= $dir ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= t('settings') ?> - ZuckBook</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="/assets/dark-mode.css">
<script src="/assets/avatar-placeholder.js"></script>
<script src="/assets/online-status.js"></script>
<script src="/assets/dark-mode.js"></script>

<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f0f2f5; }

.header { 
    background: white; 
    border-bottom: 1px solid #e4e6eb; 
    padding: 8px 16px; 
    position: sticky; 
    top: 0; 
    z-index: 100; 
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
}
.header-content { 
    max-width: 1200px; 
    margin: 0 auto; 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    gap: 16px; 
}
.logo { 
    font-size: 32px; 
    font-weight: bold; 
    color: #1877f2; 
    cursor: pointer; 
    font-family: 'Segoe UI', sans-serif;
    line-height: 1;
    user-select: none;
}
.search-box { position: relative; }
.search-box .search-icon { 
    position: absolute; 
    left: 12px; 
    top: 50%; 
    transform: translateY(-50%); 
    color: #65676b; 
    font-size: 14px;
    pointer-events: none;
}
.search-box input { 
    padding: 8px 16px 8px 36px; 
    border: 1px solid #ccc; 
    border-radius: 20px; 
    background: #f0f2f5; 
    width: 240px; 
    outline: none;
    font-size: 14px;
}
.search-box input:focus {
    background: white;
    border-color: #1877f2;
}
.header-icons { display: flex; gap: 8px; }
.header-icon { 
    width: 40px; 
    height: 40px; 
    border-radius: 50%; 
    background: #f0f2f5; 
    border: none; 
    cursor: pointer; 
    font-size: 20px; 
    color: #050505;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s;
}
.header-icon:hover {
    background: #e4e6eb;
}
.user-avatar { 
    width: 32px; 
    height: 32px; 
    border-radius: 50%; 
    object-fit: cover; 
    cursor: pointer; 
    border: 2px solid #e4e6eb;
    background: #f0f2f5;
}

.user-avatar-placeholder {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #1877f2;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 14px;
    cursor: pointer;
    border: 2px solid #e4e6eb;
}

.container { max-width: 1200px; margin: 16px auto; display: grid; grid-template-columns: 280px 1fr 360px; gap: 16px; padding: 0 16px; }

.sidebar { background: white; border-radius: 8px; padding: 8px 0; height: fit-content; position: sticky; top: 60px; }
.sidebar-item { padding: 8px 16px; display: flex; align-items: center; gap: 12px; cursor: pointer; text-decoration: none; color: #050505; font-size: 15px; }
.sidebar-item:hover { background: #f2f2f2; }

.content { background: white; border-radius: 8px; padding: 24px; }
.content-title { font-size: 28px; font-weight: bold; margin-bottom: 24px; }

.settings-section { margin-bottom: 32px; }
.section-title { font-size: 16px; font-weight: 600; margin-bottom: 16px; color: #050505; }

.setting-item { padding: 16px; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; }
.setting-label { font-weight: 600; font-size: 15px; color: #050505; }
.setting-desc { font-size: 13px; color: #65676b; margin-top: 4px; }

select, input[type="text"] { padding: 8px 12px; border: 1px solid #ccc; border-radius: 6px; font-family: inherit; font-size: 14px; }
select:focus, input[type="text"]:focus { outline: none; border-color: #1877f2; }

.toggle { width: 48px; height: 28px; background: #ccc; border-radius: 20px; position: relative; cursor: pointer; transition: 0.3s; border: none; }
.toggle.active { background: #1877f2; }
.toggle::after { content: ''; width: 24px; height: 24px; background: white; position: absolute; top: 2px; left: 2px; border-radius: 50%; transition: 0.3s; }
.toggle.active::after { left: 22px; }

.button-group { display: flex; gap: 12px; margin-top: 24px; }
.btn { padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 15px; transition: 0.2s; }
.btn-primary { background: #1877f2; color: white; }
.btn-primary:hover { background: #0a66c2; }
.btn-secondary { background: #e4e6eb; color: #050505; }
.btn-secondary:hover { background: #d0d2d7; }
.btn-danger { background: #f02849; color: white; }
.btn-danger:hover { background: #d91e3f; }

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
    .header { 
        padding: 8px 12px; 
    }
    
    .header-content { 
        gap: 8px; 
    }
    
    .logo { 
        font-size: 28px; 
    }
    
    .search-box { 
        display: none; 
    }
    
    .header-icons { 
        gap: 4px; 
    }
    
    .header-icon { 
        width: 36px; 
        height: 36px; 
        font-size: 18px; 
    }
    
    .user-avatar,
    .user-avatar-placeholder { 
        width: 32px; 
        height: 32px; 
    }
    
    /* Container */
    .container { 
        padding: 0 8px; 
        margin-top: 12px;
        gap: 12px;
    }
    
    /* Content */
    .content { 
        padding: 16px; 
        border-radius: 0;
    }
    
    .content-title { 
        font-size: 22px; 
        margin-bottom: 20px;
    }
    
    /* Settings sections */
    .settings-section { 
        margin-bottom: 24px; 
    }
    
    .section-title { 
        font-size: 15px; 
        margin-bottom: 12px;
        padding: 8px 0;
        border-bottom: 1px solid #e5e7eb;
    }
    
    /* Setting items */
    .setting-item { 
        padding: 14px 12px; 
        margin-bottom: 10px;
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    
    .setting-label { 
        font-size: 14px; 
    }
    
    .setting-desc { 
        font-size: 12px; 
    }
    
    /* Buttons and controls */
    .btn { 
        padding: 10px 20px; 
        font-size: 14px;
        width: 100%;
    }
    
    select, 
    input[type="text"] { 
        padding: 10px 12px; 
        font-size: 14px;
        width: 100%;
    }
    
    .toggle {
        align-self: flex-end;
    }
    
    .button-group { 
        flex-direction: column; 
        gap: 10px;
    }
}

/* Small mobile devices (575px and below) */
@media (max-width: 575px) {
    /* Header */
    .header { 
        padding: 6px 8px; 
    }
    
    .header-content { 
        gap: 6px; 
    }
    
    .logo { 
        font-size: 26px; 
    }
    
    .header-icons { 
        gap: 2px; 
    }
    
    .header-icon { 
        width: 32px; 
        height: 32px; 
        font-size: 16px; 
    }
    
    .user-avatar,
    .user-avatar-placeholder { 
        width: 28px; 
        height: 28px; 
        font-size: 12px;
    }
    
    /* Container */
    .container { 
        padding: 0 6px; 
        margin-top: 10px;
        gap: 10px;
    }
    
    /* Content */
    .content { 
        padding: 12px; 
    }
    
    .content-title { 
        font-size: 20px; 
        margin-bottom: 16px;
    }
    
    /* Settings sections */
    .settings-section { 
        margin-bottom: 20px; 
    }
    
    .section-title { 
        font-size: 14px; 
        margin-bottom: 10px;
        padding: 6px 0;
    }
    
    /* Setting items */
    .setting-item { 
        padding: 12px 10px; 
        margin-bottom: 8px;
        gap: 10px;
    }
    
    .setting-label { 
        font-size: 13px; 
    }
    
    .setting-desc { 
        font-size: 11px; 
    }
    
    /* Buttons and controls */
    .btn { 
        padding: 9px 16px; 
        font-size: 13px;
    }
    
    select, 
    input[type="text"] { 
        padding: 9px 10px; 
        font-size: 13px;
    }
}

/* Extra small screens (360px and below) */
@media (max-width: 360px) {
    .content-title { 
        font-size: 18px; 
    }
    
    .section-title { 
        font-size: 13px; 
    }
    
    .setting-label { 
        font-size: 12px; 
    }
    
    .setting-desc { 
        font-size: 10px; 
    }
    
    .btn { 
        padding: 8px 14px; 
        font-size: 12px;
    }
}

/* RTL Support */
[dir="rtl"] .search-box .search-icon {
    left: auto;
    right: 12px;
}

[dir="rtl"] .search-box input {
    padding: 8px 36px 8px 16px;
}

[dir="rtl"] .sidebar-item {
    text-align: right;
}

[dir="rtl"] .setting-item {
    flex-direction: row-reverse;
}
</style>
</head>
<body>

<div class="header">
    <div class="header-content">
        <div class="logo" onclick="window.location.href='/home.php'">f</div>
        <div class="search-box">
            <i class="fas fa-search search-icon"></i>
            <input type="text" placeholder="<?= t('search') ?>" class="search-input">
        </div>
        <div class="header-icons">
            <button class="header-icon" onclick="window.location.href='/home.php'"><i class="fas fa-home"></i></button>
            <button class="header-icon"><i class="fas fa-user-friends"></i></button>
            <button class="header-icon"><i class="fas fa-gamepad"></i></button>
        </div>
        <div style="display: flex; gap: 8px; align-items: center;">
            <button class="header-icon" onclick="window.location.href='/notifications.php'"><i class="fas fa-bell"></i></button>
            <img src="<?= $userImage ?>" class="user-avatar" alt="<?= $userName ?>" onclick="window.location.href='/profile.php?id=<?= $user_id ?>'" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            <div class="user-avatar-placeholder" style="display: none;" onclick="window.location.href='/profile.php?id=<?= $user_id ?>'">
                <?= strtoupper(substr($userName, 0, 1)) ?>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="sidebar">
        <a href="/profile.php?id=<?= $user_id ?>" class="sidebar-item"><i class="fas fa-user"></i> <?= $userName ?></a>
        <a href="/friend_requests.php" class="sidebar-item"><i class="fas fa-user-friends"></i> <?= t('friends') ?></a>
        <a href="/coins.php" class="sidebar-item"><i class="fas fa-coins"></i> <?= t('coins') ?> (<?= formatCoins($userCoins) ?>)</a>
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
        <a href="/notifications.php" class="sidebar-item"><i class="fas fa-bell"></i> <?= t('notifications') ?> (<?= $unread ?>)</a>
        <a href="/settings.php" class="sidebar-item"><i class="fas fa-cog"></i> <?= t('settings') ?></a>
        <a href="/backend/logout.php" class="sidebar-item"><i class="fas fa-sign-out-alt"></i> <?= t('logout') ?></a>
    </div>
    
    <div class="content">
        <div class="content-title"><i class="fas fa-cog"></i> <?= t('settings_privacy') ?></div>
        
        <!-- Account Section -->
        <div class="settings-section">
            <div class="section-title"><i class="fas fa-user"></i> <?= t('account') ?></div>
            
            <div class="setting-item">
                <div>
                    <div class="setting-label"><?= t('edit_profile') ?></div>
                    <div class="setting-desc"><?= t('update_personal_info') ?></div>
                </div>
                <button class="btn btn-primary" onclick="window.location.href='/edit_profile.php'"><?= t('edit') ?></button>
            </div>

            <div class="setting-item">
                <div>
                    <div class="setting-label"><?= t('change_password') ?></div>
                    <div class="setting-desc"><?= t('update_security') ?></div>
                </div>
                <button class="btn btn-secondary" onclick="window.location.href='/change_password.php'"><?= t('change') ?></button>
            </div>
        </div>

        <!-- Preferences Section -->
        <div class="settings-section">
            <div class="section-title"><i class="fas fa-palette"></i> <?= t('preferences') ?></div>
            
            <div class="setting-item">
                <div>
                    <div class="setting-label"><?= t('language') ?></div>
                    <div class="setting-desc"><?= t('choose_language') ?></div>
                </div>
                <select onchange="changeLanguage(this.value)">
                    <option value="en" <?= $lang=='en'?'selected':'' ?>>English</option>
                    <option value="ar" <?= $lang=='ar'?'selected':'' ?>>العربية</option>
                </select>
            </div>

            <div class="setting-item">
                <div>
                    <div class="setting-label"><?= t('dark_mode') ?></div>
                    <div class="setting-desc"><?= t('switch_theme') ?></div>
                </div>
                <button class="toggle" id="darkModeToggle" onclick="toggleDarkMode()"></button>
            </div>
        </div>

        <!-- Notifications Section -->
        <div class="settings-section">
            <div class="section-title"><i class="fas fa-bell"></i> <?= t('notifications_settings') ?></div>
            
            <div class="setting-item">
                <div>
                    <div class="setting-label"><?= t('push_notifications') ?></div>
                    <div class="setting-desc"><?= t('receive_alerts') ?></div>
                </div>
                <button class="toggle active" onclick="toggleNotifications(this)"></button>
            </div>

            <div class="setting-item">
                <div>
                    <div class="setting-label"><?= t('email_notifications') ?></div>
                    <div class="setting-desc"><?= t('get_email_updates') ?></div>
                </div>
                <button class="toggle" onclick="toggleEmailNotifications(this)"></button>
            </div>
        </div>

        <!-- Privacy Section -->
        <div class="settings-section">
            <div class="section-title"><i class="fas fa-lock"></i> <?= t('privacy') ?></div>
            
            <div class="setting-item">
                <div>
                    <div class="setting-label"><?= t('profile_visibility') ?></div>
                    <div class="setting-desc"><?= t('who_can_see') ?></div>
                </div>
                <select>
                    <option><?= t('everyone') ?></option>
                    <option><?= t('friends_only') ?></option>
                    <option><?= t('private') ?></option>
                </select>
            </div>
        </div>

        <!-- Danger Zone -->
        <div class="settings-section">
            <div class="section-title"><i class="fas fa-exclamation-triangle"></i> <?= t('danger_zone') ?></div>
            
            <div class="setting-item">
                <div>
                    <div class="setting-label"><?= t('logout') ?></div>
                    <div class="setting-desc"><?= t('sign_out') ?></div>
                </div>
                <button class="btn btn-secondary" onclick="window.location.href='/backend/logout.php'"><?= t('logout') ?></button>
            </div>

            <div class="setting-item">
                <div>
                    <div class="setting-label"><?= t('delete_account') ?></div>
                    <div class="setting-desc"><?= t('permanently_delete') ?></div>
                </div>
                <button class="btn btn-danger" onclick="if(confirm('<?= t('are_you_sure') ?>')) window.location.href='/backend/delete_account.php'"><?= t('delete') ?></button>
            </div>
        </div>
    </div>
    
    <div class="right-sidebar">
        <div class="right-sidebar-title"><i class="fas fa-lightbulb"></i> <?= t('tips') ?></div>
        <div style="font-size: 14px; color: #65676b; line-height: 1.6;">
            <p>• <?= t('keep_password_secure') ?></p>
            <p>• <?= t('review_privacy') ?></p>
            <p>• <?= t('enable_notifications') ?></p>
        </div>
    </div>
</div>

<script>
function changeLanguage(lang) {
    fetch("/backend/update_language.php", {
        method: "POST",
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: "lang=" + lang
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}

function toggleNotifications(el) {
    el.classList.toggle("active");
}

function toggleEmailNotifications(el) {
    el.classList.toggle("active");
}

// Initialize dark mode toggle state
document.addEventListener('DOMContentLoaded', function() {
    const darkModeToggle = document.getElementById('darkModeToggle');
    if (darkModeToggle) {
        // Check current dark mode state
        fetch('/backend/get_dark_mode.php')
            .then(res => res.json())
            .then(data => {
                if (data.dark_mode) {
                    darkModeToggle.classList.add('active');
                }
            });
    }
});
</script>

</body>
</html>