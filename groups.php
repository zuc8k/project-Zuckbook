<?php
require_once __DIR__ . "/backend/config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: /");
    exit;
}

$user_id = intval($_SESSION['user_id']);

$stmt = $conn->prepare("SELECT id, coins, is_verified, name, profile_image FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$userData = $stmt->get_result()->fetch_assoc();

$userName = htmlspecialchars($userData['name']);
$userImage = $userData['profile_image'] ? "/uploads/" . htmlspecialchars($userData['profile_image']) : "/assets/zuckuser.png";
$userCoins = $userData['coins'];
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Groups - ZuckBook</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="/assets/dark-mode.css">
<script src="/assets/avatar-placeholder.js"></script>
<script src="/assets/online-status.js"></script>
<script src="/assets/dark-mode.js"></script>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f0f2f5; }

/* Facebook-like header */
.header { 
    background: white; 
    border-bottom: 1px solid #ddd; 
    padding: 0 16px; 
    position: sticky; 
    top: 0; 
    z-index: 1000;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    height: 56px;
}
.header-content { 
    max-width: 1200px; 
    margin: 0 auto; 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    height: 100%;
}
.logo { 
    font-size: 28px; 
    font-weight: bold; 
    color: #1877f2; 
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
}
.logo i { font-size: 24px; }
.search-box {
    flex: 1;
    max-width: 680px;
    margin: 0 20px;
}
.search-box input {
    width: 100%;
    padding: 8px 16px;
    border-radius: 20px;
    border: none;
    background: #f0f2f5;
    font-size: 15px;
}
.header-icons { 
    display: flex; 
    gap: 8px; 
    align-items: center;
}
.header-icon { 
    width: 40px; 
    height: 40px; 
    border-radius: 50%; 
    background: #f0f2f5; 
    border: none; 
    cursor: pointer; 
    font-size: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s;
}
.header-icon:hover { background: #e4e6eb; }
.user-avatar { 
    width: 40px; 
    height: 40px; 
    border-radius: 50%; 
    object-fit: cover; 
    cursor: pointer;
    border: 2px solid transparent;
}
.user-avatar:hover { border-color: #1877f2; }

/* Main container */
.container { 
    max-width: 1200px; 
    margin: 20px auto; 
    padding: 0 16px;
    display: grid;
    grid-template-columns: 280px 1fr 280px;
    gap: 20px;
}

/* Left sidebar */
.sidebar-left {
    position: sticky;
    top: 76px;
    height: calc(100vh - 96px);
    overflow-y: auto;
}
.sidebar-card {
    background: white;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 16px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
}
.sidebar-title {
    font-size: 17px;
    font-weight: 600;
    margin-bottom: 16px;
    color: #050505;
}
.sidebar-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    border-radius: 8px;
    cursor: pointer;
    color: #050505;
    text-decoration: none;
    margin-bottom: 4px;
    transition: background 0.2s;
}
.sidebar-item:hover {
    background: #f0f2f5;
}
.sidebar-item i {
    width: 20px;
    text-align: center;
    color: #1877f2;
    font-size: 18px;
}
.sidebar-item.active {
    background: #e7f3ff;
    color: #1877f2;
    font-weight: 500;
}
.sidebar-item.active i {
    color: #1877f2;
}

/* Main content */
.main-content { 
    background: white; 
    border-radius: 8px; 
    padding: 20px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
}
.content-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid #e4e6eb;
}
.content-title { 
    font-size: 24px; 
    font-weight: 700; 
    color: #050505;
    display: flex;
    align-items: center;
    gap: 10px;
}
.create-group-btn {
    background: #1877f2;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: background 0.2s;
}
.create-group-btn:hover {
    background: #166fe5;
}

/* Groups grid */
.groups-grid { 
    display: grid; 
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); 
    gap: 20px;
}

.group-card { 
    background: white; 
    border-radius: 8px; 
    overflow: hidden; 
    box-shadow: 0 1px 2px rgba(0,0,0,0.1); 
    cursor: pointer; 
    transition: 0.2s;
    border: 1px solid #e4e6eb;
}
.group-card:hover { 
    box-shadow: 0 4px 12px rgba(0,0,0,0.15); 
    transform: translateY(-2px);
}

