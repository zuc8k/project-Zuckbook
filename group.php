<?php
session_start();
if(!isset($_GET['id'])){
    die("Group not found");
}
$group_id = intval($_GET['id']);
?>
<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Group - ZuckBook</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f0f2f5; }

/* Header */
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
}
.user-avatar { 
    width: 40px; 
    height: 40px; 
    border-radius: 50%; 
    object-fit: cover; 
    cursor: pointer;
}

/* Group cover */
.group-cover {
    height: 350px;
    background: linear-gradient(135deg, #1877f2, #42b72a);
    position: relative;
    overflow: hidden;
}

.group-cover img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.cover-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(transparent, rgba(0,0,0,0.7));
    padding: 30px;
    color: white;
}

.group-header {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    align-items: flex-end;
    gap: 20px;
}

.group-avatar {
    width: 168px;
    height: 168px;
    border-radius: 12px;
    object-fit: cover;
    border: 5px solid white;
    background: white;
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

.group-title {
    flex: 1;
    padding-bottom: 20px;
}

.group-name {
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 8px;
    text-shadow: 0 1px 2px rgba(0,0,0,0.5);
}

.group-meta {
    font-size: 15px;
    opacity: 0.9;
    margin-bottom: 16px;
}

.group-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.action-btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
}

.btn-primary {
    background: #1877f2;
    color: white;
}

.btn-primary:hover {
    background: #166fe5;
}

.btn-secondary {
    background: rgba(255,255,255,0.2);
    color: white;
    backdrop-filter: blur(10px);
}

.btn-secondary:hover {
    background: rgba(255,255,255,0.3);
}

.btn-joined {
    background: #42b72a;
    color: white;
    cursor: default;
}

.btn-pending {
    background: #ff9800;
    color: white;
    cursor: default;
}

/* Main container */
.container {
    max-width: 1200px;
    margin: 20px auto;
    padding: 0 16px;
    display: grid;
    grid-template-columns: 360px 1fr;
    gap: 20px;
}

/* Sidebar */
.sidebar {
    position: sticky;
    top: 76px;
    height: calc(100vh - 96px);
    overflow-y: auto;
}

.sidebar-card {
    background: white;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 16px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

.sidebar-title {
    font-size: 17px;
    font-weight: 600;
    margin-bottom: 16px;
    color: #050505;
    padding-bottom: 12px;
    border-bottom: 1px solid #e4e6eb;
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

.member-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 8px;
    border-radius: 8px;
    margin-bottom: 8px;
    transition: background 0.2s;
}

.member-item:hover {
    background: #f0f2f5;
}

.member-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    object-fit: cover;
}

.member-name {
    font-size: 14px;
    font-weight: 500;
    color: #050505;
}

.member-role {
    font-size: 12px;
    color: #65676b;
    margin-top: 2px;
}

/* Main content */
.main-content {
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

/* Create post */
.create-post {
    background: white;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 20px;
    border: 1px solid #e4e6eb;
}

.post-input {
    width: 100%;
    min-height: 80px;
    border: none;
    padding: 12px;
    font-size: 15px;
    resize: vertical;
    border-radius: 8px;
    background: #f0f2f5;
    margin-bottom: 12px;
}

.post-input:focus {
    outline: none;
    background: white;
    box-shadow: 0 0 0 2px #1877f2;
}

.post-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.post-media {
    display: flex;
    gap: 12px;
}

.media-btn {
    background: none;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    color: #65676b;
    font-weight: 500;
    transition: background 0.2s;
}

.media-btn:hover {
    background: #f0f2f5;
}

.post-submit {
    background: #1877f2;
    color: white;
    border: none;
    padding: 10px 24px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}

.post-submit:hover {
    background: #166fe5;
}

.post-submit:disabled {
    background: #e4e6eb;
    color: #bcc0c4;
    cursor: not-allowed;
}

/* Posts */
.post {
    background: white;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 16px;
    border: 1px solid #e4e6eb;
}

.post-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}

