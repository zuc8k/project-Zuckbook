<?php
session_start();
require_once __DIR__ . "/backend/config.php";
require_once __DIR__ . "/includes/helpers.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: /");
    exit;
}

$user_id = intval($_SESSION['user_id']);

// Get user data
$userStmt = $conn->prepare("SELECT id, name, coins, profile_image, is_verified, subscription_tier, subscription_expires FROM users WHERE id = ?");
$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$user = $userStmt->get_result()->fetch_assoc();

if (!$user) {
    session_destroy();
    exit;
}

$userName = htmlspecialchars($user['name']);
$userImage = $user['profile_image'] ? "/uploads/" . htmlspecialchars($user['profile_image']) : "/assets/zuckuser.png";
$userCoins = $user['coins'];
$currentTier = $user['subscription_tier'] ?? 'free';
$subscriptionExpires = $user['subscription_expires'];

// Check if subscription is active
$isSubscribed = false;
if ($subscriptionExpires && strtotime($subscriptionExpires) > time()) {
    $isSubscribed = true;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الاشتراكات - ZuckBook</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Cairo', sans-serif; background: #f0f2f5; color: #050505; padding-top: 70px; }

        /* Header */
        .header { background: #ffffff; height: 56px; border-bottom: 1px solid #e4e6eb; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 0 16px; position: fixed; top: 0; left: 0; right: 0; z-index: 300; display: flex; align-items: center; }
        .header-content { width: 100%; display: flex; justify-content: space-between; align-items: center; max-width: 100%; padding: 0 16px; }
        .logo { font-size: 40px; font-weight: bold; color: #1877f2; cursor: pointer; }
        .coins-display { display: flex; align-items: center; gap: 8px; background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); padding: 8px 16px; border-radius: 20px; color: white; font-weight: 700; font-size: 16px; }
        .back-btn { padding: 8px 16px; background: #e4e6eb; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; color: #050505; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .back-btn:hover { background: #d8dadf; }

        /* Main Content */
        .container { max-width: 1200px; margin: 30px auto; padding: 0 20px; }
        .page-title { text-align: center; margin-bottom: 20px; }
        .page-title h1 { font-size: 36px; font-weight: 800; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 10px; }
        .page-title p { font-size: 18px; color: #65676b; }

        /* Billing Toggle */
        .billing-toggle { display: flex; justify-content: center; align-items: center; gap: 15px; margin: 30px 0; }
        .toggle-btn { padding: 12px 30px; background: #e4e6eb; border: none; border-radius: 25px; cursor: pointer; font-weight: 700; font-size: 16px; color: #65676b; transition: all 0.3s ease; }
        .toggle-btn.active { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4); }
        .save-badge { background: #10b981; color: white; padding: 6px 12px; border-radius: 20px; font-size: 14px; font-weight: 700; }

        /* Plans Grid */
        .plans-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 30px; margin-top: 40px; }
        .plan-card { background: white; border-radius: 20px; padding: 35px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1); transition: all 0.3s ease; position: relative; overflow: hidden; }
        .plan-card:hover { transform: translateY(-10px); box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15); }
        .plan-card.recommended { border: 3px solid #9333ea; }
        .recommended-badge { position: absolute; top: 20px; left: 20px; background: linear-gradient(135deg, #9333ea 0%, #7c3aed 100%); color: white; padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: 700; }
        .plan-icon { width: 70px; height: 70px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; color: white; margin-bottom: 20px; }
        .plan-name { font-size: 28px; font-weight: 800; margin-bottom: 15px; }
        .plan-price { display: flex; align-items: baseline; gap: 8px; margin-bottom: 10px; }
        .price-amount { font-size: 48px; font-weight: 800; }
        .price-coins { font-size: 24px; color: #f59e0b; font-weight: 700; }
        .price-period { font-size: 16px; color: #65676b; }
        .plan-features { list-style: none; margin: 25px 0; }
        .plan-features li { padding: 12px 0; display: flex; align-items: center; gap: 12px; border-bottom: 1px solid #f0f2f5; }
        .plan-features li:last-child { border-bottom: none; }
        .plan-features i { color: #10b981; font-size: 18px; }
        .subscribe-btn { width: 100%; padding: 16px; border: none; border-radius: 12px; font-size: 18px; font-weight: 700; cursor: pointer; transition: all 0.3s ease; color: white; display: flex; align-items: center; justify-content: center; gap: 10px; }
        .subscribe-btn:hover { transform: scale(1.02); }
        .subscribe-btn:disabled { opacity: 0.6; cursor: not-allowed; }
        .insufficient-coins { background: #dc2626; }

        /* Confirmation Modal */
        .confirm-modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.85); z-index: 9999; align-items: center; justify-content: center; animation: fadeIn 0.3s ease; }
        .confirm-modal.show { display: flex; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .confirm-modal-content { background: white; border-radius: 24px; padding: 0; max-width: 480px; width: 90%; overflow: hidden; animation: slideUp 0.4s ease; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4); }
        @keyframes slideUp { from { opacity: 0; transform: translateY(50px) scale(0.9); } to { opacity: 1; transform: translateY(0) scale(1); } }
        .modal-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 30px; text-align: center; color: white; position: relative; }
        .modal-header::before { content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.1)" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,112C672,96,768,96,864,112C960,128,1056,160,1152,160C1248,160,1344,128,1392,112L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom; background-size: cover; }
        .confirm-icon { width: 90px; height: 90px; background: rgba(255, 255, 255, 0.25); backdrop-filter: blur(10px); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; animation: scaleIn 0.5s ease; position: relative; z-index: 1; }
        @keyframes scaleIn { 0% { transform: scale(0) rotate(-180deg); } 50% { transform: scale(1.1) rotate(10deg); } 100% { transform: scale(1) rotate(0); } }
        .confirm-icon i { font-size: 45px; color: white; }
        .modal-header h3 { font-size: 26px; font-weight: 800; margin-bottom: 8px; position: relative; z-index: 1; }
        .modal-header p { font-size: 15px; opacity: 0.95; position: relative; z-index: 1; }
        .modal-body { padding: 35px 30px; }
        .confirm-details { background: linear-gradient(135deg, #f0f2f5 0%, #e4e6eb 100%); border-radius: 16px; padding: 25px; margin-bottom: 25px; }
        .detail-row { display: flex; justify-content: space-between; align-items: center; padding: 14px 0; border-bottom: 1px solid rgba(0,0,0,0.05); }
        .detail-row:last-child { border-bottom: none; }
        .detail-label { color: #65676b; font-size: 15px; font-weight: 600; }
        .detail-value { font-weight: 800; font-size: 17px; color: #050505; }
        .detail-value.highlight { color: #667eea; font-size: 19px; }
        .confirm-actions { display: flex; gap: 12px; }
        .modal-btn { flex: 1; padding: 16px; border: none; border-radius: 12px; font-size: 16px; font-weight: 700; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .modal-btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .modal-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5); }
        .modal-btn-secondary { background: #e4e6eb; color: #050505; }
        .modal-btn-secondary:hover { background: #d8dadf; }

        /* Success Modal */
        .success-modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.85); z-index: 9999; align-items: center; justify-content: center; animation: fadeIn 0.3s ease; }
        .success-modal.show { display: flex; }
        .success-modal-content { background: white; border-radius: 24px; padding: 0; max-width: 520px; width: 90%; overflow: hidden; animation: slideUp 0.4s ease; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4); }
        .success-header { background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 45px 30px; text-align: center; color: white; position: relative; }
        .success-header::before { content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.1)" d="M0,160L48,144C96,128,192,96,288,90.7C384,85,480,107,576,128C672,149,768,171,864,165.3C960,160,1056,128,1152,122.7C1248,117,1344,139,1392,149.3L1440,160L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom; background-size: cover; }
        .success-icon { width: 100px; height: 100px; background: rgba(255, 255, 255, 0.25); backdrop-filter: blur(10px); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; animation: successPulse 0.6s ease; position: relative; z-index: 1; }
        @keyframes successPulse { 0% { transform: scale(0); } 50% { transform: scale(1.15); } 100% { transform: scale(1); } }
        .success-icon i { font-size: 50px; color: white; }
        .success-header h2 { font-size: 28px; font-weight: 800; margin-bottom: 10px; position: relative; z-index: 1; }
        .success-header p { font-size: 16px; opacity: 0.95; position: relative; z-index: 1; }
        .success-body { padding: 35px 30px; }
        .subscription-details { background: linear-gradient(135deg, #f0f2f5 0%, #e4e6eb 100%); border-radius: 16px; padding: 25px; margin-bottom: 25px; }
        .coins-remaining { background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); color: white; padding: 25px; border-radius: 16px; text-align: center; margin-bottom: 25px; box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3); }
        .coins-remaining .amount { font-size: 42px; font-weight: 800; display: block; margin-bottom: 8px; }
        .coins-remaining .label { font-size: 15px; opacity: 0.95; }

        @media (max-width: 768px) {
            .plans-grid { grid-template-columns: 1fr; }
            .page-title h1 { font-size: 28px; }
            .billing-toggle { flex-direction: column; }
            .confirm-actions, .modal-actions { flex-direction: column; }
        }

        /* ==================== MOBILE RESPONSIVE STYLES ==================== */

        /* Tablets and below (1024px) */
        @media (max-width: 1024px) {
            .container {
                padding: 0 12px;
            }
        }

        /* Mobile devices (768px and below) */
        @media (max-width: 768px) {
            body {
                padding-top: 60px;
            }

            /* Header */
            .header {
                height: 50px;
                padding: 0 10px;
            }

            .header-content {
                padding: 0 8px;
            }

            .logo {
                font-size: 32px;
            }

            .back-btn {
                padding: 6px 12px;
                font-size: 14px;
            }

            .coins-display {
                padding: 6px 12px;
                font-size: 14px;
            }

            /* Main content */
            .container {
                margin: 20px auto;
                padding: 0 10px;
            }

            .page-title h1 {
                font-size: 24px;
            }

            .page-title p {
                font-size: 15px;
            }

            /* Billing toggle */
            .billing-toggle {
                flex-direction: row;
                flex-wrap: wrap;
                gap: 10px;
            }

            .toggle-btn {
                padding: 10px 20px;
                font-size: 14px;
            }

            .save-badge {
                width: 100%;
                text-align: center;
            }

            /* Plans grid */
            .plans-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .plan-card {
                padding: 25px;
            }

            .plan-icon {
                width: 60px;
                height: 60px;
                font-size: 28px;
            }

            .plan-name {
                font-size: 24px;
            }

            .price-amount {
                font-size: 40px;
            }

            .price-coins {
                font-size: 20px;
            }

            .plan-features li {
                padding: 10px 0;
                font-size: 14px;
            }

            .subscribe-btn {
                padding: 14px;
                font-size: 16px;
            }

            /* Modals */
            .confirm-modal-content,
            .success-modal-content {
                width: 95%;
                max-width: 95%;
            }

            .modal-header,
            .success-header {
                padding: 30px 20px;
            }

            .confirm-icon,
            .success-icon {
                width: 70px;
                height: 70px;
                font-size: 35px;
            }

            .modal-header h3,
            .success-header h2 {
                font-size: 22px;
            }

            .modal-body,
            .success-body {
                padding: 25px 20px;
            }

            .confirm-details,
            .subscription-details {
                padding: 20px;
            }

            .detail-row {
                padding: 12px 0;
            }

            .detail-label,
            .detail-value {
                font-size: 14px;
            }

            .confirm-actions {
                flex-direction: column;
            }

            .modal-btn {
                padding: 14px;
                font-size: 15px;
            }

            .coins-remaining .amount {
                font-size: 36px;
            }
        }

        /* Small mobile devices (575px and below) */
        @media (max-width: 575px) {
            .header {
                height: 48px;
                padding: 0 8px;
            }

            .logo {
                font-size: 28px;
            }

            .back-btn {
                padding: 5px 10px;
                font-size: 13px;
                gap: 4px;
            }

            .coins-display {
                padding: 5px 10px;
                font-size: 13px;
                gap: 4px;
            }

            .container {
                margin: 15px auto;
                padding: 0 8px;
            }

            .page-title h1 {
                font-size: 20px;
            }

            .page-title p {
                font-size: 14px;
            }

            .toggle-btn {
                padding: 8px 16px;
                font-size: 13px;
            }

            .save-badge {
                padding: 5px 10px;
                font-size: 12px;
            }

            .plans-grid {
                gap: 15px;
            }

            .plan-card {
                padding: 20px;
            }

            .recommended-badge {
                top: 15px;
                left: 15px;
                padding: 5px 12px;
                font-size: 12px;
            }

            .plan-icon {
                width: 50px;
                height: 50px;
                font-size: 24px;
                margin-bottom: 15px;
            }

            .plan-name {
                font-size: 20px;
                margin-bottom: 12px;
            }

            .price-amount {
                font-size: 32px;
            }

            .price-coins {
                font-size: 18px;
            }

            .price-period {
                font-size: 14px;
            }

            .plan-features {
                margin: 20px 0;
            }

            .plan-features li {
                padding: 8px 0;
                font-size: 13px;
                gap: 10px;
            }

            .plan-features i {
                font-size: 16px;
            }

            .subscribe-btn {
                padding: 12px;
                font-size: 15px;
            }

            .modal-header,
            .success-header {
                padding: 25px 15px;
            }

            .confirm-icon,
            .success-icon {
                width: 60px;
                height: 60px;
                font-size: 30px;
                margin-bottom: 15px;
            }

            .modal-header h3,
            .success-header h2 {
                font-size: 20px;
            }

            .modal-header p,
            .success-header p {
                font-size: 13px;
            }

            .modal-body,
            .success-body {
                padding: 20px 15px;
            }

            .confirm-details,
            .subscription-details {
                padding: 18px;
            }

            .detail-row {
                padding: 10px 0;
            }

            .detail-label {
                font-size: 13px;
            }

            .detail-value {
                font-size: 13px;
            }

            .modal-btn {
                padding: 12px;
                font-size: 14px;
            }

            .coins-remaining {
                padding: 20px;
            }

            .coins-remaining .amount {
                font-size: 32px;
            }

            .coins-remaining .label {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>

<!-- Header -->
<div class="header">
    <div class="header-content">
        <div style="display: flex; align-items: center; gap: 20px;">
            <div class="logo" onclick="window.location.href='/home.php'">f</div>
            <a href="/home.php" class="back-btn">
                <i class="fas fa-arrow-right"></i>
                العودة
            </a>
        </div>
        
        <div class="coins-display">
            <i class="fas fa-coins"></i>
            <?= formatCoins($userCoins) ?>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="container">
    <div class="page-title">
        <h1>اختر خطة الاشتراك المناسبة</h1>
        <p>احصل على مميزات حصرية وطور تجربتك على ZuckBook</p>
    </div>

    <?php if ($isSubscribed): ?>
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 16px; text-align: center; margin-bottom: 30px;">
        <h3 style="font-size: 20px; margin-bottom: 10px;"><i class="fas fa-check-circle"></i> لديك اشتراك نشط</h3>
        <p style="font-size: 16px; opacity: 0.95;">الخطة: <?= ucfirst($currentTier) ?> | ينتهي في: <?= date('Y-m-d', strtotime($subscriptionExpires)) ?></p>
    </div>
    <?php endif; ?>

    <!-- Billing Toggle -->
    <div class="billing-toggle">
        <button class="toggle-btn active" id="monthlyBtn" onclick="switchBilling('monthly')">
            شهري
        </button>
        <span class="save-badge">وفر حتى 40%</span>
        <button class="toggle-btn" id="yearlyBtn" onclick="switchBilling('yearly')">
            سنوي
        </button>
    </div>

    <!-- Plans Grid -->
    <div class="plans-grid">
        <!-- Basic Plan -->
        <div class="plan-card">
            <div class="plan-icon" style="background: linear-gradient(135deg, #1877f2 0%, #0c63e4 100%);">
                <i class="fas fa-star"></i>
            </div>
            <h2 class="plan-name">Basic</h2>
            <div class="plan-price">
                <span class="price-amount monthly-price">30</span>
                <span class="price-amount yearly-price" style="display: none;">190</span>
                <span class="price-coins"><i class="fas fa-coins"></i></span>
                <span class="price-period monthly-period">/ شهر</span>
                <span class="price-period yearly-period" style="display: none;">/ سنة</span>
            </div>
            <ul class="plan-features">
                <li><i class="fas fa-check-circle"></i> علامة التحقق الزرقاء</li>
                <li><i class="fas fa-check-circle"></i> إزالة الإعلانات</li>
                <li><i class="fas fa-check-circle"></i> أولوية في الدعم الفني</li>
                <li><i class="fas fa-check-circle"></i> رفع الصور بجودة عالية</li>
            </ul>
            <button class="subscribe-btn" style="background: linear-gradient(135deg, #1877f2 0%, #0c63e4 100%);" 
                    onclick="subscribe('basic', 'monthly', 30)" 
                    data-monthly="30" 
                    data-yearly="190"
                    id="basicBtn">
                <i class="fas fa-rocket"></i>
                <span>اشترك الآن</span>
            </button>
        </div>

        <!-- Premium Plan (Recommended) -->
        <div class="plan-card recommended">
            <div class="recommended-badge">
                <i class="fas fa-star"></i> الأكثر شعبية
            </div>
            <div class="plan-icon" style="background: linear-gradient(135deg, #9333ea 0%, #7c3aed 100%);">
                <i class="fas fa-crown"></i>
            </div>
            <h2 class="plan-name">Premium</h2>
            <div class="plan-price">
                <span class="price-amount monthly-price">90</span>
                <span class="price-amount yearly-price" style="display: none;">490</span>
                <span class="price-coins"><i class="fas fa-coins"></i></span>
                <span class="price-period monthly-period">/ شهر</span>
                <span class="price-period yearly-period" style="display: none;">/ سنة</span>
            </div>
            <ul class="plan-features">
                <li><i class="fas fa-check-circle"></i> كل مميزات Basic</li>
                <li><i class="fas fa-check-circle"></i> شارة Premium الذهبية</li>
                <li><i class="fas fa-check-circle"></i> مساحة تخزين غير محدودة</li>
                <li><i class="fas fa-check-circle"></i> إنشاء مجموعات مميزة</li>
                <li><i class="fas fa-check-circle"></i> تحليلات متقدمة للمنشورات</li>
                <li><i class="fas fa-check-circle"></i> دعم فني على مدار الساعة</li>
            </ul>
            <button class="subscribe-btn" style="background: linear-gradient(135deg, #9333ea 0%, #7c3aed 100%);" 
                    onclick="subscribe('premium', 'monthly', 90)" 
                    data-monthly="90" 
                    data-yearly="490"
                    id="premiumBtn">
                <i class="fas fa-crown"></i>
                <span>اشترك الآن</span>
            </button>
        </div>

        <!-- Elite Plan -->
        <div class="plan-card">
            <div class="plan-icon" style="background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);">
                <i class="fas fa-gem"></i>
            </div>
            <h2 class="plan-name">Elite</h2>
            <div class="plan-price">
                <span class="price-amount monthly-price">350</span>
                <span class="price-amount yearly-price" style="display: none;">999</span>
                <span class="price-coins"><i class="fas fa-coins"></i></span>
                <span class="price-period monthly-period">/ شهر</span>
                <span class="price-period yearly-period" style="display: none;">/ سنة</span>
            </div>
            <ul class="plan-features">
                <li><i class="fas fa-check-circle"></i> كل مميزات Premium</li>
                <li><i class="fas fa-check-circle"></i> شارة Elite الماسية</li>
                <li><i class="fas fa-check-circle"></i> حساب مدير مخصص</li>
                <li><i class="fas fa-check-circle"></i> ظهور في الصفحة الرئيسية</li>
                <li><i class="fas fa-check-circle"></i> أدوات تسويق متقدمة</li>
                <li><i class="fas fa-check-circle"></i> وصول مبكر للمميزات الجديدة</li>
                <li><i class="fas fa-check-circle"></i> إمكانية البث المباشر</li>
                <li><i class="fas fa-check-circle"></i> تخصيص كامل للملف الشخصي</li>
            </ul>
            <button class="subscribe-btn" style="background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);" 
                    onclick="subscribe('elite', 'monthly', 350)" 
                    data-monthly="350" 
                    data-yearly="999"
                    id="eliteBtn">
                <i class="fas fa-gem"></i>
                <span>اشترك الآن</span>
            </button>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div class="confirm-modal" id="confirmModal">
    <div class="confirm-modal-content">
        <div class="modal-header">
            <div class="confirm-icon">
                <i class="fas fa-crown"></i>
            </div>
            <h3>تأكيد الاشتراك</h3>
            <p>هل أنت متأكد من رغبتك في الاشتراك؟</p>
        </div>
        
        <div class="modal-body">
            <div class="confirm-details" id="confirmDetails">
                <!-- Will be filled by JavaScript -->
            </div>

            <div class="confirm-actions">
                <button class="modal-btn modal-btn-secondary" onclick="closeConfirmModal()">
                    <i class="fas fa-times"></i> إلغاء
                </button>
                <button class="modal-btn modal-btn-primary" id="confirmSubscribeBtn">
                    <i class="fas fa-check"></i> تأكيد الاشتراك
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="success-modal" id="successModal">
    <div class="success-modal-content">
        <div class="success-header">
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>
            <h2>تم التفعيل بنجاح!</h2>
            <p>تم تفعيل اشتراكك بنجاح</p>
        </div>
        
        <div class="success-body">
            <div class="subscription-details" id="subscriptionDetails">
                <!-- Will be filled by JavaScript -->
            </div>

            <div class="coins-remaining">
                <span class="amount" id="remainingCoins">0</span>
                <span class="label">كوين متبقي</span>
            </div>

            <div class="confirm-actions">
                <button class="modal-btn modal-btn-secondary" onclick="closeSuccessModal()">
                    <i class="fas fa-home"></i> العودة للرئيسية
                </button>
                <button class="modal-btn modal-btn-primary" onclick="location.reload()">
                    <i class="fas fa-sync"></i> تحديث الصفحة
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentBilling = 'monthly';
const userCoins = <?= $userCoins ?>;
let pendingSubscription = null;

function switchBilling(type) {
    currentBilling = type;
    
    // Update toggle buttons
    document.getElementById('monthlyBtn').classList.toggle('active', type === 'monthly');
    document.getElementById('yearlyBtn').classList.toggle('active', type === 'yearly');
    
    // Update prices
    document.querySelectorAll('.monthly-price, .monthly-period').forEach(el => {
        el.style.display = type === 'monthly' ? 'inline' : 'none';
    });
    document.querySelectorAll('.yearly-price, .yearly-period').forEach(el => {
        el.style.display = type === 'yearly' ? 'inline' : 'none';
    });
    
    // Update button onclick handlers
    updateButtonHandlers();
}

function updateButtonHandlers() {
    const basicBtn = document.getElementById('basicBtn');
    const premiumBtn = document.getElementById('premiumBtn');
    const eliteBtn = document.getElementById('eliteBtn');
    
    const basicPrice = currentBilling === 'monthly' ? 30 : 190;
    const premiumPrice = currentBilling === 'monthly' ? 90 : 490;
    const elitePrice = currentBilling === 'monthly' ? 350 : 999;
    
    basicBtn.onclick = () => subscribe('basic', currentBilling, basicPrice);
    premiumBtn.onclick = () => subscribe('premium', currentBilling, premiumPrice);
    eliteBtn.onclick = () => subscribe('elite', currentBilling, elitePrice);
    
    // Update button states based on coins
    updateButtonStates(basicBtn, basicPrice);
    updateButtonStates(premiumBtn, premiumPrice);
    updateButtonStates(eliteBtn, elitePrice);
}

function updateButtonStates(btn, price) {
    if (userCoins < price) {
        btn.classList.add('insufficient-coins');
        btn.innerHTML = '<i class="fas fa-coins"></i> <span>كوينات غير كافية</span>';
        btn.disabled = true;
    } else {
        btn.classList.remove('insufficient-coins');
        btn.disabled = false;
    }
}

function subscribe(plan, billing, price) {
    if (userCoins < price) {
        alert(`❌ عذراً!\n\nليس لديك كوينات كافية للاشتراك.\n\nالمطلوب: ${price} كوين\nلديك: ${userCoins} كوين\n\nاذهب إلى صفحة الكوينات لشحن حسابك.`);
        return;
    }
    
    const planNames = {
        'basic': 'Basic',
        'premium': 'Premium',
        'elite': 'Elite'
    };
    
    const planIcons = {
        'basic': 'fa-star',
        'premium': 'fa-crown',
        'elite': 'fa-gem'
    };
    
    const billingText = billing === 'monthly' ? 'شهري' : 'سنوي';
    
    // Store subscription details
    pendingSubscription = { plan, billing, price, planName: planNames[plan], billingText };
    
    // Show confirmation modal
    showConfirmModal(planNames[plan], planIcons[plan], billingText, price);
}

function showConfirmModal(planName, planIcon, billingText, price) {
    const confirmDetails = document.getElementById('confirmDetails');
    confirmDetails.innerHTML = `
        <div class="detail-row">
            <span class="detail-label">الخطة</span>
            <span class="detail-value highlight">${planName}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">النوع</span>
            <span class="detail-value">${billingText}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">السعر</span>
            <span class="detail-value" style="color: #f59e0b;">${price} <i class="fas fa-coins"></i></span>
        </div>
        <div class="detail-row">
            <span class="detail-label">الكوينات المتبقية</span>
            <span class="detail-value" style="color: #10b981;">${userCoins - price} <i class="fas fa-coins"></i></span>
        </div>
    `;
    
    // Update icon
    document.querySelector('.confirm-icon i').className = `fas ${planIcon}`;
    
    document.getElementById('confirmModal').classList.add('show');
}

function closeConfirmModal() {
    document.getElementById('confirmModal').classList.remove('show');
    // Don't clear pendingSubscription here - it's needed for success modal
}

function processSubscription() {
    if (!pendingSubscription) return;
    
    const { plan, billing, price } = pendingSubscription;
    
    const btn = document.getElementById('confirmSubscribeBtn');
    const originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> جاري المعالجة...';
    
    fetch('/backend/process_subscription.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `plan=${plan}&billing=${billing}&price=${price}`
    })
    .then(res => {
        if (!res.ok) {
            throw new Error(`HTTP error! status: ${res.status}`);
        }
        return res.text();
    })
    .then(text => {
        console.log('Response:', text);
        try {
            const data = JSON.parse(text);
            btn.disabled = false;
            btn.innerHTML = originalHTML;
            
            if (data.status === 'success') {
                // Don't close confirm modal yet - keep pendingSubscription
                document.getElementById('confirmModal').classList.remove('show');
                showSuccessModal(data);
            } else {
                let errorMsg = data.message || 'حدث خطأ غير متوقع';
                if (data.debug) {
                    console.error('Debug info:', data.debug);
                }
                alert('❌ خطأ: ' + errorMsg);
            }
        } catch (e) {
            console.error('JSON parse error:', e);
            console.error('Response text:', text);
            btn.disabled = false;
            btn.innerHTML = originalHTML;
            alert('❌ خطأ في معالجة الاستجابة: ' + e.message);
        }
    })
    .catch(err => {
        console.error('Error:', err);
        btn.disabled = false;
        btn.innerHTML = originalHTML;
        // Don't clear pendingSubscription on error - user might retry
        alert('❌ خطأ في الاتصال: ' + err.message);
    });
}

function showSuccessModal(data) {
    const subscriptionDetails = document.getElementById('subscriptionDetails');
    const expiresDate = new Date(data.expires_at).toLocaleDateString('ar-EG', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
    
    // Extract plan info from success message if pendingSubscription is null
    let planName = 'Premium';
    let billingText = 'شهري';
    let price = 0;
    
    if (pendingSubscription) {
        planName = pendingSubscription.planName;
        billingText = pendingSubscription.billingText;
        price = pendingSubscription.price;
    } else if (data.message) {
        // Try to extract from message
        if (data.message.includes('Basic')) planName = 'Basic';
        else if (data.message.includes('Premium')) planName = 'Premium';
        else if (data.message.includes('Elite')) planName = 'Elite';
        
        if (data.message.includes('سنوي')) billingText = 'سنوي';
    }
    
    subscriptionDetails.innerHTML = `
        <div class="detail-row">
            <span class="detail-label">الخطة</span>
            <span class="detail-value highlight">${planName}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">نوع الاشتراك</span>
            <span class="detail-value">${billingText}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">تاريخ الانتهاء</span>
            <span class="detail-value">${expiresDate}</span>
        </div>
        ${price > 0 ? `<div class="detail-row">
            <span class="detail-label">المبلغ المدفوع</span>
            <span class="detail-value">${price} <i class="fas fa-coins" style="color: #f59e0b;"></i></span>
        </div>` : ''}
    `;
    
    document.getElementById('remainingCoins').textContent = data.remaining_coins;
    document.getElementById('successModal').classList.add('show');
}

function closeSuccessModal() {
    document.getElementById('successModal').classList.remove('show');
    pendingSubscription = null; // Clear it here after success modal is closed
    window.location.href = '/my_subscription.php';
}

// Set up confirm button
document.addEventListener('DOMContentLoaded', () => {
    updateButtonHandlers();
    
    document.getElementById('confirmSubscribeBtn').onclick = processSubscription;
    
    // Close modals on outside click
    document.getElementById('confirmModal').addEventListener('click', (e) => {
        if (e.target.id === 'confirmModal') {
            closeConfirmModal();
            pendingSubscription = null; // Clear when user clicks outside
        }
    });
    
    document.getElementById('successModal').addEventListener('click', (e) => {
        if (e.target.id === 'successModal') {
            closeSuccessModal();
        }
    });
    
    // Clear pendingSubscription when cancel button is clicked
    document.querySelector('.modal-btn-secondary').addEventListener('click', () => {
        pendingSubscription = null;
    });
});
</script>

</body>
</html>
