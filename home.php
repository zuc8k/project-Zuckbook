<?php
session_start();
require_once __DIR__ . "/backend/config.php";
require_once __DIR__ . "/includes/helpers.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: /");
    exit;
}

 $user_id = intval($_SESSION['user_id']);

 $userStmt = $conn->prepare("SELECT id, coins, is_verified, name, profile_image, is_banned, ban_expires_at, role, profile_setup_completed FROM users WHERE id = ?");
 $userStmt->bind_param("i", $user_id);
 $userStmt->execute();
 $userData = $userStmt->get_result()->fetch_assoc();

if (!$userData) {
    session_destroy();
    exit;
}

// Check if profile setup is completed
if ($userData['profile_setup_completed'] == 0) {
    header("Location: /setup_profile.php");
    exit;
}

if ($userData['is_banned'] == 1 && ($userData['ban_expires_at'] === NULL || $userData['ban_expires_at'] > date("Y-m-d H:i:s"))) {
    $banExpires = $userData['ban_expires_at'];
    $isPermanent = ($banExpires === NULL);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Account Suspended - ZuckBook</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: #f0f2f5;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }

            .ban-container {
                background: white;
                border-radius: 12px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1), 0 8px 16px rgba(0, 0, 0, 0.1);
                max-width: 500px;
                width: 100%;
                overflow: hidden;
                animation: slideUp 0.5s ease;
            }

            @keyframes slideUp {
                from {
                    opacity: 0;
                    transform: translateY(30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .ban-header {
                background: #ef4444;
                padding: 40px 30px;
                text-align: center;
                color: white;
            }

            .ban-icon {
                width: 80px;
                height: 80px;
                background: rgba(255,255,255,0.2);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 20px;
                font-size: 40px;
                animation: pulse 2s infinite;
            }

            @keyframes pulse {
                0%, 100% {
                    transform: scale(1);
                }
                50% {
                    transform: scale(1.05);
                }
            }

            .ban-header h1 {
                font-size: 28px;
                font-weight: 700;
                margin-bottom: 10px;
            }

            .ban-header p {
                font-size: 16px;
                opacity: 0.9;
            }

            .ban-body {
                padding: 40px 30px;
            }

            .ban-info {
                background: #fef2f2;
                border: 2px solid #fecaca;
                border-radius: 12px;
                padding: 20px;
                margin-bottom: 25px;
            }

            .ban-info-item {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 12px 0;
                border-bottom: 1px solid #fecaca;
            }

            .ban-info-item:last-child {
                border-bottom: none;
            }

            .ban-info-item i {
                color: #ef4444;
                font-size: 18px;
                width: 24px;
                text-align: center;
            }

            .ban-info-label {
                font-size: 14px;
                color: #991b1b;
                font-weight: 600;
            }

            .ban-info-value {
                font-size: 15px;
                color: #7f1d1d;
                font-weight: 700;
                margin-left: auto;
            }

            .ban-message {
                background: #f9fafb;
                border-radius: 12px;
                padding: 20px;
                margin-bottom: 25px;
            }

            .ban-message h3 {
                font-size: 16px;
                font-weight: 700;
                color: #1f2937;
                margin-bottom: 12px;
                display: flex;
                align-items: center;
                gap: 8px;
            }

            .ban-message h3 i {
                color: #ef4444;
            }

            .ban-message p {
                font-size: 14px;
                color: #6b7280;
                line-height: 1.6;
            }

            .ban-actions {
                display: flex;
                gap: 12px;
            }

            .btn {
                flex: 1;
                padding: 14px 20px;
                border-radius: 10px;
                font-weight: 600;
                font-size: 15px;
                text-decoration: none;
                text-align: center;
                transition: all 0.3s ease;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
            }

            .btn-primary {
                background: #1877f2;
                color: white;
                box-shadow: 0 2px 4px rgba(24, 119, 242, 0.2);
            }

            .btn-primary:hover {
                background: #166fe5;
            }

            .btn-primary:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
            }

            .btn-secondary {
                background: #f3f4f6;
                color: #374151;
                border: 2px solid #e5e7eb;
            }

            .btn-secondary:hover {
                background: #e5e7eb;
            }

            @media (max-width: 600px) {
                .ban-header {
                    padding: 30px 20px;
                }

                .ban-header h1 {
                    font-size: 24px;
                }

                .ban-body {
                    padding: 30px 20px;
                }

                .ban-actions {
                    flex-direction: column;
                }
            }
        </style>
    </head>
    <body>
        <div class="ban-container">
            <div class="ban-header">
                <div class="ban-icon">
                    <i class="fas fa-ban"></i>
                </div>
                <h1>Account Suspended</h1>
                <p>Your access has been temporarily restricted</p>
            </div>

            <div class="ban-body">
                <div class="ban-info">
                    <div class="ban-info-item">
                        <i class="fas fa-user"></i>
                        <span class="ban-info-label">Account</span>
                        <span class="ban-info-value"><?= htmlspecialchars($userData['name']) ?></span>
                    </div>
                    <div class="ban-info-item">
                        <i class="fas fa-clock"></i>
                        <span class="ban-info-label">Status</span>
                        <span class="ban-info-value">
                            <?php if($isPermanent): ?>
                                Permanently Banned
                            <?php else: ?>
                                Suspended
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php if(!$isPermanent): ?>
                    <div class="ban-info-item">
                        <i class="fas fa-calendar-alt"></i>
                        <span class="ban-info-label">Expires</span>
                        <span class="ban-info-value"><?= date('M d, Y H:i', strtotime($banExpires)) ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="ban-message">
                    <h3>
                        <i class="fas fa-info-circle"></i>
                        Why was I suspended?
                    </h3>
                    <p>
                        Your account has been suspended due to violations of our community guidelines or terms of service. 
                        This may include inappropriate content, harassment, spam, or other prohibited activities.
                    </p>
                </div>

                <div class="ban-actions">
                    <a href="/create_ticket.php" class="btn btn-primary">
                        <i class="fas fa-headset"></i>
                        Contact Support
                    </a>
                    <a href="/backend/logout.php" class="btn btn-secondary">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

 $userName = htmlspecialchars($userData['name']);
 $userImage = $userData['profile_image'] ? "/uploads/" . htmlspecialchars($userData['profile_image']) : "/assets/zuckuser.png";
 $userCoins = $userData['coins'];
 $userRole = $userData['role'] ?? 'user';

 $notifStmt = $conn->prepare("SELECT COUNT(*) as total FROM notifications WHERE user_id = ? AND is_read = 0");
 $notifStmt->bind_param("i", $user_id);
 $notifStmt->execute();
 $unread = $notifStmt->get_result()->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>ZuckBook</title>
<script src="https://cdn.socket.io/4.5.4/socket.io.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="/assets/dark-mode.css">
<script src="/assets/avatar-placeholder.js"></script>
<script src="/assets/online-status.js"></script>
<script src="/assets/dark-mode.js"></script>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f0f2f5; color: #050505; }

/* Header - Facebook Style */
.header { background: white; border-bottom: 1px solid #e5e7eb; padding: 0 16px; position: sticky; top: 0; z-index: 100; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.header-content { max-width: 1400px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; height: 56px; }

.header-left { display: flex; align-items: center; gap: 8px; }
.logo { font-size: 28px; font-weight: bold; color: #1877f2; cursor: pointer; }
.search-box { position: relative; }
.search-box input { width: 240px; padding: 10px 16px 10px 40px; border: none; border-radius: 50px; background: #f0f2f5; font-size: 15px; transition: 0.2s; }
.search-box input:focus { outline: none; background: #e4e6eb; width: 280px; }
.search-box i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #65676b; }

.search-results { display: none; position: absolute; top: 100%; left: 0; width: 340px; background: white; border-radius: 8px; box-shadow: 0 4px 16px rgba(0,0,0,0.2); z-index: 1000; max-height: 450px; overflow-y: auto; margin-top: 8px; }
.search-results.show { display: block; }
.search-result-item { padding: 12px 16px; display: flex; align-items: center; gap: 12px; cursor: pointer; border-bottom: 1px solid #f0f2f5; transition: all 0.2s ease; }
.search-result-item:hover { background: #f0f2f5; }
.search-result-item:last-child { border-bottom: none; }
.search-result-avatar { width: 48px; height: 48px; border-radius: 50%; object-fit: cover; border: 2px solid #e4e6eb; background: #f0f2f5; flex-shrink: 0; }
.search-result-info { flex: 1; min-width: 0; }
.search-result-name { font-weight: 600; font-size: 15px; display: flex; align-items: center; gap: 6px; color: #050505; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.search-result-btn { background: #1877f2; color: white; border: none; padding: 8px 20px; border-radius: 6px; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.2s ease; flex-shrink: 0; }
.search-result-btn:hover { background: #166fe5; transform: scale(1.05); }
.search-result-btn:disabled { background: #65676b; cursor: not-allowed; transform: scale(1); }
.search-empty { padding: 24px; text-align: center; color: #65676b; font-size: 14px; }
.search-result-btn { background: #1877f2; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: 600; transition: 0.2s; }
.search-result-btn:hover { background: #0a66c2; }
.search-result-btn:disabled { background: #65676b; cursor: not-allowed; }
.search-empty { padding: 16px; text-align: center; color: #65676b; font-size: 14px; }

.header-center { display: flex; gap: 4px; justify-content: center; flex: 1; }
.nav-item { 
    width: 112px; 
    height: 48px; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    border-radius: 8px; 
    cursor: pointer; 
    transition: all 0.2s ease; 
    color: #65676b; 
    text-decoration: none; 
    border-bottom: 3px solid transparent; 
    position: relative;
}
.nav-item:hover { background: #f0f2f5; }
.nav-item.active { 
    color: #1877f2; 
    border-bottom-color: #1877f2; 
}
.nav-item i { 
    font-size: 24px; 
    transition: transform 0.2s ease;
}
.nav-item:active i { 
    transform: scale(0.9); 
}

.header-right { display: flex; gap: 8px; align-items: center; }
.header-icon { 
    width: 40px; 
    height: 40px; 
    border-radius: 50%; 
    background: #e4e6eb; 
    border: none; 
    cursor: pointer; 
    font-size: 18px; 
    color: #050505; 
    transition: all 0.2s ease; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    position: relative; 
}
.header-icon:hover { 
    background: #d8dadf; 
    transform: scale(1.05);
}
.header-icon:active { 
    transform: scale(0.95); 
}
.header-icon .badge { 
    position: absolute; 
    top: -4px; 
    right: -4px; 
    background: #e41e3a; 
    color: white; 
    font-size: 11px; 
    font-weight: 700; 
    padding: 2px 6px; 
    border-radius: 10px; 
    min-width: 18px; 
    text-align: center; 
}
.user-avatar { 
    width: 40px; 
    height: 40px; 
    border-radius: 50%; 
    object-fit: cover; 
    cursor: pointer; 
    border: 2px solid transparent; 
    transition: all 0.2s ease; 
}
.user-avatar:hover { 
    border-color: #1877f2; 
    transform: scale(1.05);
}
.user-avatar:active { 
    transform: scale(0.95); 
}

/* Main Layout */
.main-container { max-width: 940px; margin: 0 auto; padding: 16px; }

/* Create Post */
.create-post { background: white; border-radius: 8px; padding: 12px 16px; box-shadow: 0 1px 2px rgba(0,0,0,0.1); margin-bottom: 16px; }
.create-post-header { display: flex; gap: 8px; }
.create-post-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
.create-post-input { flex: 1; background: #f0f2f5; border: none; border-radius: 50px; padding: 10px 16px; cursor: pointer; font-size: 15px; color: #65676b; text-align: left; }
.create-post-input:hover { background: #e4e6eb; }
.create-post-divider { height: 1px; background: #e5e7eb; margin: 12px 0 8px; }
.create-post-actions { display: flex; gap: 8px; }
.post-action-btn { flex: 1; background: none; border: none; padding: 10px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; color: #65676b; font-size: 15px; font-weight: 600; border-radius: 8px; transition: 0.2s; }
.post-action-btn:hover { background: #f0f2f5; }
.post-action-btn i { font-size: 20px; }
.post-action-btn.photo i { color: #45bd62; }
.post-action-btn.feeling i { color: #f7b928; }
.post-action-btn.checkin i { color: #f5533d; }

/* Posts Feed */
.feed { display: flex; flex-direction: column; gap: 16px; }

.post { background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,0.1); }
.post-header { padding: 12px 16px; display: flex; align-items: center; gap: 8px; }
.post-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
.post-user-details { flex: 1; }
.post-user-name { font-weight: 600; font-size: 15px; display: flex; align-items: center; gap: 4px; }
.post-user-name .verified { 
    width: 16px; 
    height: 16px; 
    border-radius: 50%; 
    display: inline-flex; 
    align-items: center; 
    justify-content: center; 
    color: white; 
    font-size: 9px; 
    margin-left: 4px;
    box-shadow: 0 0 10px currentColor, 0 0 20px currentColor, inset 0 0 8px rgba(255,255,255,0.3);
    animation: glow 2s ease-in-out infinite;
}
@keyframes glow {
    0%, 100% { box-shadow: 0 0 10px currentColor, 0 0 20px currentColor, inset 0 0 8px rgba(255,255,255,0.3); }
    50% { box-shadow: 0 0 15px currentColor, 0 0 30px currentColor, inset 0 0 12px rgba(255,255,255,0.5); }
}
.post-user-name .verified.basic { background: #1877f2; color: #1877f2; }
.post-user-name .verified.premium { background: #9333ea; color: #9333ea; }
.post-user-name .verified.elite { background: #dc2626; color: #dc2626; }
.post-time { font-size: 13px; color: #65676b; cursor: pointer; }
.post-time:hover { text-decoration: underline; }
.post-menu-btn { width: 36px; height: 36px; border-radius: 50%; border: none; background: none; cursor: pointer; font-size: 18px; color: #65676b; display: flex; align-items: center; justify-content: center; transition: 0.2s; }
.post-menu-btn:hover { background: #f0f2f5; }
.post-content { padding: 0 16px 12px; font-size: 15px; line-height: 1.5; white-space: pre-wrap; }
.post-image { width: 100%; max-height: 600px; object-fit: contain; background: #f0f2f5; }
.post-stats { padding: 10px 16px; display: flex; justify-content: space-between; font-size: 15px; color: #65676b; border-top: 1px solid #e5e7eb; margin-top: 12px; }
.post-stats-left { display: flex; gap: 8px; }
.reaction-icons { display: inline-flex; align-items: center; }
.reaction-icons span { width: 20px; height: 20px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; margin-left: -4px; border: 2px solid white; }
.reaction-icons .like { background: #1877f2; }
.reaction-icons .heart { background: #f33e58; }
.post-actions { display: flex; border-top: 1px solid #e5e7eb; padding: 4px 16px; }
.post-action { flex: 1; background: none; border: none; padding: 10px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; color: #65676b; font-size: 15px; font-weight: 600; transition: 0.2s; border-radius: 4px; }
.post-action:hover { background: #f0f2f5; }
.post-action.liked { color: #1877f2; }

/* Comments Section */
.comments-section { padding: 12px 16px; border-top: 1px solid #e5e7eb; background: #f8f9fa; }
.comments-section.hidden { display: none; }
.comment { display: flex; gap: 12px; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid #e5e7eb; }
.comment:last-child { border-bottom: none; margin-bottom: 0; }
.comment-avatar { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }
.comment-content { flex: 1; }
.comment-header { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; }
.comment-author { font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 4px; }
.comment-verify { color: #1877f2; font-size: 10px; }
.comment-time { font-size: 12px; color: #65676b; }
.comment-text { font-size: 14px; line-height: 1.4; color: #050505; }
.comment-actions { display: flex; gap: 12px; margin-top: 6px; font-size: 12px; }
.comment-action { background: none; border: none; color: #65676b; cursor: pointer; padding: 0; }
.comment-action:hover { color: #1877f2; }
.comment-icons { display: flex; gap: 8px; margin-top: 8px; }
.comment-icon-btn { background: none; border: none; color: #65676b; cursor: pointer; font-size: 14px; padding: 4px 8px; }
.comment-icon-btn:hover { color: #1877f2; }

.add-comment { display: flex; gap: 8px; padding-top: 12px; border-top: 1px solid #e5e7eb; }
.add-comment-avatar { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }
.add-comment-input { flex: 1; background: #f0f2f5; border: none; border-radius: 20px; padding: 8px 16px; font-size: 14px; }
.add-comment-input:focus { outline: none; background: #e4e6eb; }

/* Reply Styles */
.reply-box { padding: 8px 0; }
.reply-box.hidden { display: none; }
.reply-input { width: 100%; background: #f0f2f5; border: none; border-radius: 20px; padding: 8px 16px; font-size: 14px; }
.reply-input:focus { outline: none; background: #e4e6eb; }

.replies-list { margin-top: 8px; padding-left: 20px; border-left: 2px solid #e5e7eb; }
.reply { display: flex; gap: 8px; margin-bottom: 8px; }
.reply-avatar { width: 28px; height: 28px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }
.reply-content { flex: 1; }
.reply-header { display: flex; align-items: center; gap: 6px; margin-bottom: 2px; }
.reply-author { font-weight: 600; font-size: 13px; display: flex; align-items: center; gap: 3px; }
.reply-time { font-size: 11px; color: #65676b; }
.reply-text { font-size: 13px; line-height: 1.3; color: #050505; }
.reply-actions { display: flex; gap: 8px; margin-top: 4px; font-size: 11px; }
.reply-action { background: none; border: none; color: #65676b; cursor: pointer; padding: 0; }
.reply-action:hover { color: #1877f2; }
.reply-action.liked { color: #1877f2; }

/* Heart Button Styles */
.heart-btn { transition: 0.2s; }
.heart-btn.liked i { color: #f33e58; }

/* Loading */
.loading { text-align: center; padding: 20px; color: #65676b; }

/* Modal */
.modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.8); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(4px); }
.modal.show { display: flex; }
.modal-content { background: white; border-radius: 8px; padding: 20px; width: 90%; max-width: 500px; box-shadow: 0 8px 32px rgba(0,0,0,0.2); }
.modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #e5e7eb; }
.modal-header h2 { font-size: 20px; font-weight: 700; }
.modal-close { width: 36px; height: 36px; border-radius: 50%; background: #e4e6eb; border: none; font-size: 20px; cursor: pointer; color: #65676b; display: flex; align-items: center; justify-content: center; transition: 0.2s; }
.modal-close:hover { background: #d8dadf; }
.modal-textarea { width: 100%; height: 120px; padding: 12px; border: none; border-radius: 8px; font-family: inherit; font-size: 15px; resize: none; background: #f0f2f5; }
.modal-textarea:focus { outline: none; background: #e4e6eb; }
.modal-actions { display: flex; gap: 8px; margin-top: 16px; }
.modal-actions button { flex: 1; padding: 12px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 15px; }
.modal-actions .cancel { border: none; background: #e4e6eb; color: #050505; }
.modal-actions .submit { border: none; background: #1877f2; color: white; }
.modal-actions .submit:hover { background: #166fe5; }

/* Top Bar Modals */
.top-modal { display: none; position: fixed; top: 60px; right: 16px; width: 360px; background: white; border-radius: 8px; box-shadow: 0 12px 28px rgba(0,0,0,0.2); z-index: 999; max-height: 500px; overflow-y: auto; }
.top-modal.show { display: block; }
.modal-header-bar { 
    padding: 16px; 
    border-bottom: 1px solid #e5e7eb; 
    font-weight: 700; 
    font-size: 20px; 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    color: #050505;
}
.modal-close-btn { 
    width: 36px; 
    height: 36px; 
    border-radius: 50%; 
    background: #e4e6eb; 
    border: none; 
    font-size: 20px; 
    cursor: pointer; 
    color: #65676b; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    transition: all 0.2s ease;
}
.modal-close-btn:hover {
    background: #d8dadf;
    transform: rotate(90deg);
}
.modal-close-btn:active {
    transform: rotate(90deg) scale(0.9);
}
.modal-item { 
    padding: 10px 12px; 
    margin: 4px 8px; 
    border-radius: 10px; 
    cursor: pointer; 
    transition: all 0.2s ease; 
    display: flex; 
    align-items: center; 
    gap: 12px; 
}
.modal-item:hover { 
    background: #f0f2f5; 
    transform: translateX(4px);
}
.modal-item:active {
    background: #e4e6eb;
    transform: translateX(2px) scale(0.98);
}
.modal-item-icon { 
    width: 56px; 
    height: 56px; 
    border-radius: 50%; 
    background: linear-gradient(135deg, #1877f2, #4267B2); 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    color: white; 
    font-size: 24px; 
    flex-shrink: 0;
    box-shadow: 0 2px 8px rgba(24, 119, 242, 0.3);
}
.modal-item-content { 
    flex: 1; 
    min-width: 0;
}
.modal-item-title { 
    font-weight: 600; 
    font-size: 15px; 
    color: #050505;
    margin-bottom: 2px;
}
.modal-item-desc { 
    font-size: 13px; 
    color: #65676b; 
    line-height: 1.3;
}
.modal-empty { 
    padding: 32px 24px; 
    text-align: center; 
    color: #65676b; 
    font-size: 14px;
}

/* Notification Detail Modal */
.notification-detail-modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
.notification-detail-modal.show { display: flex; }
.notification-detail-content { background: white; border-radius: 12px; width: 90%; max-width: 500px; padding: 24px; position: relative; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
.notification-detail-close { position: absolute; top: 16px; right: 16px; background: none; border: none; font-size: 24px; cursor: pointer; color: #65676b; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; }
.notification-detail-close:hover { color: #050505; }
.notification-detail-body { text-align: center; }
.notification-detail-icon { font-size: 48px; margin-bottom: 16px; }
.notification-detail-title { font-size: 20px; font-weight: 700; margin-bottom: 12px; }
.notification-detail-message { font-size: 16px; color: #65676b; line-height: 1.5; margin-bottom: 20px; }
.notification-detail-time { font-size: 13px; color: #999; margin-bottom: 20px; }
.notification-detail-actions { display: flex; gap: 12px; }
.notification-detail-actions button { flex: 1; padding: 10px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: 0.2s; }
.notification-detail-actions .btn-primary { background: #1877f2; color: white; }
.notification-detail-actions .btn-primary:hover { background: #166fe5; }
.notification-detail-actions .btn-secondary { background: #e4e6eb; color: #050505; }
.notification-detail-actions .btn-secondary:hover { background: #d8dadf; }

/* User Menu Dropdown */
.user-menu { 
    display: none; 
    position: fixed; 
    top: 60px; 
    right: 16px; 
    width: 360px; 
    background: white; 
    border-radius: 12px; 
    box-shadow: 0 8px 24px rgba(0,0,0,0.15); 
    z-index: 999; 
    overflow: hidden;
    border: 1px solid #e4e6eb;
}
.user-menu.show { display: block; }
.user-menu-header { 
    padding: 16px; 
    display: flex; 
    gap: 12px; 
    align-items: center; 
    background: linear-gradient(135deg, #f0f2f5 0%, #e4e6eb 100%);
    border-bottom: 1px solid #e4e6eb;
}
.user-menu-header img { 
    width: 60px; 
    height: 60px; 
    border-radius: 50%; 
    object-fit: cover; 
    border: 3px solid white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.user-menu-header-info h3 { 
    font-size: 17px; 
    font-weight: 700; 
    display: flex; 
    align-items: center; 
    gap: 6px; 
    color: #050505;
}
.user-menu-header-info p { 
    font-size: 13px; 
    color: #65676b; 
    margin-top: 2px;
}
.user-menu-item { 
    display: flex; 
    align-items: center; 
    gap: 12px; 
    padding: 12px 16px; 
    cursor: pointer; 
    transition: all 0.2s ease; 
    text-decoration: none; 
    color: inherit;
    position: relative;
}
.user-menu-item:hover { 
    background: #f0f2f5; 
}
.user-menu-item:active {
    background: #e4e6eb;
    transform: scale(0.98);
}
.user-menu-item i { 
    width: 36px; 
    height: 36px; 
    border-radius: 50%; 
    background: #e4e6eb; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    font-size: 18px;
    color: #1877f2;
    transition: all 0.2s ease;
}
.user-menu-item:hover i {
    background: #d8dadf;
    transform: scale(1.05);
}
.user-menu-item span { 
    flex: 1; 
    font-size: 15px; 
    font-weight: 500;
    color: #050505;
}
.user-menu-item .chevron { 
    color: #65676b; 
    font-size: 16px;
    transition: transform 0.2s ease;
}
.user-menu-item:hover .chevron {
    transform: translateX(4px);
}
.user-menu-divider { 
    height: 1px; 
    background: #e5e7eb; 
    margin: 8px 16px; 
}
.user-menu-item.logout {
    color: #e41e3a;
}
.user-menu-item.logout i {
    background: rgba(228, 30, 58, 0.1);
    color: #e41e3a;
}
.user-menu-item.logout:hover i {
    background: rgba(228, 30, 58, 0.15);
}

/* Coins Display */
.coins-display { display: flex; align-items: center; gap: 4px; background: linear-gradient(135deg, #ffd700, #ffaa00); padding: 4px 12px; border-radius: 20px; color: white; font-weight: 600; font-size: 13px; cursor: pointer; transition: 0.2s; }
.coins-display:hover { transform: scale(1.05); }
.coins-display i { font-size: 14px; }

/* Notification Badge */
.notification-badge { position: absolute; top: -8px; right: -8px; background: #f02849; color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; }

/* Responsive - Tablet and Small Desktop */
@media (max-width: 1100px) {
    .nav-item { width: 90px; }
}

/* Responsive - Tablet */
@media (max-width: 900px) {
    .header-content { padding: 0 8px; }
    .search-box input { width: 40px; padding: 10px; background: none; }
    .search-box input:focus { width: 200px; background: #f0f2f5; }
    .search-box i { left: 10px; }
    .nav-item { width: 60px; }
    .coins-display span { display: none; }
}

/* Responsive - Mobile */
@media (max-width: 600px) {
    .header { padding: 0 8px; }
    .header-content { height: 50px; }
    .logo { font-size: 24px; }
    .search-box { display: none; }
    
    .header-center { 
        display: flex !important; 
        gap: 2px; 
        flex: 1;
        justify-content: center;
    }
    
    .nav-item { 
        width: 50px !important; 
        height: 44px;
        border-radius: 8px;
        background: transparent;
    }
    
    .nav-item i { 
        font-size: 22px; 
    }
    
    .nav-item.active { 
        border-bottom-width: 3px; 
        background: rgba(24, 119, 242, 0.05);
    }
    
    .nav-item:active {
        background: #f0f2f5;
    }
    
    .header-right { gap: 6px; }
    
    .header-icon { 
        width: 38px; 
        height: 38px; 
        font-size: 17px;
        background: #e4e6eb;
    }
    
    .header-icon:active {
        background: #d8dadf;
    }
    
    .user-avatar { 
        width: 38px; 
        height: 38px; 
    }
    .coins-display { display: none; }
    
    .top-modal { 
        right: 8px; 
        left: 8px; 
        width: auto; 
    }
    
    .user-menu { 
        right: 8px; 
        left: 8px; 
        width: auto;
        max-width: 100%;
        margin: 0;
        border-radius: 12px;
        top: 56px;
    }
    
    .user-menu-header {
        padding: 16px;
    }
    
    .user-menu-header img {
        width: 60px;
        height: 60px;
        border-width: 3px;
    }
    
    .user-menu-header-info h3 {
        font-size: 17px;
    }
    
    .user-menu-header-info p {
        font-size: 14px;
    }
    
    .user-menu-item {
        padding: 16px;
        min-height: 64px;
        border-bottom: 1px solid #e4e6eb;
    }
    
    .user-menu-item:last-child {
        border-bottom: none;
    }
    
    .user-menu-item i {
        width: 44px;
        height: 44px;
        font-size: 20px;
    }
    
    .user-menu-item span {
        font-size: 16px;
        font-weight: 500;
    }
    
    .user-menu-item .chevron {
        font-size: 18px;
    }
    
    .user-menu-divider {
        margin: 0;
        height: 1px;
    }
    
    .main-container { 
        padding: 8px; 
        margin-top: 50px;
    }
    
    .create-post { 
        padding: 12px; 
        border-radius: 8px;
        margin-bottom: 8px;
    }
    
    .create-post-input { 
        font-size: 15px; 
        padding: 12px;
    }
    
    .create-post-actions { 
        margin-top: 12px;
        flex-wrap: wrap;
        gap: 8px;
    }
    
    .create-post-btn { 
        padding: 10px 16px;
        font-size: 14px;
    }
    
    .post { 
        margin-bottom: 8px;
        border-radius: 8px;
    }
    
    .post-header { 
        padding: 12px; 
    }
    
    .post-avatar { 
        width: 40px; 
        height: 40px; 
    }
    
    .post-author { 
        font-size: 15px; 
    }
    
    .post-time { 
        font-size: 12px; 
    }
    
    .post-content { 
        padding: 0 12px 12px; 
        font-size: 15px;
        line-height: 1.5;
    }
    
    .post-image { 
        margin-top: 8px;
        border-radius: 0;
    }
    
    .post-stats { 
        padding: 8px 12px; 
        font-size: 13px;
    }
    
    .post-actions { 
        padding: 4px 8px; 
    }
    
    .post-action { 
        padding: 10px 8px;
        font-size: 14px;
    }
    
    .post-action i { 
        font-size: 18px; 
    }
    
    .modal-content { 
        padding: 16px; 
        width: 95%;
        max-width: 95%;
    }
    
    .modal-header { 
        padding: 12px 0;
        margin-bottom: 16px;
    }
    
    .modal-header h2 { 
        font-size: 18px; 
    }
    
    .modal-close { 
        width: 32px; 
        height: 32px; 
        font-size: 20px;
    }
    
    .notification-detail-content { 
        padding: 16px; 
    }
    
    /* Comments section */
    .comments-section { 
        padding: 12px; 
    }
    
    .comment { 
        margin-bottom: 12px; 
    }
    
    .comment-avatar { 
        width: 32px; 
        height: 32px; 
    }
    
    .comment-content { 
        padding: 8px 12px;
        font-size: 14px;
    }
    
    .comment-author { 
        font-size: 14px; 
    }
    
    .comment-text { 
        font-size: 14px;
        line-height: 1.4;
    }
    
    /* Add comment form */
    .add-comment { 
        padding: 12px; 
    }
    
    .comment-input { 
        padding: 10px 12px;
        font-size: 14px;
    }
    
    .comment-submit { 
        padding: 8px 16px;
        font-size: 14px;
    }
    
    /* Reactions */
    .reactions-icon { 
        font-size: 16px; 
    }
    
    /* Loading state */
    .loading-posts { 
        padding: 20px;
        font-size: 14px;
    }
    
    /* Empty state */
    .no-posts { 
        padding: 30px 20px;
        font-size: 14px;
    }
    
    /* Notification badge */
    .notification-badge { 
        width: 20px; 
        height: 20px; 
        font-size: 11px;
        top: -6px;
        right: -6px;
    }
}

/* Responsive - Very Small Mobile */
@media (max-width: 360px) {
    .logo { font-size: 20px; }
    
    .nav-item { 
        width: 46px !important; 
        height: 42px;
        border-radius: 6px;
    }
    
    .nav-item i { 
        font-size: 20px; 
    }
    
    .header-icon { 
        width: 34px; 
        height: 34px; 
        font-size: 15px; 
    }
    
    .user-avatar { 
        width: 34px; 
        height: 34px; 
    }
    
    .main-container { 
        padding: 6px; 
    }
    
    .create-post { 
        padding: 10px; 
    }
    
    .create-post-input { 
        font-size: 14px; 
        padding: 10px;
    }
    
    .post { 
        margin-bottom: 6px; 
    }
    
    .post-header { 
        padding: 10px; 
    }
    
    .post-avatar { 
        width: 36px; 
        height: 36px; 
    }
    
    .post-author { 
        font-size: 14px; 
    }
    
    .post-time { 
        font-size: 11px; 
    }
    
    .post-content { 
        padding: 0 10px 10px; 
        font-size: 14px;
    }
    
    .post-stats { 
        padding: 6px 10px; 
        font-size: 12px;
    }
    
    .post-actions { 
        padding: 2px 6px; 
    }
    
    .post-action { 
        padding: 8px 6px;
        font-size: 13px;
    }
    
    .post-action i { 
        font-size: 16px; 
    }
    
    .modal-content { 
        padding: 12px; 
    }
    
    .modal-header h2 { 
        font-size: 16px; 
    }
    
    .comment-avatar { 
        width: 28px; 
        height: 28px; 
    }
    
    .comment-content { 
        padding: 6px 10px;
        font-size: 13px;
    }
    
    .notification-badge { 
        width: 18px; 
        height: 18px; 
        font-size: 10px;
    }
}

/* Touch-friendly on mobile */
@media (hover: none) and (pointer: coarse) {
    .nav-item:hover { background: transparent; }
    .nav-item:active { background: #f0f2f5; }
    .post-action:hover { background: transparent; }
    .post-action:active { background: #f0f2f5; }
    .modal-item:hover { background: transparent; }
    .modal-item:active { background: #f0f2f5; }
    .user-menu-item:hover { background: transparent; }
    .user-menu-item:active { background: #f0f2f5; }
}
</style>
</head>
<body>

<!-- Header -->
<div class="header">
    <div class="header-content">
        <div class="header-left">
            <div class="logo" onclick="window.location.href='/home.php'">Z</div>
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search ZuckBook" oninput="searchUsers(this.value)">
                <div id="searchResults" class="search-results"></div>
            </div>
        </div>
        
        <div class="header-center">
            <a href="/home.php" class="nav-item active" title="Home"><i class="fas fa-home"></i></a>
            <a href="/friends.php" class="nav-item" title="Friends"><i class="fas fa-user-friends"></i></a>
            <a href="/videos.php" class="nav-item" title="Watch"><i class="fas fa-play-circle"></i></a>
            <a href="/groups.php" class="nav-item" title="Groups"><i class="fas fa-users"></i></a>
            <a href="/profile.php?id=<?= $user_id ?>" class="nav-item" title="Profile"><i class="fas fa-user"></i></a>
        </div>
        
        <div class="header-right">
            <button class="header-icon" onclick="window.location.href='/friends.php'" title="Friends"><i class="fas fa-user-friends"></i></button>
            <div class="coins-display" onclick="window.location.href='/coins.php'" title="Coins">
                <i class="fas fa-coins"></i>
                <span><?= formatCoins($userCoins) ?></span>
            </div>
            <button class="header-icon" onclick="window.location.href='/groups.php'" title="Groups"><i class="fas fa-users"></i></button>
            <button class="header-icon" onclick="openMessengerModal()" title="Messenger"><i class="fab fa-facebook-messenger"></i></button>
            <button class="header-icon" onclick="openNotificationsModal()" title="Notifications">
                <i class="fas fa-bell"></i>
                <?php if($unread > 0): ?>
                    <span class="badge"><?= $unread > 9 ? '9+' : $unread ?></span>
                <?php endif; ?>
            </button>
            <img src="<?= $userImage ?>" 
                 class="user-avatar" 
                 alt="<?= $userName ?>"
                 data-name="<?= $userName ?>"
                 onclick="toggleUserMenu()">
        </div>
    </div>
</div>

<!-- Main Container -->
<div class="main-container">
    <div class="feed">
        <!-- Create Post -->
        <div class="create-post">
            <div class="create-post-header">
                <img src="<?= $userImage ?>" 
                     class="create-post-avatar" 
                     alt="<?= $userName ?>"
                     data-name="<?= $userName ?>"
                     onerror="this.onerror=null; this.src='/assets/zuckuser.png';">
                <input type="text" class="create-post-input" placeholder="What's on your mind, <?= explode(' ', $userName)[0] ?>?" onclick="openPostModal()" readonly>
            </div>
            <div class="create-post-divider"></div>
            <div class="create-post-actions">
                <button class="post-action-btn photo" onclick="openPhotoVideoModal()"><i class="fas fa-images"></i> Photo</button>
                <button class="post-action-btn feeling" onclick="openPostModal()"><i class="fas fa-smile"></i> Feeling</button>
                <button class="post-action-btn checkin" onclick="openPostModal()"><i class="fas fa-map-marker-alt"></i> Check in</button>
            </div>
        </div>
        
        <div id="posts"></div>
        <div id="loading" class="loading">Loading posts...</div>
    </div>
</div>

<!-- User Menu Dropdown -->
<div id="userMenu" class="user-menu">
    <div class="user-menu-header" onclick="window.location.href='/profile.php?id=<?= $user_id ?>'">
        <img src="<?= $userImage ?>">
        <div class="user-menu-header-info">
            <h3><?= $userName ?> <?php if($userData['is_verified'] == 1): ?><i class="fas fa-check-circle" style="color: #1877f2; font-size: 16px; margin-left: 4px;"></i><?php endif; ?></h3>
            <p>See your profile</p>
        </div>
    </div>
    <div class="user-menu-divider"></div>
    <a href="/settings.php" class="user-menu-item">
        <i class="fas fa-cog"></i>
        <span>Settings & Privacy</span>
        <i class="fas fa-chevron-right chevron"></i>
    </a>
    <a href="/friend_requests.php" class="user-menu-item">
        <i class="fas fa-user-friends"></i>
        <span>Friend Requests</span>
        <i class="fas fa-chevron-right chevron"></i>
    </a>
    <a href="/coins.php" class="user-menu-item">
        <i class="fas fa-coins"></i>
        <span>Coins Center</span>
        <i class="fas fa-chevron-right chevron"></i>
    </a>
    <a href="/subscriptions.php" class="user-menu-item">
        <i class="fas fa-crown"></i>
        <span>Subscriptions</span>
        <i class="fas fa-chevron-right chevron"></i>
    </a>
    <?php 
    // Check if user has active subscription
    $checkSub = $conn->query("SELECT subscription_tier, subscription_expires FROM users WHERE id = {$_SESSION['user_id']}");
    $subData = $checkSub->fetch_assoc();
    $hasActiveSub = ($subData['subscription_expires'] && strtotime($subData['subscription_expires']) > time() && $subData['subscription_tier'] !== 'free');
    if($hasActiveSub): 
    ?>
    <a href="/my_subscription.php" class="user-menu-item">
        <i class="fas fa-id-card"></i>
        <span>My Subscription</span>
        <i class="fas fa-chevron-right chevron"></i>
    </a>
    <?php endif; ?>
    <?php if(in_array($userRole, ['cofounder', 'mod', 'sup'])): ?>
    <a href="/admin_pin_login.php" class="user-menu-item">
        <i class="fas fa-shield-alt"></i>
        <span>Admin Dashboard</span>
        <i class="fas fa-chevron-right chevron"></i>
    </a>
    <?php endif; ?>
    <a href="/notifications.php" class="user-menu-item">
        <i class="fas fa-bell"></i>
        <span>Notifications</span>
        <i class="fas fa-chevron-right chevron"></i>
    </a>
    <a href="/chat.php" class="user-menu-item">
        <i class="fas fa-comments"></i>
        <span>Messages</span>
        <i class="fas fa-chevron-right chevron"></i>
    </a>
    <a href="/create_ticket.php" class="user-menu-item">
        <i class="fas fa-ticket-alt"></i>
        <span>Support Ticket</span>
        <i class="fas fa-chevron-right chevron"></i>
    </a>
    <a href="/forgot_password.php" class="user-menu-item">
        <i class="fas fa-key"></i>
        <span>Recover Account</span>
        <i class="fas fa-chevron-right chevron"></i>
    </a>
    <div class="user-menu-divider"></div>
    <a href="/backend/logout.php" class="user-menu-item">
        <i class="fas fa-sign-out-alt"></i>
        <span>Log Out</span>
    </a>
</div>

<!-- Create Post Modal -->
<div id="postModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Create Post</h2>
            <button class="modal-close" onclick="closePostModal()"><i class="fas fa-times"></i></button>
        </div>
        <div style="display: flex; gap: 12px; margin-bottom: 16px; align-items: center;">
            <img src="<?= $userImage ?>" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
            <div>
                <div style="font-weight: 600; font-size: 15px;"><?= $userName ?></div>
                <select id="postPrivacy" style="font-size: 13px; padding: 4px 12px; border: none; border-radius: 4px; background: #e4e6eb; cursor: pointer;">
                    <option value="public"><i class="fas fa-globe"></i> Public</option>
                    <option value="friends"><i class="fas fa-user-friends"></i> Friends</option>
                    <option value="private"><i class="fas fa-lock"></i> Only Me</option>
                </select>
            </div>
        </div>
        <textarea id="postContent" class="modal-textarea" placeholder="What's on your mind, <?= explode(' ', $userName)[0] ?>?"></textarea>
        <div class="modal-actions">
            <button class="submit" onclick="createPost()">Post</button>
        </div>
    </div>
</div>

<!-- Photo/Video Modal -->
<div id="photoVideoModal" class="modal">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h2>Add Photo or Video</h2>
            <button class="modal-close" onclick="closePhotoVideoModal()"><i class="fas fa-times"></i></button>
        </div>
        <div style="display: flex; flex-direction: column; gap: 12px;">
            <label style="display: flex; align-items: center; gap: 12px; padding: 16px; background: #f0f2f5; border-radius: 8px; cursor: pointer; transition: 0.2s;" onmouseover="this.style.background='#e4e6eb'" onmouseout="this.style.background='#f0f2f5'">
                <i class="fas fa-image" style="font-size: 24px; color: #45bd62;"></i>
                <div>
                    <div style="font-weight: 600; font-size: 15px;">Upload Photo</div>
                    <div style="font-size: 13px; color: #65676b;">JPG, PNG, GIF (Max 5MB)</div>
                </div>
                <input type="file" id="photoInput" accept="image/*" style="display: none;" onchange="handlePhotoSelect(this)">
            </label>
            <label style="display: flex; align-items: center; gap: 12px; padding: 16px; background: #f0f2f5; border-radius: 8px; cursor: pointer; transition: 0.2s;" onmouseover="this.style.background='#e4e6eb'" onmouseout="this.style.background='#f0f2f5'">
                <i class="fas fa-video" style="font-size: 24px; color: #f5533d;"></i>
                <div>
                    <div style="font-weight: 600; font-size: 15px;">Upload Video</div>
                    <div style="font-size: 13px; color: #65676b;">MP4, WebM (Max 50MB)</div>
                </div>
                <input type="file" id="videoInput" accept="video/*" style="display: none;" onchange="handleVideoSelect(this)">
            </label>
        </div>
    </div>
</div>

<script>
const userId = <?= $user_id ?>;
const socketUrl = window.location.hostname === 'localhost' ? 'http://localhost:3000' : window.location.origin;
const socket = io(socketUrl, { reconnection: true, reconnectionDelay: 1000, reconnectionDelayMax: 5000, reconnectionAttempts: 5 });

socket.on('connect', () => {
    console.log('Connected');
    socket.emit('join_user', userId);
});

socket.on('reaction_update', (data) => {
    let counter = document.getElementById("likes-"+data.post_id);
    if(counter) counter.innerText = data.total + " reactions";
});

function openPostModal() { document.getElementById("postModal").classList.add("show"); }
function closePostModal() { document.getElementById("postModal").classList.remove("show"); document.getElementById("postContent").value = ""; }

function openPhotoVideoModal() { document.getElementById("photoVideoModal").classList.add("show"); }
function closePhotoVideoModal() { document.getElementById("photoVideoModal").classList.remove("show"); }

function handlePhotoSelect(input) {
    if (input.files.length === 0) return;
    const file = input.files[0];
    
    if (file.size > 5 * 1024 * 1024) {
        alert("Photo size must be less than 5MB");
        return;
    }
    
    uploadMedia(file, 'photo');
}

function handleVideoSelect(input) {
    if (input.files.length === 0) return;
    const file = input.files[0];
    
    if (file.size > 50 * 1024 * 1024) {
        alert("Video size must be less than 50MB");
        return;
    }
    
    uploadMedia(file, 'video');
}

function uploadMedia(file, type) {
    closePhotoVideoModal();
    
    const formData = new FormData();
    formData.append('file', file);
    formData.append('type', type);
    
    const loadingDiv = document.createElement('div');
    loadingDiv.style.cssText = 'position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border-radius: 8px; box-shadow: 0 8px 32px rgba(0,0,0,0.2); z-index: 2000;';
    loadingDiv.innerHTML = '<div style="text-align: center;"><i class="fas fa-spinner fa-spin" style="font-size: 24px; color: #1877f2;"></i><p style="margin-top: 12px; color: #65676b;">Uploading...</p></div>';
    document.body.appendChild(loadingDiv);
    
    fetch('/backend/upload_media.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        document.body.removeChild(loadingDiv);
        
        if (data.status === 'success') {
            openPostWithMedia(data.filename, type);
        } else {
            alert('Upload failed: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(err => {
        document.body.removeChild(loadingDiv);
        alert('Upload error: ' + err);
    });
}

function openPostWithMedia(filename, type) {
    openPostModal();
    
    const postContent = document.getElementById('postContent');
    const mediaPreview = document.createElement('div');
    mediaPreview.style.cssText = 'margin-top: 12px; position: relative;';
    
    if (type === 'photo') {
        mediaPreview.innerHTML = `
            <img src="/uploads/${filename}" style="width: 100%; max-height: 300px; border-radius: 8px; object-fit: cover;">
            <input type="hidden" id="mediaFile" value="${filename}">
            <input type="hidden" id="mediaType" value="photo">
            <button type="button" onclick="removeMedia()" style="position: absolute; top: 8px; right: 8px; background: rgba(0,0,0,0.6); color: white; border: none; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: 18px;"><i class="fas fa-times"></i></button>
        `;
    } else {
        mediaPreview.innerHTML = `
            <video style="width: 100%; max-height: 300px; border-radius: 8px; background: #000;" controls>
                <source src="/uploads/${filename}" type="video/mp4">
            </video>
            <input type="hidden" id="mediaFile" value="${filename}">
            <input type="hidden" id="mediaType" value="video">
            <button type="button" onclick="removeMedia()" style="position: absolute; top: 8px; right: 8px; background: rgba(0,0,0,0.6); color: white; border: none; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: 18px;"><i class="fas fa-times"></i></button>
        `;
    }
    
    const existingPreview = document.getElementById('mediaPreview');
    if (existingPreview) existingPreview.remove();
    
    mediaPreview.id = 'mediaPreview';
    postContent.parentNode.insertBefore(mediaPreview, postContent.nextSibling);
}

function removeMedia() {
    const preview = document.getElementById('mediaPreview');
    if (preview) preview.remove();
}

function createPost() {
    let content = document.getElementById("postContent").value.trim();
    let privacy = document.getElementById("postPrivacy").value;
    let mediaFile = document.getElementById("mediaFile");
    let mediaType = document.getElementById("mediaType");
    
    if(!content && !mediaFile) { alert("Please write something or add a photo/video!"); return; }

    const formData = new FormData();
    formData.append('content', content);
    formData.append('privacy', privacy);
    
    if (mediaFile) {
        formData.append('media_file', mediaFile.value);
        formData.append('media_type', mediaType.value);
    }

    fetch("/backend/create_post.php", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.status === "posted") {
            closePostModal();
            removeMedia();
            
            // إضافة البوست الجديد في الأعلى مباشرة
            const newPost = createPostElement(data.post);
            const postsContainer = document.getElementById("posts");
            postsContainer.insertAdjacentHTML('afterbegin', newPost);
            
            document.getElementById("postContent").value = "";
        } else {
            alert("Error: " + (data.error || "Unknown error"));
        }
    })
    .catch(err => alert("Error: " + err));
}

function createPostElement(post) {
    const verified = post.is_verified == 1 ? '<i class="fas fa-check-circle" style="color: #1877f2; font-size: 14px; margin-left: 4px;"></i>' : '';
    const postImage = post.image ? `<img src="/uploads/${post.image}" class="post-image">` : '';
    const postVideo = post.video ? `<video class="post-image" controls><source src="/uploads/${post.video}" type="video/mp4"></video>` : '';
    
    return `
        <div class="post" data-post-id="${post.id}">
            <div class="post-header">
                <img src="${post.profile_image}" 
                     class="post-avatar" 
                     alt="${post.name}"
                     data-name="${post.name}"
                     onclick="window.location.href='/profile.php?id=${post.user_id}'" 
                     style="cursor: pointer;">
                <div class="post-user-details">
                    <div class="post-user-name" style="cursor: pointer;" onclick="window.location.href='/profile.php?id=${post.user_id}'">${post.name} ${verified}</div>
                    <div class="post-time">just now · <i class="fas fa-globe-americas"></i></div>
                </div>
                <button class="post-menu-btn"><i class="fas fa-ellipsis-h"></i></button>
            </div>
            <div class="post-content">${post.content ?? ''}</div>
            ${postImage}
            ${postVideo}
            <div class="post-stats">
                <div class="post-stats-left">
                    <span class="reaction-icons"><span class="like"><i class="fas fa-thumbs-up"></i></span><span class="heart"><i class="fas fa-heart"></i></span></span>
                    <span id="likes-${post.id}">0 reactions</span>
                </div>
                <div><span class="comment-count" onclick="toggleComments(${post.id})" style="cursor: pointer;">0 comments</span></div>
            </div>
            <div class="post-actions">
                <button class="post-action" onclick="likePost(${post.id}, this)"><i class="fas fa-thumbs-up"></i> Like</button>
                <button class="post-action" onclick="toggleComments(${post.id})"><i class="fas fa-comment"></i> Comment</button>
                <button class="post-action"><i class="fas fa-share"></i> Share</button>
            </div>
            <div class="comments-section hidden" id="comments-${post.id}">
                <div id="comments-list-${post.id}"></div>
                <div class="add-comment">
                    <img src="${'<?= $userImage ?>'}" class="add-comment-avatar">
                    <input type="text" class="add-comment-input" placeholder="Write a comment..." onkeypress="if(event.key==='Enter') submitComment(${post.id}, this)">
                </div>
            </div>
        </div>
    `;
}

function likePost(postId, btn) {
    fetch("/backend/reaction_post.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "post_id=" + postId + "&reaction=like"
    })
    .then(res => res.json())
    .then(data => {
        if(data.status === "added") {
            btn.classList.add("liked");
            updateLikeCount(postId, data.count);
        } else if(data.status === "removed") {
            btn.classList.remove("liked");
            updateLikeCount(postId, data.count);
        }
        socket.emit('reaction_update', { post_id: postId, type: 'like', total: data.count });
    });
}

function updateLikeCount(postId, count) {
    let counter = document.getElementById("likes-" + postId);
    if(counter) {
        counter.innerText = count + " reaction" + (count !== 1 ? "s" : "");
    }
}

function commentPost(postId) {
    let comment = prompt("Write a comment:");
    if(!comment || !comment.trim()) return;

    fetch("/backend/add_comment.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "post_id=" + postId + "&comment=" + encodeURIComponent(comment)
    })
    .then(res => res.json())
    .then(data => {
        if(data.status === "success") {
            let postElement = document.querySelector(`[data-post-id="${postId}"]`);
            if(postElement) {
                let commentCount = postElement.querySelector(".comment-count");
                if(commentCount) {
                    let currentText = commentCount.innerText;
                    let count = parseInt(currentText) || 0;
                    count++;
                    commentCount.innerText = count + " comment" + (count !== 1 ? "s" : "");
                }
            }
        }
    })
    .catch(err => console.error("Comment error:", err));
}

let page = 1, loading = false, noMorePosts = false;

function loadPosts() {
    if(loading || noMorePosts) return;
    loading = true;

    fetch(`/backend/get_posts.php?page=${page}&limit=10`)
    .then(res => res.json())
    .then(data => {
        if(!Array.isArray(data) || data.length === 0) {
            noMorePosts = true;
            document.getElementById("loading").innerText = "No more posts";
            return;
        }

        let container = document.getElementById("posts");
        data.forEach(post => {
            // Facebook-style verification badge - simple blue checkmark
            let verified = post.is_verified == 1 ? `<span class="verified-badge"><i class="fas fa-check-circle" style="color: #1877f2; font-size: 14px; margin-left: 4px;"></i></span>` : '';
            let likedClass = post.is_liked ? "liked" : "";
            let postImage = post.image ? `<img src="/uploads/${post.image}" class="post-image">` : '';

            container.innerHTML += `
                <div class="post" data-post-id="${post.id}">
                    <div class="post-header">
                        <img src="${post.profile_image}" 
                             class="post-avatar" 
                             alt="${post.name}"
                             data-name="${post.name}"
                             onclick="window.location.href='/profile.php?id=${post.user_profile_id}'" 
                             style="cursor: pointer;">
                        <div class="post-user-details">
                            <div class="post-user-name" style="cursor: pointer;" onclick="window.location.href='/profile.php?id=${post.user_profile_id}'">${post.name} ${verified}</div>
                            <div class="post-time">${post.created_at} · <i class="fas fa-globe-americas"></i></div>
                        </div>
                        <button class="post-menu-btn"><i class="fas fa-ellipsis-h"></i></button>
                    </div>
                    <div class="post-content">${post.content ?? ''}</div>
                    ${postImage}
                    <div class="post-stats">
                        <div class="post-stats-left">
                            <span class="reaction-icons"><span class="like"><i class="fas fa-thumbs-up"></i></span><span class="heart"><i class="fas fa-heart"></i></span></span>
                            <span id="likes-${post.id}">${post.like_count}</span>
                        </div>
                        <div><span class="comment-count" onclick="toggleComments(${post.id})" style="cursor: pointer;">${post.comment_count} comments</span></div>
                    </div>
                    <div class="post-actions">
                        <button class="post-action ${likedClass}" onclick="likePost(${post.id}, this)"><i class="fas fa-thumbs-up"></i> Like</button>
                        <button class="post-action" onclick="toggleComments(${post.id})"><i class="fas fa-comment"></i> Comment</button>
                        <button class="post-action"><i class="fas fa-share"></i> Share</button>
                    </div>
                    <div class="comments-section hidden" id="comments-${post.id}">
                        <div id="comments-list-${post.id}"></div>
                        <div class="add-comment">
                            <img src="${'<?= $userImage ?>'}" class="add-comment-avatar">
                            <input type="text" class="add-comment-input" placeholder="Write a comment..." onkeypress="if(event.key==='Enter') submitComment(${post.id}, this)">
                        </div>
                    </div>
                </div>
            `;
        });

        page++;
        loading = false;
    })
    .catch(() => { loading = false; });
}

window.addEventListener("scroll", () => {
    if(window.innerHeight + window.scrollY >= document.body.offsetHeight - 200) {
        loadPosts();
    }
});

loadPosts();

// Search functionality
function searchUsers(query) {
    const resultsDiv = document.getElementById("searchResults");
    
    if (query.length < 2) {
        resultsDiv.classList.remove("show");
        return;
    }

    fetch("/backend/search_users.php?q=" + encodeURIComponent(query))
    .then(res => res.json())
    .then(data => {
        if (!Array.isArray(data) || data.length === 0) {
            resultsDiv.innerHTML = '<div class="search-empty"><i class="fas fa-search" style="font-size: 24px; margin-bottom: 8px; opacity: 0.5;"></i><br>No users found</div>';
            resultsDiv.classList.add("show");
            return;
        }

        let html = '';
        data.forEach(user => {
            let badgeClass = 'basic';
            if (user.subscription_tier === 'premium') badgeClass = 'premium';
            else if (user.subscription_tier === 'elite') badgeClass = 'elite';
            
            const verified = user.is_verified == 1 ? `<i class="fas fa-check-circle" style="color: #1877f2; font-size: 14px; margin-left: 4px;"></i>` : '';
            
            // Generate default avatar if no image
            const userName = user.name || 'User';
            const firstLetter = userName.charAt(0).toUpperCase();
            
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
                    </div>
                    <button class="search-result-btn" onclick="addFriendFromSearch(${user.id}, this)">Add</button>
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
        document.getElementById("searchResults").classList.remove("show");
    }
});
</script>

<!-- Apps Modal -->
<div id="appsModal" class="top-modal">
    <div class="modal-header-bar">
        <span>Menu</span>
        <button class="modal-close-btn" onclick="closeAppsModal()"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-item" onclick="window.location.href='/games.php'">
        <div class="modal-item-icon"><i class="fas fa-gamepad"></i></div>
        <div class="modal-item-content">
            <div class="modal-item-title">Games</div>
            <div class="modal-item-desc">Play games with friends</div>
        </div>
    </div>
    <div class="modal-item" onclick="window.location.href='/marketplace.php'">
        <div class="modal-item-icon"><i class="fas fa-store"></i></div>
        <div class="modal-item-content">
            <div class="modal-item-title">Marketplace</div>
            <div class="modal-item-desc">Buy and sell items</div>
        </div>
    </div>
    <div class="modal-item" onclick="window.location.href='/events.php'">
        <div class="modal-item-icon"><i class="fas fa-calendar-alt"></i></div>
        <div class="modal-item-content">
            <div class="modal-item-title">Events</div>
            <div class="modal-item-desc">Discover upcoming events</div>
        </div>
    </div>
</div>

<!-- Messenger Modal -->
<div id="messengerModal" class="top-modal">
    <div class="modal-header-bar">
        <span>Chats</span>
        <button class="modal-close-btn" onclick="closeMessengerModal()"><i class="fas fa-times"></i></button>
    </div>
    <div id="messengerList"></div>
</div>

<!-- Notifications Modal -->
<div id="notificationsModal" class="top-modal">
    <div class="modal-header-bar">
        <span>Notifications</span>
        <button class="modal-close-btn" onclick="closeNotificationsModal()"><i class="fas fa-times"></i></button>
    </div>
    <div id="notificationsList"></div>
</div>

<!-- Notification Detail Modal -->
<div id="notificationDetailModal" class="notification-detail-modal">
    <div class="notification-detail-content">
        <button class="notification-detail-close" onclick="closeNotificationDetail()"><i class="fas fa-times"></i></button>
        <div id="notificationDetailBody"></div>
    </div>
</div>

<script>
// User Menu Toggle
function toggleUserMenu() {
    closeAllModals();
    document.getElementById("userMenu").classList.toggle("show");
}

// Close all modals
function closeAllModals() {
    document.getElementById("userMenu").classList.remove("show");
    document.getElementById("appsModal").classList.remove("show");
    document.getElementById("messengerModal").classList.remove("show");
    document.getElementById("notificationsModal").classList.remove("show");
}

function openAppsModal() {
    closeAllModals();
    document.getElementById("appsModal").classList.add("show");
}

function closeAppsModal() {
    document.getElementById("appsModal").classList.remove("show");
}

function openMessengerModal() {
    closeAllModals();
    document.getElementById("messengerModal").classList.add("show");
    loadMessengerList();
}

function closeMessengerModal() {
    document.getElementById("messengerModal").classList.remove("show");
}

function openNotificationsModal() {
    closeAllModals();
    document.getElementById("notificationsModal").classList.add("show");
    loadNotificationsList();
}

function closeNotificationsModal() {
    document.getElementById("notificationsModal").classList.remove("show");
}

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
                        <img src="${conv.profile_image || '/assets/zuckuser.png'}" 
                             class="user-avatar"
                             alt="${conv.name}"
                             data-name="${conv.name}"
                             style="width: 56px; height: 56px; border-radius: 50%; object-fit: cover;">
                        <div class="modal-item-content">
                            <div class="modal-item-title">${conv.name}</div>
                            <div class="modal-item-desc">${conv.last_message || 'No messages yet'}</div>
                        </div>
                    </div>
                `;
            });
        } else {
            html = '<div class="modal-empty">No conversations yet</div>';
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
        let notifications = data.notifications || [];
        if (notifications.length > 0) {
            notifications.forEach(notif => {
                html += `
                    <div class="modal-item" onclick="showNotificationDetail(${notif.id}, '${notif.message.replace(/'/g, "\\'")}', '${notif.time_ago}', '${notif.type}')">
                        <div class="modal-item-icon"><i class="fas fa-bell"></i></div>
                        <div class="modal-item-content">
                            <div class="modal-item-title">${notif.message}</div>
                            <div class="modal-item-desc">${notif.time_ago}</div>
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

function showNotificationDetail(notifId, message, timeAgo, type) {
    let icon = '<i class="fas fa-bell"></i>';
    if (type === 'friend_request') icon = '<i class="fas fa-user-plus"></i>';
    if (type === 'post_like') icon = '<i class="fas fa-heart"></i>';
    if (type === 'comment') icon = '<i class="fas fa-comment"></i>';
    if (type === 'follow') icon = '<i class="fas fa-user-check"></i>';
    if (type === 'welcome') icon = '<i class="fas fa-star"></i>';
    
    let html = `
        <div class="notification-detail-icon">${icon}</div>
        <div class="notification-detail-title">New Notification</div>
        <div class="notification-detail-message">${message}</div>
        <div class="notification-detail-time">${timeAgo}</div>
        <div class="notification-detail-actions">
            <button class="btn-secondary" onclick="closeNotificationDetail()">Close</button>
        </div>
    `;
    
    document.getElementById("notificationDetailBody").innerHTML = html;
    document.getElementById("notificationDetailModal").classList.add("show");
    
    // Mark as read
    markNotificationRead(notifId);
}

function closeNotificationDetail() {
    document.getElementById("notificationDetailModal").classList.remove("show");
}

function markNotificationRead(notifId) {
    fetch("/backend/mark_seen.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "notification_id=" + notifId
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            // Update notification count
            updateNotificationCount();
            // Reload notifications list
            loadNotificationsList();
        }
    });
}

function updateNotificationCount() {
    fetch("/backend/get_unread_notifications.php")
    .then(res => res.json())
    .then(data => {
        let count = data.notifications ? data.notifications.length : 0;
        let badge = document.querySelector(".header-icon .notification-badge");
        
        if (count > 0) {
            if (!badge) {
                badge = document.createElement("span");
                badge.className = "notification-badge";
                document.querySelector(".header-icon[onclick*='openNotificationsModal']").appendChild(badge);
            }
            badge.textContent = count;
            badge.style.display = "flex";
        } else {
            if (badge) badge.style.display = "none";
        }
    });
}

// Close modals when clicking outside
document.addEventListener("click", (e) => {
    if (!e.target.closest(".header-icon") && !e.target.closest(".top-modal") && !e.target.closest(".user-avatar") && !e.target.closest(".user-menu")) {
        closeAllModals();
    }
});

// Comments Functions
function toggleComments(postId) {
    const commentsSection = document.getElementById(`comments-${postId}`);
    if (commentsSection.classList.contains('hidden')) {
        commentsSection.classList.remove('hidden');
        loadComments(postId);
    } else {
        commentsSection.classList.add('hidden');
    }
}

function loadComments(postId) {
    fetch(`/backend/get_comments.php?post_id=${postId}`)
    .then(res => res.json())
    .then(data => {
        let html = '';
        if (Array.isArray(data) && data.length > 0) {
            data.forEach(comment => {
                const verified = comment.is_verified == 1 ? '<i class="fas fa-check-circle" style="color: #1877f2; font-size: 12px; margin-left: 4px;"></i>' : '';
                html += `
                    <div class="comment" data-comment-id="${comment.id}">
                        <img src="${comment.profile_image || '/assets/zuckuser.png'}" 
                             class="comment-avatar" 
                             alt="${comment.name}"
                             data-name="${comment.name}"
                             onclick="window.location.href='/profile.php?id=${comment.user_id}'" 
                             style="cursor: pointer;">
                        <div class="comment-content">
                            <div class="comment-header">
                                <div class="comment-author" style="cursor: pointer;" onclick="window.location.href='/profile.php?id=${comment.user_id}'">
                                    ${comment.name}
                                    ${verified}
                                </div>
                                <div class="comment-time">${comment.created_at}</div>
                            </div>
                            <div class="comment-text">${comment.content}</div>
                            <div class="comment-actions">
                                <button class="comment-action" onclick="likeComment(${comment.id}, this)"><i class="fas fa-thumbs-up"></i> Like</button>
                                <button class="comment-action" onclick="toggleReplyBox(${comment.id})">Reply</button>
                            </div>
                            <div class="comment-icons">
                                <button class="comment-icon-btn heart-btn" onclick="likeComment(${comment.id}, this)" title="Like"><i class="far fa-heart"></i></button>
                                <button class="comment-icon-btn" title="Emoji"><i class="fas fa-smile"></i></button>
                                <button class="comment-icon-btn" title="Share"><i class="fas fa-share"></i></button>
                            </div>
                            <div class="reply-box hidden" id="reply-box-${comment.id}">
                                <input type="text" class="reply-input" placeholder="Write a reply..." onkeypress="if(event.key==='Enter') submitReply(${postId}, ${comment.id}, this)">
                            </div>
                            <div class="replies-list" id="replies-${comment.id}"></div>
                        </div>
                    </div>
                `;
            });
        }
        document.getElementById(`comments-list-${postId}`).innerHTML = html;
    })
    .catch(err => console.error('Error loading comments:', err));
}

function submitComment(postId, input) {
    const comment = input.value.trim();
    if (!comment) return;

    fetch('/backend/add_comment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `post_id=${postId}&comment=${encodeURIComponent(comment)}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            input.value = '';
            loadComments(postId);
            // Update comment count
            const postElement = document.querySelector(`[data-post-id="${postId}"]`);
            if (postElement) {
                const commentCount = postElement.querySelector('.comment-count');
                if (commentCount) {
                    let count = parseInt(commentCount.innerText) || 0;
                    count++;
                    commentCount.innerText = count + ' comment' + (count !== 1 ? 's' : '');
                }
            }
        }
    })
    .catch(err => console.error('Error posting comment:', err));
}

// Like Comment Function
function likeComment(commentId, btn) {
    fetch('/backend/like_comment.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `comment_id=${commentId}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            const heartBtn = btn.closest('.comment').querySelector('.heart-btn');
            if (data.liked) {
                heartBtn.innerHTML = '<i class="fas fa-heart"></i>';
                heartBtn.classList.add('liked');
            } else {
                heartBtn.innerHTML = '<i class="far fa-heart"></i>';
                heartBtn.classList.remove('liked');
            }
        }
    })
    .catch(err => console.error('Error liking comment:', err));
}

// Toggle Reply Box
function toggleReplyBox(commentId) {
    const replyBox = document.getElementById(`reply-box-${commentId}`);
    replyBox.classList.toggle('hidden');
    if (!replyBox.classList.contains('hidden')) {
        replyBox.querySelector('.reply-input').focus();
    }
}

// Submit Reply
function submitReply(postId, commentId, input) {
    const reply = input.value.trim();
    if (!reply) return;

    fetch('/backend/add_reply.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `comment_id=${commentId}&reply=${encodeURIComponent(reply)}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            input.value = '';
            loadReplies(commentId);
            toggleReplyBox(commentId);
        }
    })
    .catch(err => console.error('Error posting reply:', err));
}

// Load Replies
function loadReplies(commentId) {
    fetch(`/backend/get_replies.php?comment_id=${commentId}`)
    .then(res => res.json())
    .then(data => {
        let html = '';
        if (Array.isArray(data) && data.length > 0) {
            data.forEach(reply => {
                const verified = reply.is_verified == 1 ? '<i class="fas fa-check-circle" style="color: #1877f2; font-size: 12px; margin-left: 4px;"></i>' : '';
                html += `
                    <div class="reply" data-reply-id="${reply.id}">
                        <img src="${reply.profile_image || '/assets/zuckuser.png'}" 
                             class="reply-avatar comment-avatar" 
                             alt="${reply.name}"
                             data-name="${reply.name}"
                             onclick="window.location.href='/profile.php?id=${reply.user_id}'" 
                             style="cursor: pointer;">
                        <div class="reply-content">
                            <div class="reply-header">
                                <div class="reply-author" style="cursor: pointer;" onclick="window.location.href='/profile.php?id=${reply.user_id}'">
                                    ${reply.name}
                                    ${verified}
                                </div>
                                <div class="reply-time">${reply.created_at}</div>
                            </div>
                            <div class="reply-text">${reply.content}</div>
                            <div class="reply-actions">
                                <button class="reply-action" onclick="likeReply(${reply.id}, this)"><i class="fas fa-thumbs-up"></i> Like</button>
                            </div>
                        </div>
                    </div>
                `;
            });
        }
        document.getElementById(`replies-${commentId}`).innerHTML = html;
    })
    .catch(err => console.error('Error loading replies:', err));
}

// Like Reply
function likeReply(replyId, btn) {
    fetch('/backend/like_reply.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `reply_id=${replyId}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') {
            if (data.liked) {
                btn.classList.add('liked');
            } else {
                btn.classList.remove('liked');
            }
        }
    })
    .catch(err => console.error('Error liking reply:', err));
}

// Handle broken avatar images
document.addEventListener('DOMContentLoaded', function() {
    // Create placeholder for create post avatar
    const createPostAvatar = document.querySelector('.create-post-avatar');
    if (createPostAvatar) {
        createPostAvatar.addEventListener('error', function() {
            const name = '<?= $userName ?>';
            const firstLetter = name.charAt(0).toUpperCase();
            
            const canvas = document.createElement('canvas');
            canvas.width = 40;
            canvas.height = 40;
            const ctx = canvas.getContext('2d');
            
            ctx.fillStyle = '#1877f2';
            ctx.fillRect(0, 0, 40, 40);
            
            ctx.fillStyle = '#ffffff';
            ctx.font = 'bold 18px Arial';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(firstLetter, 20, 20);
            
            this.src = canvas.toDataURL();
            this.onerror = null;
        });
    }
    
    // Handle all post avatars
    document.querySelectorAll('.post-avatar, .user-avatar, .comment-avatar, .reply-avatar, .add-comment-avatar').forEach(img => {
        img.addEventListener('error', function() {
            this.src = '/assets/zuckuser.png';
            this.onerror = null;
        });
    });
});
</script>
</body>
</html>