.post-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.post-user-info {
    flex: 1;
}

.post-user-name {
    font-weight: 600;
    color: #050505;
    font-size: 15px;
}

.post-time {
    font-size: 13px;
    color: #65676b;
    margin-top: 2px;
}

.post-content {
    font-size: 15px;
    line-height: 1.5;
    color: #050505;
    margin-bottom: 16px;
    white-space: pre-wrap;
}

.post-actions-bar {
    display: flex;
    gap: 4px;
    padding-top: 12px;
    border-top: 1px solid #e4e6eb;
}

.post-action {
    flex: 1;
    background: none;
    border: none;
    padding: 8px;
    border-radius: 4px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    color: #65676b;
    font-weight: 500;
    transition: background 0.2s;
}

.post-action:hover {
    background: #f0f2f5;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #65676b;
}

.empty-state i {
    font-size: 64px;
    color: #e4e6eb;
    margin-bottom: 16px;
    display: block;
}

.empty-state h3 {
    font-size: 20px;
    margin-bottom: 8px;
    color: #050505;
}

/* Responsive */
@media (max-width: 1100px) {
    .container {
        grid-template-columns: 300px 1fr;
    }
}

@media (max-width: 900px) {
    .container {
        grid-template-columns: 1fr;
    }
    .sidebar {
        display: none;
    }
    .group-header {
        flex-direction: column;
        align-items: flex-start;
    }
    .group-avatar {
        width: 120px;
        height: 120px;
    }
    .group-cover {
        height: 280px;
    }
}

@media (max-width: 600px) {
    .group-cover {
        height: 200px;
    }
    .group-actions {
        flex-direction: column;
        width: 100%;
    }
    .action-btn {
        width: 100%;
        justify-content: center;
    }
    .post-actions {
        flex-direction: column;
        gap: 12px;
    }
    .post-media {
        width: 100%;
        justify-content: space-between;
    }
}
</style>
</head>
<body>

<div class="header">
    <div class="header-content">
        <div class="logo" onclick="window.location.href='/home.php'">
            <i class="fab fa-facebook"></i>
            <span>ZuckBook</span>
        </div>
        
        <div class="header-icons">
            <button class="header-icon" onclick="window.location.href='/home.php'" title="Home">
                <i class="fas fa-home"></i>
            </button>
            <button class="header-icon" onclick="window.location.href='/groups.php'" title="Groups">
                <i class="fas fa-users"></i>
            </button>
            <button class="header-icon" onclick="window.location.href='/chat.php'" title="Messages">
                <i class="fas fa-comment-dots"></i>
            </button>
            <img src="/assets/zuckuser.png" class="user-avatar" 
                 onclick="window.location.href='/profile.php'" 
                 title="Profile">
        </div>
    </div>
</div>

