<?php
session_start();
require_once __DIR__ . "/backend/config.php";

if(!isset($_SESSION['user_id'])){
    header("Location: /index.php");
    exit;
}

$ticket_id = intval($_GET['id'] ?? 0);
$user_id = intval($_SESSION['user_id']);

// Get ticket details - make sure it belongs to this user
$ticketStmt = $conn->prepare("SELECT * FROM support_tickets WHERE id=? AND user_id=?");
$ticketStmt->bind_param("ii", $ticket_id, $user_id);
$ticketStmt->execute();
$ticket = $ticketStmt->get_result()->fetch_assoc();

if(!$ticket){
    header("Location: /home.php");
    exit;
}

// Get messages
$messagesStmt = $conn->prepare("SELECT * FROM ticket_messages WHERE ticket_id=? ORDER BY created_at ASC");
$messagesStmt->bind_param("i", $ticket_id);
$messagesStmt->execute();
$messages = $messagesStmt->get_result();
?>

<!DOCTYPE html>
<html dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>تذكرة الدعم #<?= $ticket_id ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

body { 
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding: 20px;
}

.container {
    max-width: 900px;
    margin: 0 auto;
}

.header {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    padding: 25px 30px;
    border-radius: 20px;
    margin-bottom: 25px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.header h2 {
    font-size: 24px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 700;
}

.status-badge {
    display: inline-block;
    padding: 8px 18px;
    border-radius: 25px;
    font-size: 13px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-open { 
    background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);
    color: #0d5e3a;
}

.status-claimed { 
    background: linear-gradient(135deg, #a1c4fd 0%, #c2e9fb 100%);
    color: #0c4a6e;
}

.status-refused { 
    background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
    color: #7f1d1d;
}

.status-done { 
    background: linear-gradient(135deg, #e0e7ff 0%, #cfd9df 100%);
    color: #374151;
}

.back-btn {
    padding: 12px 24px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    text-decoration: none;
    border-radius: 12px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.back-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
}

.chat-container {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    display: flex;
    flex-direction: column;
    height: calc(100vh - 200px);
    max-height: 700px;
}

.messages-area {
    flex: 1;
    overflow-y: auto;
    padding: 25px;
}

.messages-area::-webkit-scrollbar {
    width: 8px;
}

.messages-area::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.05);
}

.messages-area::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 10px;
}

.message {
    margin-bottom: 20px;
    display: flex;
    flex-direction: column;
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.message-bubble {
    max-width: 70%;
    padding: 15px 20px;
    border-radius: 18px;
    position: relative;
    word-wrap: break-word;
}

.message-user .message-bubble {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    align-self: flex-end;
    border-bottom-right-radius: 5px;
}

.message-support .message-bubble {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    color: #2d3748;
    align-self: flex-start;
    border-bottom-left-radius: 5px;
}

.message-sender {
    font-size: 12px;
    font-weight: 700;
    margin-bottom: 5px;
    opacity: 0.9;
}

.message-content {
    font-size: 15px;
    line-height: 1.5;
    margin-bottom: 5px;
}

.message-time {
    font-size: 11px;
    opacity: 0.7;
    text-align: left;
}

.message-user .message-time {
    text-align: right;
}

.input-area {
    padding: 20px 25px;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    border-top: 2px solid rgba(102, 126, 234, 0.2);
}

.input-container {
    display: flex;
    gap: 12px;
    align-items: flex-end;
}

.input-container textarea {
    flex: 1;
    padding: 15px;
    border: 2px solid rgba(102, 126, 234, 0.3);
    border-radius: 25px;
    font-size: 15px;
    font-family: inherit;
    resize: none;
    min-height: 50px;
    max-height: 120px;
    transition: all 0.3s ease;
    background: white;
}

.input-container textarea:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.send-btn {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 50%;
    font-size: 18px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    flex-shrink: 0;
}

.send-btn:hover:not(:disabled) {
    transform: scale(1.1);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
}

.send-btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}