.group-cover {
    width: 100%; 
    height: 120px; 
    background: linear-gradient(135deg, #1877f2, #42b72a);
    position: relative;
}
.group-avatar {
    position: absolute;
    bottom: -20px;
    left: 20px;
    width: 80px;
    height: 80px;
    border-radius: 8px;
    object-fit: cover;
    border: 4px solid white;
    background: white;
}
.group-info { 
    padding: 30px 20px 20px 20px; 
}
.group-name { 
    font-weight: 600; 
    font-size: 17px; 
    margin-bottom: 8px;
    color: #050505;
}
.group-description {
    font-size: 14px;
    color: #65676b;
    margin-bottom: 12px;
    line-height: 1.4;
}
.group-meta { 
    font-size: 13px; 
    color: #65676b;
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 16px;
}
.group-meta i {
    color: #1877f2;
}
.group-actions {
    display: flex;
    gap: 8px;
}
.join-btn {
    flex: 1;
    background: #1877f2;
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}
.join-btn:hover {
    background: #166fe5;
}
.join-btn.joined {
    background: #e4e6eb;
    color: #65676b;
    cursor: default;
}
.join-btn.pending {
    background: #fff3cd;
    color: #856404;
    cursor: default;
}
.view-btn {
    background: #e4e6eb;
    color: #050505;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}
.view-btn:hover {
    background: #d8dadf;
}

/* Right sidebar */
.sidebar-right {
    position: sticky;
    top: 76px;
    height: calc(100vh - 96px);
    overflow-y: auto;
}
.suggested-title {
    font-size: 17px;
    font-weight: 600;
    margin-bottom: 16px;
    color: #050505;
}
.suggested-group {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: white;
    border-radius: 8px;
    margin-bottom: 8px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    cursor: pointer;
    transition: transform 0.2s;
}
.suggested-group:hover {
    transform: translateX(4px);
}
.suggested-avatar {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    object-fit: cover;
}
.suggested-info {
    flex: 1;
}
.suggested-name {
    font-weight: 600;
    font-size: 14px;
    color: #050505;
}
.suggested-members {
    font-size: 12px;
    color: #65676b;
}

.empty { 
    text-align: center; 
    padding: 60px 20px; 
    color: #65676b;
}
.empty i {
    font-size: 64px;
    color: #e4e6eb;
    margin-bottom: 16px;
    display: block;
}
.empty h3 {
    font-size: 20px;
    margin-bottom: 8px;
    color: #050505;
}
.empty p {
    margin-bottom: 20px;
}

/* Responsive */
@media (max-width: 1100px) {
    .container {
        grid-template-columns: 240px 1fr;
    }
    .sidebar-right {
        display: none;
    }
}
@media (max-width: 900px) {
    .container {
        grid-template-columns: 1fr;
    }
    .sidebar-left {
        display: none;
    }
    .search-box {
        display: none;
    }
}
@media (max-width: 600px) {
    .groups-grid { 
        grid-template-columns: 1fr; 
    }
    .content-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
    }
    .create-group-btn {
        width: 100%;
        justify-content: center;
    }
}
</style>
</head>
<body>

<div class="header">
    <div class="header-content">
        <div class="logo" onclick="window.location.href='/home.php'">
            <span>ZuckBook</span>
        </div>
        
        <div class="search-box">
            <input type="text" placeholder="Search groups..." id="searchInput">
        </div>
        
        <div class="header-icons">
            <button class="header-icon" onclick="window.location.href='/home.php'" title="Home">
                <i class="fas fa-home"></i>
            </button>
            <button class="header-icon" onclick="window.location.href='/friends.php'" title="Friends">
                <i class="fas fa-user-friends"></i>
            </button>
            <button class="header-icon" onclick="window.location.href='/chat.php'" title="Messages">
                <i class="fas fa-comment-dots"></i>
            </button>
            <button class="header-icon" onclick="window.location.href='/notifications.php'" title="Notifications">
                <i class="fas fa-bell"></i>
            </button>
            <img src="<?= $userImage ?>" class="user-avatar" 
                 alt="<?= $userName ?>"
                 data-name="<?= $userName ?>"
                 onclick="window.location.href='/profile.php?id=<?= $user_id ?>'" 
                 title="Profile">
        </div>
    </div>
</div>

<div class="container">
    <!-- Left sidebar -->
    <div class="sidebar-left">
        <div class="sidebar-card">
            <div class="sidebar-title">Menu</div>
            <a href="/home.php" class="sidebar-item">
                <i class="fas fa-home"></i>
                <span>Home</span>
            </a>
            <a href="/friends.php" class="sidebar-item">
                <i class="fas fa-user-friends"></i>
                <span>Friends</span>
            </a>
            <a href="/groups.php" class="sidebar-item active">
                <i class="fas fa-users"></i>
                <span>Groups</span>
            </a>
            <a href="/chat.php" class="sidebar-item">
                <i class="fas fa-comment-dots"></i>
                <span>Messages</span>
            </a>
            <a href="/notifications.php" class="sidebar-item">
                <i class="fas fa-bell"></i>
                <span>Notifications</span>
            </a>
        </div>
        
        <div class="sidebar-card">
            <div class="sidebar-title">Your Groups</div>
            <div id="yourGroups"></div>
        </div>
        
        <div class="sidebar-card">
            <div class="sidebar-title">Shortcuts</div>
            <a href="/create_group.php" class="sidebar-item">
                <i class="fas fa-plus-circle"></i>
                <span>Create Group</span>
            </a>
            <a href="/discover_groups.php" class="sidebar-item">
                <i class="fas fa-compass"></i>
                <span>Discover</span>
            </a>
            <a href="/group_invites.php" class="sidebar-item">
                <i class="fas fa-user-plus"></i>
                <span>Invites</span>
            </a>
        </div>
    </div>
    
    <!-- Main content -->
    <div class="main-content">
        <div class="content-header">
            <div class="content-title">
                <i class="fas fa-users"></i>
                <span>Groups</span>
            </div>
            <button class="create-group-btn" onclick="window.location.href='/create_group.php'">
                <i class="fas fa-plus"></i>
                <span>Create Group</span>
            </button>
        </div>
        
        <div class="groups-grid" id="groupsList"></div>
        <div id="emptyState" class="empty" style="display: none;">
            <i class="fas fa-users"></i>
            <h3>No groups found</h3>
            <p>Be the first to create a group or discover existing ones</p>
            <button class="create-group-btn" onclick="window.location.href='/create_group.php'">
                <i class="fas fa-plus"></i>
                <span>Create Your First Group</span>
            </button>
        </div>
    </div>
    
    <!-- Right sidebar -->
    <div class="sidebar-right">
        <div class="sidebar-card">
            <div class="suggested-title">Suggested Groups</div>
            <div id="suggestedGroups"></div>
        </div>
        
        <div class="sidebar-card">
            <div class="suggested-title">Popular Categories</div>
            <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px;">
                <span style="background: #e7f3ff; color: #1877f2; padding: 6px 12px; border-radius: 20px; font-size: 13px; cursor: pointer;">Technology</span>
                <span style="background: #e7f3ff; color: #1877f2; padding: 6px 12px; border-radius: 20px; font-size: 13px; cursor: pointer;">Gaming</span>
                <span style="background: #e7f3ff; color: #1877f2; padding: 6px 12px; border-radius: 20px; font-size: 13px; cursor: pointer;">Sports</span>
                <span style="background: #e7f3ff; color: #1877f2; padding: 6px 12px; border-radius: 20px; font-size: 13px; cursor: pointer;">Music</span>
                <span style="background: #e7f3ff; color: #1877f2; padding: 6px 12px; border-radius: 20px; font-size: 13px; cursor: pointer;">Art</span>
                <span style="background: #e7f3ff; color: #1877f2; padding: 6px 12px; border-radius: 20px; font-size: 13px; cursor: pointer;">Business</span>
            </div>
        </div>
    </div>
</div>

<script>
function loadGroups() {
    fetch('/backend/get_groups.php')
    .then(res => res.json())
    .then(data => {
        const container = document.getElementById('groupsList');
        const emptyState = document.getElementById('emptyState');
        const yourGroupsContainer = document.getElementById('yourGroups');
        const suggestedContainer = document.getElementById('suggestedGroups');
        
        if (!Array.isArray(data) || data.length === 0) {
            emptyState.style.display = 'block';
            yourGroupsContainer.innerHTML = '<div style="color: #65676b; text-align: center; padding: 20px;">No groups yet</div>';
            suggestedContainer.innerHTML = '<div style="color: #65676b; text-align: center; padding: 20px;">No suggestions</div>';
            return;
        }
        
        // Filter user's groups
        const yourGroups = data.filter(group => group.is_member);
        const otherGroups = data.filter(group => !group.is_member);
        
        // Display all groups
        container.innerHTML = data.map(group => `
            <div class="group-card">
                <div class="group-cover" style="background: ${group.cover_image ? `url('/uploads/${group.cover_image}') center/cover` : 'linear-gradient(135deg, #1877f2, #42b72a)'}">
                    <img src="${group.image ? '/uploads/' + group.image : '/assets/group.png'}" 
                         class="group-avatar" 
                         alt="${group.name}">
                </div>
                <div class="group-info">
                    <div class="group-name">${group.name}</div>
                    <div class="group-description">${group.description || 'No description'}</div>
                    <div class="group-meta">
                        <i class="fas fa-users"></i>
                        <span>${group.members_count} members</span>
                        <i class="fas fa-newspaper" style="margin-left: 12px;"></i>
                        <span>${group.posts_count} posts</span>
                    </div>
                    <div class="group-actions">
                        ${group.is_member ? 
                            `<button class="join-btn joined" disabled>
                                <i class="fas fa-check"></i> Joined
                            </button>` : 
                            `<button class="join-btn" onclick="joinGroup(${group.id}, this)">
                                <i class="fas fa-user-plus"></i> Join Group
                            </button>`
                        }
                        <button class="view-btn" onclick="window.location.href='/group.php?id=${group.id}'">
                            <i class="fas fa-eye"></i> View
                        </button>
                    </div>
                </div>
            </div>
        `).join('');
        
        // Display user's groups in sidebar
        if (yourGroups.length > 0) {
            yourGroupsContainer.innerHTML = yourGroups.slice(0, 5).map(group => `
                <a href="/group.php?id=${group.id}" class="sidebar-item" style="margin-bottom: 8px;">
                    <img src="${group.image ? '/uploads/' + group.image : '/assets/group.png'}" 
                         style="width: 32px; height: 32px; border-radius: 6px; object-fit: cover;">
                    <span style="font-size: 14px;">${group.name}</span>
                </a>
            `).join('');
        } else {
            yourGroupsContainer.innerHTML = '<div style="color: #65676b; text-align: center; padding: 20px;">No groups yet</div>';
        }
        
        // Display suggested groups
        if (otherGroups.length > 0) {
            suggestedContainer.innerHTML = otherGroups.slice(0, 5).map(group => `
                <div class="suggested-group" onclick="window.location.href='/group.php?id=${group.id}'">
                    <img src="${group.image ? '/uploads/' + group.image : '/assets/group.png'}" 
                         class="suggested-avatar">
                    <div class="suggested-info">
                        <div class="suggested-name">${group.name}</div>
                        <div class="suggested-members">${group.members_count} members</div>
                    </div>
                    <button class="join-btn" style="padding: 4px 12px; font-size: 12px;" 
                            onclick="event.stopPropagation(); joinGroup(${group.id}, this)">
                        Join
                    </button>
                </div>
            `).join('');
        } else {
            suggestedContainer.innerHTML = '<div style="color: #65676b; text-align: center; padding: 20px;">No suggestions</div>';
        }
    })
    .catch(err => console.error('Error loading groups:', err));
}

function joinGroup(groupId, button) {
    if (button.disabled) return;
    
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Joining...';
    
    fetch('/backend/join_group.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `group_id=${groupId}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'joined' || data.status === 'already') {
            button.innerHTML = '<i class="fas fa-check"></i> Joined';
            button.classList.add('joined');
            button.classList.remove('join-btn');
            button.style.background = '#e4e6eb';
            button.style.color = '#65676b';
            
            // Reload groups after a short delay
            setTimeout(() => {
                loadGroups();
            }, 1000);
        } else if (data.status === 'pending') {
            button.innerHTML = '<i class="fas fa-clock"></i> Pending';
            button.classList.add('pending');
            button.style.background = '#fff3cd';
            button.style.color = '#856404';
        }
    })
    .catch(err => {
        console.error('Error joining group:', err);
        button.disabled = false;
        button.innerHTML = '<i class="fas fa-user-plus"></i> Join Group';
    });
}

// Search functionality
document.getElementById('searchInput')?.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const groups = document.querySelectorAll('.group-card');
    
    groups.forEach(group => {
        const groupName = group.querySelector('.group-name').textContent.toLowerCase();
        const groupDesc = group.querySelector('.group-description').textContent.toLowerCase();
        
        if (groupName.includes(searchTerm) || groupDesc.includes(searchTerm)) {
            group.style.display = 'block';
        } else {
            group.style.display = 'none';
        }
    });
});

loadGroups();
</script>

</body>
</html>
