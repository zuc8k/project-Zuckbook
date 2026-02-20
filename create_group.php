<?php
require_once __DIR__ . "/backend/config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: /");
    exit;
}

$user_id = intval($_SESSION['user_id']);

$stmt = $conn->prepare("SELECT id, name, profile_image FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$userData = $stmt->get_result()->fetch_assoc();

$userName = htmlspecialchars($userData['name']);
$userImage = $userData['profile_image'] ? "/uploads/" . htmlspecialchars($userData['profile_image']) : "/assets/zuckuser.png";
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Group - ZuckBook</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="/assets/dark-mode.css">
<script src="/assets/dark-mode.js"></script>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f0f2f5; }

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

.container {
    max-width: 800px;
    margin: 30px auto;
    padding: 0 16px;
}

.create-card {
    background: white;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.create-title {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 30px;
    color: #050505;
    text-align: center;
}

.form-group {
    margin-bottom: 24px;
}

.form-label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
    color: #050505;
    font-size: 15px;
}

.form-input {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 15px;
    transition: all 0.2s ease;
    background: white;
    color: #050505;
}

.form-input:focus {
    outline: none;
    border-color: #1877f2;
    box-shadow: 0 0 0 3px rgba(24, 119, 242, 0.1);
}

.form-input::placeholder {
    color: #8a8d91;
}

textarea.form-input {
    min-height: 120px;
    resize: vertical;
    font-family: inherit;
    line-height: 1.5;
}

.form-hint {
    font-size: 13px;
    color: #65676b;
    margin-top: 6px;
    line-height: 1.4;
}

.upload-area {
    border: 2px dashed #ddd;
    border-radius: 12px;
    padding: 40px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background: #f7f8fa;
}

.upload-area:hover {
    border-color: #1877f2;
    background: #e7f3ff;
    transform: translateY(-2px);
}

.upload-icon {
    font-size: 48px;
    color: #1877f2;
    margin-bottom: 16px;
    transition: transform 0.3s ease;
}

.upload-area:hover .upload-icon {
    transform: scale(1.1);
}

.upload-text {
    font-size: 16px;
    color: #050505;
    margin-bottom: 8px;
    font-weight: 600;
}

.upload-subtext {
    font-size: 14px;
    color: #65676b;
}

.upload-preview {
    margin-top: 20px;
    display: none;
}

