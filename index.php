<?php
session_start();
require_once __DIR__ . "/backend/config.php";

// Check Remember Me token
require_once __DIR__ . "/backend/check_remember_me.php";

// If already logged in, redirect to home
if (isset($_SESSION['user_id'])) {
    header("Location: /home.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ZuckBook - تسجيل الدخول</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
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
    font-size: 15px;
    transition: all 0.3s ease;
    font-family: inherit;
}

.form-group input:focus {
    outline: none;
    border-color: #1877f2;
    box-shadow: 0 0 0 3px rgba(24, 119, 242, 0.1);
    background: #f8f9ff;
}

.form-group input::placeholder {
    color: #999;
}

.remember-me-container {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding: 0 4px;
}

.remember-me-label {
    display: flex;
    align-items: center;
    cursor: pointer;
    user-select: none;
    position: relative;
}

.remember-me-checkbox {
    position: absolute;
    opacity: 0;
    cursor: pointer;
    height: 0;
    width: 0;
}

.checkmark {
    position: relative;
    height: 20px;
    width: 20px;
    background-color: #fff;
    border: 2px solid #ddd;
    border-radius: 4px;
    transition: all 0.3s ease;
    margin-left: 8px;
}

.remember-me-label:hover .checkmark {
    border-color: #1877f2;
    background-color: #f0f7ff;
}

.remember-me-checkbox:checked ~ .checkmark {
    background-color: #1877f2;
    border-color: #1877f2;
}

.checkmark:after {
    content: "";
    position: absolute;
    display: none;
    left: 6px;
    top: 2px;
    width: 5px;
    height: 10px;
    border: solid white;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
}

.remember-me-checkbox:checked ~ .checkmark:after {
    display: block;
}

.remember-me-text {
    font-size: 14px;
    color: #050505;
    font-weight: 500;
}

.forgot-link {
    font-size: 14px;
    color: #1877f2;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s ease;
}

.forgot-link:hover {
    color: #166fe5;
    text-decoration: underline;
}

.btn-login {
    width: 100%;
    padding: 12px;
    border: none;
    border-radius: 8px;
    background: #1877f2;
    color: white;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-bottom: 16px;
}

.btn-login:hover {
    background: #166fe5;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(24, 119, 242, 0.3);
}

.btn-login:active {
    transform: translateY(0);
}

.divider {
    display: flex;
    align-items: center;
    margin: 24px 0;
    color: #999;
    font-size: 13px;
}

.divider::before,
.divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: #e0e0e0;
}

.divider span {
    padding: 0 12px;
}

.links {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.link-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 15px;
    background: #f8f9fa;
    border-radius: 8px;
    text-decoration: none;
    color: #333;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s ease;
    border: 1px solid #e0e0e0;
}

.link-item:hover {
    background: #f0f2f5;
    border-color: #1877f2;
    color: #1877f2;
}

.link-item i {
    font-size: 16px;
}

.link-register {
    background: linear-gradient(135deg, rgba(24, 119, 242, 0.1) 0%, rgba(24, 119, 242, 0.05) 100%);
    border: 2px solid #1877f2;
    color: #1877f2;
}

.link-register:hover {
    background: linear-gradient(135deg, rgba(24, 119, 242, 0.2) 0%, rgba(24, 119, 242, 0.1) 100%);
}

.link-recover {
    color: #ff9800;
}

.link-recover:hover {
    color: #ff9800;
    border-color: #ff9800;
    background: #fff8f0;
}

.link-ticket {
    color: #ffc107;
}

.link-ticket:hover {
    color: #ffc107;
    border-color: #ffc107;
    background: #fffbf0;
}

.footer {
    text-align: center;
    padding: 20px 30px;
    background: #f8f9fa;
    border-top: 1px solid #e0e0e0;
    font-size: 12px;
    color: #999;
}

.footer a {
    color: #1877f2;
    text-decoration: none;
    font-weight: 600;
}

