<?php
session_start();
require_once __DIR__ . "/backend/config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: /");
    exit;
}

if (!isset($_GET['user'])) {
    die("No user selected");
}

$current_user = intval($_SESSION['user_id']);
$chat_user = intval($_GET['user']);

$userStmt = $conn->prepare("SELECT id, name, profile_image FROM users WHERE id = ?");
$userStmt->bind_param("i", $current_user);
$userStmt->execute();
$userData = $userStmt->get_result()->fetch_assoc();

if (!$userData) {
    session_destroy();
    exit;
}

$userName = htmlspecialchars($userData['name']);
$userImage = $userData['profile_image'] ? "/uploads/" . htmlspecialchars($userData['profile_image']) : "/assets/zuckuser.png";

$stmt = $conn->prepare("SELECT id, name, username, profile_image, last_seen, TIMESTAMPDIFF(MINUTE, last_seen, NOW()) as minutes_ago FROM users WHERE id = ?");
$stmt->bind_param("i", $chat_user);
$stmt->execute();
$chatUserData = $stmt->get_result()->fetch_assoc();

if (!$chatUserData) {
    die("User not found");
}

$chatUserName = htmlspecialchars($chatUserData['name']);
$chatUserUsername = htmlspecialchars($chatUserData['username'] ?? '');

// Check if profile image exists
$chatUserImage = "/assets/zuckuser.png";
if ($chatUserData['profile_image']) {
    $imagePath = __DIR__ . "/uploads/" . $chatUserData['profile_image'];
    if (file_exists($imagePath)) {
        $chatUserImage = "/uploads/" . htmlspecialchars($chatUserData['profile_image']);
    }
}

$minutes_ago = intval($chatUserData['minutes_ago']);
$isOnline = $minutes_ago < 5;

