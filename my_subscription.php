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
$userStmt = $conn->prepare("SELECT id, name, coins, profile_image, subscription_tier, subscription_expires FROM users WHERE id = ?");
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
$daysRemaining = 0;
if ($subscriptionExpires && strtotime($subscriptionExpires) > time()) {
    $isSubscribed = true;
    $daysRemaining = ceil((strtotime($subscriptionExpires) - time()) / 86400);
}

// Plan details
$planDetails = [
    'free' => [
        'name' => 'Free',
        'color' => '#65676b',
        'icon' => 'fa-user',
        'features' => [
            'Basic profile features',
            'Create posts',
            'Join groups',
            'Send messages'
        ]
    ],
    'basic' => [
        'name' => 'Basic',
        'color' => '#1877f2',
        'icon' => 'fa-star',
        'features' => [
            'Blue verification badge',
            'Ad-free experience',
            'Priority support',
            'High-quality photo uploads',
            'Custom profile themes'
        ]
    ],
    'premium' => [
        'name' => 'Premium',
        'color' => '#9333ea',
        'icon' => 'fa-crown',
        'features' => [
            'All Basic features',
            'Gold Premium badge',
            'Unlimited storage',
            'Create premium groups',
            'Advanced post analytics',
            '24/7 priority support',
            'Custom profile URL'
        ]
    ],
    'elite' => [
        'name' => 'Elite',
        'color' => '#dc2626',
        'icon' => 'fa-gem',
        'features' => [
            'All Premium features',
            'Diamond Elite badge',
            'Dedicated account manager',
            'Featured on homepage',
            'Advanced marketing tools',
            'Early access to new features',
            'Live streaming capability',
            'Full profile customization'
        ]
    ]
];