.no-messages {
    text-align: center;
    padding: 60px 20px;
    color: #667eea;
}

.no-messages i {
    font-size: 64px;
    margin-bottom: 20px;
    opacity: 0.5;
}

.no-messages p {
    font-size: 18px;
    font-weight: 600;
}

@media (max-width: 768px) {
    body {
        padding: 10px;
    }
    
    .header {
        flex-direction: column;
        align-items: stretch;
        padding: 20px;
    }
    
    .header h2 {
        font-size: 20px;
    }
    
    .chat-container {
        height: calc(100vh - 180px);
    }
    
    .message-bubble {
        max-width: 85%;
    }
    
    .input-container {
        gap: 8px;
    }
}
</style>
</head>
<body>

<div class="container">
    <div class="header">
        <div>
            <h2>
                <i class="fas fa-ticket-alt"></i>
                تذكرة دعم #<?= $ticket_id ?>
            </h2>
            <span class="status-badge status-<?= $ticket['status'] ?>">
                <?php
                $statusNames = [
                    'open' => 'مفتوحة',
                    'claimed' => 'قيد المعالجة',
                    'refused' => 'مرفوضة',
                    'done' => 'منتهية'
                ];
                echo $statusNames[$ticket['status']] ?? $ticket['status'];
                ?>
            </span>
        </div>
        <a href="/home.php" class="back-btn">
            <i class="fas fa-home"></i>
            الرئيسية
        </a>
    </div>

    <div class="chat-container">
        <div class="messages-area" id="messagesArea">
            <?php if($messages->num_rows > 0): ?>
                <?php while($msg = $messages->fetch_assoc()): ?>
                <div class="message message-<?= $msg['sender_type'] ?>">
                    <div class="message-bubble">
                        <div class="message-sender">
                            <?= $msg['sender_type'] === 'user' ? 'أنت' : 'الدعم الفني' ?>
                        </div>
                        <div class="message-content">
                            <?= nl2br(htmlspecialchars($msg['message'])) ?>
                        </div>
                        <div class="message-time">
                            <?= date('H:i', strtotime($msg['created_at'])) ?>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-messages">
                    <i class="fas fa-comments"></i>
                    <p>ابدأ المحادثة الآن</p>
                </div>
            <?php endif; ?>
        </div>

        <form class="input-area" id="messageForm">
            <input type="hidden" name="ticket_id" value="<?= $ticket_id ?>">
            <div class="input-container">
                <textarea 
                    name="message" 
                    id="messageInput" 
                    placeholder="اكتب رسالتك..." 
                    required
                    rows="1"
                ></textarea>
                <button type="submit" class="send-btn" id="sendBtn">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Auto scroll to bottom
function scrollToBottom() {
    const messagesArea = document.getElementById('messagesArea');
    messagesArea.scrollTop = messagesArea.scrollHeight;
}

scrollToBottom();

// Auto-resize textarea
const messageInput = document.getElementById('messageInput');
messageInput.addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
});

// Handle Enter key (Shift+Enter for new line)
messageInput.addEventListener('keydown', function(e) {
    if(e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        document.getElementById('messageForm').dispatchEvent(new Event('submit'));
    }
});

// Handle form submission
document.getElementById('messageForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const sendBtn = document.getElementById('sendBtn');
    const messageInput = document.getElementById('messageInput');
    
    if(!messageInput.value.trim()) return;
    
    // Disable button
    sendBtn.disabled = true;
    sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    
    fetch('send_user_message.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            location.reload();
        } else {
            alert(data.message || 'حدث خطأ أثناء الإرسال');
            sendBtn.disabled = false;
            sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('حدث خطأ أثناء الإرسال');
        sendBtn.disabled = false;
        sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i>';
    });
});

// Auto-refresh messages every 5 seconds
setInterval(() => {
    location.reload();
}, 5000);
</script>

</body>
</html>
