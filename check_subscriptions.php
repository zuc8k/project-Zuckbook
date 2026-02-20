<?php
session_start();
require_once __DIR__ . "/backend/config.php";

// Only allow admins to run this
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized");
}

$user_id = intval($_SESSION['user_id']);
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user || !in_array($user['role'], ['cofounder', 'mod', 'sup'])) {
    die("Unauthorized - Admin access required");
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Check Subscriptions - ZuckBook</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: system-ui, -apple-system, sans-serif; background: #f0f2f5; padding: 40px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 40px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h1 { color: #1877f2; margin-bottom: 20px; }
        .btn { padding: 12px 24px; background: #1877f2; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; font-size: 16px; }
        .btn:hover { background: #166fe5; }
        .result { margin-top: 20px; padding: 20px; background: #f0f2f5; border-radius: 8px; white-space: pre-wrap; font-family: monospace; }
        .success { color: #10b981; font-weight: 600; }
        .info { color: #65676b; }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-clock"></i> Check Expired Subscriptions</h1>
        <p style="color: #65676b; margin-bottom: 20px;">This will check all users and expire subscriptions that have passed their expiration date.</p>
        
        <button class="btn" onclick="checkSubscriptions()">
            <i class="fas fa-sync"></i> Check Now
        </button>
        
        <div id="result" class="result" style="display: none;"></div>
    </div>

    <script>
    function checkSubscriptions() {
        const btn = event.target;
        const result = document.getElementById('result');
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking...';
        result.style.display = 'block';
        result.innerHTML = 'Processing...';
        
        fetch('/backend/cron/check_expired_subscriptions.php')
        .then(res => res.text())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sync"></i> Check Now';
            result.innerHTML = data;
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-sync"></i> Check Now';
            result.innerHTML = 'Error: ' + err.message;
        });
    }
    </script>
</body>
</html>