if ($isOnline) {
    $statusText = 'Active now';
} else if ($minutes_ago < 1) {
    $statusText = "Active just now";
} else if ($minutes_ago < 60) {
    $statusText = "Active {$minutes_ago}m ago";
} else if ($minutes_ago < 1440) {
    $hours = floor($minutes_ago / 60);
    $statusText = "Active {$hours}h ago";
} else if ($minutes_ago < 10080) {
    $days = floor($minutes_ago / 1440);
    $statusText = "Active {$days}d ago";
} else {
    $statusText = "Active a while ago";
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $chatUserName ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="/assets/avatar-placeholder.js"></script>
<script src="/assets/online-status.js"></script>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #000; height: 100vh; display: flex; flex-direction: column; overflow: hidden; color: #fff; }

/* Header */
.header { background: #1c1e21; padding: 10px 16px; display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #2f3031; }
.header-left { display: flex; align-items: center; gap: 12px; flex: 1; }
.back-btn { background: none; border: none; color: #0a7cff; font-size: 22px; cursor: pointer; padding: 4px; }
.chat-user-info { display: flex; align-items: center; gap: 10px; flex: 1; cursor: pointer; }
.chat-avatar-wrapper { position: relative; }
.chat-avatar { width: 36px; height: 36px; border-radius: 50%; object-fit: cover; }
.online-dot { position: absolute; bottom: 0; right: 0; width: 12px; height: 12px; background: #31a24c; border: 2px solid #1c1e21; border-radius: 50%; }
.online-indicator { position: absolute; bottom: 0; right: 0; width: 12px; height: 12px; background: #31a24c; border: 2px solid #1c1e21; border-radius: 50%; }
.user-status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 4px; }
.chat-user-details { flex: 1; }
.chat-user-name { color: #e4e6eb; font-size: 16px; font-weight: 600; }
.chat-user-status { color: #b0b3b8; font-size: 12px; }
.header-actions { display: flex; gap: 16px; }
.header-btn { background: none; border: none; color: #0a7cff; font-size: 22px; cursor: pointer; padding: 4px; }

/* Profile Card */
.profile-card { background: #1c1e21; padding: 32px 20px; text-align: center; border-bottom: 1px solid #2f3031; }
.profile-avatar-large { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; margin: 0 auto 12px; display: block; }
.profile-name { color: #e4e6eb; font-size: 18px; font-weight: 600; margin-bottom: 4px; }
.profile-username { color: #b0b3b8; font-size: 14px; margin-bottom: 8px; }
.profile-status { color: #b0b3b8; font-size: 13px; }
.view-profile-btn { background: #3a3b3c; color: #e4e6eb; border: none; padding: 8px 20px; border-radius: 20px; margin-top: 12px; cursor: pointer; font-size: 14px; font-weight: 600; }
.view-profile-btn:hover { background: #4e4f50; }
.encryption-notice { color: #8a8d91; font-size: 11px; margin-top: 16px; line-height: 1.4; }
.encryption-notice a { color: #0a7cff; text-decoration: none; }

/* Messages */
.messages-wrapper { flex: 1; overflow-y: auto; padding: 16px; display: flex; flex-direction: column; gap: 2px; }
.message { display: flex; gap: 6px; align-items: flex-end; margin-bottom: 2px; max-width: 70%; animation: fadeIn 0.2s; }
.message.sent { align-self: flex-end; flex-direction: row-reverse; }
.message.received { align-self: flex-start; }
.message-avatar { width: 28px; height: 28px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }
.message.sent .message-avatar { display: none; }
.message-bubble { padding: 8px 12px; border-radius: 18px; word-wrap: break-word; position: relative; }
.message.sent .message-bubble { background: #0a7cff; color: #fff; border-bottom-right-radius: 4px; }
.message.received .message-bubble { background: #262626; color: #e4e6eb; border-bottom-left-radius: 4px; }
.message-text { font-size: 15px; line-height: 1.4; }
.message-time { font-size: 11px; opacity: 0.6; margin-top: 4px; }

/* Media Messages */
.message-media { max-width: 250px; border-radius: 18px; overflow: hidden; cursor: pointer; margin-bottom: 4px; }
.message-media img, .message-media video { width: 100%; display: block; }

/* Voice Message */
.voice-message { display: flex; align-items: center; gap: 10px; padding: 8px 12px; min-width: 200px; background: #262626; border-radius: 18px; }
.message.sent .voice-message { background: #0a7cff; }
.voice-play-btn { width: 32px; height: 32px; border-radius: 50%; background: rgba(255,255,255,0.2); border: none; color: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 14px; flex-shrink: 0; }
.voice-waveform { flex: 1; height: 20px; background: rgba(255,255,255,0.1); border-radius: 10px; position: relative; overflow: hidden; }
.voice-progress { height: 100%; background: rgba(255,255,255,0.3); width: 0%; transition: width 0.1s; }
.voice-duration { font-size: 12px; opacity: 0.8; flex-shrink: 0; }

/* Reactions */
.message-reactions { position: absolute; bottom: -8px; right: 8px; background: #1c1e21; border: 1px solid #2f3031; border-radius: 12px; padding: 2px 6px; font-size: 14px; display: flex; gap: 2px; }
.message.received .message-reactions { left: 8px; right: auto; }

/* Input Area */
.input-area { background: #1c1e21; padding: 12px 16px; border-top: 1px solid #2f3031; }
.input-wrapper { display: flex; align-items: center; gap: 8px; background: #262626; border-radius: 20px; padding: 6px 12px; }
.input-icons { display: flex; gap: 4px; }
.input-icon-btn { background: none; border: none; color: #0a7cff; font-size: 20px; cursor: pointer; padding: 4px; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 50%; }
.input-icon-btn:hover { background: rgba(10, 124, 255, 0.1); }
.message-input { flex: 1; background: transparent; border: none; color: #e4e6eb; font-size: 15px; padding: 6px 4px; outline: none; font-family: inherit; }
.message-input::placeholder { color: #8a8d91; }
.send-btn { background: none; border: none; color: #0a7cff; font-size: 20px; cursor: pointer; padding: 4px; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; }
.send-btn:disabled { color: #4e4f50; cursor: not-allowed; }
.like-btn { background: none; border: none; font-size: 24px; cursor: pointer; padding: 4px; }

/* Recording UI */
.recording-ui { display: none; align-items: center; gap: 12px; padding: 12px 16px; background: #1c1e21; border-top: 1px solid #2f3031; }
.recording-ui.active { display: flex; }
.recording-indicator { display: flex; align-items: center; gap: 8px; flex: 1; }
.recording-dot { width: 12px; height: 12px; background: #ff4444; border-radius: 50%; animation: pulse 1.5s infinite; }
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }
.recording-time { color: #e4e6eb; font-size: 15px; font-weight: 600; }
.recording-waveform { flex: 1; height: 40px; display: flex; align-items: center; gap: 2px; }
.wave-bar { width: 3px; background: #0a7cff; border-radius: 2px; animation: wave 0.8s infinite ease-in-out; }
.wave-bar:nth-child(2) { animation-delay: 0.1s; }
.wave-bar:nth-child(3) { animation-delay: 0.2s; }
.wave-bar:nth-child(4) { animation-delay: 0.3s; }
.wave-bar:nth-child(5) { animation-delay: 0.4s; }
@keyframes wave { 0%, 100% { height: 8px; } 50% { height: 24px; } }
.cancel-recording-btn { background: #3a3b3c; color: #e4e6eb; border: none; padding: 8px 16px; border-radius: 20px; cursor: pointer; font-size: 14px; }
.send-recording-btn { background: #0a7cff; color: #fff; border: none; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; font-size: 18px; display: flex; align-items: center; justify-content: center; }

/* File Preview */
.file-preview { display: none; padding: 12px 16px; background: #1c1e21; border-top: 1px solid #2f3031; }
.file-preview.active { display: block; }
.preview-content { position: relative; display: inline-block; }
.preview-image { max-width: 200px; max-height: 200px; border-radius: 12px; }
.remove-preview-btn { position: absolute; top: 8px; right: 8px; background: rgba(0,0,0,0.7); color: #fff; border: none; width: 28px; height: 28px; border-radius: 50%; cursor: pointer; font-size: 14px; }

@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

::-webkit-scrollbar { width: 8px; }
::-webkit-scrollbar-thumb { background: #3a3b3c; border-radius: 4px; }
::-webkit-scrollbar-track { background: transparent; }

/* Mobile */
@media (max-width: 768px) {
    .message { max-width: 80%; }
    .profile-card { padding: 24px 16px; }
}
</style>
</head>
<body>

<!-- Header -->
<div class="header">
    <div class="header-left">
        <button class="back-btn" onclick="window.location.href='/home.php'"><i class="fas fa-chevron-left"></i></button>
        <div class="chat-user-info" onclick="scrollToProfile()" data-user-id="<?= $chat_user ?>">
            <div class="chat-avatar-wrapper">
                <img src="<?= $chatUserImage ?>" class="chat-avatar user-avatar" alt="<?= $chatUserName ?>" data-name="<?= $chatUserName ?>" loading="eager">
                <?php if ($isOnline): ?>
                <div class="online-indicator"></div>
                <?php else: ?>
                <div class="online-indicator" style="display:none;"></div>
                <?php endif; ?>
            </div>
            <div class="chat-user-details">
                <div class="chat-user-name"><?= $chatUserName ?></div>
                <div class="user-status-text" style="color: <?= $isOnline ? '#31a24c' : '#8a8d91' ?>;">
                    <?= $isOnline ? 'Active now' : $statusText . ' • Offline' ?>
                </div>
            </div>
        </div>
    </div>
    <div class="header-actions">
        <button class="header-btn"><i class="fas fa-phone"></i></button>
        <button class="header-btn"><i class="fas fa-video"></i></button>
    </div>
</div>

<!-- Profile Card -->
<div class="profile-card" id="profile-card" data-user-id="<?= $chat_user ?>">
    <img src="<?= $chatUserImage ?>" class="profile-avatar-large profile-avatar" alt="<?= $chatUserName ?>" data-name="<?= $chatUserName ?>" loading="eager">
    <div class="profile-name"><?= $chatUserName ?></div>
    <?php if ($chatUserUsername): ?>
    <div class="profile-username">@<?= $chatUserUsername ?></div>
    <?php endif; ?>
    <div class="user-status-text" style="color: <?= $isOnline ? '#31a24c' : '#8a8d91' ?>;">
        <?= $isOnline ? 'Active now' : $statusText . ' • Offline' ?>
    </div>
    <button class="view-profile-btn" onclick="window.location.href='/profile.php?id=<?= $chat_user ?>'">View profile</button>
    <div class="encryption-notice">
        <i class="fas fa-lock"></i> Messages and calls are secured with end-to-end encryption. <a href="#">Learn more</a>
    </div>
</div>

<!-- Messages -->
<div class="messages-wrapper" id="messages-wrapper"></div>

<!-- File Preview -->
<div class="file-preview" id="file-preview">
    <div class="preview-content">
        <img id="preview-image" class="preview-image" style="display:none;">
        <video id="preview-video" class="preview-image" style="display:none;" controls></video>
        <button class="remove-preview-btn" onclick="clearFilePreview()"><i class="fas fa-times"></i></button>
    </div>
</div>

<!-- Recording UI -->
<div class="recording-ui" id="recording-ui">
    <div class="recording-indicator">
        <div class="recording-dot"></div>
        <div class="recording-time" id="recording-time">0:00</div>
    </div>
    <div class="recording-waveform">
        <div class="wave-bar"></div>
        <div class="wave-bar"></div>
        <div class="wave-bar"></div>
        <div class="wave-bar"></div>
        <div class="wave-bar"></div>
    </div>
    <button class="cancel-recording-btn" onclick="cancelRecording()">Cancel</button>
    <button class="send-recording-btn" onclick="sendRecording()"><i class="fas fa-paper-plane"></i></button>
</div>

<!-- Input Area -->
<div class="input-area" id="input-area">
    <form id="message-form" class="input-wrapper">
        <div class="input-icons">
            <button type="button" class="input-icon-btn" onclick="document.getElementById('image-input').click()">
                <i class="fas fa-image"></i>
            </button>
            <input type="file" id="image-input" accept="image/*" style="display:none;" onchange="handleFileSelect(this, 'image')">
            
            <button type="button" class="input-icon-btn" onclick="document.getElementById('video-input').click()">
                <i class="fas fa-video"></i>
            </button>
            <input type="file" id="video-input" accept="video/*" style="display:none;" onchange="handleFileSelect(this, 'video')">
            
            <button type="button" class="input-icon-btn" id="mic-btn" onclick="startRecording()">
                <i class="fas fa-microphone"></i>
            </button>
        </div>
        <input type="text" id="message-input" class="message-input" placeholder="Aa" autocomplete="off">
        <button type="submit" class="send-btn" id="send-btn"><i class="fas fa-paper-plane"></i></button>
        <button type="button" class="like-btn" id="like-btn" onclick="sendLike()" style="display:none;">👍</button>
    </form>
</div>

<script>
const chatUser = <?= $chat_user ?>;
const currentUser = <?= $current_user ?>;
const chatUserName = "<?= $chatUserName ?>";
let lastMessageId = 0;
let mediaRecorder = null;
let audioChunks = [];
let recordingStartTime = 0;
let recordingInterval = null;
let selectedFile = null;
let selectedFileType = null;

// Load Messages
function loadMessages() {
    fetch("/backend/get_messages.php?user=" + chatUser + "&last_id=" + lastMessageId)
    .then(res => res.json())
    .then(data => {
        if (!data || !data.length) return;
        
        const wrapper = document.getElementById("messages-wrapper");
        const shouldScroll = wrapper.scrollHeight - wrapper.scrollTop - wrapper.clientHeight < 100;
        
        data.forEach(msg => {
            if (document.getElementById("msg-" + msg.id)) return;
            
            lastMessageId = Math.max(lastMessageId, msg.id);
            const isMe = msg.sender_id == currentUser;
            
            let messageHTML = `
                <div class="message ${isMe ? 'sent' : 'received'}" id="msg-${msg.id}">
                    ${!isMe ? `<img src="${msg.sender_avatar ? '/uploads/' + msg.sender_avatar : '/assets/zuckuser.png'}" class="message-avatar" alt="${escapeHtml(msg.sender_name)}" data-name="${escapeHtml(msg.sender_name)}" loading="lazy">` : ''}
                    <div>
            `;
            
            // Image
            if (msg.image) {
                messageHTML += `
                    <div class="message-media">
                        <img src="/uploads/${msg.image}" onclick="window.open('/uploads/${msg.image}', '_blank')" onerror="this.style.display='none';" loading="lazy">
                    </div>
                `;
            }
            
            // Video
            if (msg.video) {
                messageHTML += `
                    <div class="message-media">
                        <video src="/uploads/${msg.video}" controls onerror="this.style.display='none';"></video>
                    </div>
                `;
            }
            
            // Voice
            if (msg.voice) {
                messageHTML += `
                    <div class="voice-message">
                        <button class="voice-play-btn" onclick="playVoice(this, '/uploads/${msg.voice}')">
                            <i class="fas fa-play"></i>
                        </button>
                        <div class="voice-waveform">
                            <div class="voice-progress"></div>
                        </div>
                        <div class="voice-duration">0:00</div>
                    </div>
                `;
            }
            
            // Text
            if (msg.message) {
                messageHTML += `
                    <div class="message-bubble">
                        <div class="message-text">${escapeHtml(msg.message)}</div>
                    </div>
                `;
            }
            
            messageHTML += `</div></div>`;
            wrapper.insertAdjacentHTML("beforeend", messageHTML);
        });
        
        if (shouldScroll) {
            wrapper.scrollTop = wrapper.scrollHeight;
        }
    });
}

// Send Message
document.getElementById("message-form").addEventListener("submit", function(e) {
    e.preventDefault();
    const input = document.getElementById("message-input");
    const text = input.value.trim();
    
    if (!text && !selectedFile) return;
    
    const formData = new FormData();
    formData.append("receiver_id", chatUser);
    if (text) formData.append("message", text);
    if (selectedFile) formData.append(selectedFileType, selectedFile);
    
    fetch("/backend/send_message.php", {
        method: "POST",
        body: formData
    }).then(() => {
        input.value = "";
        clearFilePreview();
        loadMessages();
        updateLastSeen();
    });
});

// Send Like
function sendLike() {
    const formData = new FormData();
    formData.append("receiver_id", chatUser);
    formData.append("message", "👍");
    
    fetch("/backend/send_message.php", {
        method: "POST",
        body: formData
    }).then(() => {
        loadMessages();
    });
}

// Toggle Like/Send Button
document.getElementById("message-input").addEventListener("input", function() {
    const sendBtn = document.getElementById("send-btn");
    const likeBtn = document.getElementById("like-btn");
    
    if (this.value.trim()) {
        sendBtn.style.display = "flex";
        likeBtn.style.display = "none";
    } else {
        sendBtn.style.display = "none";
        likeBtn.style.display = "block";
    }
});

// File Handling
function handleFileSelect(input, type) {
    if (input.files && input.files[0]) {
        selectedFile = input.files[0];
        selectedFileType = type;
        
        const preview = document.getElementById("file-preview");
        const previewImage = document.getElementById("preview-image");
        const previewVideo = document.getElementById("preview-video");
        
        preview.classList.add("active");
        
        if (type === 'image') {
            previewImage.src = URL.createObjectURL(selectedFile);
            previewImage.style.display = "block";
            previewVideo.style.display = "none";
        } else {
            previewVideo.src = URL.createObjectURL(selectedFile);
            previewVideo.style.display = "block";
            previewImage.style.display = "none";
        }
    }
}

function clearFilePreview() {
    selectedFile = null;
    selectedFileType = null;
    document.getElementById("file-preview").classList.remove("active");
    document.getElementById("image-input").value = "";
    document.getElementById("video-input").value = "";
}

// Voice Recording
async function startRecording() {
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        mediaRecorder = new MediaRecorder(stream);
        audioChunks = [];
        
        mediaRecorder.ondataavailable = (e) => {
            audioChunks.push(e.data);
        };
        
        mediaRecorder.start();
        recordingStartTime = Date.now();
        
        document.getElementById("input-area").style.display = "none";
        document.getElementById("recording-ui").classList.add("active");
        
        recordingInterval = setInterval(updateRecordingTime, 100);
    } catch (err) {
        alert("Microphone access denied");
    }
}

function updateRecordingTime() {
    const elapsed = Math.floor((Date.now() - recordingStartTime) / 1000);
    const minutes = Math.floor(elapsed / 60);
    const seconds = elapsed % 60;
    document.getElementById("recording-time").textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;
}

function cancelRecording() {
    if (mediaRecorder && mediaRecorder.state !== "inactive") {
        mediaRecorder.stop();
        mediaRecorder.stream.getTracks().forEach(track => track.stop());
    }
    clearInterval(recordingInterval);
    document.getElementById("recording-ui").classList.remove("active");
    document.getElementById("input-area").style.display = "block";
    audioChunks = [];
}

function sendRecording() {
    if (mediaRecorder && mediaRecorder.state !== "inactive") {
        mediaRecorder.stop();
        
        mediaRecorder.onstop = () => {
            const audioBlob = new Blob(audioChunks, { type: 'audio/mp3' });
            const formData = new FormData();
            formData.append("receiver_id", chatUser);
            formData.append("voice", audioBlob, "voice.mp3");
            
            fetch("/backend/send_message.php", {
                method: "POST",
                body: formData
            }).then(() => {
                loadMessages();
                cancelRecording();
            });
            
            mediaRecorder.stream.getTracks().forEach(track => track.stop());
        };
    }
}

// Play Voice
function playVoice(btn, src) {
    const audio = new Audio(src);
    const icon = btn.querySelector("i");
    const progress = btn.parentElement.querySelector(".voice-progress");
    const duration = btn.parentElement.querySelector(".voice-duration");
    
    icon.classList.remove("fa-play");
    icon.classList.add("fa-pause");
    
    audio.play();
    
    audio.ontimeupdate = () => {
        const percent = (audio.currentTime / audio.duration) * 100;
        progress.style.width = percent + "%";
        
        const mins = Math.floor(audio.currentTime / 60);
        const secs = Math.floor(audio.currentTime % 60);
        duration.textContent = `${mins}:${secs.toString().padStart(2, '0')}`;
    };
    
    audio.onended = () => {
        icon.classList.remove("fa-pause");
        icon.classList.add("fa-play");
        progress.style.width = "0%";
    };
    
    btn.onclick = () => {
        if (audio.paused) {
            audio.play();
            icon.classList.remove("fa-play");
            icon.classList.add("fa-pause");
        } else {
            audio.pause();
            icon.classList.remove("fa-pause");
            icon.classList.add("fa-play");
        }
    };
}

// Update Status
function updateUserStatus() {
    const chatUserElement = document.querySelector('[data-user-id="' + chatUser + '"]');
    if (chatUserElement) {
        updateStatusDisplay(chatUser, chatUserElement);
    }
}

function updateLastSeen() {
    updateMyLastSeen();
}

function scrollToProfile() {
    document.getElementById("profile-card").scrollIntoView({ behavior: "smooth" });
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Initialize
setInterval(loadMessages, 3000);
setInterval(updateUserStatus, 5000);
loadMessages();

// Scroll to bottom on load
setTimeout(() => {
    document.getElementById("messages-wrapper").scrollTop = document.getElementById("messages-wrapper").scrollHeight;
}, 500);
</script>

</body>
</html>
