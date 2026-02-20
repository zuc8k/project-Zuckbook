<?php
session_start();
require_once __DIR__ . "/backend/config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: /");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /subscriptions.php");
    exit;
}

$user_id = intval($_SESSION['user_id']);
$plan_id = isset($_POST['plan_id']) ? intval($_POST['plan_id']) : 0;
$billing_type = isset($_POST['billing_type']) ? $_POST['billing_type'] : 'monthly';

if (!$plan_id) {
    die("Invalid plan selected");
}

// Get plan details
$planStmt = $conn->prepare("SELECT * FROM subscription_plans WHERE id = ? AND is_active = 1");
$planStmt->bind_param("i", $plan_id);
$planStmt->execute();
$plan = $planStmt->get_result()->fetch_assoc();

if (!$plan) {
    die("Plan not found");
}

$amount = $billing_type === 'yearly' ? $plan['yearly_price'] : $plan['monthly_price'];

// Here you would integrate with a payment gateway (Stripe, PayPal, etc.)
// For now, we'll just show a success message

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment - ZuckBook</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: system-ui, -apple-system, sans-serif; background: #f0f2f5; }
.container { max-width: 500px; margin: 50px auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
h1 { color: #1877f2; margin-bottom: 20px; }
.plan-info { background: #f0f2f5; padding: 15px; border-radius: 8px; margin: 20px 0; }
.price { font-size: 28px; font-weight: bold; color: #1877f2; }
.btn { width: 100%; padding: 12px; background: #1877f2; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; margin-top: 20px; }
.btn:hover { background: #166fe5; }

/* ==================== MOBILE RESPONSIVE STYLES ==================== */

@media (max-width: 768px) {
    .container {
        margin: 30px auto;
        padding: 20px;
        border-radius: 8px;
    }

    h1 {
        font-size: 22px;
        margin-bottom: 16px;
    }

    .plan-info {
        padding: 12px;
        margin: 16px 0;
    }

    .plan-info h3 {
        font-size: 18px;
    }

    .price {
        font-size: 24px;
    }

    .plan-info p {
        font-size: 14px;
    }

    .btn {
        padding: 11px;
        font-size: 15px;
    }
}

@media (max-width: 575px) {
    .container {
        margin: 20px 10px;
        padding: 16px;
    }

    h1 {
        font-size: 20px;
        margin-bottom: 14px;
    }

    .plan-info {
        padding: 10px;
        margin: 14px 0;
    }

    .plan-info h3 {
        font-size: 16px;
    }

    .price {
        font-size: 22px;
    }

    .plan-info p {
        font-size: 13px;
    }

    p {
        font-size: 14px;
    }

    .btn {
        padding: 10px;
        font-size: 14px;
    }
}
</style>
</head>
<body>

<div class="container">
    <h1>Complete Your Subscription</h1>
    
    <div class="plan-info">
        <h3><?= htmlspecialchars($plan['name']) ?> Plan</h3>
        <div class="price">EGP <?= $amount ?></div>
        <p style="color: #65676b; margin-top: 10px;">Billing: <?= ucfirst($billing_type) ?></p>
    </div>

    <p>You will be charged <strong>EGP <?= $amount ?></strong> for your <?= $billing_type ?> subscription.</p>

    <form method="POST" action="/backend/process_payment.php">
        <input type="hidden" name="plan_id" value="<?= $plan_id ?>">
        <input type="hidden" name="billing_type" value="<?= $billing_type ?>">
        <input type="hidden" name="amount" value="<?= $amount ?>">
        <button type="submit" class="btn">Proceed to Payment</button>
    </form>

    <button class="btn" style="background: #e4e6eb; color: #050505; margin-top: 10px;" onclick="history.back()">Cancel</button>
</div>

</body>
</html>
