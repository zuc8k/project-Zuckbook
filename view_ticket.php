<?php
require_once __DIR__ . "/backend/config.php";

$error = '';
$ticket = null;
$messages = null;

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $ticket_input = trim($_POST['ticket_input'] ?? '');
    
    if(empty($ticket_input)){
        $error = 'يرجى إدخال رقم التذكرة أو الكود';
    } else {
        $stmt = $conn->prepare("
            SELECT id, user_id, username, email, status, ticket_code, created_at
            FROM support_tickets
            WHERE ticket_code = ? OR id = ? OR id = CAST(? AS UNSIGNED)
            LIMIT 1
        ");
        $stmt->bind_param("sss", $ticket_input, $ticket_input, $ticket_input);
        $stmt->execute();
        $ticket = $stmt->get_result()->fetch_assoc();
        
        if(!$ticket){
            $error = 'لم نجد التذكرة. تأكد من الرقم أو الكود';
        } else {
            $msgStmt = $conn->prepare("
                SELECT id, sender_type, message, created_at
                FROM ticket_messages
                WHERE ticket_id = ?
                ORDER BY created_at ASC
            ");
            $msgStmt->bind_param("i", $ticket['id']);
            $msgStmt->execute();
            $messages = $msgStmt->get_result();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>تتبع التذكرة - ZuckBook Support</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
<style>
    :root {
        --fb-blue: #1877f2;
        --fb-blue-hover: #166fe5;
        --fb-bg: #f0f2f5;
        --fb-card: #ffffff;
        --fb-text: #050505;
        --fb-secondary: #65676b;
        --fb-border: #dddfe2;
        --fb-success: #31a24c;
        --fb-warning: #f7931e;
        --fb-error: #fa3e3e;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }
    
    body {
        font-family: 'Tajawal', 'Segoe UI', Helvetica, Arial, sans-serif;
        background: var(--fb-bg);
        color: var(--fb-text);
        min-height: 100vh;
        display: flex;
        flex-direction: column;
        align-items: center;
    }

    /* Top Navigation Bar */
    .top-nav {
        width: 100%;
        background: var(--fb-card);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 0 16px;
        position: sticky;
        top: 0;
        z-index: 100;
    }

    .nav-inner {
        max-width: 1000px;
        margin: 0 auto;
        height: 56px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .logo {
        font-size: 28px;
        font-weight: 700;
        color: var(--fb-blue);
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .logo i { font-size: 24px; }

    .lang-btn {
        background: #e4e6eb;
        border: none;
        padding: 8px 12px;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        color: var(--fb-text);
        transition: 0.2s;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .lang-btn:hover { background: #d8dadf; }

    /* Main Container */
    .main-container {
        width: 100%;
        max-width: 700px;
        margin: 20px auto;
        padding: 0 16px;
        flex: 1;
    }

    /* Card Styles */
    .fb-card {
        background: var(--fb-card);
        border-radius: 8px;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        margin-bottom: 16px;
        overflow: hidden;
        animation: fadeIn 0.4s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .card-header {
        padding: 16px;
        border-bottom: 1px solid var(--fb-border);
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .card-header-icon {
        width: 40px;
        height: 40px;
        background: #e7f3ff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--fb-blue);
        font-size: 18px;
    }

    .card-header h1 {
        font-size: 20px;
        font-weight: 700;
    }

    .card-body { padding: 20px; }

    /* Form Elements */
    .input-group {
        margin-bottom: 16px;
    }

    .input-label {
        display: block;
        margin-bottom: 6px;
        font-size: 15px;
        font-weight: 600;
        color: var(--fb-text);
    }

    .input-field {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid var(--fb-border);
        border-radius: 6px;
        font-size: 16px;
        background: #f0f2f5;
        transition: 0.2s;
        font-family: inherit;
    }

    .input-field:focus {
        outline: none;
        border-color: var(--fb-blue);
        background: #fff;
        box-shadow: 0 0 0 2px rgba(24, 119, 242, 0.2);
    }

    .fb-btn {
        width: 100%;
        padding: 12px 16px;
        border: none;
        border-radius: 6px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .fb-btn-primary {
        background: var(--fb-blue);
        color: white;
    }

    .fb-btn-primary:hover {
        background: var(--fb-blue-hover);
    }

    /* Alerts */
    .alert-box {
        padding: 12px 16px;
        border-radius: 6px;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 10px;
        background: #ffebe8;
        border: 1px solid #dd3c10;
        color: #dd3c10;
    }

    /* Ticket Status Section */
    .ticket-status-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }

    .status-item {
        background: #f0f2f5;
        padding: 12px;
        border-radius: 8px;
    }

    .status-label {
        font-size: 13px;
        color: var(--fb-secondary);
        margin-bottom: 4px;
    }

    .status-value {
        font-weight: 700;
        color: var(--fb-text);
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 12px;
        border-radius: 4px;
        font-size: 13px;
        font-weight: 600;
    }

    .bg-open { background: #fff3cd; color: #856404; }
    .bg-claimed { background: #cce5ff; color: #004085; }
    .bg-done { background: #d4edda; color: #155724; }
    .bg-refused { background: #f8d7da; color: #721c24; }

    /* Code Box */
    .code-box {
        background: #f0f2f5;
        padding: 16px;
        border-radius: 8px;
        text-align: center;
        border: 1px dashed var(--fb-border);
        margin: 20px 0;
    }

    .code-label {
        font-size: 13px;
        color: var(--fb-secondary);
        margin-bottom: 8px;
        text-transform: uppercase;
    }

    .code-value {
        font-size: 28px;
        font-weight: 700;
        color: var(--fb-blue);
        letter-spacing: 2px;
        font-family: 'Courier New', monospace;
    }

    .copy-btn {
        background: transparent;
        border: none;
        color: var(--fb-blue);
        cursor: pointer;
        font-weight: 600;
        margin-top: 8px;
        padding: 4px 8px;
        border-radius: 4px;
    }

    .copy-btn:hover { background: #e7f3ff; }

    /* Chat/Messages Style */
    .chat-container {
        padding: 16px;
        background: #f8f9fa;
        border-radius: 8px;
        max-height: 400px;
        overflow-y: auto;
    }

    .chat-bubble {
        max-width: 80%;
        padding: 10px 14px;
        border-radius: 18px;
        margin-bottom: 12px;
        position: relative;
        font-size: 15px;
        line-height: 1.4;
        box-shadow: 0 1px 1px rgba(0,0,0,0.04);
    }

    .bubble-user {
        background: var(--fb-blue);
        color: white;
        margin-left: auto; /* RTL support handled by flex */
        border-bottom-right-radius: 4px;
    }

    .bubble-support {
        background: #e4e6eb;
        color: var(--fb-text);
        margin-right: auto;
        border-bottom-left-radius: 4px;
    }

    .bubble-time {
        display: block;
        font-size: 11px;
        margin-top: 6px;
        opacity: 0.7;
        text-align: left;
    }

    .bubble-user .bubble-time { text-align: right; color: rgba(255,255,255,0.8); }
    
    .sender-name {
        font-size: 12px;
        font-weight: 700;
        margin-bottom: 4px;
        display: block;
        opacity: 0.8;
    }

    /* Footer */
    .fb-footer {
        text-align: center;
        padding: 20px;
        color: var(--fb-secondary);
        font-size: 14px;
    }

    .fb-footer a {
        color: var(--fb-blue);
        text-decoration: none;
        font-weight: 600;
    }

    /* Responsive */
    @media (max-width: 600px) {
        .ticket-status-grid { grid-template-columns: 1fr; }
        .main-container { margin: 10px auto; }
    }
    
    /* Custom Scrollbar */
    .chat-container::-webkit-scrollbar { width: 6px; }
    .chat-container::-webkit-scrollbar-thumb { background: #bec3c9; border-radius: 3px; }
</style>
</head>
<body>

<!-- Top Nav -->
<nav class="top-nav">
    <div class="nav-inner">
        <a href="/" class="logo">
            <i class="fab fa-meta"></i> ZuckBook
        </a>
        <button class="lang-btn" onclick="toggleLanguage()" id="langBtn">
            <i class="fas fa-globe"></i> English
        </button>
    </div>
</nav>

<div class="main-container">
    
    <?php if($error): ?>
        <div class="fb-card">
            <div class="card-body">
                <div class="alert-box">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
                <a href="/view_ticket.php" class="fb-btn fb-btn-primary" style="text-decoration:none;">
                    <i class="fas fa-redo"></i> حاول مرة أخرى
                </a>
            </div>
        </div>
    <?php endif; ?>

    <?php if(!$ticket && !$error): ?>
        <!-- Search Form -->
        <div class="fb-card">
            <div class="card-header">
                <div class="card-header-icon">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <h1>تتبع طلب الدعم</h1>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="input-group">
                        <label class="input-label">رقم التذكرة أو الكود</label>
                        <input type="text" name="ticket_input" class="input-field" 
                               placeholder="مثال: 1001 أو TK-5500XYZ" required>
                    </div>
                    <button type="submit" class="fb-btn fb-btn-primary">
                        <i class="fas fa-search"></i> عرض التذكرة
                    </button>
                </form>
                
                <div style="margin-top: 20px; text-align: center; color: var(--fb-secondary); font-size: 14px;">
                    <p>لم تجد كود التذكرة؟</p>
                    <p>تم إرسال الكود إلى بريدك الإلكتروني عند إنشاء الطلب.</p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if($ticket): ?>
        <!-- Ticket Details -->
        <div class="fb-card">
            <div class="card-header">
                <div class="card-header-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div>
                    <h1>تم العثور على الطلب</h1>
                    <span style="color: var(--fb-secondary); font-size: 14px;">
                        تم الإنشاء: <?= date('d M Y - h:i A', strtotime($ticket['created_at'])) ?>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <!-- Code Box -->
                <div class="code-box">
                    <div class="code-label">كود التتبع</div>
                    <div class="code-value" id="ticketCode"><?= htmlspecialchars($ticket['ticket_code']) ?></div>
                    <button class="copy-btn" onclick="copyCode()">
                        <i class="fas fa-copy"></i> نسخ الكود
                    </button>
                </div>

                <!-- Status Grid -->
                <div class="ticket-status-grid">
                    <div class="status-item">
                        <div class="status-label">الحالة</div>
                        <div class="status-value">
                            <span class="status-badge bg-<?= $ticket['status'] ?>">
                                <?php 
                                    $statuses = [
                                        'open' => 'قيد المراجعة',
                                        'claimed' => 'جاري المعالجة',
                                        'refused' => 'مرفوض',
                                        'done' => 'تم الحل'
                                    ];
                                    echo $statuses[$ticket['status']] ?? 'غير معروف';
                                ?>
                            </span>
                        </div>
                    </div>
                    <div class="status-item">
                        <div class="status-label">صاحب الطلب</div>
                        <div class="status-value"><?= htmlspecialchars($ticket['username']) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <?php if($messages && $messages->num_rows > 0): ?>
        <div class="fb-card">
            <div class="card-header">
                <h1><i class="fas fa-comments"></i> المحادثات</h1>
            </div>
            <div class="card-body chat-container" style="background: #fff;">
                <?php while($msg = $messages->fetch_assoc()): ?>
                    <div class="chat-bubble bubble-<?= $msg['sender_type'] === 'user' ? 'user' : 'support' ?>">
                        <span class="sender-name">
                            <?= $msg['sender_type'] === 'user' ? 'أنت' : 'فريق الدعم' ?>
                        </span>
                        <?= nl2br(htmlspecialchars($msg['message'])) ?>
                        <span class="bubble-time"><?= date('H:i', strtotime($msg['created_at'])) ?></span>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endif; ?>

        <a href="/view_ticket.php" class="fb-btn fb-btn-primary" style="text-decoration:none; margin-top:16px;">
            <i class="fas fa-search"></i> بحث عن تذكرة أخرى
        </a>
    <?php endif; ?>

    <div class="fb-footer">
        هل تواجه مشكلة؟ <a href="/create_ticket.php">إنشاء طلب جديد</a>
    </div>
</div>

<script>
function copyCode() {
    const code = document.getElementById('ticketCode').innerText;
    navigator.clipboard.writeText(code).then(() => {
        alert('تم نسخ الكود!');
    }).catch(() => {
        alert('فشل النسخ.');
    });
}

// Language Toggle Logic (Simplified for example)
function toggleLanguage() {
    const currentLang = document.documentElement.lang;
    const newLang = currentLang === 'ar' ? 'en' : 'ar';
    
    // In a real scenario, you would reload or change text via JS
    // Here we just switch direction for demonstration if needed
    document.documentElement.lang = newLang;
    document.documentElement.dir = newLang === 'ar' ? 'rtl' : 'ltr';
    
    // Update button text (Mock)
    const btn = document.getElementById('langBtn');
    btn.innerHTML = newLang === 'ar' ? '<i class="fas fa-globe"></i> English' : '<i class="fas fa-globe"></i> العربية';
    
    // Store preference
    localStorage.setItem('lang', newLang);
}

// On load check saved lang
document.addEventListener('DOMContentLoaded', () => {
    const savedLang = localStorage.getItem('lang') || 'ar';
    if(savedLang !== document.documentElement.lang) {
        // Apply language logic here
    }
});
</script>
</body>
</html>
```