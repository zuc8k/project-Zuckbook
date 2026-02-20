<?php
session_start();
require_once __DIR__ . "/backend/config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: /");
    exit;
}

$user_id = intval($_SESSION['user_id']);

$userStmt = $conn->prepare("SELECT id, name, profile_image FROM users WHERE id = ?");
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$userData = $userStmt->get_result()->fetch_assoc();

if (!$userData) {
    session_destroy();
    exit;
}

$userName = htmlspecialchars($userData['name']);
// Fix image path - use absolute path from root
if ($userData['profile_image']) {
    $userImage = "/uploads/" . htmlspecialchars($userData['profile_image']);
} else {
    $userImage = "/assets/zuckuser.png";
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>تغيير كلمة المرور - ZuckBook</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { 
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
    background: #f0f2f5; 
    min-height: 100vh;
}

.header { 
    background: white; 
    border-bottom: 1px solid #e4e6eb; 
    padding: 8px 16px; 
    position: sticky; 
    top: 0; 
    z-index: 100; 
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
}
.header-content { 
    max-width: 1200px; 
    margin: 0 auto; 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    gap: 16px; 
}
.logo { 
    font-size: 32px; 
    font-weight: bold; 
    color: #1877f2; 
    cursor: pointer; 
    font-family: 'Segoe UI', sans-serif;
}
.header-right { 
    display: flex; 
    gap: 12px; 
    align-items: center; 
}
.user-avatar { 
    width: 40px; 
    height: 40px; 
    border-radius: 50%; 
    object-fit: cover; 
    cursor: pointer; 
    border: 2px solid #e4e6eb;
    background: #f0f2f5;
}

.user-avatar:error {
    content: url('/assets/zuckuser.png');
}

.user-avatar-placeholder {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #1877f2;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 18px;
    cursor: pointer;
    border: 2px solid #e4e6eb;
}

.container { 
    max-width: 600px; 
    margin: 40px auto; 
    padding: 0 16px; 
}

.card { 
    background: white; 
    border-radius: 8px; 
    padding: 32px; 
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
}

.card-header {
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid #e4e6eb;
}

.card-title { 
    font-size: 24px; 
    font-weight: 700; 
    color: #050505; 
    margin-bottom: 8px;
}

.card-subtitle {
    font-size: 14px;
    color: #65676b;
}

.form-group { 
    margin-bottom: 20px; 
}

.form-label { 
    display: block; 
    font-weight: 600; 
    font-size: 15px; 
    color: #050505; 
    margin-bottom: 8px; 
}

.form-input { 
    width: 100%; 
    padding: 12px 16px; 
    border: 1px solid #ccd0d5; 
    border-radius: 6px; 
    font-family: inherit; 
    font-size: 15px; 
    transition: all 0.2s;
    background: #f5f6f7;
}

.form-input:focus { 
    outline: none; 
    border-color: #1877f2; 
    background: white;
    box-shadow: 0 0 0 2px rgba(24, 119, 242, 0.1);
}

.password-wrapper {
    position: relative;
}

.toggle-password {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #65676b;
    cursor: pointer;
    font-size: 18px;
    padding: 4px;
}

.toggle-password:hover {
    color: #050505;
}

.alert { 
    padding: 12px 16px; 
    border-radius: 6px; 
    margin-bottom: 20px; 
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert-error { 
    background: #fee; 
    color: #c41e3a; 
    border: 1px solid #fcc;
}

.alert-success { 
    background: #d4edda; 
    color: #155724; 
    border: 1px solid #c3e6cb;
}

.alert-info {
    background: #e7f3ff;
    color: #004085;
    border: 1px solid #b8daff;
}

.password-requirements {
    background: #f5f6f7;
    padding: 16px;
    border-radius: 6px;
    margin-top: 12px;
    font-size: 13px;
    color: #65676b;
}

.password-requirements ul {
    margin: 8px 0 0 20px;
    padding: 0;
}

.password-requirements li {
    margin: 4px 0;
}

.button-group { 
    display: flex; 
    gap: 12px; 
    margin-top: 24px; 
}

.btn { 
    padding: 12px 24px; 
    border: none; 
    border-radius: 6px; 
    cursor: pointer; 
    font-weight: 600; 
    font-size: 15px; 
    transition: all 0.2s;
    flex: 1;
}

.btn-primary { 
    background: #1877f2; 
    color: white; 
}

.btn-primary:hover { 
    background: #166fe5; 
}

.btn-primary:disabled {
    background: #e4e6eb;
    color: #bcc0c4;
    cursor: not-allowed;
}

.btn-secondary { 
    background: #e4e6eb; 
    color: #050505; 
}

.btn-secondary:hover { 
    background: #d8dadf; 
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: #1877f2;
    text-decoration: none;
    font-size: 14px;
    margin-bottom: 20px;
    font-weight: 500;
}

.back-link:hover {
    text-decoration: underline;
}

@media (max-width: 768px) {
    .container { 
        margin: 20px auto; 
    }
    .card { 
        padding: 20px; 
    }
    .button-group {
        flex-direction: column;
    }
}

/* ==================== MOBILE RESPONSIVE STYLES ==================== */

/* Mobile devices (768px and below) */
@media (max-width: 768px) {
    body {
        padding-top: 60px;
    }

    /* Header */
    .header {
        padding: 8px 10px;
    }

    .header-content {
        gap: 10px;
    }

    .logo {
        font-size: 28px;
    }

    .user-avatar,
    .user-avatar-placeholder {
        width: 36px;
        height: 36px;
        font-size: 16px;
    }

    /* Container */
    .container {
        margin: 20px auto;
        padding: 0 10px;
    }

    .back-link {
        font-size: 13px;
        margin-bottom: 15px;
    }

    /* Card */
    .card {
        padding: 20px;
        border-radius: 12px;
    }

    .card-header {
        margin-bottom: 20px;
        padding-bottom: 12px;
    }

    .card-title {
        font-size: 20px;
    }

    .card-subtitle {
        font-size: 13px;
    }

    /* Form */
    .form-group {
        margin-bottom: 16px;
    }

    .form-label {
        font-size: 14px;
        margin-bottom: 6px;
    }

    .form-input {
        padding: 10px 14px;
        font-size: 14px;
    }

    .toggle-password {
        font-size: 16px;
    }

    .password-requirements {
        padding: 14px;
        font-size: 12px;
    }

    /* Alert */
    .alert {
        padding: 10px 14px;
        font-size: 13px;
    }

    /* Buttons */
    .button-group {
        flex-direction: column;
        margin-top: 20px;
        gap: 10px;
    }

    .btn {
        padding: 12px 20px;
        font-size: 14px;
    }
}

/* Small mobile devices (575px and below) */
@media (max-width: 575px) {
    .header {
        padding: 6px 8px;
    }

    .header-content {
        gap: 8px;
    }

    .logo {
        font-size: 26px;
    }

    .user-avatar,
    .user-avatar-placeholder {
        width: 32px;
        height: 32px;
        font-size: 14px;
    }

    .container {
        margin: 15px auto;
        padding: 0 8px;
    }

    .back-link {
        font-size: 12px;
        margin-bottom: 12px;
    }

    .card {
        padding: 16px;
    }

    .card-header {
        margin-bottom: 16px;
        padding-bottom: 10px;
    }

    .card-title {
        font-size: 18px;
    }

    .card-subtitle {
        font-size: 12px;
    }

    .form-group {
        margin-bottom: 14px;
    }

    .form-label {
        font-size: 13px;
    }

    .form-input {
        padding: 9px 12px;
        font-size: 13px;
    }

    .toggle-password {
        font-size: 15px;
        left: 10px;
    }

    .password-requirements {
        padding: 12px;
        font-size: 11px;
    }

    .alert {
        padding: 9px 12px;
        font-size: 12px;
    }

    .button-group {
        margin-top: 16px;
        gap: 8px;
    }

    .btn {
        padding: 10px 16px;
        font-size: 13px;
    }
}
</style>
</head>
<body>

<div class="header">
    <div class="header-content">
        <div class="logo" onclick="window.location.href='/home.php'">f</div>
        <div class="header-right">
            <img src="<?= $userImage ?>" class="user-avatar" alt="<?= $userName ?>" onclick="window.location.href='/profile.php?id=<?= $user_id ?>'" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
            <div class="user-avatar-placeholder" style="display: none;" onclick="window.location.href='/profile.php?id=<?= $user_id ?>'">
                <?= strtoupper(substr($userName, 0, 1)) ?>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <a href="/settings.php" class="back-link">
        <i class="fas fa-arrow-right"></i>
        العودة للإعدادات
    </a>

    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <i class="fas fa-lock"></i>
                تغيير كلمة المرور
            </div>
            <div class="card-subtitle">
                قم بتحديث كلمة المرور الخاصة بك للحفاظ على أمان حسابك
            </div>
        </div>

        <div id="alertContainer"></div>

        <form id="changePasswordForm" onsubmit="return handleSubmit(event)">
            <div class="form-group">
                <label class="form-label" for="current_password">
                    <i class="fas fa-key"></i>
                    كلمة المرور الحالية
                </label>
                <div class="password-wrapper">
                    <input 
                        type="password" 
                        id="current_password" 
                        name="current_password" 
                        class="form-input" 
                        placeholder="أدخل كلمة المرور الحالية"
                        required
                    >
                    <button type="button" class="toggle-password" onclick="togglePassword('current_password')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="new_password">
                    <i class="fas fa-lock"></i>
                    كلمة المرور الجديدة
                </label>
                <div class="password-wrapper">
                    <input 
                        type="password" 
                        id="new_password" 
                        name="new_password" 
                        class="form-input" 
                        placeholder="أدخل كلمة المرور الجديدة"
                        required
                        minlength="6"
                        oninput="checkPasswordMatch()"
                    >
                    <button type="button" class="toggle-password" onclick="togglePassword('new_password')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="password-requirements">
                    <strong>متطلبات كلمة المرور:</strong>
                    <ul>
                        <li>يجب أن تحتوي على 6 أحرف على الأقل</li>
                        <li>يُفضل استخدام مزيج من الأحرف والأرقام</li>
                        <li>تجنب استخدام معلومات شخصية واضحة</li>
                    </ul>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="confirm_password">
                    <i class="fas fa-check-circle"></i>
                    تأكيد كلمة المرور الجديدة
                </label>
                <div class="password-wrapper">
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        class="form-input" 
                        placeholder="أعد إدخال كلمة المرور الجديدة"
                        required
                        minlength="6"
                        oninput="checkPasswordMatch()"
                    >
                    <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <div id="passwordMatchMessage" style="margin-top: 8px; font-size: 13px;"></div>
            </div>

            <div class="button-group">
                <button type="button" class="btn btn-secondary" onclick="window.location.href='/settings.php'">
                    <i class="fas fa-times"></i>
                    إلغاء
                </button>
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <i class="fas fa-save"></i>
                    حفظ التغييرات
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Handle image load error
document.addEventListener('DOMContentLoaded', function() {
    const avatar = document.querySelector('.user-avatar');
    if (avatar) {
        avatar.onerror = function() {
            this.src = '/assets/zuckuser.png';
            this.onerror = null; // Prevent infinite loop
        };
    }
});

function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = input.nextElementSibling.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

function checkPasswordMatch() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const messageDiv = document.getElementById('passwordMatchMessage');
    const submitBtn = document.getElementById('submitBtn');
    
    if (confirmPassword === '') {
        messageDiv.innerHTML = '';
        submitBtn.disabled = false;
        return;
    }
    
    if (newPassword === confirmPassword) {
        messageDiv.innerHTML = '<span style="color: #42b72a;"><i class="fas fa-check-circle"></i> كلمات المرور متطابقة</span>';
        submitBtn.disabled = false;
    } else {
        messageDiv.innerHTML = '<span style="color: #f02849;"><i class="fas fa-times-circle"></i> كلمات المرور غير متطابقة</span>';
        submitBtn.disabled = true;
    }
}

function showAlert(message, type) {
    const alertContainer = document.getElementById('alertContainer');
    const iconClass = type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle';
    
    alertContainer.innerHTML = `
        <div class="alert alert-${type}">
            <i class="fas ${iconClass}"></i>
            <span>${message}</span>
        </div>
    `;
    
    // Scroll to top to show alert
    window.scrollTo({ top: 0, behavior: 'smooth' });
    
    // Auto-hide success messages after 5 seconds
    if (type === 'success') {
        setTimeout(() => {
            alertContainer.innerHTML = '';
        }, 5000);
    }
}

function handleSubmit(event) {
    event.preventDefault();
    
    const currentPassword = document.getElementById('current_password').value;
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const submitBtn = document.getElementById('submitBtn');
    
    // Validate passwords match
    if (newPassword !== confirmPassword) {
        showAlert('كلمات المرور الجديدة غير متطابقة', 'error');
        return false;
    }
    
    // Validate password length
    if (newPassword.length < 6) {
        showAlert('كلمة المرور يجب أن تحتوي على 6 أحرف على الأقل', 'error');
        return false;
    }
    
    // Disable submit button
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري الحفظ...';
    
    // Send request
    fetch('/backend/change_password.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `current_password=${encodeURIComponent(currentPassword)}&new_password=${encodeURIComponent(newPassword)}&confirm_password=${encodeURIComponent(confirmPassword)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            // Clear form
            document.getElementById('changePasswordForm').reset();
            document.getElementById('passwordMatchMessage').innerHTML = '';
            
            // Redirect after 2 seconds
            setTimeout(() => {
                window.location.href = '/settings.php';
            }, 2000);
        } else {
            showAlert(data.message, 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save"></i> حفظ التغييرات';
        }
    })
    .catch(error => {
        showAlert('حدث خطأ أثناء تغيير كلمة المرور. حاول مرة أخرى.', 'error');
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-save"></i> حفظ التغييرات';
    });
    
    return false;
}
</script>

</body>
</html>