<div class="group-cover" id="cover">
    <div class="cover-overlay">
        <div class="group-header">
            <img id="groupAvatar" class="group-avatar" src="/assets/group.png">
            <div class="group-title">
                <h1 class="group-name" id="group-name"></h1>
                <div class="group-meta" id="group-meta"></div>
                <p id="group-desc" style="margin-top: 8px; font-size: 15px; opacity: 0.9;"></p>
                <div class="group-actions">
                    <button id="joinBtn" class="action-btn btn-primary"></button>
                    <button class="action-btn btn-secondary">
                        <i class="fas fa-share"></i> Share
                    </button>
                    <button class="action-btn btn-secondary">
                        <i class="fas fa-ellipsis-h"></i> More
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-card">
            <div class="sidebar-title">About</div>
            <div id="aboutInfo" style="font-size: 14px; color: #65676b; line-height: 1.5;"></div>
            
            <div style="margin-top: 20px; display: flex; gap: 12px; flex-wrap: wrap;">
                <div style="text-align: center;">
                    <div style="font-size: 20px; font-weight: 600; color: #050505;" id="membersCount">0</div>
                    <div style="font-size: 13px; color: #65676b;">Members</div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 20px; font-weight: 600; color: #050505;" id="postsCount">0</div>
                    <div style="font-size: 13px; color: #65676b;">Posts</div>
                </div>
            </div>
        </div>
        
        <div class="sidebar-card">
            <div class="sidebar-title">Members</div>
            <div id="membersList"></div>
            <a href="#" class="sidebar-item" style="justify-content: center; color: #1877f2; font-weight: 500;">
                <i class="fas fa-users"></i>
                <span>See All Members</span>
            </a>
        </div>
        
        <div class="sidebar-card">
            <div class="sidebar-title">Group Rules</div>
            <div style="font-size: 14px; color: #65676b;">
                <div style="display: flex; align-items: flex-start; gap: 8px; margin-bottom: 12px;">
                    <i class="fas fa-check-circle" style="color: #42b72a; margin-top: 2px;"></i>
                    <span>Be respectful to all members</span>
                </div>
                <div style="display: flex; align-items: flex-start; gap: 8px; margin-bottom: 12px;">
                    <i class="fas fa-check-circle" style="color: #42b72a; margin-top: 2px;"></i>
                    <span>No spam or self-promotion</span>
                </div>
                <div style="display: flex; align-items: flex-start; gap: 8px; margin-bottom: 12px;">
                    <i class="fas fa-check-circle" style="color: #42b72a; margin-top: 2px;"></i>
                    <span>Stay on topic</span>
                </div>
                <div style="display: flex; align-items: flex-start; gap: 8px;">
                    <i class="fas fa-check-circle" style="color: #42b72a; margin-top: 2px;"></i>
                    <span>No hate speech or harassment</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main content -->
    <div class="main-content">
        <!-- Create post (only for members) -->
        <div id="postBox" class="create-post" style="display: none;">
            <textarea class="post-input" id="postContent" placeholder="What's on your mind?"></textarea>
            <div class="post-actions">
                <div class="post-media">
                    <button class="media-btn">
                        <i class="fas fa-image" style="color: #45bd62;"></i>
                        <span>Photo/Video</span>
                    </button>
                    <button class="media-btn">
                        <i class="fas fa-user-tag" style="color: #1877f2;"></i>
                        <span>Tag People</span>
                    </button>
                    <button class="media-btn">
                        <i class="fas fa-smile" style="color: #f7b928;"></i>
                        <span>Feeling/Activity</span>
                    </button>
                </div>
                <button class="post-submit" id="postSubmit" onclick="createPost()" disabled>Post</button>
            </div>
        </div>
        
        <!-- Posts -->
        <div id="posts"></div>
        
        <!-- Empty state -->
        <div id="emptyState" class="empty-state" style="display: none;">
            <i class="fas fa-newspaper"></i>
            <h3>No posts yet</h3>
            <p>Be the first to share something in this group</p>
        </div>
    </div>
</div>

<script>
let groupId = <?= $group_id ?>;
let membershipStatus = null;
let groupData = null;

// Enable post button when typing
document.getElementById('postContent')?.addEventListener('input', function() {
    const submitBtn = document.getElementById('postSubmit');
    if (submitBtn) {
        submitBtn.disabled = this.value.trim().length === 0;
    }
});

