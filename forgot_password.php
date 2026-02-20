<?php
session_start();
require_once __DIR__ . "/backend/config.php";

// If already logged in, redirect to home
if(isset($_SESSION['user_id'])){
    header("Location: /home.php");
    exit;
}

$error = '';
$success = '';
$step = 1; // Step 1: Enter username/email, Step 2: Confirm details

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    if(isset($_POST['step']) && $_POST['step'] == 1){
        $username_or_email = trim($_POST['username_or_email'] ?? '');
        
        if(empty($username_or_email)){
            $error = 'يرجى إدخال اسم المستخدم أو البريد الإلكتروني';
        } else {
            // Search for user
            $stmt = $conn->prepare("SELECT id, name, email, username FROM users WHERE username=? OR email=?");
            $stmt->bind_param("ss", $username_or_email, $username_or_email);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            
            if(!$user){
                $error = 'لم نجد حساب بهذا الاسم أو البريد الإلكتروني';
            } else {
                // Store user info in session temporarily
                $_SESSION['forgot_user'] = [
                    'id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'username' => $user['username']
                ];
                $step = 2;
            }
        }
    } elseif(isset($_POST['step']) && $_POST['step'] == 2){
        if(!isset($_SESSION['forgot_user'])){
            $error = 'حدث خطأ. يرجى المحاولة مرة أخرى';
        } else {
            $reason = trim($_POST['reason'] ?? '');
            $details = trim($_POST['details'] ?? '');
            
            if(empty($reason) || empty($details)){
                $error = 'جميع الحقول مطلوبة';
            } elseif(strlen($details) < 20){
                $error = 'التفاصيل يجب أن تكون 20 حرف على الأقل';
            } else {
                $user = $_SESSION['forgot_user'];
                
                // Create support ticket for account recovery
                $ticketStmt = $conn->prepare("
                    INSERT INTO support_tickets (user_id, username, email, status, ticket_code)
                    VALUES (?, ?, ?, 'open', ?)
                ");
                
                // Generate unique ticket code
                $ticket_code = 'TICKET-' . strtoupper(bin2hex(random_bytes(4)));
                
                $ticketStmt->bind_param("isss", $user['id'], $user['name'], $user['email'], $ticket_code);
                
                if($ticketStmt->execute()){
                    $ticket_id = $conn->insert_id;
                    
                    // Add recovery request message
                    $message = "طلب استرجاع حساب\n\n";
                    $message .= "اسم المستخدم: " . $user['username'] . "\n";
                    $message .= "البريد الإلكتروني: " . $user['email'] . "\n";
                    $message .= "السبب: " . $reason . "\n\n";
                    $message .= "التفاصيل:\n" . $details;
                    
                    $msgStmt = $conn->prepare("
                        INSERT INTO ticket_messages (ticket_id, sender_type, message)
                        VALUES (?, 'user', ?)
                    ");
                    $msgStmt->bind_param("is", $ticket_id, $message);
                    $msgStmt->execute();
                    
                    // Store ticket info in session for display
                    $_SESSION['recovery_ticket'] = [
                        'id' => $ticket_id,
                        'code' => $ticket_code,
                        'username' => $user['username'],
                        'email' => $user['email'],
                        'reason' => $reason,
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                    
                    // Clear forgot_user from session
                    unset($_SESSION['forgot_user']);
                    
                    $step = 3; // Success step
                } else {
                    $error = 'حدث خطأ أثناء معالجة الطلب';
                }
            }
        }
    }
}

$forgot_user = $_SESSION['forgot_user'] ?? null;
$recovery_ticket = $_SESSION['recovery_ticket'] ?? null;
if($recovery_ticket){
    unset($_SESSION['recovery_ticket']);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>استرجاع الحساب - ZuckBook</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
.lang-toggle {
    position: fixed;
    top: 20px;
    left: 20px;
    background: #1877f2;
    color: white;
    border: none;
    padding: 10px 16px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 6px;
    z-index: 1000;
}

.lang-toggle:hover {
    background: #166fe5;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(24, 119, 242, 0.3);
}

@media (max-width: 768px) {
    .lang-toggle {
        top: 10px;
        left: 10px;
        padding: 8px 12px;
        font-size: 12px;
    }
}

* { margin: 0; padding: 0; box-sizing: border-box; }
body { 
    font-family: 'Segoe UI', Helvetica, Arial, sans-serif;
    background: #f0f2f5;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.container {
    width: 100%;
    max-width: 500px;
}

.card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    overflow: hidden;
    animation: slideUp 0.5s ease;
}

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

.header {
    padding: 30px;
    text-align: center;
    border-bottom: 1px solid #e0e0e0;
}

.header h1 {
    font-size: 24px;
    font-weight: 700;
    color: #050505;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.header p {
    font-size: 14px;
    color: #65676b;
}

.content {
    padding: 30px;
}

.progress-bar {
    display: flex;
    gap: 10px;
    margin-bottom: 30px;
}

.progress-step {
    flex: 1;
    height: 4px;
    background: #e0e0e0;
    border-radius: 2px;
    overflow: hidden;
}

.progress-step.active {
    background: #1877f2;
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
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
    transition: 0.3s;
    font-family: inherit;
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

.alert {
    padding: 12px 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert-error {
    background: #fee;
    color: #c33;
    border: 1px solid #fcc;
}

.alert-success {
    background: #efe;
    color: #3c3;
    border: 1px solid #cfc;
}

.btn {
    width: 100%;
    padding: 12px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-primary {
    background: #1877f2;
    color: white;
}

.btn-primary:hover {
    background: #166fe5;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(24, 119, 242, 0.3);
}

.btn-secondary {
    background: #e4e6eb;
    color: #050505;
    margin-top: 15px;
}

.btn-secondary:hover {
    background: #d8dadf;
}

.footer {
    text-align: center;
    padding: 20px;
    background: #f8f9fa;
    border-top: 1px solid #e0e0e0;
    font-size: 14px;
    color: #666;
}

.footer a {
    color: #1877f2;
    text-decoration: none;
    font-weight: 600;
}

.footer a:hover {
    text-decoration: underline;
}

.user-info-box {
    background: #f0f7ff;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    border-right: 4px solid #1877f2;
}

.user-info-box h3 {
    color: #1877f2;
    font-size: 14px;
    margin-bottom: 10px;
}

.user-info-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    font-size: 14px;
    color: #555;
}

.user-info-label {
    font-weight: 600;
}

.success-box {
    text-align: center;
    padding: 40px 20px;
}

.success-icon {
    font-size: 60px;
    color: #4caf50;
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
    line-height: 1.6;
}

.ticket-info {
    background: #f0f2f5;
    padding: 20px;
    border-radius: 8px;
    margin: 20px 0;
    text-align: right;
}

.ticket-info .info-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px solid #e0e0e0;
    font-size: 14px;
}

.ticket-info .info-row:last-child {
    border-bottom: none;
}

.ticket-info .label {
    color: #65676b;
    font-weight: 500;
}

.ticket-info .value {
    color: #050505;
    font-weight: 600;
}

.reason-options {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-bottom: 20px;
}

.reason-option {
    padding: 12px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    cursor: pointer;
    text-align: center;
    transition: 0.3s;
    font-size: 14px;
}

.reason-option:hover {
    border-color: #667eea;
    background: #f0f7ff;
}

.reason-option input[type="radio"] {
    display: none;
}

.reason-option input[type="radio"]:checked + label {
    color: #667eea;
    font-weight: 600;
}

@media (max-width: 600px) {
    .header h1 {
        font-size: 24px;
    }
    
    .content {
        padding: 20px;
    }
    
    .reason-options {
        grid-template-columns: 1fr;
    }
}
</style>
</head>
<body>

<button class="lang-toggle" onclick="toggleLanguage()" id="langBtn">
    <i class="fas fa-globe"></i> English
</button>

<div class="container">
    <div class="card">
        <div class="header">
            <h1><i class="fas fa-key"></i> استرجاع الحساب</h1>
            <p>ساعدنا لاسترجاع حسابك</p>
        </div>

        <div class="content">
            <?php if($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if($success && $step == 3): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <!-- Progress Bar -->
            <div class="progress-bar">
                <div class="progress-step <?= $step >= 1 ? 'active' : '' ?>"></div>
                <div class="progress-step <?= $step >= 2 ? 'active' : '' ?>"></div>
                <div class="progress-step <?= $step >= 3 ? 'active' : '' ?>"></div>
            </div>

            <?php if($step == 1): ?>
                <!-- Step 1: Find Account -->
                <form method="POST">
                    <input type="hidden" name="step" value="1">
                    
                    <div class="form-group">
                        <label for="username_or_email">
                            <i class="fas fa-user"></i> اسم المستخدم أو البريد الإلكتروني
                        </label>
                        <input type="text" id="username_or_email" name="username_or_email" 
                               placeholder="أدخل اسم المستخدم أو البريد الإلكتروني" required>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> البحث عن الحساب
                    </button>
                </form>

                <button class="btn btn-secondary" onclick="window.location.href='/index.php'">
                    <i class="fas fa-arrow-left"></i> العودة إلى تسجيل الدخول
                </button>

            <?php elseif($step == 2 && $forgot_user): ?>
                <!-- Step 2: Confirm Details and Reason -->
                <div class="user-info-box">
                    <h3><i class="fas fa-info-circle"></i> تأكيد بيانات الحساب</h3>
                    <div class="user-info-item">
                        <span class="user-info-label">اسم المستخدم:</span>
                        <span><?= htmlspecialchars($forgot_user['username']) ?></span>
                    </div>
                    <div class="user-info-item">
                        <span class="user-info-label">الاسم:</span>
                        <span><?= htmlspecialchars($forgot_user['name']) ?></span>
                    </div>
                    <div class="user-info-item">
                        <span class="user-info-label">البريد:</span>
                        <span><?= htmlspecialchars($forgot_user['email']) ?></span>
                    </div>
                </div>

                <form method="POST">
                    <input type="hidden" name="step" value="2">

                    <div class="form-group">
                        <label><i class="fas fa-question-circle"></i> ما سبب نسيانك للحساب؟</label>
                        <select name="reason" required>
                            <option value="">اختر السبب</option>
                            <option value="نسيت كلمة المرور">نسيت كلمة المرور</option>
                            <option value="نسيت اسم المستخدم">نسيت اسم المستخدم</option>
                            <option value="حسابي مقفول">حسابي مقفول</option>
                            <option value="لا أستطيع تسجيل الدخول">لا أستطيع تسجيل الدخول</option>
                            <option value="مشكلة أخرى">مشكلة أخرى</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="details">
                            <i class="fas fa-comment"></i> تفاصيل إضافية
                        </label>
                        <textarea id="details" name="details" 
                                  placeholder="أخبرنا بالمزيد عن المشكلة..." required></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> إرسال طلب الاسترجاع
                    </button>
                </form>

                <button class="btn btn-secondary" onclick="window.location.href='/forgot_password.php'">
                    <i class="fas fa-arrow-left"></i> البحث عن حساب آخر
                </button>

            <?php elseif($step == 3 && $recovery_ticket): ?>
                <!-- Step 3: Success -->
                <div class="success-box">
                    <div class="success-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h2>تم فتح الطلب بنجاح! 🎉</h2>
                    <p>تم فتح طلب استرجاع الحساب بنجاح. سيقوم فريق الدعم بمراجعة طلبك.</p>
                    
                    <div class="ticket-info">
                        <div class="info-row">
                            <span class="label">رقم الطلب:</span>
                            <span class="value">#<?= str_pad($recovery_ticket['id'], 6, '0', STR_PAD_LEFT) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label">اسم المستخدم:</span>
                            <span class="value"><?= htmlspecialchars($recovery_ticket['username']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label">البريد الإلكتروني:</span>
                            <span class="value"><?= htmlspecialchars($recovery_ticket['email']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label">السبب:</span>
                            <span class="value"><?= htmlspecialchars($recovery_ticket['reason']) ?></span>
                        </div>
                        <div class="info-row">
                            <span class="label">الحالة:</span>
                            <span class="value" style="color: #1877f2;">قيد المراجعة</span>
                        </div>
                    </div>

                    <div style="background: #fff9e6; padding: 15px; border-radius: 8px; margin: 20px 0; text-align: center; border: 2px dashed #ffc107;">
                        <div style="font-size: 12px; color: #666; margin-bottom: 8px;">كود التكت الفريد (احفظه في مكان آمن)</div>
                        <div style="font-size: 24px; font-weight: bold; color: #ffc107; font-family: 'Courier New', monospace; letter-spacing: 2px; margin-bottom: 10px;">
                            <?= $recovery_ticket['code'] ?>
                        </div>
                        <button onclick="copyCode('<?= $recovery_ticket['code'] ?>')" style="padding: 8px 16px; background: #ffc107; color: black; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 12px;">
                            <i class="fas fa-copy"></i> نسخ الكود
                        </button>
                    </div>

                    <div style="background: #f0f7ff; padding: 15px; border-radius: 8px; margin: 20px 0; border-right: 4px solid #1877f2;">
                        <p style="color: #333; font-size: 14px; margin-bottom: 8px;">
                            <i class="fas fa-info-circle" style="color: #1877f2;"></i> <strong>ملاحظة مهمة:</strong>
                        </p>
                        <p style="color: #666; font-size: 13px;">
                            احفظ هذا الكود في مكان آمن. يمكنك استخدامه للبحث عن هذا التكت في أي وقت من خلال صفحة "عرض التكت".
                        </p>
                    </div>

                    <p style="color: #65676b; font-size: 13px; margin-top: 20px;">
                        <i class="fas fa-envelope"></i> سيتم إرسال تحديثات على بريدك الإلكتروني
                    </p>

                    <div style="margin-top: 30px; display: flex; gap: 10px; flex-direction: column;">
                        <a href="/index.php" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt"></i> العودة إلى تسجيل الدخول
                        </a>
                        <button class="btn btn-secondary" onclick="window.location.href='/view_ticket.php'">
                            <i class="fas fa-search"></i> عرض التكت
                        </button>
                    </div>
                </div>

                    <p style="color: #65676b; font-size: 13px; margin-top: 20px;">
                        <i class="fas fa-envelope"></i> سيتم إرسال تحديثات على بريدك الإلكتروني
                    </p>

                    <div style="margin-top: 30px; display: flex; gap: 10px; flex-direction: column;">
                        <a href="/index.php" class="btn btn-primary">
                            <i class="fas fa-sign-in-alt"></i> العودة إلى تسجيل الدخول
                        </a>
                        <button class="btn btn-secondary" onclick="window.location.href='/view_ticket.php'">
                            <i class="fas fa-search"></i> عرض التكت
                        </button>
                    </div>
                </div>

            <?php else: ?>
                <!-- Default: Step 1 -->
                <form method="POST">
                    <input type="hidden" name="step" value="1">
                    
                    <div class="form-group">
                        <label for="username_or_email">
                            <i class="fas fa-user"></i> اسم المستخدم أو البريد الإلكتروني
                        </label>
                        <input type="text" id="username_or_email" name="username_or_email" 
                               placeholder="أدخل اسم المستخدم أو البريد الإلكتروني" required>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> البحث عن الحساب
                    </button>
                </form>

                <button class="btn btn-secondary" onclick="window.location.href='/index.php'">
                    <i class="fas fa-arrow-left"></i> العودة إلى تسجيل الدخول
                </button>
            <?php endif; ?>
        </div>

        <div class="footer">
            هل تحتاج مساعدة؟ <a href="/create_ticket.php">فتح تكت دعم</a>
        </div>
    </div>
</div>

<script>
function copyCode(code) {
    navigator.clipboard.writeText(code).then(() => {
        alert('تم نسخ الكود بنجاح!');
    }).catch(() => {
        alert('فشل النسخ. حاول يدويًا.');
    });
}

let currentLang = 'ar';

const translations = {
    ar: {
        pageTitle: 'استرجاع الحساب - ZuckBook',
        headerTitle: 'استرجاع الحساب',
        headerSubtitle: 'ساعدنا لاسترجاع حسابك',
        step1Label: 'اسم المستخدم أو البريد الإلكتروني',
        step1Placeholder: 'أدخل اسم المستخدم أو البريد الإلكتروني',
        searchBtn: 'البحث عن الحساب',
        backToLogin: 'العودة إلى تسجيل الدخول',
        confirmDetails: 'تأكيد بيانات الحساب',
        username: 'اسم المستخدم:',
        name: 'الاسم:',
        email: 'البريد:',
        reasonLabel: 'ما سبب نسيانك للحساب؟',
        reasonSelect: 'اختر السبب',
        reasonPassword: 'نسيت كلمة المرور',
        reasonUsername: 'نسيت اسم المستخدم',
        reasonLocked: 'حسابي مقفول',
        reasonCantLogin: 'لا أستطيع تسجيل الدخول',
        reasonOther: 'مشكلة أخرى',
        detailsLabel: 'تفاصيل إضافية',
        detailsPlaceholder: 'أخبرنا بالمزيد عن المشكلة...',
        submitBtn: 'إرسال طلب الاسترجاع',
        searchAnother: 'البحث عن حساب آخر',
        successTitle: 'تم فتح الطلب بنجاح! 🎉',
        successMsg: 'تم فتح طلب استرجاع الحساب بنجاح. سيقوم فريق الدعم بمراجعة طلبك.',
        ticketNumber: 'رقم الطلب:',
        reason: 'السبب:',
        status: 'الحالة:',
        underReview: 'قيد المراجعة',
        ticketCodeLabel: 'كود التكت الفريد (احفظه في مكان آمن)',
        copyCode: 'نسخ الكود',
        importantNote: 'ملاحظة مهمة:',
        importantText: 'احفظ هذا الكود في مكان آمن. يمكنك استخدامه للبحث عن هذا التكت في أي وقت من خلال صفحة "عرض التكت".',
        emailUpdates: 'سيتم إرسال تحديثات على بريدك الإلكتروني',
        backToLoginBtn: 'العودة إلى تسجيل الدخول',
        viewTicket: 'عرض التكت',
        footerText: 'هل تحتاج مساعدة؟',
        footerLink: 'فتح تكت دعم'
    },
    en: {
        pageTitle: 'Account Recovery - ZuckBook',
        headerTitle: 'Account Recovery',
        headerSubtitle: 'Help us recover your account',
        step1Label: 'Username or Email',
        step1Placeholder: 'Enter your username or email',
        searchBtn: 'Search for Account',
        backToLogin: 'Back to Login',
        confirmDetails: 'Confirm Account Details',
        username: 'Username:',
        name: 'Name:',
        email: 'Email:',
        reasonLabel: 'Why did you forget your account?',
        reasonSelect: 'Choose a reason',
        reasonPassword: 'Forgot password',
        reasonUsername: 'Forgot username',
        reasonLocked: 'My account is locked',
        reasonCantLogin: 'Cannot login',
        reasonOther: 'Other issue',
        detailsLabel: 'Additional Details',
        detailsPlaceholder: 'Tell us more about the issue...',
        submitBtn: 'Submit Recovery Request',
        searchAnother: 'Search for Another Account',
        successTitle: 'Request Opened Successfully! 🎉',
        successMsg: 'Your account recovery request has been opened successfully. Our support team will review your request.',
        ticketNumber: 'Ticket Number:',
        reason: 'Reason:',
        status: 'Status:',
        underReview: 'Under Review',
        ticketCodeLabel: 'Unique Ticket Code (Save it in a safe place)',
        copyCode: 'Copy Code',
        importantNote: 'Important Note:',
        importantText: 'Save this code in a safe place. You can use it to search for this ticket anytime from the "View Ticket" page.',
        emailUpdates: 'Updates will be sent to your email',
        backToLoginBtn: 'Back to Login',
        viewTicket: 'View Ticket',
        footerText: 'Need help?',
        footerLink: 'Open Support Ticket'
    }
};

function toggleLanguage() {
    currentLang = currentLang === 'ar' ? 'en' : 'ar';
    localStorage.setItem('language', currentLang);
    updatePageLanguage();
}

function updatePageLanguage() {
    const t = translations[currentLang];
    const html = document.documentElement;
    
    html.dir = currentLang === 'ar' ? 'rtl' : 'ltr';
    html.lang = currentLang;
    
    document.title = t.pageTitle;
    
    document.getElementById('langBtn').innerHTML = currentLang === 'ar' 
        ? '<i class="fas fa-globe"></i> English' 
        : '<i class="fas fa-globe"></i> العربية';
    
    document.querySelectorAll('[data-i18n]').forEach(el => {
        const key = el.getAttribute('data-i18n');
        if (t[key]) {
            el.textContent = t[key];
        }
    });
    
    document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
        const key = el.getAttribute('data-i18n-placeholder');
        if (t[key]) {
            el.placeholder = t[key];
        }
    });
}

window.addEventListener('DOMContentLoaded', () => {
    const savedLang = localStorage.getItem('language');
    if (savedLang) {
        currentLang = savedLang;
        updatePageLanguage();
    }
});
</script>