.footer a:hover {
    text-decoration: underline;
}

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
    
    .lang-toggle {
        top: 10px;
        left: 10px;
        padding: 8px 12px;
        font-size: 12px;
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

        <!-- Login Card -->
        <div class="card">
            <div class="header">
                <h2 data-i18n="welcome">أهلا بك</h2>
                <p data-i18n="signIn">أدخل بيانات حسابك للمتابعة</p>
            </div>

            <div class="content">
                <!-- LOGIN FORM -->
                <form action="/backend/login.php" method="POST" id="loginForm">
                    <div class="form-group">
                        <label for="login" data-i18n="emailLabel"><i class="fas fa-user"></i> البريد الإلكتروني أو اسم المستخدم</label>
                        <input type="text" id="login" name="login" data-i18n-placeholder="enterEmail" placeholder="أدخل بريدك أو اسم المستخدم" required>
                    </div>

                    <div class="form-group">
                        <label for="password" data-i18n="password"><i class="fas fa-lock"></i> كلمة المرور</label>
                        <input type="password" id="password" name="password" data-i18n-placeholder="enterPassword" placeholder="أدخل كلمة المرور" required>
                    </div>

                    <div class="remember-me-container">
                        <label class="remember-me-label">
                            <input type="checkbox" name="remember_me" id="remember_me" class="remember-me-checkbox" value="1">
                            <span class="checkmark"></span>
                            <span class="remember-me-text" data-i18n="rememberMe">تذكرني</span>
                        </label>
                        <a href="/forgot_password.php" class="forgot-link" data-i18n="forgotPassword">نسيت كلمة المرور؟</a>
                    </div>

                    <button type="submit" class="btn-login" data-i18n="signInBtn">
                        <i class="fas fa-sign-in-alt"></i> تسجيل الدخول
                    </button>
                </form>

                <div class="divider">
                    <span data-i18n="newToZuckBook">جديد في ZuckBook؟</span>
                </div>

                <div class="links">
                    <a href="/register.php" class="link-item link-register" data-i18n-link="createAccount">
                        <span><i class="fas fa-user-plus"></i> إنشاء حساب جديد</span>
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <a href="/forgot_password.php" class="link-item link-recover" data-i18n-link="recoverAccount">
                        <span><i class="fas fa-key"></i> استرجاع الحساب</span>
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <a href="/view_ticket.php" class="link-item link-ticket" data-i18n-link="viewTicket">
                        <span><i class="fas fa-search"></i> عرض التكت</span>
                        <i class="fas fa-arrow-left"></i>
                    </a>
                </div>
            </div>

            <div class="footer">
                <p><span data-i18n="copyright">© 2024 ZuckBook. جميع الحقوق محفوظة.</span> | <a href="#" data-i18n-link="privacy">سياسة الخصوصية</a> | <a href="#" data-i18n-link="terms">شروط الخدمة</a></p>
            </div>
        </div>
    </div>
</div>

<script>
function toggleLang() {
    alert('تبديل اللغة قريباً!');
}
</script>

</body>
</html>

<script>
let currentLang = 'ar'; // Default language

const translations = {
    ar: {
        welcome: 'أهلا بك',
        signIn: 'تسجيل الدخول',
        enterEmail: 'أدخل بريدك أو اسم المستخدم',
        emailLabel: 'البريد الإلكتروني أو اسم المستخدم',
        password: 'كلمة المرور',
        enterPassword: 'أدخل كلمة المرور',
        rememberMe: 'تذكرني',
        forgotPassword: 'نسيت كلمة المرور؟',
        signInBtn: 'تسجيل الدخول',
        newToZuckBook: 'جديد في ZuckBook؟',
        createAccount: 'إنشاء حساب جديد',
        recoverAccount: 'استرجاع الحساب',
        viewTicket: 'عرض التكت',
        copyright: '© 2024 ZuckBook. جميع الحقوق محفوظة.',
        privacy: 'سياسة الخصوصية',
        terms: 'شروط الخدمة',
        logoText: 'تواصل مع أصدقائك وشارك لحظاتك المميزة'
    },
    en: {
        welcome: 'Welcome Back',
        signIn: 'Sign in to your account to continue',
        enterEmail: 'Enter your email or username',
        emailLabel: 'Email or Username',
        password: 'Password',
        enterPassword: 'Enter your password',
        rememberMe: 'Remember me',
        forgotPassword: 'Forgot password?',
        signInBtn: 'Sign In',
        newToZuckBook: 'New to ZuckBook?',
        createAccount: 'Create Account',
        recoverAccount: 'Recover Account',
        viewTicket: 'View Ticket',
        copyright: '© 2024 ZuckBook. All rights reserved.',
        privacy: 'Privacy Policy',
        terms: 'Terms of Service',
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
    
    // Update HTML direction
    html.dir = currentLang === 'ar' ? 'rtl' : 'ltr';
    html.lang = currentLang;
    
    // Update button text
    document.getElementById('langBtn').innerHTML = currentLang === 'ar' 
        ? '<i class="fas fa-globe"></i> English' 
        : '<i class="fas fa-globe"></i> العربية';
    
    // Update all text elements
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
    
    // Update links text
    document.querySelectorAll('[data-i18n-link]').forEach(el => {
        const key = el.getAttribute('data-i18n-link');
        if (t[key]) {
            el.textContent = t[key];
        }
    });
}

// Load saved language on page load
window.addEventListener('DOMContentLoaded', () => {
    const savedLang = localStorage.getItem('language');
    if (savedLang) {
        currentLang = savedLang;
        updatePageLanguage();
    }
    
    // Load saved remember me preference
    const rememberMeCheckbox = document.getElementById('remember_me');
    const savedRememberMe = localStorage.getItem('remember_me_checked');
    if (savedRememberMe === 'true') {
        rememberMeCheckbox.checked = true;
    }
    
    // Save remember me preference when changed
    rememberMeCheckbox.addEventListener('change', function() {
        localStorage.setItem('remember_me_checked', this.checked);
    });
    
    // Add form submit handler for debugging
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        const rememberMe = document.getElementById('remember_me').checked;
        console.log('Remember Me checked:', rememberMe);
        // Form will submit normally
    });
});
</script>

</body>
</html>