/* ================= LOAD GROUP ================= */
function loadGroup() {
    fetch(`/backend/get_group.php?group_id=${groupId}`)
    .then(res => res.json())
    .then(data => {
        if (data.error) {
            alert(data.error);
            return;
        }

        groupData = data.group;
        membershipStatus = data.is_member ? "approved" : "not_member";

        // Update group info
        document.getElementById("group-name").innerText = groupData.name;
        document.getElementById("group-desc").innerText = groupData.description || "No description";
        document.getElementById("group-meta").innerText = 
            `Created by ${groupData.owner_name} • ${groupData.visibility === 'public' ? 'Public' : 'Private'} Group`;
        
        document.getElementById("membersCount").innerText = groupData.members_count || 0;
        document.getElementById("postsCount").innerText = groupData.posts_count || 0;
        
        // Update about info
        const aboutInfo = document.getElementById("aboutInfo");
        aboutInfo.innerHTML = `
            <div style="margin-bottom: 12px;">
                <div style="font-weight: 600; color: #050505; margin-bottom: 4px;">Description</div>
                <div>${groupData.description || "No description provided"}</div>
            </div>
            <div style="margin-bottom: 12px;">
                <div style="font-weight: 600; color: #050505; margin-bottom: 4px;">Visibility</div>
                <div>${groupData.visibility === 'public' ? 'Public - Anyone can join' : 
                       groupData.visibility === 'private' ? 'Private - Members only' : 
                       'Secret - Hidden from search'}</div>
            </div>
            <div>
                <div style="font-weight: 600; color: #050505; margin-bottom: 4px;">Created</div>
                <div>${new Date(groupData.created_at).toLocaleDateString()}</div>
            </div>
        `;

        // Update cover and avatar
        const coverDiv = document.getElementById("cover");
        const groupAvatar = document.getElementById("groupAvatar");
        
        if (groupData.cover_image) {
            coverDiv.style.background = `url('/uploads/${groupData.cover_image}') center/cover`;
        }
        
        if (groupData.image) {
            groupAvatar.src = `/uploads/${groupData.image}`;
        }

        // Update join button
        const joinBtn = document.getElementById("joinBtn");
        
        if (membershipStatus === "approved") {
            joinBtn.innerHTML = '<i class="fas fa-check"></i> Joined';
            joinBtn.className = 'action-btn btn-joined';
            joinBtn.disabled = true;
            document.getElementById("postBox").style.display = "block";
            loadMembers();
        } else if (membershipStatus === "pending") {
            joinBtn.innerHTML = '<i class="fas fa-clock"></i> Pending Approval';
            joinBtn.className = 'action-btn btn-pending';
            joinBtn.disabled = true;
        } else {
            joinBtn.innerHTML = '<i class="fas fa-user-plus"></i> Join Group';
            joinBtn.className = 'action-btn btn-primary';
            joinBtn.onclick = joinGroup;
            joinBtn.disabled = false;
        }
    })
    .catch(err => console.error('Error loading group:', err));
}

/* ================= LOAD MEMBERS ================= */
function loadMembers() {
    fetch(`/backend/get_group_members.php?group_id=${groupId}`)
    .then(res => res.json())
    .then(data => {
        if (data.error) {
            console.error(data.error);
            return;
        }
        
        const membersList = document.getElementById("membersList");
        if (data.length === 0) {
            membersList.innerHTML = '<div style="color: #65676b; text-align: center; padding: 20px;">No members</div>';
            return;
        }
        
        membersList.innerHTML = data.slice(0, 5).map(member => `
            <div class="member-item">
                <img src="${member.profile_image ? '/uploads/' + member.profile_image : '/assets/zuckuser.png'}" 
                     class="member-avatar">
                <div>
                    <div class="member-name">${member.name}</div>
                    <div class="member-role">${member.role === 'admin' ? 'Admin' : 'Member'}</div>
                </div>
            </div>
        `).join('');
    })
    .catch(err => console.error('Error loading members:', err));
}

