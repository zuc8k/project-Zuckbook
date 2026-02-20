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
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Watch - ZuckBook</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="/assets/dark-mode.css">
<script src="/assets/avatar-placeholder.js"></script>
<script src="/assets/online-status.js"></script>
<script src="/assets/dark-mode.js"></script>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #18191a; color: #e4e6eb; }

/* Header */
.header { background: #242526; border-bottom: 1px solid #3a3b3c; padding: 0 16px; position: sticky; top: 0; z-index: 100; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.header-content { max-width: 1400px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; height: 56px; }

.header-left { display: flex; align-items: center; gap: 8px; }
.logo { font-size: 28px; font-weight: bold; color: #1877f2; cursor: pointer; }
.search-box { position: relative; }
.search-box input { width: 240px; padding: 10px 16px 10px 40px; border: none; border-radius: 50px; background: #3a3b3c; font-size: 15px; transition: 0.2s; color: #e4e6eb; }
.search-box input:focus { outline: none; background: #4e4f50; width: 280px; }
.search-box i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #b0b3b8; }

.header-center { display: flex; gap: 4px; justify-content: center; flex: 1; }
.nav-item { width: 112px; height: 48px; display: flex; align-items: center; justify-content: center; border-radius: 8px; cursor: pointer; transition: 0.2s; color: #b0b3b8; text-decoration: none; border-bottom: 3px solid transparent; }
.nav-item:hover { background: #3a3b3c; }
.nav-item.active { color: #1877f2; border-bottom-color: #1877f2; }
.nav-item i { font-size: 22px; }

.header-right { display: flex; gap: 8px; align-items: center; }
.header-icon { width: 40px; height: 40px; border-radius: 50%; background: #3a3b3c; border: none; cursor: pointer; font-size: 18px; color: #e4e6eb; transition: 0.2s; display: flex; align-items: center; justify-content: center; position: relative; }
.header-icon:hover { background: #4e4f50; }
.header-icon .badge { position: absolute; top: -4px; right: -4px; background: #e41e3a; color: white; font-size: 11px; font-weight: 700; padding: 2px 6px; border-radius: 10px; min-width: 18px; text-align: center; }
.user-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; cursor: pointer; border: 2px solid transparent; transition: 0.2s; }
.user-avatar:hover { border-color: #1877f2; }

/* Main Layout */
.main-container { max-width: 1400px; margin: 0 auto; padding: 16px; display: grid; grid-template-columns: 360px 1fr; gap: 16px; }

/* Sidebar */
.sidebar { position: sticky; top: 72px; height: calc(100vh - 88px); overflow-y: auto; }
.sidebar::-webkit-scrollbar { width: 8px; }
.sidebar::-webkit-scrollbar-track { background: transparent; }
.sidebar::-webkit-scrollbar-thumb { background: #3a3b3c; border-radius: 10px; }

.sidebar-section { margin-bottom: 16px; }
.sidebar-title { font-size: 20px; font-weight: 700; padding: 8px 16px; color: #e4e6eb; }

.sidebar-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; cursor: pointer; border-radius: 8px; transition: 0.2s; text-decoration: none; color: #e4e6eb; }
.sidebar-item:hover { background: #3a3b3c; }
.sidebar-item.active { background: #263951; color: #1877f2; }
.sidebar-item-icon { width: 36px; height: 36px; border-radius: 50%; background: #3a3b3c; display: flex; align-items: center; justify-content: center; font-size: 18px; }
.sidebar-item.active .sidebar-item-icon { background: #1877f2; color: white; }
.sidebar-item-text { flex: 1; font-size: 15px; font-weight: 600; }

/* Video Grid */
.videos-container { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; }

.video-card { background: #242526; border-radius: 12px; overflow: hidden; cursor: pointer; transition: 0.2s; }
.video-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,0.3); }

.video-thumbnail { position: relative; width: 100%; padding-top: 56.25%; background: #000; overflow: hidden; }
.video-thumbnail video { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; }
.video-thumbnail img { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; }

.video-duration { position: absolute; bottom: 8px; right: 8px; background: rgba(0,0,0,0.8); color: white; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }

.play-overlay { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 60px; height: 60px; background: rgba(255,255,255,0.9); border-radius: 50%; display: flex; align-items: center; justify-content: center; opacity: 0; transition: 0.3s; }
.video-card:hover .play-overlay { opacity: 1; }
.play-overlay i { font-size: 24px; color: #1877f2; margin-left: 4px; }

.video-info { padding: 12px; }
.video-title { font-size: 15px; font-weight: 600; color: #e4e6eb; margin-bottom: 8px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

.video-meta { display: flex; align-items: center; gap: 8px; font-size: 13px; color: #b0b3b8; }
.video-author { display: flex; align-items: center; gap: 8px; }
.video-author-avatar { width: 24px; height: 24px; border-radius: 50%; object-fit: cover; }
.video-stats { display: flex; align-items: center; gap: 12px; }

/* Video Player Modal */
.video-modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.95); z-index: 2000; align-items: center; justify-content: center; }
.video-modal.show { display: flex; }

.video-player-container { width: 90%; max-width: 1200px; background: #242526; border-radius: 12px; overflow: hidden; }

.video-player { width: 100%; background: #000; }
.video-player video { width: 100%; display: block; }

.video-details { padding: 20px; }
.video-details-title { font-size: 20px; font-weight: 700; color: #e4e6eb; margin-bottom: 12px; }

.video-details-meta { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
.video-details-author { display: flex; align-items: center; gap: 12px; }
.video-details-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
.video-details-name { font-size: 15px; font-weight: 600; color: #e4e6eb; }
.video-details-time { font-size: 13px; color: #b0b3b8; }

.video-actions { display: flex; gap: 12px; }
.video-action-btn { padding: 8px 16px; background: #3a3b3c; border: none; border-radius: 6px; color: #e4e6eb; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: 0.2s; }
.video-action-btn:hover { background: #4e4f50; }
.video-action-btn.liked { color: #1877f2; }

.close-modal { position: absolute; top: 20px; right: 20px; width: 40px; height: 40px; background: rgba(255,255,255,0.1); border: none; border-radius: 50%; color: white; font-size: 24px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: 0.2s; }
.close-modal:hover { background: rgba(255,255,255,0.2); }

/* Empty State */
.empty-state { text-align: center; padding: 60px 20px; color: #b0b3b8; }
.empty-state i { font-size: 64px; margin-bottom: 16px; opacity: 0.5; }
.empty-state h3 { font-size: 20px; margin-bottom: 8px; color: #e4e6eb; }

/* Responsive */
@media (max-width: 1100px) {
    .main-container { grid-template-columns: 1fr; }
    .sidebar { display: none; }
}

@media (max-width: 768px) {
    .videos-container { grid-template-columns: 1fr; }
    .video-player-container { width: 100%; height: 100%; border-radius: 0; }
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
                <input type="text" placeholder="Search videos">
            </div>
        </div>
        
        <div class="header-center">
            <a href="/home.php" class="nav-item"><i class="fas fa-home"></i></a>
            <a href="/friends.php" class="nav-item"><i class="fas fa-user-friends"></i></a>
            <a href="/videos.php" class="nav-item active"><i class="fas fa-play-circle"></i></a>
            <a href="/groups.php" class="nav-item"><i class="fas fa-users"></i></a>
            <a href="/profile.php?id=<?= $user_id ?>" class="nav-item"><i class="fas fa-user"></i></a>
        </div>
        
        <div class="header-right">
            <button class="header-icon" title="Menu"><i class="fas fa-th"></i></button>
            <button class="header-icon" title="Messenger"><i class="fab fa-facebook-messenger"></i></button>
            <button class="header-icon" title="Notifications">
                <i class="fas fa-bell"></i>
                <?php if($unread > 0): ?>
                    <span class="badge"><?= $unread > 9 ? '9+' : $unread ?></span>
                <?php endif; ?>
            </button>
            <img src="<?= $userImage ?>" class="user-avatar" alt="<?= $userName ?>" data-name="<?= $userName ?>" onclick="window.location.href='/profile.php?id=<?= $user_id ?>'">
        </div>
    </div>
</div>

<!-- Main Container -->
<div class="main-container">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-section">
            <div class="sidebar-title">Watch</div>
            
            <a href="#" class="sidebar-item active">
                <div class="sidebar-item-icon"><i class="fas fa-home"></i></div>
                <span class="sidebar-item-text">Home</span>
            </a>
            
            <a href="#" class="sidebar-item">
                <div class="sidebar-item-icon"><i class="fas fa-fire"></i></div>
                <span class="sidebar-item-text">Trending</span>
            </a>
            
            <a href="#" class="sidebar-item">
                <div class="sidebar-item-icon"><i class="fas fa-bookmark"></i></div>
                <span class="sidebar-item-text">Saved</span>
            </a>
            
            <a href="#" class="sidebar-item">
                <div class="sidebar-item-icon"><i class="fas fa-history"></i></div>
                <span class="sidebar-item-text">Watch History</span>
            </a>
            
            <a href="#" class="sidebar-item">
                <div class="sidebar-item-icon"><i class="fas fa-video"></i></div>
                <span class="sidebar-item-text">Your Videos</span>
            </a>
        </div>
    </div>
    
    <!-- Videos Grid -->
    <div>
        <div class="videos-container" id="videosContainer">
            <!-- Sample Video Cards -->
            <div class="video-card" onclick="openVideo(1)">
                <div class="video-thumbnail">
                    <img src="https://picsum.photos/400/225?random=1" alt="Video">
                    <div class="video-duration">5:24</div>
                    <div class="play-overlay">
                        <i class="fas fa-play"></i>
                    </div>
                </div>
                <div class="video-info">
                    <div class="video-title">Amazing Nature Documentary - Wildlife in 4K</div>
                    <div class="video-meta">
                        <div class="video-author">
                            <img src="<?= $userImage ?>" class="video-author-avatar">
                            <span><?= $userName ?></span>
                        </div>
                    </div>
                    <div class="video-stats">
                        <span><i class="fas fa-eye"></i> 1.2M views</span>
                        <span>2 days ago</span>
                    </div>
                </div>
            </div>

            <div class="video-card" onclick="openVideo(2)">
                <div class="video-thumbnail">
                    <img src="https://picsum.photos/400/225?random=2" alt="Video">
                    <div class="video-duration">12:45</div>
                    <div class="play-overlay">
                        <i class="fas fa-play"></i>
                    </div>
                </div>
                <div class="video-info">
                    <div class="video-title">Cooking Tutorial: Perfect Pasta Recipe</div>
                    <div class="video-meta">
                        <div class="video-author">
                            <img src="<?= $userImage ?>" class="video-author-avatar">
                            <span><?= $userName ?></span>
                        </div>
                    </div>
                    <div class="video-stats">
                        <span><i class="fas fa-eye"></i> 856K views</span>
                        <span>1 week ago</span>
                    </div>
                </div>
            </div>

            <div class="video-card" onclick="openVideo(3)">
                <div class="video-thumbnail">
                    <img src="https://picsum.photos/400/225?random=3" alt="Video">
                    <div class="video-duration">8:15</div>
                    <div class="play-overlay">
                        <i class="fas fa-play"></i>
                    </div>
                </div>
                <div class="video-info">
                    <div class="video-title">Tech Review: Latest Smartphone Features</div>
                    <div class="video-meta">
                        <div class="video-author">
                            <img src="<?= $userImage ?>" class="video-author-avatar">
                            <span><?= $userName ?></span>
                        </div>
                    </div>
                    <div class="video-stats">
                        <span><i class="fas fa-eye"></i> 2.5M views</span>
                        <span>3 days ago</span>
                    </div>
                </div>
            </div>

            <div class="video-card" onclick="openVideo(4)">
                <div class="video-thumbnail">
                    <img src="https://picsum.photos/400/225?random=4" alt="Video">
                    <div class="video-duration">15:30</div>
                    <div class="play-overlay">
                        <i class="fas fa-play"></i>
                    </div>
                </div>
                <div class="video-info">
                    <div class="video-title">Travel Vlog: Exploring Beautiful Destinations</div>
                    <div class="video-meta">
                        <div class="video-author">
                            <img src="<?= $userImage ?>" class="video-author-avatar">
                            <span><?= $userName ?></span>
                        </div>
                    </div>
                    <div class="video-stats">
                        <span><i class="fas fa-eye"></i> 3.8M views</span>
                        <span>5 days ago</span>
                    </div>
                </div>
            </div>

            <div class="video-card" onclick="openVideo(5)">
                <div class="video-thumbnail">
                    <img src="https://picsum.photos/400/225?random=5" alt="Video">
                    <div class="video-duration">6:42</div>
                    <div class="play-overlay">
                        <i class="fas fa-play"></i>
                    </div>
                </div>
                <div class="video-info">
                    <div class="video-title">Fitness Workout: 10 Minute Home Exercise</div>
                    <div class="video-meta">
                        <div class="video-author">
                            <img src="<?= $userImage ?>" class="video-author-avatar">
                            <span><?= $userName ?></span>
                        </div>
                    </div>
                    <div class="video-stats">
                        <span><i class="fas fa-eye"></i> 1.5M views</span>
                        <span>1 day ago</span>
                    </div>
                </div>
            </div>

            <div class="video-card" onclick="openVideo(6)">
                <div class="video-thumbnail">
                    <img src="https://picsum.photos/400/225?random=6" alt="Video">
                    <div class="video-duration">20:18</div>
                    <div class="play-overlay">
                        <i class="fas fa-play"></i>
                    </div>
                </div>
                <div class="video-info">
                    <div class="video-title">Gaming Highlights: Epic Moments Compilation</div>
                    <div class="video-meta">
                        <div class="video-author">
                            <img src="<?= $userImage ?>" class="video-author-avatar">
                            <span><?= $userName ?></span>
                        </div>
                    </div>
                    <div class="video-stats">
                        <span><i class="fas fa-eye"></i> 4.2M views</span>
                        <span>4 days ago</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Video Player Modal -->
<div id="videoModal" class="video-modal">
    <button class="close-modal" onclick="closeVideo()"><i class="fas fa-times"></i></button>
    
    <div class="video-player-container">
        <div class="video-player">
            <video id="videoPlayer" controls>
                <source src="/assets/coins-ad.mp4" type="video/mp4">
                Your browser does not support the video tag.
            </video>
        </div>
        
        <div class="video-details">
            <div class="video-details-title" id="videoTitle">Video Title</div>
            
            <div class="video-details-meta">
                <div class="video-details-author">
                    <img src="<?= $userImage ?>" class="video-details-avatar">
                    <div>
                        <div class="video-details-name"><?= $userName ?></div>
                        <div class="video-details-time">2 days ago • 1.2M views</div>
                    </div>
                </div>
                
                <div class="video-actions">
                    <button class="video-action-btn" onclick="likeVideo()">
                        <i class="fas fa-thumbs-up"></i>
                        <span>Like</span>
                    </button>
                    <button class="video-action-btn">
                        <i class="fas fa-share"></i>
                        <span>Share</span>
                    </button>
                    <button class="video-action-btn">
                        <i class="fas fa-bookmark"></i>
                        <span>Save</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function openVideo(videoId) {
    const modal = document.getElementById('videoModal');
    const player = document.getElementById('videoPlayer');
    const title = document.getElementById('videoTitle');
    
    // Set video title based on ID
    const titles = {
        1: 'Amazing Nature Documentary - Wildlife in 4K',
        2: 'Cooking Tutorial: Perfect Pasta Recipe',
        3: 'Tech Review: Latest Smartphone Features',
        4: 'Travel Vlog: Exploring Beautiful Destinations',
        5: 'Fitness Workout: 10 Minute Home Exercise',
        6: 'Gaming Highlights: Epic Moments Compilation'
    };
    
    title.textContent = titles[videoId] || 'Video Title';
    
    modal.classList.add('show');
    player.play();
}

function closeVideo() {
    const modal = document.getElementById('videoModal');
    const player = document.getElementById('videoPlayer');
    
    modal.classList.remove('show');
    player.pause();
    player.currentTime = 0;
}

function likeVideo() {
    const btn = event.target.closest('.video-action-btn');
    btn.classList.toggle('liked');
    
    if (btn.classList.contains('liked')) {
        btn.querySelector('span').textContent = 'Liked';
    } else {
        btn.querySelector('span').textContent = 'Like';
    }
}

// Close modal when clicking outside
document.getElementById('videoModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeVideo();
    }
});

// Close with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeVideo();
    }
});
</script>

</body>
</html>