$currentPlan = $planDetails[$currentTier];
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Subscription - ZuckBook</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f0f2f5; color: #050505; }

        /* Header */
        .header { background: #ffffff; height: 56px; border-bottom: 1px solid #e4e6eb; box-shadow: 0 2px 4px rgba(0,0,0,0.1); padding: 0 16px; position: fixed; top: 0; left: 0; right: 0; z-index: 300; display: flex; align-items: center; }
        .header-content { width: 100%; display: flex; justify-content: space-between; align-items: center; padding: 0 16px; }
        .logo { font-size: 40px; font-weight: bold; color: #1877f2; cursor: pointer; }
        .back-btn { padding: 8px 16px; background: #e4e6eb; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; color: #050505; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .back-btn:hover { background: #d8dadf; }

        /* Main Content */
        .container { max-width: 900px; margin: 80px auto 40px; padding: 0 20px; }
        
        /* Subscription Card */
        .subscription-card { background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1); margin-bottom: 30px; }
        .card-header { padding: 40px; text-align: center; color: white; position: relative; overflow: hidden; }
        .card-header::before { content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.1)" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,112C672,96,768,96,864,112C960,128,1056,160,1152,160C1248,160,1344,128,1392,112L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom; background-size: cover; }
        .plan-icon { width: 100px; height: 100px; background: rgba(255, 255, 255, 0.25); backdrop-filter: blur(10px); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 50px; position: relative; z-index: 1; }
        .plan-name { font-size: 36px; font-weight: 900; margin-bottom: 10px; position: relative; z-index: 1; }
        .plan-status { font-size: 16px; opacity: 0.95; position: relative; z-index: 1; }

        /* Countdown Timer */
        .countdown-section { padding: 35px; background: linear-gradient(135deg, #f0f2f5 0%, #e4e6eb 100%); }
        .countdown-title { text-align: center; font-size: 18px; font-weight: 700; color: #65676b; margin-bottom: 25px; }
        .countdown-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; }
        .countdown-item { background: white; border-radius: 16px; padding: 20px; text-align: center; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05); }
        .countdown-value { font-size: 36px; font-weight: 900; color: #050505; display: block; margin-bottom: 5px; }
        .countdown-label { font-size: 13px; color: #65676b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }

        /* Features Section */
        .features-section { padding: 35px; }
        .features-title { font-size: 20px; font-weight: 800; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .features-list { list-style: none; }
        .feature-item { padding: 15px; display: flex; align-items: center; gap: 15px; border-bottom: 1px solid #f0f2f5; transition: all 0.2s ease; }
        .feature-item:last-child { border-bottom: none; }
        .feature-item:hover { background: #f0f2f5; border-radius: 8px; }
        .feature-icon { width: 40px; height: 40px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 18px; flex-shrink: 0; }
        .feature-text { font-size: 15px; color: #050505; font-weight: 500; }

        /* Actions */
        .actions-section { padding: 0 35px 35px; display: flex; gap: 12px; }
        .action-btn { flex: 1; padding: 16px; border: none; border-radius: 12px; font-size: 16px; font-weight: 700; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4); }
        .btn-secondary { background: #e4e6eb; color: #050505; }
        .btn-secondary:hover { background: #d8dadf; }
        .btn-danger { background: #dc2626; color: white; }
        .btn-danger:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(220, 38, 38, 0.4); }

        /* Free Plan Message */
        .free-plan-message { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 40px; text-align: center; border-radius: 16px; margin-bottom: 30px; }
        .free-plan-message h2 { font-size: 28px; font-weight: 800; margin-bottom: 15px; }
        .free-plan-message p { font-size: 16px; opacity: 0.95; margin-bottom: 25px; }
        .upgrade-btn { background: white; color: #667eea; padding: 16px 32px; border: none; border-radius: 12px; font-size: 16px; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .upgrade-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(255, 255, 255, 0.3); }

        @media (max-width: 768px) {
            .countdown-grid { grid-template-columns: repeat(2, 1fr); }
            .actions-section { flex-direction: column; }
        }

        /* ==================== MOBILE RESPONSIVE STYLES ==================== */

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

            /* Container */
            .container {
                margin: 60px auto 30px;
                padding: 0 10px;
            }

            /* Free plan message */
            .free-plan-message {
                padding: 30px 20px;
            }

            .free-plan-message h2 {
                font-size: 24px;
            }

            .free-plan-message p {
                font-size: 15px;
            }

            .upgrade-btn {
                padding: 14px 24px;
                font-size: 15px;
            }

            /* Subscription card */
            .subscription-card {
                margin-bottom: 20px;
            }

            .card-header {
                padding: 30px 20px;
            }

            .plan-icon {
                width: 80px;
                height: 80px;
                font-size: 40px;
                margin-bottom: 15px;
            }

            .plan-name {
                font-size: 28px;
            }

            .plan-status {
                font-size: 14px;
            }

            /* Countdown */
            .countdown-section {
                padding: 25px 20px;
            }

            .countdown-title {
                font-size: 16px;
                margin-bottom: 20px;
            }

            .countdown-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }

            .countdown-item {
                padding: 15px;
            }

            .countdown-value {
                font-size: 28px;
            }

            .countdown-label {
                font-size: 12px;
            }

            /* Features */
            .features-section {
                padding: 25px 20px;
            }

            .features-title {
                font-size: 18px;
            }

            .feature-item {
                padding: 12px;
                gap: 12px;
            }

            .feature-icon {
                width: 36px;
                height: 36px;
                font-size: 16px;
            }

            .feature-text {
                font-size: 14px;
            }

            /* Actions */
            .actions-section {
                padding: 0 20px 25px;
                flex-direction: column;
            }

            .action-btn {
                padding: 14px;
                font-size: 15px;
            }

            /* Cancel modal */
            #cancelModal > div {
                width: 95%;
                max-width: 95%;
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

            .container {
                margin: 55px auto 25px;
                padding: 0 8px;
            }

            .free-plan-message {
                padding: 25px 15px;
            }

            .free-plan-message h2 {
                font-size: 20px;
            }

            .free-plan-message p {
                font-size: 14px;
                margin-bottom: 20px;
            }

            .upgrade-btn {
                padding: 12px 20px;
                font-size: 14px;
            }

            .card-header {
                padding: 25px 15px;
            }

            .plan-icon {
                width: 70px;
                height: 70px;
                font-size: 35px;
            }

            .plan-name {
                font-size: 24px;
            }

            .plan-status {
                font-size: 13px;
            }

            .countdown-section {
                padding: 20px 15px;
            }

            .countdown-title {
                font-size: 15px;
                margin-bottom: 15px;
            }

            .countdown-grid {
                gap: 10px;
            }

            .countdown-item {
                padding: 12px;
            }

            .countdown-value {
                font-size: 24px;
            }

            .countdown-label {
                font-size: 11px;
            }

            .features-section {
                padding: 20px 15px;
            }

            .features-title {
                font-size: 16px;
                margin-bottom: 15px;
            }

            .feature-item {
                padding: 10px;
                gap: 10px;
            }

            .feature-icon {
                width: 32px;
                height: 32px;
                font-size: 14px;
            }

            .feature-text {
                font-size: 13px;
            }

            .actions-section {
                padding: 0 15px 20px;
            }

            .action-btn {
                padding: 12px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>

<!-- Cancel Subscription Confirmation Modal -->
<div id="cancelModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 16px; max-width: 500px; width: 90%; padding: 0; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.3); animation: slideUp 0.3s ease;">
        <div style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); padding: 30px; text-align: center; color: white;">
            <div style="width: 70px; height: 70px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; font-size: 32px;">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h2 style="font-size: 24px; font-weight: 800; margin-bottom: 8px;">Cancel Subscription?</h2>
            <p style="font-size: 14px; opacity: 0.95;">This action will create a cancellation request</p>
        </div>
        
        <div style="padding: 30px;">
            <div style="background: #fef2f2; border-left: 4px solid #ef4444; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <p style="font-size: 14px; color: #991b1b; line-height: 1.6; margin: 0;">
                    <strong>Are you sure you want to cancel your subscription?</strong><br><br>
                    A support ticket will be created and our team will process your refund request within 24-48 hours.
                </p>
            </div>
            
            <div style="display: flex; gap: 12px;">
                <button onclick="closeCancelModal()" style="flex: 1; padding: 14px; background: #f3f4f6; border: none; border-radius: 10px; font-weight: 600; font-size: 15px; cursor: pointer; color: #374151; transition: all 0.2s;">
                    <i class="fas fa-times"></i> No, Keep It
                </button>
                <button onclick="confirmCancellation()" style="flex: 1; padding: 14px; background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); border: none; border-radius: 10px; font-weight: 600; font-size: 15px; cursor: pointer; color: white; transition: all 0.2s;">
                    <i class="fas fa-check"></i> Yes, Cancel
                </button>
            </div>
        </div>
    </div>
</div>

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

<!-- Header -->
<div class="header">
    <div class="header-content">
        <div class="logo" onclick="window.location.href='/home.php'">f</div>
        <a href="/home.php" class="back-btn">
            <i class="fas fa-arrow-left"></i>
            Back to Home
        </a>
    </div>
</div>

<!-- Main Content -->
<div class="container">
    <?php if ($currentTier === 'free'): ?>
        <!-- Free Plan Message -->
        <div class="free-plan-message">
            <h2><i class="fas fa-crown"></i> Upgrade Your Experience</h2>
            <p>You're currently on the Free plan. Upgrade to unlock premium features and take your ZuckBook experience to the next level!</p>
            <a href="/subscriptions.php" class="upgrade-btn">
                <i class="fas fa-rocket"></i>
                View Plans & Upgrade
            </a>
        </div>
    <?php endif; ?>

    <!-- Subscription Card -->
    <div class="subscription-card">
        <div class="card-header" style="background: linear-gradient(135deg, <?= $currentPlan['color'] ?> 0%, <?= $currentPlan['color'] ?>dd 100%);">
            <div class="plan-icon">
                <i class="fas <?= $currentPlan['icon'] ?>"></i>
            </div>
            <h1 class="plan-name"><?= $currentPlan['name'] ?> Plan</h1>
            <p class="plan-status">
                <?php if ($isSubscribed): ?>
                    <i class="fas fa-check-circle"></i> Active Subscription
                <?php else: ?>
                    <i class="fas fa-info-circle"></i> Current Plan
                <?php endif; ?>
            </p>
        </div>

        <?php if ($isSubscribed): ?>
        <!-- Countdown Timer -->
        <div class="countdown-section">
            <div class="countdown-title">
                <i class="fas fa-clock"></i> Time Remaining Until Expiration
            </div>
            <div class="countdown-grid">
                <div class="countdown-item">
                    <span class="countdown-value" id="days">0</span>
                    <span class="countdown-label">Days</span>
                </div>
                <div class="countdown-item">
                    <span class="countdown-value" id="hours">0</span>
                    <span class="countdown-label">Hours</span>
                </div>
                <div class="countdown-item">
                    <span class="countdown-value" id="minutes">0</span>
                    <span class="countdown-label">Minutes</span>
                </div>
                <div class="countdown-item">
                    <span class="countdown-value" id="seconds">0</span>
                    <span class="countdown-label">Seconds</span>
                </div>
            </div>
            <p style="text-align: center; margin-top: 20px; color: #65676b; font-size: 14px;">
                <i class="fas fa-calendar-alt"></i> 
                Expires on: <strong><?= date('F j, Y \a\t g:i A', strtotime($subscriptionExpires)) ?></strong>
            </p>
        </div>
        <?php endif; ?>

        <!-- Features Section -->
        <div class="features-section">
            <h2 class="features-title">
                <i class="fas fa-star"></i>
                Your Plan Features
            </h2>
            <ul class="features-list">
                <?php foreach ($currentPlan['features'] as $feature): ?>
                <li class="feature-item">
                    <div class="feature-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <span class="feature-text"><?= $feature ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Actions -->
        <div class="actions-section">
            <?php if ($currentTier !== 'elite'): ?>
            <a href="/subscriptions.php" class="action-btn btn-primary">
                <i class="fas fa-arrow-up"></i>
                Upgrade Plan
            </a>
            <?php endif; ?>
            <?php if ($isSubscribed): ?>
            <a href="/subscriptions.php" class="action-btn btn-secondary">
                <i class="fas fa-sync"></i>
                Renew Subscription
            </a>
            <button onclick="cancelSubscription()" class="action-btn btn-danger">
                <i class="fas fa-times-circle"></i>
                Cancel Subscription
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($isSubscribed): ?>
<script>
// Countdown Timer
const expirationDate = new Date('<?= $subscriptionExpires ?>').getTime();

function updateCountdown() {
    const now = new Date().getTime();
    const distance = expirationDate - now;

    if (distance < 0) {
        document.getElementById('days').textContent = '0';
        document.getElementById('hours').textContent = '0';
        document.getElementById('minutes').textContent = '0';
        document.getElementById('seconds').textContent = '0';
        return;
    }

    const days = Math.floor(distance / (1000 * 60 * 60 * 24));
    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((distance % (1000 * 60)) / 1000);

    document.getElementById('days').textContent = days;
    document.getElementById('hours').textContent = hours;
    document.getElementById('minutes').textContent = minutes;
    document.getElementById('seconds').textContent = seconds;
}

// Update countdown every second
updateCountdown();
setInterval(updateCountdown, 1000);
</script>
<?php endif; ?>

<script>
function cancelSubscription() {
    document.getElementById('cancelModal').style.display = 'flex';
}

function closeCancelModal() {
    document.getElementById('cancelModal').style.display = 'none';
}

function confirmCancellation() {
    const modal = document.getElementById('cancelModal');
    const btn = modal.querySelector('button[onclick="confirmCancellation()"]');
    const originalHTML = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    
    fetch('/backend/create_cancellation_ticket.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        }
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalHTML;
        
        if (data.status === 'success') {
            closeCancelModal();
            showSuccessModal(data.ticket_id, data.refund_amount);
        } else {
            closeCancelModal();
            showErrorModal(data.message || 'Unknown error');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = originalHTML;
        console.error('Error:', err);
        closeCancelModal();
        showErrorModal('Connection error');
    });
}

function showSuccessModal(ticketId, refundAmount) {
    const modal = document.createElement('div');
    modal.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 10000; display: flex; align-items: center; justify-content: center;';
    modal.innerHTML = `
        <div style="background: white; border-radius: 16px; max-width: 500px; width: 90%; padding: 0; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.3); animation: slideUp 0.3s ease;">
            <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 30px; text-align: center; color: white;">
                <div style="width: 70px; height: 70px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; font-size: 32px;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h2 style="font-size: 24px; font-weight: 800; margin-bottom: 8px;">Request Submitted!</h2>
                <p style="font-size: 14px; opacity: 0.95;">Your cancellation request has been created</p>
            </div>
            
            <div style="padding: 30px;">
                <div style="background: #f0fdf4; border-left: 4px solid #10b981; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                    <div style="font-size: 13px; color: #065f46; margin-bottom: 12px;">
                        <strong>Ticket ID:</strong> #${ticketId}
                    </div>
                    <div style="font-size: 13px; color: #065f46; margin-bottom: 12px;">
                        <strong>Estimated Refund:</strong> ${refundAmount} coins
                    </div>
                    <div style="font-size: 13px; color: #065f46;">
                        <strong>Processing Time:</strong> 24-48 hours
                    </div>
                </div>
                
                <p style="font-size: 14px; color: #6b7280; line-height: 1.6; margin-bottom: 20px;">
                    Our support team will review your request and process your refund. You can track your ticket status in the Support section.
                </p>
                
                <button onclick="window.location.href='/home.php'" style="width: 100%; padding: 14px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); border: none; border-radius: 10px; font-weight: 600; font-size: 15px; cursor: pointer; color: white;">
                    <i class="fas fa-home"></i> Back to Home
                </button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

function showErrorModal(message) {
    const modal = document.createElement('div');
    modal.style.cssText = 'position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 10000; display: flex; align-items: center; justify-content: center;';
    modal.innerHTML = `
        <div style="background: white; border-radius: 16px; max-width: 500px; width: 90%; padding: 0; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.3); animation: slideUp 0.3s ease;">
            <div style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); padding: 30px; text-align: center; color: white;">
                <div style="width: 70px; height: 70px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; font-size: 32px;">
                    <i class="fas fa-times-circle"></i>
                </div>
                <h2 style="font-size: 24px; font-weight: 800; margin-bottom: 8px;">Error</h2>
                <p style="font-size: 14px; opacity: 0.95;">Something went wrong</p>
            </div>
            
            <div style="padding: 30px;">
                <div style="background: #fef2f2; border-left: 4px solid #ef4444; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <p style="font-size: 14px; color: #991b1b; margin: 0;">${message}</p>
                </div>
                
                <button onclick="this.parentElement.parentElement.parentElement.remove()" style="width: 100%; padding: 14px; background: #f3f4f6; border: none; border-radius: 10px; font-weight: 600; font-size: 15px; cursor: pointer; color: #374151;">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}
</script>

</body>
</html>