/* ================= JOIN GROUP ================= */
function joinGroup() {
    const joinBtn = document.getElementById("joinBtn");
    joinBtn.disabled = true;
    joinBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Joining...';
    
    fetch("/backend/join_group.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "group_id=" + groupId
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === "joined" || data.status === "already") {
            joinBtn.innerHTML = '<i class="fas fa-check"></i> Joined';
            joinBtn.className = 'action-btn btn-joined';
            document.getElementById("postBox").style.display = "block";
            loadGroup();
            loadPosts();
        } else if (data.status === "pending") {
            joinBtn.innerHTML = '<i class="fas fa-clock"></i> Pending Approval';
            joinBtn.className = 'action-btn btn-pending';
        } else {
            joinBtn.innerHTML = '<i class="fas fa-user-plus"></i> Join Group';
            joinBtn.className = 'action-btn btn-primary';
            joinBtn.disabled = false;
            alert("Failed to join group");
        }
    })
    .catch(err => {
        console.error('Error joining group:', err);
        joinBtn.innerHTML = '<i class="fas fa-user-plus"></i> Join Group';
        joinBtn.className = 'action-btn btn-primary';
        joinBtn.disabled = false;
        alert("Network error. Please try again.");
    });
}

/* ================= CREATE POST ================= */
function createPost() {
    const content = document.getElementById("postContent").value.trim();
    const submitBtn = document.getElementById("postSubmit");
    
    if (!content) return;
    
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Posting...';
    
    fetch("/backend/create_group_post.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: `group_id=${groupId}&content=${encodeURIComponent(content)}`
    })
    .then(res => res.json())
    .then(data => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Post';
        
        if (data.status === "posted") {
            document.getElementById("postContent").value = "";
            submitBtn.disabled = true;
            loadPosts();
            
            // Show success message
            const postBox = document.getElementById("postBox");
            const successMsg = document.createElement('div');
            successMsg.style.background = '#d4edda';
            successMsg.style.color = '#155724';
            successMsg.style.padding = '12px';
            successMsg.style.borderRadius = '6px';
            successMsg.style.marginTop = '12px';
            successMsg.style.fontSize = '14px';
            successMsg.innerHTML = '<i class="fas fa-check"></i> Post published successfully';
            postBox.appendChild(successMsg);
            
            setTimeout(() => successMsg.remove(), 3000);
        } else {
            alert(data.error || "Failed to create post");
        }
    })
    .catch(err => {
        console.error('Error creating post:', err);
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Post';
        alert("Network error. Please try again.");
    });
}

/* ================= LOAD POSTS ================= */
function loadPosts() {
    fetch(`/backend/get_group_posts.php?group_id=${groupId}`)
    .then(res => res.json())
    .then(data => {
        const container = document.getElementById("posts");
        const emptyState = document.getElementById("emptyState");
        
        if (data.error) {
            if (data.error === "Not a member") {
                container.innerHTML = '';
                emptyState.style.display = 'block';
                emptyState.innerHTML = `
                    <i class="fas fa-lock"></i>
                    <h3>Join to see posts</h3>
                    <p>This group's posts are only visible to members</p>
                `;
            }
            return;
        }
        
        if (!Array.isArray(data) || data.length === 0) {
            container.innerHTML = '';
            emptyState.style.display = 'block';
            return;
        }
        
        emptyState.style.display = 'none';
        container.innerHTML = data.map(post => `
            <div class="post">
                <div class="post-header">
                    <img src="${post.profile_image}" class="post-avatar">
                    <div class="post-user-info">
                        <div class="post-user-name">${post.name}</div>
                        <div class="post-time">${post.created_at}</div>
                    </div>
                </div>
                <div class="post-content">${post.content}</div>
                <div class="post-actions-bar">
                    <button class="post-action">
                        <i class="fas fa-thumbs-up"></i>
                        <span>Like</span>
                    </button>
                    <button class="post-action">
                        <i class="fas fa-comment"></i>
                        <span>Comment</span>
                    </button>
                    <button class="post-action">
                        <i class="fas fa-share"></i>
                        <span>Share</span>
                    </button>
                </div>
            </div>
        `).join('');
    })
    .catch(err => console.error('Error loading posts:', err));
}

// Initialize
loadGroup();
loadPosts();
</script>

</body>
</html>