<?php
session_start();
require_once __DIR__ . "/backend/config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: /");
    exit;
}

$user_id = intval($_SESSION['user_id']);

$userStmt = $conn->prepare("SELECT id, coins, is_verified, name, profile_image, cover_image, cover_position, is_banned, ban_expires_at, bio, tagline, job_title, city, from_city, relationship_status, created_at FROM users WHERE id = ?");
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
$coverImage = $userData['cover_image'] ? "/uploads/" . htmlspecialchars($userData['cover_image']) : "";
$coverPos = $userData['cover_position'] ?? 50;
$userBio = htmlspecialchars($userData['bio'] ?? '');
$userTagline = htmlspecialchars($userData['tagline'] ?? '');
$userJobTitle = htmlspecialchars($userData['job_title'] ?? '');
$userCity = htmlspecialchars($userData['city'] ?? '');
$userFromCity = htmlspecialchars($userData['from_city'] ?? '');
$userRelationship = htmlspecialchars($userData['relationship_status'] ?? '');
$joinDate = $userData['created_at'] ? date('F Y', strtotime($userData['created_at'])) : '';

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
<title>Edit Profile - ZuckBook</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="/assets/dark-mode.css">
<script src="/assets/dark-mode.js"></script>
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

.container { max-width: 1200px; margin: 16px auto; display: grid; grid-template-columns: 280px 1fr 380px; gap: 16px; padding: 0 16px; }

