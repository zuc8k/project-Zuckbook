<?php
session_start();
require_once __DIR__ . "/backend/config.php";

if(!isset($_SESSION['user_id'])){
    header("Location: /index.php");
    exit;
}

$user_id = intval($_SESSION['user_id']);
$error = '';
$success = '';

// Get user info
$userStmt = $conn->prepare("SELECT name, email FROM users WHERE id=?");
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();

if(!$user){
    session_destroy();
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $subject = trim($_POST['subject'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $priority = trim($_POST['priority'] ?? 'normal');
    
    if(empty($subject) || empty($category) || empty($message)){
        $error = 'all_fields_required';
    } elseif(strlen($message) < 20){
        $error = 'message_too_short';
    } else {
        // Generate random ticket code
        $ticket_code = 'TICKET-' . strtoupper(bin2hex(random_bytes(4)));
        
        // Create ticket
        $ticketStmt = $conn->prepare("
            INSERT INTO support_tickets (user_id, username, email, status, ticket_code)
            VALUES (?, ?, ?, 'open', ?)
        ");
        $ticketStmt->bind_param("isss", $user_id, $user['name'], $user['email'], $ticket_code);
        
        if($ticketStmt->execute()){
            $ticket_id = $conn->insert_id;
            
            // Add first message
            $msgStmt = $conn->prepare("
                INSERT INTO ticket_messages (ticket_id, sender_type, message)
                VALUES (?, 'user', ?)
            ");
            $msgStmt->bind_param("is", $ticket_id, $message);
            $msgStmt->execute();
            
            // Store ticket info in session for display
            $_SESSION['ticket_created'] = [
                'id' => $ticket_id,
                'code' => $ticket_code,
                'subject' => $subject,
                'category' => $category,
                'priority' => $priority,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $success = 'ticket_created';
        } else {
            $error = 'ticket_error';
        }
    }
}

$ticket_created = $_SESSION['ticket_created'] ?? null;
if($ticket_created){
    unset($_SESSION['ticket_created']);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl" id="html">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title data-i18n="page_title">فتح تكت دعم - ZuckBook</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

body { 
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    background: #f0f2f5;
    min-height: 100vh;
    padding: 20px;
}

.lang-toggle {
    position: fixed;
    top: 20px;
    right: 20px;
    background: #1877f2;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    z-index: 1000;
    box-shadow: 0 2px 8px rgba(24, 119, 242, 0.3);
}

.lang-toggle:hover {
    background: #166fe5;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(24, 119, 242, 0.4);
}

.container {
    max-width: 900px;
    margin: 60px auto 0;
}

.header {
    text-align: center;
    margin-bottom: 40px;
}

.header-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #1877f2, #0a66c2);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    font-size: 36px;
    color: white;
    box-shadow: 0 8px 24px rgba(24, 119, 242, 0.3);
}

.header h1 {
    font-size: 32px;
    color: #050505;
    margin-bottom: 10px;
    font-weight: 700;
}

.header p {
    color: #65676b;
    font-size: 16px;
}

.content-wrapper {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 20px;
}

.card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    padding: 30px;
}

.alert {
    padding: 15px 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 14px;
    font-weight: 500;
}

.alert-error {
    background: #fee;
    color: #c33;
    border: 1px solid #fcc;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert i {
    font-size: 18px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #050505;
    font-size: 14px;
}

.form-group input,
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    font-size: 14px;
    font-family: inherit;
    transition: 0.2s;
}

.form-group input:focus,
.form-group textarea:focus,
.form-group select:focus {
    outline: none;
    border-color: #1877f2;
    box-shadow: 0 0 0 3px rgba(24, 119, 242, 0.1);
}

.form-group textarea {
    resize: vertical;
    min-height: 120px;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

.btn {
    width: 100%;
    padding: 14px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-primary {
    background: linear-gradient(135deg, #1877f2, #0a66c2);
    color: white;
    box-shadow: 0 4px 12px rgba(24, 119, 242, 0.3);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(24, 119, 242, 0.4);
}

.sidebar-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
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

.info-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 0;
    border-bottom: 1px solid #e5e7eb;
    font-size: 14px;
}

.info-item:last-child {
    border-bottom: none;
}

.info-label {
    color: #65676b;
    font-weight: 500;
}

.info-value {
    color: #050505;
    font-weight: 600;
    margin-left: auto;
}

.tips-list {
    list-style: none;
    padding: 0;
}

.tips-list li {
    padding: 10px 0;
    padding-right: 25px;
    position: relative;
    color: #65676b;
    font-size: 13px;
    line-height: 1.5;
}

.tips-list li:before {
    content: '✓';
    position: absolute;
    right: 0;
    color: #1877f2;
    font-weight: bold;
}

.success-box {
    text-align: center;
    padding: 40px 20px;
}

.success-icon {
    font-size: 64px;
    color: #10b981;
    margin-bottom: 20px;
}

.success-box h2 {
    font-size: 24px;
    color: #050505;
    margin-bottom: 10px;
}

.success-box p {
    color: #65676b;
    margin-bottom: 20px;
}

.ticket-info {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin: 20px 0;
}

.ticket-info-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #e5e7eb;
}

.ticket-info-row:last-child {
    border-bottom: none;
}

.ticket-code {
    font-family: monospace;
    font-size: 18px;
    font-weight: 700;
    color: #1877f2;
    letter-spacing: 1px;
}

@media (max-width: 768px) {
    .content-wrapper {
        grid-template-columns: 1fr;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .header h1 {
        font-size: 24px;
    }
    
    .lang-toggle {
        top: 10px;
        right: 10px;
        padding: 8px 16px;
        font-size: 12px;
    }
}
</style>
</head>
<body>

<button class="lang-toggle" onclick="toggleLanguage()" id="langBtn">
    <i class="fas fa-globe"></i>
    <span data-i18n="lang_btn">English</span>
</button>

<div class="container">
    <div class="header">
        <div class="header-icon">
            <i class="fas fa-headset"></i>
        </div>
        <h1 data-i18n="page_heading">فتح تكت دعم</h1>
        <p data-i18n="page_subtitle">نحن هنا لمساعدتك. أخبرنا بمشكلتك وسنقوم بحلها في أسرع وقت</p>
    </div>

    <div class="content-wrapper">
        <div>
            <?php if($ticket_created): ?>
                <div class="card success-box">
                    <div class="success-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h2 data-i18n="success_title">تم فتح التكت بنجاح! 🎉</h2>
                    <p data-i18n="success_message">شكراً لتواصلك معنا. سيتم الرد على تكتك في أقرب وقت ممكن.</p>
                    
                    <div class="ticket-info">
                        <div class="ticket-info-row">
                            <span data-i18n="ticket_number">رقم التكت:</span>
                            <span>#<?= str_pad($ticket_created['id'], 6, '0', STR_PAD_LEFT) ?></span>
                        </div>
                        <div class="ticket-info-row">
                            <span data-i18n="ticket_code">كود التكت:</span>
                            <span class="ticket-code"><?= $ticket_created['code'] ?></span>
                        </div>
                    </div>

                    <div style="display: flex; gap: 10px; margin-top: 20px;">
                        <a href="/home.php" class="btn btn-primary" style="text-decoration: none;">
                            <i class="fas fa-home"></i>
                            <span data-i18n="back_home">العودة للرئيسية</span>
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <?php if($error): ?>
                        <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <span data-i18n="error_<?= $error ?>">
                                <?php
                                $errors = [
                                    'all_fields_required' => 'جميع الحقول مطلوبة',
                                    'message_too_short' => 'الرسالة يجب أن تكون 20 حرف على الأقل',
                                    'ticket_error' => 'حدث خطأ أثناء فتح التكت'
                                ];
                                echo $errors[$error] ?? $error;
                                ?>
                            </span>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="form-group">
                            <label for="subject">
                                <i class="fas fa-heading"></i>
                                <span data-i18n="subject_label">موضوع التكت</span>
                            </label>
                            <input type="text" id="subject" name="subject" data-i18n-placeholder="subject_placeholder" placeholder="مثال: مشكلة في تسجيل الدخول" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="category">
                                    <i class="fas fa-list"></i>
                                    <span data-i18n="category_label">الفئة</span>
                                </label>
                                <select id="category" name="category" required>
                                    <option value="" data-i18n="category_select">اختر الفئة</option>
                                    <option value="technical" data-i18n="category_technical">مشكلة تقنية</option>
                                    <option value="account" data-i18n="category_account">مشكلة في الحساب</option>
                                    <option value="payment" data-i18n="category_payment">مشكلة في الدفع</option>
                                    <option value="feature" data-i18n="category_feature">طلب ميزة جديدة</option>
                                    <option value="other" data-i18n="category_other">أخرى</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="priority">
                                    <i class="fas fa-exclamation"></i>
                                    <span data-i18n="priority_label">الأولوية</span>
                                </label>
                                <select id="priority" name="priority" required>
                                    <option value="low" data-i18n="priority_low">منخفضة</option>
                                    <option value="normal" selected data-i18n="priority_normal">عادية</option>
                                    <option value="high" data-i18n="priority_high">عالية</option>
                                    <option value="urgent" data-i18n="priority_urgent">عاجلة</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="message">
                                <i class="fas fa-comment"></i>
                                <span data-i18n="message_label">تفاصيل المشكلة</span>
                            </label>
                            <textarea id="message" name="message" data-i18n-placeholder="message_placeholder" placeholder="أخبرنا بالمشكلة بالتفصيل..." required></textarea>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i>
                            <span data-i18n="submit_btn">فتح التكت</span>
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <div class="sidebar">
            <div class="sidebar-card">
                <div class="sidebar-title">
                    <i class="fas fa-user"></i>
                    <span data-i18n="user_info_title">بيانات حسابك</span>
                </div>
                <div class="info-item">
                    <span class="info-label" data-i18n="name_label">الاسم:</span>
                    <span class="info-value"><?= htmlspecialchars($user['name']) ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label" data-i18n="email_label">البريد:</span>
                    <span class="info-value" style="font-size: 12px;"><?= htmlspecialchars($user['email']) ?></span>
                </div>
            </div>

            <div class="sidebar-card">
                <div class="sidebar-title">
                    <i class="fas fa-lightbulb"></i>
                    <span data-i18n="tips_title">نصائح مفيدة</span>
                </div>
                <ul class="tips-list">
                    <li data-i18n="tip1">كن واضحاً في وصف المشكلة</li>
                    <li data-i18n="tip2">أضف أي تفاصيل إضافية مهمة</li>
                    <li data-i18n="tip3">حدد الأولوية بدقة</li>
                    <li data-i18n="tip4">تحقق من بريدك للتحديثات</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
let currentLang = localStorage.getItem('language') || 'ar';

const translations = {
    ar: {
        page_title: 'فتح تكت دعم - ZuckBook',
        lang_btn: 'English',
        page_heading: 'فتح تكت دعم',
        page_subtitle: 'نحن هنا لمساعدتك. أخبرنا بمشكلتك وسنقوم بحلها في أسرع وقت',
        success_title: 'تم فتح التكت بنجاح! 🎉',
        success_message: 'شكراً لتواصلك معنا. سيتم الرد على تكتك في أقرب وقت ممكن.',
        ticket_number: 'رقم التكت:',
        ticket_code: 'كود التكت:',
        back_home: 'العودة للرئيسية',
        error_all_fields_required: 'جميع الحقول مطلوبة',
        error_message_too_short: 'الرسالة يجب أن تكون 20 حرف على الأقل',
        error_ticket_error: 'حدث خطأ أثناء فتح التكت',
        subject_label: 'موضوع التكت',
        subject_placeholder: 'مثال: مشكلة في تسجيل الدخول',
        category_label: 'الفئة',
        category_select: 'اختر الفئة',
        category_technical: 'مشكلة تقنية',
        category_account: 'مشكلة في الحساب',
        category_payment: 'مشكلة في الدفع',
        category_feature: 'طلب ميزة جديدة',
        category_other: 'أخرى',
        priority_label: 'الأولوية',
        priority_low: 'منخفضة',
        priority_normal: 'عادية',
        priority_high: 'عالية',
        priority_urgent: 'عاجلة',
        message_label: 'تفاصيل المشكلة',
        message_placeholder: 'أخبرنا بالمشكلة بالتفصيل...',
        submit_btn: 'فتح التكت',
        user_info_title: 'بيانات حسابك',
        name_label: 'الاسم:',
        email_label: 'البريد:',
        tips_title: 'نصائح مفيدة',
        tip1: 'كن واضحاً في وصف المشكلة',
        tip2: 'أضف أي تفاصيل إضافية مهمة',
        tip3: 'حدد الأولوية بدقة',
        tip4: 'تحقق من بريدك للتحديثات'
    },
    en: {
        page_title: 'Open Support Ticket - ZuckBook',
        lang_btn: 'العربية',
        page_heading: 'Open Support Ticket',
        page_subtitle: 'We are here to help you. Tell us your problem and we will solve it as soon as possible',
        success_title: 'Ticket Created Successfully! 🎉',
        success_message: 'Thank you for contacting us. Your ticket will be answered as soon as possible.',
        ticket_number: 'Ticket Number:',
        ticket_code: 'Ticket Code:',
        back_home: 'Back to Home',
        error_all_fields_required: 'All fields are required',
        error_message_too_short: 'Message must be at least 20 characters',
        error_ticket_error: 'An error occurred while creating the ticket',
        subject_label: 'Ticket Subject',
        subject_placeholder: 'Example: Login issue',
        category_label: 'Category',
        category_select: 'Choose a category',
        category_technical: 'Technical Issue',
        category_account: 'Account Issue',
        category_payment: 'Payment Issue',
        category_feature: 'Feature Request',
        category_other: 'Other',
        priority_label: 'Priority',
        priority_low: 'Low',
        priority_normal: 'Normal',
        priority_high: 'High',
        priority_urgent: 'Urgent',
        message_label: 'Problem Details',
        message_placeholder: 'Tell us about the problem in detail...',
        submit_btn: 'Open Ticket',
        user_info_title: 'Your Account Info',
        name_label: 'Name:',
        email_label: 'Email:',
        tips_title: 'Helpful Tips',
        tip1: 'Be clear in describing the problem',
        tip2: 'Add any additional important details',
        tip3: 'Set priority accurately',
        tip4: 'Check your email for updates'
    }
};

function toggleLanguage() {
    currentLang = currentLang === 'ar' ? 'en' : 'ar';
    localStorage.setItem('language', currentLang);
    updatePageLanguage();
}

function updatePageLanguage() {
    const t = translations[currentLang];
    const html = document.getElementById('html');
    
    html.dir = currentLang === 'ar' ? 'rtl' : 'ltr';
    html.lang = currentLang;
    
    document.title = t.page_title;
    
    // Update all elements with data-i18n
    document.querySelectorAll('[data-i18n]').forEach(el => {
        const key = el.getAttribute('data-i18n');
        if (t[key]) {
            el.textContent = t[key];
        }
    });
    
    // Update placeholders
    document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
        const key = el.getAttribute('data-i18n-placeholder');
        if (t[key]) {
            el.placeholder = t[key];
        }
    });
    
    // Update lang button position
    const langBtn = document.querySelector('.lang-toggle');
    if(currentLang === 'ar') {
        langBtn.style.right = '20px';
        langBtn.style.left = 'auto';
    } else {
        langBtn.style.left = '20px';
        langBtn.style.right = 'auto';
    }
}

// Initialize language on page load
window.addEventListener('DOMContentLoaded', () => {
    updatePageLanguage();
});
</script>

</body>
</html>
