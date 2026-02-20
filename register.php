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
$name = '';
$email = '';
$username = '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if(empty($name) || empty($email) || empty($username) || empty($password)){
        $error = 'جميع الحقول مطلوبة';
    } elseif(strlen($password) < 6){
        $error = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
    } elseif($password !== $confirm_password){
        $error = 'كلمات المرور غير متطابقة';
    } else {
        // Check if email exists
        $checkStmt = $conn->prepare("SELECT id FROM users WHERE email=? OR username=?");
        $checkStmt->bind_param("ss", $email, $username);
        $checkStmt->execute();
        
        if($checkStmt->get_result()->num_rows > 0){
            $error = 'البريد الإلكتروني أو اسم المستخدم موجود بالفعل';
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            
            // Insert user
            $stmt = $conn->prepare("INSERT INTO users (name, email, username, password) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $name, $email, $username, $hashed_password);
            
            if($stmt->execute()){
                $user_id = $conn->insert_id;
                
                // Generate avatar URL using DiceBear API
                $avatar_url = 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . urlencode($username);
                
                // Download and save avatar locally
                $uploads_dir = __DIR__ . '/uploads/avatars/';
                if (!is_dir($uploads_dir)) {
                    mkdir($uploads_dir, 0755, true);
                }
                
                $avatar_filename = 'avatar_' . $user_id . '.svg';
                $avatar_path = $uploads_dir . $avatar_filename;
                
                // Download avatar from DiceBear
                $avatar_content = @file_get_contents($avatar_url);
                if ($avatar_content) {
                    file_put_contents($avatar_path, $avatar_content);
                    $avatar_db_path = '/uploads/avatars/' . $avatar_filename;
                } else {
                    // Fallback to URL if download fails
                    $avatar_db_path = $avatar_url;
                }
                
                // Update user with avatar
                $updateStmt = $conn->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
                $updateStmt->bind_param("si", $avatar_db_path, $user_id);
                $updateStmt->execute();
                
                // Create welcome notification
                $notifStmt = $conn->prepare("
                    INSERT INTO notifications (user_id, type, message)
                    VALUES (?, ?, ?)
                ");
                $type = 'welcome';
                $message = 'مرحباً بك في ZuckBook! 🎉';
                $notifStmt->bind_param("iss", $user_id, $type, $message);
                $notifStmt->execute();
                
                $success = 'تم إنشاء الحساب بنجاح! يمكنك الآن تسجيل الدخول.';
                
                // Clear form
                $name = $email = $username = '';
            } else {
                $error = 'حدث خطأ أثناء إنشاء الحساب';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>إنشاء حساب - ZuckBook</title>
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
    max-width: 900px;
}

.content-wrapper {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    align-items: center;
}

.logo-section {
    text-align: center;
}

.logo-section svg {
    width: 200px;
    height: 200px;
    margin-bottom: 20px;
}

.logo-section h1 {
    font-size: 48px;
    font-weight: 700;
    color: #050505;
    margin-bottom: 12px;
}

.logo-section p {
    font-size: 16px;
    color: #65676b;
    line-height: 1.6;
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

.header h2 {
    font-size: 24px;
    font-weight: 700;
    color: #050505;
    margin-bottom: 8px;
}

.header p {
    font-size: 14px;
    color: #65676b;
}

.content {
    padding: 30px;
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

.form-group input {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
    transition: 0.3s;
    font-family: inherit;
}

.form-group input:focus {
    outline: none;
    border-color: #1877f2;
    box-shadow: 0 0 0 3px rgba(24, 119, 242, 0.1);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
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

.steps {
    background: #f0f7ff;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    border-right: 4px solid #1877f2;
}

.steps h3 {
    color: #1877f2;
    font-size: 14px;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.steps ol {
    margin-right: 20px;
    font-size: 13px;
    color: #555;
    line-height: 1.8;
}

.steps li {
    margin-bottom: 6px;
}

@media (max-width: 768px) {
    .content-wrapper {
        grid-template-columns: 1fr;
    }
    
    .logo-section {
        display: none;
    }
    
    .header h2 {
        font-size: 20px;
    }
    
    .logo-section svg {
        width: 150px;
        height: 150px;
    }
    
    .logo-section h1 {
        font-size: 36px;
    }
}

@media (max-width: 600px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .content {
        padding: 20px;
    }
}
</style>
</head>
<body>

<button class="lang-toggle" onclick="toggleLanguage()" id="langBtn">
    <i class="fas fa-globe"></i> English
</button>

<div class="container">
    <div class="content-wrapper">
        <!-- Logo Section -->
        <div class="logo-section">
            <svg viewBox="0 0 400 400" xmlns="http://www.w3.org/2000/svg">
                <!-- Z Shape with gradient -->
                <defs>
                    <linearGradient id="zGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                        <stop offset="0%" style="stop-color:#00a8ff;stop-opacity:1" />
                        <stop offset="100%" style="stop-color:#0066cc;stop-opacity:1" />
                    </linearGradient>
                    <filter id="glow">
                        <feGaussianBlur stdDeviation="3" result="coloredBlur"/>
                        <feMerge>
                            <feMergeNode in="coloredBlur"/>
                            <feMergeNode in="SourceGraphic"/>
                        </feMerge>
                    </filter>
                </defs>
                
                <!-- Z Letter -->
                <path d="M 80 80 L 280 80 L 100 280 L 280 280" 
                      fill="none" stroke="url(#zGradient)" stroke-width="35" 
                      stroke-linecap="round" stroke-linejoin="round" filter="url(#glow)"/>
                
                <!-- Orbit circle -->
                <circle cx="200" cy="200" r="150" fill="none" stroke="url(#zGradient)" 
                        stroke-width="8" opacity="0.3" stroke-dasharray="5,5"/>
                
                <!-- Star -->
                <circle cx="280" cy="100" r="15" fill="#00a8ff" filter="url(#glow)"/>
                <circle cx="280" cy="100" r="8" fill="white" opacity="0.8"/>
            </svg>
            <h1>ZuckBook</h1>
            <p data-i18n="logoText">تواصل مع أصدقائك وشارك لحظاتك المميزة</p>
        </div>

        <!-- Register Card -->
        <div class="card">
            <div class="header">
                <h2 data-i18n="headerTitle">إنشاء حساب</h2>
                <p data-i18n="headerSubtitle">انضم إلى مجتمع ZuckBook الآن</p>
            </div>

            <div class="content">
                <?php if($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <?php if($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <span data-i18n="successMsg"><?= htmlspecialchars($success) ?></span>
                    </div>
                    <a href="/index.php" class="btn btn-primary" data-i18n="signInNow">
                        <i class="fas fa-sign-in-alt"></i> تسجيل الدخول الآن
                    </a>
                <?php else: ?>
                    <div class="steps">
                        <h3 data-i18n="stepsTitle"><i class="fas fa-list-ol"></i> خطوات الإنشاء</h3>
                        <ol>
                            <li data-i18n="step1">أدخل بيانات حسابك الشخصية</li>
                            <li data-i18n="step2">اختر اسم مستخدم فريد</li>
                            <li data-i18n="step3">أنشئ كلمة مرور قوية</li>
                            <li data-i18n="step4">اضغط "إنشاء الحساب"</li>
                            <li data-i18n="step5">سجل الدخول واستمتع!</li>
                        </ol>
                    </div>

                    <form method="POST">
                        <div class="form-group">
                            <label for="name" data-i18n="nameLabel"><i class="fas fa-user"></i> الاسم الكامل</label>
                            <input type="text" id="name" name="name" data-i18n-placeholder="namePlaceholder" placeholder="اسمك" value="<?= htmlspecialchars($name) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="email" data-i18n="emailLabel"><i class="fas fa-envelope"></i> البريد الإلكتروني</label>
                            <input type="email" id="email" name="email" data-i18n-placeholder="emailPlaceholder" placeholder="البريد الخاص بيك" value="<?= htmlspecialchars($email) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="username" data-i18n="usernameLabel"><i class="fas fa-at"></i> اسم المستخدم</label>
                            <input type="text" id="username" name="username" data-i18n-placeholder="usernamePlaceholder" placeholder="اسم المستخدم" value="<?= htmlspecialchars($username) ?>" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="password" data-i18n="passwordLabel"><i class="fas fa-lock"></i> كلمة المرور</label>
                                <input type="password" id="password" name="password" data-i18n-placeholder="passwordPlaceholder" placeholder="••••••" required>
                            </div>

                            <div class="form-group">
                                <label for="confirm_password" data-i18n="confirmPasswordLabel"><i class="fas fa-lock"></i> تأكيد كلمة المرور</label>
                                <input type="password" id="confirm_password" name="confirm_password" data-i18n-placeholder="confirmPasswordPlaceholder" placeholder="••••••" required>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary" data-i18n="createBtn">
                            <i class="fas fa-user-plus"></i> إنشاء الحساب
                        </button>
                    </form>

                    <button class="btn btn-secondary" onclick="window.location.href='/index.php'" data-i18n="backToLogin">
                        <i class="fas fa-arrow-left"></i> العودة إلى تسجيل الدخول
                    </button>
                <?php endif; ?>
            </div>

            <div class="footer">
                <span data-i18n="footerText">بإنشاء حساب، أنت توافق على</span> <a href="#" data-i18n="termsLink">شروط الخدمة</a> و <a href="#" data-i18n="privacyLink">سياسة الخصوصية</a>
            </div>
        </div>
    </div>
</div>

</body>
</html>

<script>
let currentLang = 'ar';

const translations = {
    ar: {
        pageTitle: 'إنشاء حساب - ZuckBook',
        headerTitle: 'إنشاء حساب',
        headerSubtitle: 'انضم إلى مجتمع ZuckBook الآن',
        stepsTitle: 'خطوات الإنشاء',
        step1: 'أدخل بيانات حسابك الشخصية',
        step2: 'اختر اسم مستخدم فريد',
        step3: 'أنشئ كلمة مرور قوية',
        step4: 'اضغط "إنشاء الحساب"',
        step5: 'سجل الدخول واستمتع!',
        nameLabel: 'الاسم الكامل',
        namePlaceholder: 'اسمك',
        emailLabel: 'البريد الإلكتروني',
        emailPlaceholder: 'البريد الخاص بيك',
        usernameLabel: 'اسم المستخدم',
        usernamePlaceholder: 'اسم المستخدم',
        passwordLabel: 'كلمة المرور',
        passwordPlaceholder: '••••••',
        confirmPasswordLabel: 'تأكيد كلمة المرور',
        confirmPasswordPlaceholder: '••••••',
        createBtn: 'إنشاء الحساب',
        backToLogin: 'العودة إلى تسجيل الدخول',
        successMsg: 'تم إنشاء الحساب بنجاح! يمكنك الآن تسجيل الدخول.',
        signInNow: 'تسجيل الدخول الآن',
        footerText: 'بإنشاء حساب، أنت توافق على',
        termsLink: 'شروط الخدمة',
        privacyLink: 'سياسة الخصوصية',
        logoText: 'تواصل مع أصدقائك وشارك لحظاتك المميزة'
    },
    en: {
        pageTitle: 'Create Account - ZuckBook',
        headerTitle: 'Create Account',
        headerSubtitle: 'Join ZuckBook community now',
        stepsTitle: 'Creation Steps',
        step1: 'Enter your personal account information',
        step2: 'Choose a unique username',
        step3: 'Create a strong password',
        step4: 'Click "Create Account"',
        step5: 'Sign in and enjoy!',
        nameLabel: 'Full Name',
        namePlaceholder: 'Your name',
        emailLabel: 'Email',
        emailPlaceholder: 'Your email',
        usernameLabel: 'Username',
        usernamePlaceholder: 'Username',
        passwordLabel: 'Password',
        passwordPlaceholder: '••••••',
        confirmPasswordLabel: 'Confirm Password',
        confirmPasswordPlaceholder: '••••••',
        createBtn: 'Create Account',
        backToLogin: 'Back to Login',
        successMsg: 'Account created successfully! You can now sign in.',
        signInNow: 'Sign In Now',
        footerText: 'By creating an account, you agree to',
        termsLink: 'Terms of Service',
        privacyLink: 'Privacy Policy',
        logoText: 'Connect with friends and share your special moments'
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

</body>
</html>