.preview-image {
    max-width: 200px;
    max-height: 200px;
    border-radius: 12px;
    object-fit: cover;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.visibility-options {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
    margin-top: 12px;
}

.visibility-option {
    border: 2px solid #e4e6eb;
    border-radius: 12px;
    padding: 16px;
    cursor: pointer;
    transition: all 0.2s ease;
    background: white;
}

.visibility-option:hover {
    border-color: #1877f2;
    background: #f0f8ff;
    transform: translateY(-2px);
    box-shadow: 0 2px 8px rgba(24, 119, 242, 0.1);
}

.visibility-option.selected {
    border-color: #1877f2;
    background: #e7f3ff;
    box-shadow: 0 0 0 3px rgba(24, 119, 242, 0.1);
}

.visibility-icon {
    font-size: 24px;
    color: #1877f2;
    margin-bottom: 8px;
}

.visibility-title {
    font-weight: 600;
    margin-bottom: 4px;
    color: #050505;
    font-size: 15px;
}

.visibility-desc {
    font-size: 13px;
    color: #65676b;
    line-height: 1.4;
}

.form-actions {
    display: flex;
    gap: 12px;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e4e6eb;
}

.btn-primary {
    flex: 1;
    background: #1877f2;
    color: white;
    border: none;
    padding: 14px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-primary:hover {
    background: #166fe5;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(24, 119, 242, 0.3);
}

.btn-primary:active {
    transform: translateY(0);
}

.btn-secondary {
    flex: 1;
    background: #e4e6eb;
    color: #050505;
    border: none;
    padding: 14px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.btn-secondary:hover {
    background: #d8dadf;
    transform: translateY(-2px);
}

.btn-secondary:active {
    transform: translateY(0);
}
    background: #166fe5;
}

.btn-secondary {
    flex: 1;
    background: #e4e6eb;
    color: #050505;
    border: none;
    padding: 14px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 16px;
    cursor: pointer;
    transition: background 0.2s;
}

.btn-secondary:hover {
    background: #d8dadf;
}

.error-message {
    background: #fee;
    color: #c00;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: none;
}

.success-message {
    background: #dfd;
    color: #080;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: none;
}

@media (max-width: 600px) {
    .container {
        margin: 16px auto;
    }
    .create-card {
        padding: 20px;
    }
    .form-actions {
        flex-direction: column;
    }
}
</style>
</head>
<body>

<div class="header">
    <div class="header-content">
        <div class="logo" onclick="window.location.href='/home.php'">
          <i class="fa-brands fa-zulip"></i>
            <span>ZuckBook</span>
        </div>
        
        <div class="header-icons">
            <button class="header-icon" onclick="window.location.href='/home.php'" title="Home">
                <i class="fas fa-home"></i>
            </button>
            <button class="header-icon" onclick="window.location.href='/groups.php'" title="Groups">
                <i class="fas fa-users"></i>
            </button>
            <img src="<?= $userImage ?>" class="user-avatar" 
                 onclick="window.location.href='/profile.php?id=<?= $user_id ?>'" 
                 title="Profile">
        </div>
    </div>
</div>

<div class="container">
    <div class="create-card">
        <div class="create-title">
            <i class="fas fa-plus-circle"></i>
            Create New Group
        </div>
        
        <div id="errorMessage" class="error-message"></div>
        <div id="successMessage" class="success-message"></div>
        
        <form id="createGroupForm">
            <div class="form-group">
                <label class="form-label">Group Name *</label>
                <input type="text" class="form-input" id="groupName" 
                       placeholder="Enter group name" required>
                <div class="form-hint">Choose a descriptive name for your group</div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea class="form-input" id="groupDescription" 
                          placeholder="What is this group about?"></textarea>
                <div class="form-hint">Describe the purpose and topics of your group</div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Group Image</label>
                <div class="upload-area" id="imageUpload">
                    <div class="upload-icon">
                        <i class="fas fa-image"></i>
                    </div>
                    <div class="upload-text">Upload Group Image</div>
                    <div class="upload-subtext">Recommended: 400x400 pixels</div>
                    <input type="file" id="imageFile" accept="image/*" style="display: none;">
                </div>
                <div class="upload-preview" id="imagePreview">
                    <img id="previewImage" class="preview-image">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Cover Image</label>
                <div class="upload-area" id="coverUpload">
                    <div class="upload-icon">
                        <i class="fas fa-cover"></i>
                    </div>
                    <div class="upload-text">Upload Cover Image</div>
                    <div class="upload-subtext">Recommended: 1200x400 pixels</div>
                    <input type="file" id="coverFile" accept="image/*" style="display: none;">
                </div>
                <div class="upload-preview" id="coverPreview">
                    <img id="previewCover" class="preview-image">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Visibility *</label>
                <div class="visibility-options">
                    <div class="visibility-option selected" data-value="public" onclick="selectVisibility(this)">
                        <div class="visibility-icon">
                            <i class="fas fa-globe"></i>
                        </div>
                        <div class="visibility-title">Public</div>
                        <div class="visibility-desc">Anyone can see the group and its posts</div>
                    </div>
                    
                    <div class="visibility-option" data-value="private" onclick="selectVisibility(this)">
                        <div class="visibility-icon">
                            <i class="fas fa-lock"></i>
                        </div>
                        <div class="visibility-title">Private</div>
                        <div class="visibility-desc">Only members can see posts</div>
                    </div>
                    
                    <div class="visibility-option" data-value="secret" onclick="selectVisibility(this)">
                        <div class="visibility-icon">
                            <i class="fas fa-eye-slash"></i>
                        </div>
                        <div class="visibility-title">Secret</div>
                        <div class="visibility-desc">Hidden from search, invite only</div>
                    </div>
                </div>
                <input type="hidden" id="visibility" value="public">
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="window.location.href='/groups.php'">
                    Cancel
                </button>
                <button type="submit" class="btn-primary" id="submitBtn">
                    <i class="fas fa-plus"></i> Create Group
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let selectedVisibility = 'public';
let imageFile = null;
let coverFile = null;

function selectVisibility(element) {
    document.querySelectorAll('.visibility-option').forEach(el => {
        el.classList.remove('selected');
    });
    element.classList.add('selected');
    selectedVisibility = element.getAttribute('data-value');
    document.getElementById('visibility').value = selectedVisibility;
}

// Image upload handling
document.getElementById('imageUpload').addEventListener('click', () => {
    document.getElementById('imageFile').click();
});

document.getElementById('coverUpload').addEventListener('click', () => {
    document.getElementById('coverFile').click();
});

document.getElementById('imageFile').addEventListener('change', function(e) {
    if (this.files && this.files[0]) {
        imageFile = this.files[0];
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('previewImage').src = e.target.result;
            document.getElementById('imagePreview').style.display = 'block';
        }
        reader.readAsDataURL(this.files[0]);
    }
});

document.getElementById('coverFile').addEventListener('change', function(e) {
    if (this.files && this.files[0]) {
        coverFile = this.files[0];
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('previewCover').src = e.target.result;
            document.getElementById('coverPreview').style.display = 'block';
        }
        reader.readAsDataURL(this.files[0]);
    }
});

// Form submission
document.getElementById('createGroupForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const groupName = document.getElementById('groupName').value.trim();
    const groupDescription = document.getElementById('groupDescription').value.trim();
    
    if (!groupName) {
        showError('Group name is required');
        return;
    }
    
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
    
    const formData = new FormData();
    formData.append('name', groupName);
    formData.append('description', groupDescription);
    formData.append('visibility', selectedVisibility);
    
    if (imageFile) {
        formData.append('image', imageFile);
    }
    
    if (coverFile) {
        formData.append('cover_image', coverFile);
    }
    
    try {
        const response = await fetch('/backend/create_group.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.status === 'created') {
            showSuccess('Group created successfully! Redirecting...');
            setTimeout(() => {
                window.location.href = `/group.php?id=${data.group_id}`;
            }, 1500);
        } else {
            showError(data.error || 'Failed to create group');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-plus"></i> Create Group';
        }
    } catch (error) {
        showError('Network error. Please try again.');
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-plus"></i> Create Group';
    }
});

function showError(message) {
    const errorDiv = document.getElementById('errorMessage');
    errorDiv.textContent = message;
    errorDiv.style.display = 'block';
    document.getElementById('successMessage').style.display = 'none';
}

function showSuccess(message) {
    const successDiv = document.getElementById('successMessage');
    successDiv.textContent = message;
    successDiv.style.display = 'block';
    document.getElementById('errorMessage').style.display = 'none';
}
</script>

</body>
</html>