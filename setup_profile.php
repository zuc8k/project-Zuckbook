<?php
session_start();
require_once __DIR__ . "/backend/config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: /");
    exit;
}

$user_id = intval($_SESSION['user_id']);

// Check if user already completed setup
$stmt = $conn->prepare("SELECT profile_setup_completed FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

if ($result['profile_setup_completed'] == 1) {
    header("Location: /home.php");
    exit;
}

// Get current step
$step = isset($_GET['step']) ? intval($_GET['step']) : 1;
if ($step < 1) $step = 1;
if ($step > 2) $step = 2;

// Get user data
$stmt = $conn->prepare("SELECT name, bio, profile_image FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$userData = $stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Complete Your Profile - ZuckBook</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { 
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.setup-container {
    background: white;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    max-width: 500px;
    width: 100%;
    overflow: hidden;
}

.setup-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 32px 24px;
    text-align: center;
    color: white;
}

.setup-logo {
    font-size: 48px;
    font-weight: bold;
    margin-bottom: 8px;
}

.setup-title {
    font-size: 24px;
    font-weight: 600;
    margin-bottom: 8px;
}

.setup-subtitle {
    font-size: 14px;
    opacity: 0.9;
}

.progress-bar {
    height: 4px;
    background: rgba(255,255,255,0.3);
    position: relative;
}

.progress-fill {
    height: 100%;
    background: white;
    transition: width 0.3s;
}

.setup-body {
    padding: 32px 24px;
}

.step-indicator {
    display: flex;
    justify-content: center;
    gap: 12px;
    margin-bottom: 32px;
}

.step-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #e0e0e0;
    transition: all 0.3s;
}

.step-dot.active {
    background: #667eea;
    transform: scale(1.3);
}

.step-dot.completed {
    background: #42b72a;
}

.form-group {
    margin-bottom: 24px;
}

.form-label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
}

.form-input {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 15px;
    transition: border-color 0.3s;
    font-family: inherit;
}

.form-input:focus {
    outline: none;
    border-color: #667eea;
}

.form-textarea {
    min-height: 120px;
    resize: vertical;
}

.char-counter {
    text-align: right;
    font-size: 12px;
    color: #999;
    margin-top: 4px;
}

.avatar-upload {
    text-align: center;
    margin-bottom: 24px;
}

.avatar-preview {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    margin: 0 auto 16px;
    display: block;
    border: 4px solid #f0f0f0;
}

.upload-btn {
    background: #f0f0f0;
    color: #333;
    border: none;
    padding: 10px 24px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.3s;
}

.upload-btn:hover {
    background: #e0e0e0;
}

.upload-btn i {
    margin-right: 8px;
}

.button-group {
    display: flex;
    gap: 12px;
    margin-top: 32px;
}

.btn {
    flex: 1;
    padding: 14px 24px;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
}

.btn-secondary {
    background: #f0f0f0;
    color: #333;
}

.btn-secondary:hover {
    background: #e0e0e0;
}

.skip-link {
    text-align: center;
    margin-top: 16px;
}

.skip-link a {
    color: #999;
    text-decoration: none;
    font-size: 14px;
}

.skip-link a:hover {
    color: #667eea;
}

.success-message {
    display: none;
    text-align: center;
    padding: 32px;
}

.success-icon {
    font-size: 64px;
    color: #42b72a;
    margin-bottom: 16px;
}

.success-title {
    font-size: 24px;
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
}

.success-text {
    color: #666;
    margin-bottom: 24px;
}

@media (max-width: 768px) {
    .setup-container {
        border-radius: 0;
    }
    
    .setup-body {
        padding: 24px 16px;
    }
}
</style>
</head>
<body>

