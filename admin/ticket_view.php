<?php
session_start();
require_once __DIR__ . "/../backend/config.php";
require_once __DIR__ . "/../backend/middleware.php";

requireRole(['cofounder','mod','sup']);

$ticket_id = intval($_GET['id']);

// Get ticket details
$ticketStmt = $conn->prepare("SELECT * FROM support_tickets WHERE id=?");
$ticketStmt->bind_param("i", $ticket_id);
$ticketStmt->execute();
$ticket = $ticketStmt->get_result()->fetch_assoc();

if(!$ticket){
    header("Location: tickets.php");
    exit;
}

// Check if this is a cancellation ticket
$isCancellationTicket = false;
$cancellationData = null;

if (isset($ticket['subject']) && stripos($ticket['subject'], 'Cancellation') !== false) {
    $isCancellationTicket = true;
}
if (isset($ticket['ticket_code']) && stripos($ticket['ticket_code'], 'CANCEL') !== false) {
    $isCancellationTicket = true;
}
if (isset($ticket['cancellation_data']) && !empty($ticket['cancellation_data']) && $ticket['cancellation_data'] !== 'null') {
    $isCancellationTicket = true;
    $cancellationData = json_decode($ticket['cancellation_data'], true);
}

// Get messages
$messagesStmt = $conn->prepare("SELECT * FROM ticket_messages WHERE ticket_id=? ORDER BY created_at ASC");
$messagesStmt->bind_param("i", $ticket_id);
$messagesStmt->execute();
$messages = $messagesStmt->get_result();

