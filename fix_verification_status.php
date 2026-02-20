<?php
session_start();
require_once __DIR__ . "/backend/config.php";

// Check if user is admin
if (!isset($_SESSION['user_id'])) {
    die("Please login first");
}

echo "<!DOCTYPE html>
<html lang='ar' dir='rtl'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Fix Verification Status</title>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f2f5; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .card { background: white; border-radius: 12px; padding: 25px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        h1 { color: #1877f2; margin-bottom: 20px; }
        h2 { color: #050505; margin: 20px 0 15px; border-bottom: 2px solid #1877f2; padding-bottom: 10px; }
        .success { color: #10b981; font-weight: bold; }
        .error { color: #ef4444; font-weight: bold; }
        .warning { color: #f59e0b; font-weight: bold; }
        .info { color: #3b82f6; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        table th, table td { padding: 10px; text-align: right; border-bottom: 1px solid #e5e7eb; font-size: 13px; }
        table th { background: #f9fafb; font-weight: 600; }
        .btn { display: inline-block; padding: 12px 24px; background: #1877f2; color: white; text-decoration: none; border-radius: 8px; margin: 5px; font-weight: 600; border: none; cursor: pointer; }
        .btn:hover { background: #166fe5; }
        .btn-danger { background: #ef4444; }
        .btn-danger:hover { background: #dc2626; }
        .btn-success { background: #10b981; }
        .btn-success:hover { background: #059669; }
        .status-badge { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; }
        .badge-verified { background: #d1fae5; color: #065f46; }
        .badge-not-verified { background: #fee2e2; color: #991b1b; }
        .badge-active { background: #dbeafe; color: #1e40af; }
        .badge-expired { background: #fef3c7; color: #92400e; }
    </style>
</head>
<body>
<div class='container'>
    <h1><i class='fas fa-tools'></i> Fix Verification Status</h1>";

// Get all users
$usersStmt = $conn->query("
    SELECT id, name, subscription_tier, subscription_expires, is_verified 
    FROM users 
    ORDER BY id ASC
");

$users = $usersStmt->fetch_all(MYSQLI_ASSOC);

echo "<div class='card'>
    <h2>Current Status (Before Fix)</h2>
    <table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Subscription</th>
            <th>Expires</th>
            <th>Is Verified</th>
            <th>Should Be Verified?</th>
        </tr>";

$needsFix = [];

foreach ($users as $user) {
    $hasActiveSubscription = false;
    
    if ($user['subscription_tier'] && 
        $user['subscription_tier'] !== 'free' && 
        $user['subscription_expires'] && 
        strtotime($user['subscription_expires']) > time()) {
        $hasActiveSubscription = true;
    }
    
    $shouldBeVerified = $hasActiveSubscription ? 1 : 0;
    $currentlyVerified = $user['is_verified'];
    
    $needsFixing = ($shouldBeVerified != $currentlyVerified);
    
    if ($needsFixing) {
        $needsFix[] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'should_be' => $shouldBeVerified
        ];
    }
    
    $rowStyle = $needsFixing ? "background: #fef3c7;" : "";
    
    echo "<tr style='{$rowStyle}'>
        <td>#{$user['id']}</td>
        <td>{$user['name']}</td>
        <td><span class='status-badge " . ($hasActiveSubscription ? 'badge-active' : 'badge-expired') . "'>" . strtoupper($user['subscription_tier'] ?? 'free') . "</span></td>
        <td>" . ($user['subscription_expires'] ? date('Y-m-d', strtotime($user['subscription_expires'])) : '-') . "</td>
        <td><span class='status-badge " . ($currentlyVerified ? 'badge-verified' : 'badge-not-verified') . "'>" . ($currentlyVerified ? 'YES' : 'NO') . "</span></td>
        <td><span class='status-badge " . ($shouldBeVerified ? 'badge-verified' : 'badge-not-verified') . "'>" . ($shouldBeVerified ? 'YES' : 'NO') . "</span></td>
    </tr>";
}

echo "</table>";

if (empty($needsFix)) {
    echo "<p class='success'><i class='fas fa-check-circle'></i> All users have correct verification status!</p>";
} else {
    echo "<p class='warning'><i class='fas fa-exclamation-triangle'></i> Found " . count($needsFix) . " users that need fixing</p>";
}

echo "</div>";

// Process fix if requested
if (isset($_GET['fix']) && $_GET['fix'] === 'yes') {
    echo "<div class='card'>
        <h2 class='info'>Processing Fix...</h2>";
    
    try {
        $conn->begin_transaction();
        
        $fixed = 0;
        $errors = 0;
        
        // Fix all users
        foreach ($users as $user) {
            $hasActiveSubscription = false;
            
            if ($user['subscription_tier'] && 
                $user['subscription_tier'] !== 'free' && 
                $user['subscription_expires'] && 
                strtotime($user['subscription_expires']) > time()) {
                $hasActiveSubscription = true;
            }
            
            $shouldBeVerified = $hasActiveSubscription ? 1 : 0;
            $currentlyVerified = $user['is_verified'];
            
            if ($shouldBeVerified != $currentlyVerified) {
                $updateStmt = $conn->prepare("UPDATE users SET is_verified = ? WHERE id = ?");
                $updateStmt->bind_param("ii", $shouldBeVerified, $user['id']);
                
                if ($updateStmt->execute()) {
                    echo "<p class='success'><i class='fas fa-check'></i> Fixed user #{$user['id']} ({$user['name']}): is_verified = {$shouldBeVerified}</p>";
                    $fixed++;
                } else {
                    echo "<p class='error'><i class='fas fa-times'></i> Failed to fix user #{$user['id']}: " . $conn->error . "</p>";
                    $errors++;
                }
            }
        }
        
        $conn->commit();
        
        echo "<h3 class='success'><i class='fas fa-check-circle'></i> FIX COMPLETED!</h3>";
        echo "<p>Fixed: <strong>{$fixed}</strong> users</p>";
        echo "<p>Errors: <strong>{$errors}</strong></p>";
        echo "<p><a href='fix_verification_status.php' class='btn btn-success'>Refresh to See Results</a></p>";
        
    } catch (Exception $e) {
        $conn->rollback();
        echo "<p class='error'><i class='fas fa-times-circle'></i> Error: " . $e->getMessage() . "</p>";
    }
    
    echo "</div>";
} else {
    if (!empty($needsFix)) {
        echo "<div class='card'>
            <h2>Ready to Fix?</h2>
            <p class='warning'><i class='fas fa-exclamation-triangle'></i> This will update verification status for all users based on their subscription status:</p>
            <ul style='margin: 15px 0 15px 30px; line-height: 2;'>
                <li><strong>Active subscription</strong> (not expired) → is_verified = 1</li>
                <li><strong>No subscription or expired</strong> → is_verified = 0</li>
            </ul>
            <p><a href='?fix=yes' class='btn btn-danger'>YES, FIX ALL USERS NOW</a></p>
        </div>";
    }
}

echo "<div class='card'>
    <h2>Quick Links</h2>
    <a href='home.php' class='btn'>Home</a>
    <a href='admin_users.php' class='btn'>Admin Users</a>
    <a href='test_system.php' class='btn'>System Test</a>
</div>";

echo "</div>
</body>
</html>";
?>