<div class="setup-container">
    <div class="setup-header">
        <div class="setup-logo">f</div>
        <div class="setup-title">Complete Your Profile</div>
        <div class="setup-subtitle">Let's set up your account</div>
    </div>
    
    <div class="progress-bar">
        <div class="progress-fill" style="width: <?= ($step / 2) * 100 ?>%;"></div>
    </div>
    
    <div class="setup-body">
        <div class="step-indicator">
            <div class="step-dot <?= $step >= 1 ? 'active' : '' ?> <?= $step > 1 ? 'completed' : '' ?>"></div>
            <div class="step-dot <?= $step >= 2 ? 'active' : '' ?>"></div>
        </div>
        
        <?php if ($step == 1): ?>
        <!-- Step 1: Name -->
        <form id="step1-form">
            <div class="form-group">
                <label class="form-label">What's your name?</label>
                <input type="text" 
                       id="name-input" 
                       class="form-input" 
                       placeholder="Enter your full name"
                       value="<?= htmlspecialchars($userData['name']) ?>"
                       required>
            </div>
            
            <div class="button-group">
                <button type="submit" class="btn btn-primary">
                    Next <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </form>
        
        <?php elseif ($step == 2): ?>
        <!-- Step 2: Bio & Avatar -->
        <form id="step2-form">
            <div class="avatar-upload">
                <img id="avatar-preview" 
                     src="<?= $userData['profile_image'] ? '/uploads/' . htmlspecialchars($userData['profile_image']) : '/assets/zuckuser.png' ?>" 
                     class="avatar-preview"
                     alt="Profile Picture">
                <input type="file" id="avatar-input" accept="image/*" style="display:none;" onchange="previewAvatar(this)">
                <button type="button" class="upload-btn" onclick="document.getElementById('avatar-input').click()">
                    <i class="fas fa-camera"></i> Upload Photo
                </button>
            </div>
            
            <div class="form-group">
                <label class="form-label">Tell us about yourself (Optional)</label>
                <textarea id="bio-input" 
                          class="form-input form-textarea" 
                          placeholder="Write a short bio..."
                          maxlength="200"
                          oninput="updateCharCounter()"><?= htmlspecialchars($userData['bio'] ?? '') ?></textarea>
                <div class="char-counter">
                    <span id="char-count">0</span>/200
                </div>
            </div>
            
            <div class="button-group">
                <button type="button" class="btn btn-secondary" onclick="window.location.href='?step=1'">
                    <i class="fas fa-arrow-left"></i> Back
                </button>
                <button type="submit" class="btn btn-primary">
                    Complete <i class="fas fa-check"></i>
                </button>
            </div>
            
            <div class="skip-link">
                <a href="#" onclick="skipSetup(); return false;">Skip for now</a>
            </div>
        </form>
        <?php endif; ?>
        
        <!-- Success Message -->
        <div class="success-message" id="success-message">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="success-title">All Set!</div>
            <div class="success-text">Your profile is ready. Let's get started!</div>
            <button class="btn btn-primary" onclick="window.location.href='/home.php'">
                Go to Home <i class="fas fa-arrow-right"></i>
            </button>
        </div>
    </div>
</div>

<script>
// Step 1: Save Name
document.getElementById('step1-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const name = document.getElementById('name-input').value.trim();
    
    if (!name) {
        alert('Please enter your name');
        return;
    }
    
    const formData = new FormData();
    formData.append('name', name);
    
    fetch('/backend/setup_save_name.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            window.location.href = '?step=2';
        } else {
            alert(data.error || 'Failed to save name');
        }
    })
    .catch(err => {
        console.error(err);
        alert('An error occurred');
    });
});

// Step 2: Save Bio & Avatar
document.getElementById('step2-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const bio = document.getElementById('bio-input').value.trim();
    const avatarFile = document.getElementById('avatar-input').files[0];
    
    const formData = new FormData();
    formData.append('bio', bio);
    if (avatarFile) {
        formData.append('avatar', avatarFile);
    }
    formData.append('complete_setup', '1');
    
    fetch('/backend/setup_save_profile.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showSuccess();
        } else {
            alert(data.error || 'Failed to save profile');
        }
    })
    .catch(err => {
        console.error(err);
        alert('An error occurred');
    });
});

// Preview Avatar
function previewAvatar(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('avatar-preview').src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
}

// Update Character Counter
function updateCharCounter() {
    const bio = document.getElementById('bio-input').value;
    document.getElementById('char-count').textContent = bio.length;
}

// Show Success
function showSuccess() {
    document.querySelector('.setup-body > form').style.display = 'none';
    document.querySelector('.step-indicator').style.display = 'none';
    document.getElementById('success-message').style.display = 'block';
    
    setTimeout(() => {
        window.location.href = '/home.php';
    }, 2000);
}

// Skip Setup
function skipSetup() {
    if (confirm('Are you sure you want to skip? You can complete your profile later.')) {
        fetch('/backend/setup_skip.php', { method: 'POST' })
        .then(() => {
            window.location.href = '/home.php';
        });
    }
}

// Initialize
updateCharCounter();
</script>

</body>
</html>