$statusNames = [
    'open' => 'مفتوحة',
    'claimed' => 'قيد المعالجة',
    'refused' => 'مرفوضة',
    'done' => 'منتهية'
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تذكرة #<?= $ticket_id ?> - نظام الدعم الفني</title>
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
            color: #050505;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header */
        .page-header {
            background: white;
            padding: 20px 30px;
            border-radius: 12px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .header-content h1 {
            font-size: 24px;
            font-weight: 700;
            color: #050505;
            margin-bottom: 5px;
        }

        .header-content p {
            color: #65676b;
            font-size: 14px;
        }

        .back-btn {
            padding: 10px 20px;
            background: #1877f2;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: 0.2s;
        }

        .back-btn:hover {
            background: #166fe5;
        }

        /* Main Grid */
        .main-grid {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 20px;
        }

        /* Card */
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .card-header {
            padding: 20px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-header h2 {
            font-size: 18px;
            font-weight: 700;
            color: #050505;
        }

        .card-header i {
            color: #1877f2;
            font-size: 20px;
        }

        .card-body {
            padding: 20px;
        }

        /* Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .info-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }

        .info-label {
            font-size: 12px;
            color: #65676b;
            font-weight: 600;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .info-value {
            font-size: 15px;
            color: #050505;
            font-weight: 600;
        }

        /* Status Badge */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .status-open {
            background: #d4edda;
            color: #155724;
        }

        .status-claimed {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-refused {
            background: #f8d7da;
            color: #721c24;
        }

        .status-done {
            background: #cce5ff;
            color: #004085;
        }

        /* Actions */
        .actions-section {
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }

        .actions-title {
            font-size: 14px;
            color: #65676b;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        .action-btn {
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            transition: 0.2s;
            text-align: center;
            color: white;
        }

        .action-btn i {
            font-size: 20px;
        }

        .btn-claim {
            background: #28a745;
        }

        .btn-claim:hover {
            background: #218838;
        }

        .btn-refuse {
            background: #dc3545;
        }

        .btn-refuse:hover {
            background: #c82333;
        }

        .btn-done {
            background: #1877f2;
        }

        .btn-done:hover {
            background: #166fe5;
        }

        .action-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Messages */
        .messages-container {
            max-height: 500px;
            overflow-y: auto;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .messages-container::-webkit-scrollbar {
            width: 6px;
        }

        .messages-container::-webkit-scrollbar-track {
            background: #e5e7eb;
        }

        .messages-container::-webkit-scrollbar-thumb {
            background: #1877f2;
            border-radius: 10px;
        }

        .message {
            padding: 12px 15px;
            margin-bottom: 12px;
            border-radius: 8px;
            max-width: 85%;
        }

        .message-user {
            background: #e3f2fd;
            border-left: 3px solid #1877f2;
            margin-right: auto;
        }

        .message-support {
            background: #fff3cd;
            border-left: 3px solid #ffc107;
            margin-left: auto;
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .message-sender {
            font-weight: 700;
            font-size: 13px;
            color: #050505;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .message-time {
            font-size: 11px;
            color: #65676b;
        }

        .message-content {
            font-size: 14px;
            color: #374151;
            line-height: 1.5;
        }

        .no-messages {
            text-align: center;
            padding: 40px;
            color: #65676b;
        }

        .no-messages i {
            font-size: 48px;
            color: #e5e7eb;
            margin-bottom: 10px;
        }

        /* Chat Input */
        .chat-input-section {
            padding: 15px;
            background: #f8f9fa;
            border-top: 1px solid #e5e7eb;
        }

        .chat-input-form {
            display: flex;
            gap: 10px;
        }

        .chat-input {
            flex: 1;
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 25px;
            font-size: 14px;
            font-family: inherit;
            transition: 0.2s;
        }

        .chat-input:focus {
            outline: none;
            border-color: #1877f2;
        }

        .chat-send-btn {
            padding: 12px 24px;
            background: #1877f2;
            color: white;
            border: none;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: 0.2s;
        }

        .chat-send-btn:hover {
            background: #166fe5;
        }

        /* Sidebar */
        .sidebar-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            padding: 20px;
            margin-bottom: 15px;
        }

        .sidebar-title {
            font-size: 16px;
            font-weight: 700;
            color: #050505;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .sidebar-title i {
            color: #1877f2;
        }

        .stat-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .stat-row:last-child {
            border-bottom: none;
        }

        .stat-label {
            font-size: 13px;
            color: #65676b;
            font-weight: 500;
        }

        .stat-value {
            font-size: 14px;
            color: #050505;
            font-weight: 600;
        }

        /* Responsive */
        @media (max-width: 968px) {
            .main-grid {
                grid-template-columns: 1fr;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .actions-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Success Message */
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid #c3e6cb;
        }

        .success-message i {
            font-size: 18px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <header class="page-header">
            <div class="header-content">
                <h1><i class="fas fa-ticket-alt"></i> تذكرة دعم #<?= $ticket_id ?></h1>
                <p>تم الإنشاء: <?= date('Y/m/d - H:i', strtotime($ticket['created_at'])) ?></p>
            </div>
            <a href="tickets.php" class="back-btn">
                <i class="fas fa-arrow-right"></i>
                العودة للقائمة
            </a>
        </header>

        <!-- Main Content -->
        <div class="main-grid">
            <!-- Left Column -->
            <div class="left-column">
                <!-- Ticket Info Card -->
                <div class="card" style="margin-bottom: 20px;">
                    <div class="card-header">
                        <i class="fas fa-info-circle"></i>
                        <h2>تفاصيل التذكرة</h2>
                    </div>
                    <div class="card-body">
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">
                                    <i class="fas fa-user"></i>
                                    اسم المستخدم
                                </div>
                                <div class="info-value">
                                    <?= htmlspecialchars($ticket['username'] ?? 'غير معروف') ?>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">
                                    <i class="fas fa-envelope"></i>
                                    البريد الإلكتروني
                                </div>
                                <div class="info-value">
                                    <?= htmlspecialchars($ticket['email'] ?? 'غير متوفر') ?>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">
                                    <i class="fas fa-signal"></i>
                                    حالة التذكرة
                                </div>
                                <div class="info-value">
                                    <span class="status-badge status-<?= $ticket['status'] ?>">
                                        <i class="fas fa-circle" style="font-size: 6px;"></i>
                                        <?= $statusNames[$ticket['status']] ?? $ticket['status'] ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">
                                    <i class="fas fa-calendar-alt"></i>
                                    تاريخ الإنشاء
                                </div>
                                <div class="info-value">
                                    <?= date('Y/m/d', strtotime($ticket['created_at'])) ?>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="actions-section">
                            <div class="actions-title">
                                <i class="fas fa-bolt"></i>
                                الإجراءات المتاحة
                            </div>
                            
                            <?php if ($isCancellationTicket && $cancellationData): ?>
                            <!-- Cancellation Ticket Actions -->
                            <div style="background: #fef3c7; border: 2px solid #f59e0b; border-radius: 12px; padding: 20px; margin-bottom: 20px;">
                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                                    <i class="fas fa-exclamation-triangle" style="color: #f59e0b; font-size: 24px;"></i>
                                    <div>
                                        <h3 style="font-size: 16px; font-weight: 700; color: #92400e; margin-bottom: 5px;">تذكرة إلغاء اشتراك</h3>
                                        <p style="font-size: 13px; color: #78350f;">هذه تذكرة طلب إلغاء اشتراك واسترجاع نقود</p>
                                    </div>
                                </div>
                                
                                <div style="background: white; border-radius: 8px; padding: 15px; margin-bottom: 15px;">
                                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
                                        <div>
                                            <div style="font-size: 12px; color: #78350f; font-weight: 600; margin-bottom: 5px;">نوع الاشتراك</div>
                                            <div style="font-size: 15px; color: #92400e; font-weight: 700;"><?= strtoupper($cancellationData['subscription_tier'] ?? 'N/A') ?></div>
                                        </div>
                                        <div>
                                            <div style="font-size: 12px; color: #78350f; font-weight: 600; margin-bottom: 5px;">المبلغ المدفوع</div>
                                            <div style="font-size: 15px; color: #92400e; font-weight: 700;"><?= $cancellationData['paid_amount'] ?? 0 ?> <i class="fas fa-coins"></i></div>
                                        </div>
                                        <div>
                                            <div style="font-size: 12px; color: #78350f; font-weight: 600; margin-bottom: 5px;">مبلغ الاسترجاع</div>
                                            <div style="font-size: 15px; color: #10b981; font-weight: 700;"><?= $cancellationData['refund_amount'] ?? 0 ?> <i class="fas fa-coins"></i></div>
                                        </div>
                                        <div>
                                            <div style="font-size: 12px; color: #78350f; font-weight: 600; margin-bottom: 5px;">الكوينات الحالية</div>
                                            <div style="font-size: 15px; color: #92400e; font-weight: 700;"><?= $cancellationData['current_coins'] ?? 0 ?> <i class="fas fa-coins"></i></div>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if($ticket['status'] !== 'done' && $ticket['status'] !== 'refused'): ?>
                                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                                    <a href="#" 
                                       class="action-btn"
                                       style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);"
                                       onclick="event.preventDefault(); showRefundModal()">
                                        <i class="fas fa-hand-holding-usd"></i>
                                        استرجاع النقود
                                    </a>
                                    <a href="#" 
                                       class="action-btn btn-refuse"
                                       onclick="event.preventDefault(); showConfirmModal('رفض الطلب', 'هل أنت متأكد من رفض طلب الإلغاء؟', 'refuse', () => window.location.href='ticket_action.php?id=<?= $ticket_id ?>&action=refuse')">
                                        <i class="fas fa-times-circle"></i>
                                        رفض الطلب
                                    </a>
                                </div>
                                <?php else: ?>
                                <div style="text-align: center; padding: 15px; background: #d1fae5; border-radius: 8px; color: #065f46; font-weight: 600;">
                                    <i class="fas fa-check-circle"></i>
                                    <?= $ticket['status'] === 'done' ? 'تم استرجاع النقود بنجاح' : 'تم رفض الطلب' ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                            
                            <div class="actions-grid">
                                <?php if($ticket['status'] !== 'claimed' && $ticket['status'] !== 'done'): ?>
                                <a href="#" 
                                   class="action-btn btn-claim" 
                                   onclick="event.preventDefault(); showConfirmModal('استلام التذكرة', 'هل تريد استلام هذه التذكرة؟', 'claim', () => window.location.href='ticket_action.php?id=<?= $ticket_id ?>&action=claim')">
                                    <i class="fas fa-hand-paper"></i>
                                    استلام التذكرة
                                </a>
                                <?php else: ?>
                                <button class="action-btn btn-claim" disabled>
                                    <i class="fas fa-hand-paper"></i>
                                    استلام التذكرة
                                </button>
                                <?php endif; ?>
                                
                                <?php if($ticket['status'] !== 'refused' && $ticket['status'] !== 'done' && !$isCancellationTicket): ?>
                                <a href="#" 
                                   class="action-btn btn-refuse"
                                   onclick="event.preventDefault(); showConfirmModal('رفض التذكرة', 'هل أنت متأكد من رفض هذه التذكرة؟', 'refuse', () => window.location.href='ticket_action.php?id=<?= $ticket_id ?>&action=refuse')">
                                    <i class="fas fa-times-circle"></i>
                                    رفض التذكرة
                                </a>
                                <?php else: ?>
                                <button class="action-btn btn-refuse" disabled>
                                    <i class="fas fa-times-circle"></i>
                                    رفض التذكرة
                                </button>
                                <?php endif; ?>
                                
                                <?php if($ticket['status'] !== 'done' && !$isCancellationTicket): ?>
                                <a href="#" 
                                   class="action-btn btn-done"
                                   onclick="event.preventDefault(); showConfirmModal('إنهاء التذكرة', 'هل تريد إنهاء هذه التذكرة؟', 'done', () => window.location.href='ticket_action.php?id=<?= $ticket_id ?>&action=done')">
                                    <i class="fas fa-check-circle"></i>
                                    إنهاء التذكرة
                                </a>
                                <?php else: ?>
                                <button class="action-btn btn-done" disabled>
                                    <i class="fas fa-check-circle"></i>
                                    إنهاء التذكرة
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Messages Card -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-comments"></i>
                        <h2>المحادثات (<?= $messages->num_rows ?>)</h2>
                    </div>
                    
                    <div id="messagesContainer" class="messages-container">
                        <?php if($messages->num_rows > 0): ?>
                            <?php while($msg = $messages->fetch_assoc()): ?>
                            <div class="message message-<?= $msg['sender_type'] ?>">
                                <div class="message-header">
                                    <span class="message-sender">
                                        <i class="fas fa-<?= $msg['sender_type'] === 'user' ? 'user' : 'headset' ?>"></i>
                                        <?= $msg['sender_type'] === 'user' ? 'المستخدم' : 'فريق الدعم' ?>
                                    </span>
                                    <span class="message-time">
                                        <?= date('Y/m/d H:i', strtotime($msg['created_at'])) ?>
                                    </span>
                                </div>
                                <div class="message-content">
                                    <?= nl2br(htmlspecialchars($msg['message'])) ?>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="no-messages">
                                <i class="fas fa-inbox"></i>
                                <p>لا توجد رسائل في هذه التذكرة</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Chat Input -->
                    <div class="chat-input-section">
                        <form class="chat-input-form" id="chatForm">
                            <input type="hidden" name="ticket_id" value="<?= $ticket_id ?>">
                            <input type="text" 
                                   class="chat-input" 
                                   id="messageInput"
                                   name="message" 
                                   placeholder="اكتب رسالتك هنا..." 
                                   required>
                            <button type="submit" class="chat-send-btn">
                                <i class="fas fa-paper-plane"></i>
                                إرسال
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Right Column - Sidebar -->
            <div class="sidebar">
                <!-- Quick Info -->
                <div class="sidebar-card">
                    <div class="sidebar-title">
                        <i class="fas fa-info-circle"></i>
                        معلومات سريعة
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">رقم التذكرة</span>
                        <span class="stat-value">#<?= $ticket_id ?></span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">المستخدم</span>
                        <span class="stat-value"><?= htmlspecialchars($ticket['username'] ?? 'غير معروف') ?></span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">الحالة</span>
                        <span class="stat-value"><?= $statusNames[$ticket['status']] ?? $ticket['status'] ?></span>
                    </div>
                </div>

                <!-- Stats Card -->
                <div class="sidebar-card">
                    <div class="sidebar-title">
                        <i class="fas fa-chart-bar"></i>
                        إحصائيات التذكرة
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">عدد الرسائل</span>
                        <span class="stat-value"><?= $messages->num_rows ?></span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">تاريخ الفتح</span>
                        <span class="stat-value"><?= date('Y/m/d', strtotime($ticket['created_at'])) ?></span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">مدة الانتظار</span>
                        <span class="stat-value">
                            <?php 
                            $diff = time() - strtotime($ticket['created_at']);
                            $days = floor($diff / 86400);
                            $hours = floor(($diff % 86400) / 3600);
                            echo $days > 0 ? $days . ' يوم ' : '';
                            echo $hours . ' ساعة';
                            ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto scroll to bottom of messages
        const messagesContainer = document.getElementById('messagesContainer');
        if (messagesContainer) {
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        // Handle chat form submission
        document.getElementById('chatForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const messageInput = document.getElementById('messageInput');
            const sendBtn = this.querySelector('.chat-send-btn');
            
            // Disable button
            sendBtn.disabled = true;
            sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري الإرسال...';
            
            fetch('send_message.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Add message to container
                    const messageHTML = `
                        <div class="message message-support">
                            <div class="message-header">
                                <span class="message-sender">
                                    <i class="fas fa-headset"></i>
                                    فريق الدعم
                                </span>
                                <span class="message-time">الآن</span>
                            </div>
                            <div class="message-content">${messageInput.value}</div>
                        </div>
                    `;
                    
                    // Remove "no messages" if exists
                    const noMessages = messagesContainer.querySelector('.no-messages');
                    if (noMessages) {
                        noMessages.remove();
                    }
                    
                    messagesContainer.insertAdjacentHTML('beforeend', messageHTML);
                    messagesContainer.scrollTop = messagesContainer.scrollHeight;
                    
                    // Clear input
                    messageInput.value = '';
                    
                    // Show success message
                    const successMsg = document.createElement('div');
                    successMsg.className = 'success-message';
                    successMsg.innerHTML = '<i class="fas fa-check-circle"></i> تم إرسال الرسالة بنجاح';
                    messagesContainer.parentElement.insertBefore(successMsg, messagesContainer);
                    
                    setTimeout(() => successMsg.remove(), 3000);
                } else {
                    alert('حدث خطأ: ' + (data.error || 'غير معروف'));
                }
            })
            .catch(error => {
                alert('حدث خطأ في الإرسال');
                console.error(error);
            })
            .finally(() => {
                // Re-enable button
                sendBtn.disabled = false;
                sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i> إرسال';
            });
        });

        // Auto-refresh messages every 10 seconds
        setInterval(() => {
            fetch(`get_messages.php?ticket_id=<?= $ticket_id ?>`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.messages) {
                        // Update messages count in header
                        const header = document.querySelector('.card-header h2');
                        if (header) {
                            header.innerHTML = `<i class="fas fa-comments"></i> المحادثات (${data.messages.length})`;
                        }
                    }
                })
                .catch(error => console.error('Error refreshing messages:', error));
        }, 10000);
    </script>

    <!-- Confirmation Modal -->
    <div id="confirmModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 9999; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 16px; max-width: 450px; width: 90%; padding: 0; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.3); animation: slideUp 0.3s ease;">
            <div id="modalHeader" style="padding: 30px; text-align: center; color: white;">
                <div style="width: 70px; height: 70px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; font-size: 32px;">
                    <i id="modalIcon" class="fas fa-question-circle"></i>
                </div>
                <h2 id="modalTitle" style="font-size: 22px; font-weight: 800; margin-bottom: 8px;"></h2>
                <p id="modalMessage" style="font-size: 14px; opacity: 0.95;"></p>
            </div>
            
            <div style="padding: 25px;">
                <div style="display: flex; gap: 12px;">
                    <button onclick="closeConfirmModal()" style="flex: 1; padding: 14px; background: #f3f4f6; border: none; border-radius: 10px; font-weight: 600; font-size: 15px; cursor: pointer; color: #374151; transition: all 0.2s;">
                        <i class="fas fa-times"></i> إلغاء
                    </button>
                    <button id="confirmBtn" onclick="confirmAction()" style="flex: 1; padding: 14px; border: none; border-radius: 10px; font-weight: 600; font-size: 15px; cursor: pointer; color: white; transition: all 0.2s;">
                        <i class="fas fa-check"></i> تأكيد
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Refund Modal -->
    <?php if ($isCancellationTicket && $cancellationData): ?>
    <div id="refundModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 10000; align-items: center; justify-content: center;">
        <div style="background: white; border-radius: 20px; max-width: 550px; width: 90%; padding: 0; overflow: hidden; box-shadow: 0 25px 70px rgba(0,0,0,0.4); animation: slideUp 0.3s ease;">
            <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 35px; text-align: center; color: white; position: relative;">
                <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: url('data:image/svg+xml,<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 1440 320\"><path fill=\"rgba(255,255,255,0.1)\" d=\"M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,112C672,96,768,96,864,112C960,128,1056,160,1152,160C1248,160,1344,128,1392,112L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z\"></path></svg>') no-repeat bottom; background-size: cover;"></div>
                <div style="width: 90px; height: 90px; background: rgba(255,255,255,0.25); backdrop-filter: blur(10px); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 40px; position: relative; z-index: 1;">
                    <i class="fas fa-hand-holding-usd"></i>
                </div>
                <h2 style="font-size: 26px; font-weight: 900; margin-bottom: 10px; position: relative; z-index: 1;">تأكيد استرجاع النقود</h2>
                <p style="font-size: 15px; opacity: 0.95; position: relative; z-index: 1;">سيتم إلغاء الاشتراك واسترجاع الكوينات للمستخدم</p>
            </div>
            
            <div style="padding: 35px;">
                <div style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border-radius: 16px; padding: 25px; margin-bottom: 25px; border: 2px solid #10b981;">
                    <h3 style="font-size: 16px; font-weight: 700; color: #065f46; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-info-circle"></i>
                        تفاصيل العملية
                    </h3>
                    
                    <div style="display: grid; gap: 15px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: white; border-radius: 10px;">
                            <span style="font-size: 14px; color: #065f46; font-weight: 600;">المستخدم</span>
                            <span style="font-size: 15px; color: #047857; font-weight: 700;"><?= htmlspecialchars($ticket['username'] ?? 'N/A') ?></span>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: white; border-radius: 10px;">
                            <span style="font-size: 14px; color: #065f46; font-weight: 600;">نوع الاشتراك</span>
                            <span style="font-size: 15px; color: #047857; font-weight: 700; text-transform: uppercase;"><?= $cancellationData['subscription_tier'] ?? 'N/A' ?></span>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: white; border-radius: 10px;">
                            <span style="font-size: 14px; color: #065f46; font-weight: 600;">المبلغ المدفوع</span>
                            <span style="font-size: 15px; color: #047857; font-weight: 700;"><?= $cancellationData['paid_amount'] ?? 0 ?> <i class="fas fa-coins" style="color: #f59e0b;"></i></span>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px; background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-radius: 10px; border: 2px solid #f59e0b;">
                            <span style="font-size: 15px; color: #92400e; font-weight: 700;"><i class="fas fa-arrow-left"></i> مبلغ الاسترجاع</span>
                            <span style="font-size: 20px; color: #92400e; font-weight: 900;"><?= $cancellationData['refund_amount'] ?? 0 ?> <i class="fas fa-coins" style="color: #f59e0b;"></i></span>
                        </div>
                    </div>
                </div>
                
                <div style="background: #fef3c7; border-right: 4px solid #f59e0b; padding: 15px; border-radius: 10px; margin-bottom: 25px;">
                    <p style="font-size: 13px; color: #92400e; line-height: 1.6; margin: 0;">
                        <i class="fas fa-exclamation-triangle" style="color: #f59e0b;"></i>
                        <strong>تنبيه:</strong> عند الضغط على "تأكيد الاسترجاع"، سيتم:
                    </p>
                    <ul style="margin: 10px 0 0 20px; font-size: 13px; color: #92400e; line-height: 1.8;">
                        <li>إلغاء اشتراك المستخدم</li>
                        <li>إزالة علامة التوثيق</li>
                        <li>إضافة <?= $cancellationData['refund_amount'] ?? 0 ?> كوين لحساب المستخدم</li>
                        <li>تحديث حالة التذكرة إلى "منتهية"</li>
                    </ul>
                </div>
                
                <div style="display: flex; gap: 12px;">
                    <button onclick="closeRefundModal()" style="flex: 1; padding: 16px; background: #f3f4f6; border: none; border-radius: 12px; font-weight: 700; font-size: 15px; cursor: pointer; color: #374151; transition: all 0.2s;">
                        <i class="fas fa-times"></i> إلغاء
                    </button>
                    <button id="refundBtn" onclick="processRefund()" style="flex: 1; padding: 16px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none; border-radius: 12px; font-weight: 700; font-size: 15px; cursor: pointer; color: white; transition: all 0.2s;">
                        <i class="fas fa-check-circle"></i> تأكيد الاسترجاع
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <style>
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
    </style>

    <script>
    let confirmCallback = null;

    function showConfirmModal(title, message, type, callback) {
        const modal = document.getElementById('confirmModal');
        const header = document.getElementById('modalHeader');
        const icon = document.getElementById('modalIcon');
        const titleEl = document.getElementById('modalTitle');
        const messageEl = document.getElementById('modalMessage');
        const confirmBtn = document.getElementById('confirmBtn');
        
        titleEl.textContent = title;
        messageEl.textContent = message;
        confirmCallback = callback;
        
        // Set colors based on type
        if (type === 'claim') {
            header.style.background = 'linear-gradient(135deg, #1877f2 0%, #0a66c2 100%)';
            icon.className = 'fas fa-hand-paper';
            confirmBtn.style.background = 'linear-gradient(135deg, #1877f2 0%, #0a66c2 100%)';
        } else if (type === 'refuse') {
            header.style.background = 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)';
            icon.className = 'fas fa-times-circle';
            confirmBtn.style.background = 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)';
        } else if (type === 'done') {
            header.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
            icon.className = 'fas fa-check-circle';
            confirmBtn.style.background = 'linear-gradient(135deg, #10b981 0%, #059669 100%)';
        }
        
        modal.style.display = 'flex';
    }

    function closeConfirmModal() {
        document.getElementById('confirmModal').style.display = 'none';
        confirmCallback = null;
    }

    function confirmAction() {
        if (confirmCallback) {
            confirmCallback();
        }
        closeConfirmModal();
    }
    
    // Refund Modal
    function showRefundModal() {
        const modal = document.getElementById('refundModal');
        modal.style.display = 'flex';
    }
    
    function closeRefundModal() {
        document.getElementById('refundModal').style.display = 'none';
    }
    
    function processRefund() {
        const btn = document.getElementById('refundBtn');
        const originalHTML = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري المعالجة...';
        
        // Redirect to ticket_action.php with done action
        window.location.href = 'ticket_action.php?id=<?= $ticket_id ?>&action=done';
    }
    </script>
</body>
</html>