.sidebar { background: white; border-radius: 8px; padding: 8px 0; height: fit-content; position: sticky; top: 60px; }
.sidebar-item { padding: 8px 16px; display: flex; align-items: center; gap: 12px; cursor: pointer; text-decoration: none; color: #050505; font-size: 15px; }
.sidebar-item:hover { background: #f2f2f2; }

.content { background: white; border-radius: 8px; padding: 24px; }
.content-title { font-size: 28px; font-weight: bold; margin-bottom: 24px; }

.form-group { margin-bottom: 20px; }
.form-group label { display: block; font-weight: 600; margin-bottom: 8px; color: #050505; }
.form-group input, .form-group textarea { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; font-family: inherit; font-size: 15px; }
.form-group textarea { resize: vertical; min-height: 100px; }
.form-group input:focus, .form-group textarea:focus { outline: none; border-color: #1877f2; box-shadow: 0 0 0 2px rgba(24, 119, 242, 0.1); }

.profile-preview { display: flex; gap: 16px; margin-bottom: 24px; align-items: center; }
.preview-avatar { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 2px solid #e5e7eb; }
.preview-info { flex: 1; }
.preview-name { font-size: 18px; font-weight: 600; }
.preview-coins { font-size: 14px; color: #65676b; }

.button-group { display: flex; gap: 12px; }
.btn { padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 15px; transition: 0.2s; }
.btn-primary { background: #1877f2; color: white; }
.btn-primary:hover { background: #0a66c2; }
.btn-secondary { background: #e4e6eb; color: #050505; }
.btn-secondary:hover { background: #d0d2d7; }

.right-sidebar { background: white; border-radius: 8px; padding: 16px; height: fit-content; position: sticky; top: 76px; }
.right-sidebar-title { font-weight: 600; font-size: 17px; margin-bottom: 16px; color: #050505; }

/* Live Profile Preview */
.live-preview { background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 2px rgba(0,0,0,0.1); }
.preview-cover { width: 100%; height: 140px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); position: relative; object-fit: cover; }
.preview-profile-section { padding: 0 16px; margin-top: -40px; position: relative; }
.preview-profile-avatar { width: 80px; height: 80px; border-radius: 50%; border: 4px solid white; object-fit: cover; background: #f0f2f5; box-shadow: 0 1px 2px rgba(0,0,0,0.1); }
.preview-profile-name { font-size: 20px; font-weight: bold; margin-top: 8px; color: #050505; display: flex; align-items: center; gap: 6px; }
.preview-verify-badge { width: 18px; height: 18px; border-radius: 50%; background: #1877f2; color: white; display: inline-flex; align-items: center; justify-content: center; font-size: 10px; }
.preview-profile-bio { font-size: 14px; color: #65676b; margin-top: 8px; line-height: 1.4; }
.preview-profile-info { margin-top: 12px; padding-bottom: 16px; }
.preview-info-item { display: flex; align-items: center; gap: 8px; font-size: 14px; color: #050505; margin-bottom: 8px; }
.preview-info-icon { width: 20px; text-align: center; color: #65676b; font-size: 14px; }
.preview-stats { display: flex; justify-content: space-around; padding: 12px 0; border-top: 1px solid #e5e7eb; margin-top: 12px; }
.preview-stat { text-align: center; }
.preview-stat-value { font-size: 16px; font-weight: bold; color: #050505; }
.preview-stat-label { font-size: 12px; color: #65676b; margin-top: 2px; }

.message { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; }
.message.success { background: #d4edda; color: #155724; }
.message.error { background: #f8d7da; color: #721c24; }

@media (max-width: 1024px) {
    .container { grid-template-columns: 1fr; }
    .sidebar, .right-sidebar { display: none; }
}

@media (max-width: 768px) {
    .header-content { padding: 0 8px; }
    .search-box input { width: 180px; }
    .content { padding: 16px; }
    .button-group { flex-direction: column; }
    .btn { width: 100%; justify-content: center; }
}

/* Dark Mode Support */
.dark-mode .live-preview,
.dark-mode .preview-profile-avatar {
    border-color: var(--border-color) !important;
}

.dark-mode .preview-profile-name,
.dark-mode .preview-stat-value,
.dark-mode .preview-info-item {
    color: var(--text-primary) !important;
}

.dark-mode .preview-profile-bio,
.dark-mode .preview-stat-label,
.dark-mode .preview-info-icon {
    color: var(--text-secondary) !important;
}

.dark-mode .preview-stats {
    border-top-color: var(--border-color) !important;
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
        <a href="/coins.php" class="sidebar-item"><i class="fas fa-coins"></i> Coins (<?= $userCoins ?>)</a>
        <a href="/notifications.php" class="sidebar-item"><i class="fas fa-bell"></i> Notifications (<?= $unread ?>)</a>
        <a href="/settings.php" class="sidebar-item"><i class="fas fa-cog"></i> Settings</a>
        <a href="/backend/logout.php" class="sidebar-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
    
    <div class="content">
        <div class="content-title"><i class="fas fa-edit"></i> Edit Profile</div>
        
        <div id="message"></div>

        <div class="profile-preview">
            <img src="<?= $userImage ?>" class="preview-avatar" id="previewImage">
            <div class="preview-info">
                <div class="preview-name" id="previewName"><?= $userName ?></div>
                <div class="preview-coins"><i class="fas fa-coins"></i> <?= $userCoins ?> Coins</div>
            </div>
        </div>

        <form id="editForm">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" value="<?= $userName ?>" required>
            </div>

            <div class="form-group">
                <label for="tagline">Tagline (Short Bio)</label>
                <input type="text" id="tagline" name="tagline" value="<?= $userTagline ?>" placeholder="e.g., Digital Content Creator" maxlength="255">
                <small style="color: #65676b;">Max 255 characters</small>
            </div>

            <div class="form-group">
                <label for="bio">Bio</label>
                <textarea id="bio" name="bio" placeholder="Tell us about yourself..." maxlength="1000"><?= $userBio ?></textarea>
                <small style="color: #65676b;">Max 1000 characters</small>
            </div>

            <div class="form-group">
                <label for="jobTitle">Job Title</label>
                <input type="text" id="jobTitle" name="jobTitle" value="<?= $userJobTitle ?>" placeholder="e.g., Software Developer" maxlength="100">
            </div>

            <div class="form-group">
                <label for="city">Current City</label>
                <input type="text" id="city" name="city" value="<?= $userCity ?>" placeholder="e.g., Cairo" maxlength="100">
            </div>

            <div class="form-group">
                <label for="fromCity">From City</label>
                <input type="text" id="fromCity" name="fromCity" value="<?= $userFromCity ?>" placeholder="e.g., Alexandria" maxlength="100">
            </div>

            <div class="form-group">
                <label for="relationship">Relationship Status</label>
                <select id="relationship" name="relationship" style="width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; font-family: inherit; font-size: 15px;">
                    <option value="">Not specified</option>
                    <option value="Single" <?= $userRelationship === 'Single' ? 'selected' : '' ?>>Single</option>
                    <option value="In a relationship" <?= $userRelationship === 'In a relationship' ? 'selected' : '' ?>>In a relationship</option>
                    <option value="Engaged" <?= $userRelationship === 'Engaged' ? 'selected' : '' ?>>Engaged</option>
                    <option value="Married" <?= $userRelationship === 'Married' ? 'selected' : '' ?>>Married</option>
                    <option value="It's complicated" <?= $userRelationship === "It's complicated" ? 'selected' : '' ?>>It's complicated</option>
                </select>
            </div>

            <div class="form-group">
                <label for="profileImage">Profile Picture</label>
                <input type="file" id="profileImage" name="profileImage" accept="image/*">
                <small style="color: #65676b;">Max 5MB. Formats: JPG, PNG, GIF, WebP</small>
            </div>

            <div class="form-group">
                <label for="coverImage">Cover Photo</label>
                <input type="file" id="coverImage" name="coverImage" accept="image/*">
                <small style="color: #65676b;">Max 5MB. Formats: JPG, PNG, GIF, WebP</small>
            </div>

            <div class="form-group">
                <label for="coverPosition">Cover Position (%)</label>
                <input type="range" id="coverPosition" name="coverPosition" min="0" max="100" value="<?= $coverPos ?>" style="width: 100%; padding: 0;">
                <small style="color: #65676b;">Adjust where the cover photo is positioned</small>
            </div>

            <div class="button-group">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                <button type="button" class="btn btn-secondary" onclick="window.location.href='/profile.php?id=<?= $user_id ?>'">Cancel</button>
            </div>
        </form>
    </div>
    
    <div class="right-sidebar">
        <div class="right-sidebar-title"><i class="fas fa-eye"></i> Profile Preview</div>
        
        <div class="live-preview">
            <img src="<?= $coverImage ?: '' ?>" class="preview-cover" id="previewCover" style="<?= $coverImage ? '' : 'display: block;' ?> object-position: center <?= $coverPos ?>%;">
            
            <div class="preview-profile-section">
                <img src="<?= $userImage ?>" class="preview-profile-avatar" id="previewAvatar">
                
                <div class="preview-profile-name" id="previewProfileName">
                    <?= $userName ?>
                    <?php if($userData['is_verified'] == 1): ?>
                        <span class="preview-verify-badge">
                            <i class="fas fa-check"></i>
                        </span>
                    <?php endif; ?>
                </div>
                
                <div class="preview-profile-bio" id="previewBio" style="<?= $userBio ? '' : 'display: none;' ?>">
                    <?= $userBio ?>
                </div>
                
                <div class="preview-profile-info">
                    <div class="preview-info-item" id="previewTagline" style="<?= $userTagline ? '' : 'display: none;' ?>">
                        <i class="fas fa-quote-left preview-info-icon"></i>
                        <span id="previewTaglineText"><?= $userTagline ?></span>
                    </div>
                    
                    <div class="preview-info-item" id="previewJob" style="<?= $userJobTitle ? '' : 'display: none;' ?>">
                        <i class="fas fa-briefcase preview-info-icon"></i>
                        <span id="previewJobText"><?= $userJobTitle ?></span>
                    </div>
                    
                    <div class="preview-info-item" id="previewCity" style="<?= $userCity ? '' : 'display: none;' ?>">
                        <i class="fas fa-map-marker-alt preview-info-icon"></i>
                        <span>Lives in <span id="previewCityText"><?= $userCity ?></span></span>
                    </div>
                    
                    <div class="preview-info-item" id="previewFrom" style="<?= $userFromCity ? '' : 'display: none;' ?>">
                        <i class="fas fa-home preview-info-icon"></i>
                        <span>From <span id="previewFromText"><?= $userFromCity ?></span></span>
                    </div>
                    
                    <div class="preview-info-item" id="previewRelationship" style="<?= $userRelationship ? '' : 'display: none;' ?>">
                        <i class="fas fa-heart preview-info-icon"></i>
                        <span id="previewRelationshipText"><?= $userRelationship ?></span>
                    </div>
                </div>
                
                <div class="preview-stats">
                    <div class="preview-stat">
                        <div class="preview-stat-value">0</div>
                        <div class="preview-stat-label">Posts</div>
                    </div>
                    <div class="preview-stat">
                        <div class="preview-stat-value">0</div>
                        <div class="preview-stat-label">Followers</div>
                    </div>
                    <div class="preview-stat">
                        <div class="preview-stat-value">0</div>
                        <div class="preview-stat-label">Following</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div style="margin-top: 16px; padding: 12px; background: #e7f3ff; border-radius: 8px; font-size: 13px; color: #1877f2;">
            <i class="fas fa-info-circle"></i> Changes preview in real-time
        </div>
    </div>
</div>

<script>
// Real-time preview updates
document.getElementById('name').addEventListener('input', (e) => {
    document.getElementById('previewName').textContent = e.target.value || '<?= $userName ?>';
    document.getElementById('previewProfileName').childNodes[0].textContent = e.target.value || '<?= $userName ?>';
});

document.getElementById('tagline').addEventListener('input', (e) => {
    const taglineEl = document.getElementById('previewTagline');
    const taglineText = document.getElementById('previewTaglineText');
    if (e.target.value.trim()) {
        taglineText.textContent = e.target.value;
        taglineEl.style.display = 'flex';
    } else {
        taglineEl.style.display = 'none';
    }
});

document.getElementById('bio').addEventListener('input', (e) => {
    const bioEl = document.getElementById('previewBio');
    if (e.target.value.trim()) {
        bioEl.textContent = e.target.value;
        bioEl.style.display = 'block';
    } else {
        bioEl.style.display = 'none';
    }
});

document.getElementById('jobTitle').addEventListener('input', (e) => {
    const jobEl = document.getElementById('previewJob');
    const jobText = document.getElementById('previewJobText');
    if (e.target.value.trim()) {
        jobText.textContent = e.target.value;
        jobEl.style.display = 'flex';
    } else {
        jobEl.style.display = 'none';
    }
});

document.getElementById('city').addEventListener('input', (e) => {
    const cityEl = document.getElementById('previewCity');
    const cityText = document.getElementById('previewCityText');
    if (e.target.value.trim()) {
        cityText.textContent = e.target.value;
        cityEl.style.display = 'flex';
    } else {
        cityEl.style.display = 'none';
    }
});

document.getElementById('fromCity').addEventListener('input', (e) => {
    const fromEl = document.getElementById('previewFrom');
    const fromText = document.getElementById('previewFromText');
    if (e.target.value.trim()) {
        fromText.textContent = e.target.value;
        fromEl.style.display = 'flex';
    } else {
        fromEl.style.display = 'none';
    }
});

document.getElementById('relationship').addEventListener('change', (e) => {
    const relEl = document.getElementById('previewRelationship');
    const relText = document.getElementById('previewRelationshipText');
    if (e.target.value) {
        relText.textContent = e.target.value;
        relEl.style.display = 'flex';
    } else {
        relEl.style.display = 'none';
    }
});

document.getElementById('coverPosition').addEventListener('input', (e) => {
    const coverEl = document.getElementById('previewCover');
    coverEl.style.objectPosition = `center ${e.target.value}%`;
});

document.getElementById('profileImage').addEventListener('change', (e) => {
    if (e.target.files.length > 0) {
        const reader = new FileReader();
        reader.onload = (event) => {
            document.getElementById('previewImage').src = event.target.result;
            document.getElementById('previewAvatar').src = event.target.result;
        };
        reader.readAsDataURL(e.target.files[0]);
    }
});

document.getElementById('coverImage').addEventListener('change', (e) => {
    if (e.target.files.length > 0) {
        const reader = new FileReader();
        reader.onload = (event) => {
            const coverEl = document.getElementById('previewCover');
            coverEl.src = event.target.result;
            coverEl.style.display = 'block';
        };
        reader.readAsDataURL(e.target.files[0]);
    }
});

document.getElementById('editForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('name', document.getElementById('name').value);
    formData.append('tagline', document.getElementById('tagline').value);
    formData.append('bio', document.getElementById('bio').value);
    formData.append('jobTitle', document.getElementById('jobTitle').value);
    formData.append('city', document.getElementById('city').value);
    formData.append('fromCity', document.getElementById('fromCity').value);
    formData.append('relationship', document.getElementById('relationship').value);
    formData.append('coverPosition', document.getElementById('coverPosition').value);
    
    if (document.getElementById('profileImage').files.length > 0) {
        formData.append('profileImage', document.getElementById('profileImage').files[0]);
    }
    
    if (document.getElementById('coverImage').files.length > 0) {
        formData.append('coverImage', document.getElementById('coverImage').files[0]);
    }

    try {
        const response = await fetch('/backend/update_profile.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        const messageDiv = document.getElementById('message');

        if (data.status === 'success') {
            messageDiv.innerHTML = '<div class="message success"><i class="fas fa-check-circle"></i> Profile updated successfully!</div>';
            document.getElementById('previewName').textContent = data.name;
            if (data.profileImage) {
                document.getElementById('previewImage').src = data.profileImage + '?t=' + Date.now();
            }
            setTimeout(() => {
                window.location.href = '/profile.php?id=<?= $user_id ?>';
            }, 2000);
        } else {
            messageDiv.innerHTML = '<div class="message error"><i class="fas fa-times"></i> ' + (data.error || 'Error updating profile') + '</div>';
        }
    } catch (err) {
        document.getElementById('message').innerHTML = '<div class="message error"><i class="fas fa-times"></i> Error: ' + err.message + '</div>';
    }
});
</script>

</body>
</html>
