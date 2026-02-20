<?php
session_start();
require_once __DIR__ . "/backend/config.php";

if (!isset($_SESSION['user_id'])) {
    die("Please login first");
}

$user_id = intval($_SESSION['user_id']);

echo "<!DOCTYPE html>
<html lang='ar' dir='rtl'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>System Test - ZuckBook</title>
    <link rel='stylesheet' href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f2f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #1877f2; margin-bottom: 30px; }
        .section { background: white; border-radius: 12px; padding: 25px; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .section h2 { color: #050505; margin-bottom: 20px; border-bottom: 2px solid #1877f2; padding-bottom: 10px; }
        .success { color: #10b981; font-weight: bold; }
        .error { color: #ef4444; font-weight: bold; }
        .warning { color: #f59e0b; font-weight: bold; }
        .info { color: #3b82f6; font-weight: bold; }
        pre { background: #f5f5f5; padding: 15px; border-radius: 8px; overflow-x: auto; font-size: 13px; }
        .btn { display: inline-block; padding: 12px 24px; background: #1877f2; color: white; text-decoration: none; border-radius: 8px; margin: 5px; font-weight: 600; }
        .btn:hover { background: #0c63e4; }
        .btn-danger { background: #ef4444; }
        .btn-danger:hover { background: #dc2626; }
        .btn-success { background: #10b981; }
        .btn-success:hover { background: #059669; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        table th, table td { padding: 12px; text-align: right; border-bottom: 1px solid #e5e7eb; }
        table th { background: #f9fafb; font-weight: 600; }
        .status-badge { padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .status-active { background: #d1fae5; color: #065f46; }
        .status-expired { background: #fee2e2; color: #991b1b; }
        .status-free { background: #e5e7eb; color: #374151; }
    </style>
</head>
<body>
<div class='container'>
    <h1><i class='fas fa-tools'></i> System Test & Debug Tool</h1>";

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Section 1: User Information
echo "<div class='section'>
    <h2><i class='fas fa-user'></i> معلومات المستخدم</h2>
    <table>
        <tr><th>الحقل</th><th>القيمة</th></tr>
        <tr><td>ID</td><td>{$user['id']}</td></tr>
        <tr><td>الاسم</td><td>{$user['name']}</td></tr>
        <tr><td>الكوينات</td><td><i class='fas fa-coins' style='color: #f59e0b;'></i> {$user['coins']}</td></tr>
        <tr><td>نوع الاشتراك</td><td>";

$tier = $user['subscription_tier'] ?? 'free';
$tierColors = ['free' => '#65676b', 'basic' => '#1877f2', 'premium' => '#9333ea', 'elite' => '#dc2626'];
echo "<span style='color: {$tierColors[$tier]}; font-weight: bold;'>" . strtoupper($tier) . "</span>";

echo "</td></tr>
        <tr><td>تاريخ انتهاء الاشتراك</td><td>";

if ($user['subscription_expires']) {
    $isActive = strtotime($user['subscription_expires']) > time();
    $class = $isActive ? 'status-active' : 'status-expired';
    $status = $isActive ? 'نشط' : 'منتهي';
    echo "<span class='status-badge {$class}'>{$status}</span> ";
    echo date('Y-m-d H:i:s', strtotime($user['subscription_expires']));
    
    if ($isActive) {
        $daysLeft = ceil((strtotime($user['subscription_expires']) - time()) / 86400);
        echo " <span class='info'>({$daysLeft} يوم متبقي)</span>";
    }
} else {
    echo "<span class='status-badge status-free'>لا يوجد</span>";
}

echo "</td></tr>
        <tr><td>حالة التوثيق</td><td>";

if ($user['is_verified'] == 1) {
    echo "<span class='success'><i class='fas fa-check-circle'></i> موثق</span>";
} else {
    echo "<span class='error'><i class='fas fa-times-circle'></i> غير موثق</span>";
}

echo "</td></tr>
    </table>
</div>";

// Section 2: Database Structure Check
echo "<div class='section'>
    <h2><i class='fas fa-database'></i> فحص قاعدة البيانات</h2>";

// Check users table columns
$requiredColumns = ['subscription_tier', 'subscription_expires', 'is_verified', 'coins'];
$result = $conn->query("SHOW COLUMNS FROM users");
$existingColumns = [];
while ($row = $result->fetch_assoc()) {
    $existingColumns[] = $row['Field'];
}

echo "<h3>أعمدة جدول المستخدمين:</h3><ul>";
foreach ($requiredColumns as $col) {
    if (in_array($col, $existingColumns)) {
        echo "<li class='success'><i class='fas fa-check'></i> {$col}</li>";
    } else {
        echo "<li class='error'><i class='fas fa-times'></i> {$col} (مفقود)</li>";
    }
}
echo "</ul>";

// Check subscriptions table
$checkTable = $conn->query("SHOW TABLES LIKE 'subscriptions'");
if ($checkTable->num_rows > 0) {
    echo "<p class='success'><i class='fas fa-check'></i> جدول الاشتراكات موجود</p>";
} else {
    echo "<p class='warning'><i class='fas fa-exclamation-triangle'></i> جدول الاشتراكات غير موجود (سيتم إنشاؤه تلقائياً)</p>";
}

// Check support_tickets table columns
$checkTicketsTable = $conn->query("SHOW TABLES LIKE 'support_tickets'");
if ($checkTicketsTable->num_rows > 0) {
    echo "<h3>أعمدة جدول التذاكر:</h3><ul>";
    $ticketColumns = ['subject', 'message', 'cancellation_data'];
    $result = $conn->query("SHOW COLUMNS FROM support_tickets");
    $existingTicketColumns = [];
    while ($row = $result->fetch_assoc()) {
        $existingTicketColumns[] = $row['Field'];
    }
    
    foreach ($ticketColumns as $col) {
        if (in_array($col, $existingTicketColumns)) {
            echo "<li class='success'><i class='fas fa-check'></i> {$col}</li>";
        } else {
            echo "<li class='warning'><i class='fas fa-exclamation-triangle'></i> {$col} (مفقود - سيتم إضافته تلقائياً)</li>";
        }
    }
    echo "</ul>";
}

echo "</div>";

// Section 3: Subscription History
echo "<div class='section'>
    <h2><i class='fas fa-history'></i> سجل الاشتراكات</h2>";

$checkTable = $conn->query("SHOW TABLES LIKE 'subscriptions'");
if ($checkTable->num_rows > 0) {
    $subStmt = $conn->prepare("SELECT * FROM subscriptions WHERE user_id = ? ORDER BY started_at DESC LIMIT 5");
    $subStmt->bind_param("i", $user_id);
    $subStmt->execute();
    $subscriptions = $subStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    if (empty($subscriptions)) {
        echo "<p class='info'>لا يوجد سجل اشتراكات</p>";
    } else {
        echo "<table>
            <tr>
                <th>ID</th>
                <th>الخطة</th>
                <th>النوع</th>
                <th>السعر</th>
                <th>تاريخ البدء</th>
                <th>تاريخ الانتهاء</th>
                <th>الحالة</th>
            </tr>";
        
        foreach ($subscriptions as $sub) {
            $statusClass = $sub['status'] === 'active' ? 'status-active' : 'status-expired';
            echo "<tr>
                <td>{$sub['id']}</td>
                <td>" . strtoupper($sub['plan']) . "</td>
                <td>{$sub['billing_type']}</td>
                <td>{$sub['price']} <i class='fas fa-coins'></i></td>
                <td>" . date('Y-m-d H:i', strtotime($sub['started_at'])) . "</td>
                <td>" . date('Y-m-d H:i', strtotime($sub['expires_at'])) . "</td>
                <td><span class='status-badge {$statusClass}'>{$sub['status']}</span></td>
            </tr>";
        }
        
        echo "</table>";
    }
} else {
    echo "<p class='warning'>جدول الاشتراكات غير موجود</p>";
}

echo "</div>";

// Section 4: Cancellation Tickets
echo "<div class='section'>
    <h2><i class='fas fa-ticket-alt'></i> تذاكر الإلغاء</h2>";

$ticketStmt = $conn->prepare("
    SELECT * FROM support_tickets 
    WHERE user_id = ? 
    ORDER BY id DESC 
    LIMIT 5
");
$ticketStmt->bind_param("i", $user_id);
$ticketStmt->execute();
$tickets = $ticketStmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($tickets)) {
    echo "<p class='info'>لا توجد تذاكر</p>";
} else {
    foreach ($tickets as $ticket) {
        $isCancellation = false;
        
        if (isset($ticket['subject']) && stripos($ticket['subject'], 'Cancellation') !== false) {
            $isCancellation = true;
        }
        if (isset($ticket['ticket_code']) && stripos($ticket['ticket_code'], 'CANCEL') !== false) {
            $isCancellation = true;
        }
        if (isset($ticket['cancellation_data']) && !empty($ticket['cancellation_data']) && $ticket['cancellation_data'] !== 'null') {
            $isCancellation = true;
        }
        
        $borderColor = $isCancellation ? '#3b82f6' : '#e5e7eb';
        echo "<div style='border: 2px solid {$borderColor}; padding: 15px; margin: 10px 0; border-radius: 8px;'>";
        echo "<h3 style='color: " . ($isCancellation ? '#3b82f6' : '#65676b') . ";'>
            Ticket #{$ticket['id']} - {$ticket['ticket_code']}
            " . ($isCancellation ? "<span class='info'>(تذكرة إلغاء)</span>" : "") . "
        </h3>";
        echo "<p><strong>الحالة:</strong> <span class='status-badge status-" . ($ticket['status'] === 'done' ? 'active' : 'expired') . "'>{$ticket['status']}</span></p>";
        
        if (isset($ticket['subject'])) {
            echo "<p><strong>الموضوع:</strong> {$ticket['subject']}</p>";
        }
        
        if ($isCancellation && isset($ticket['cancellation_data']) && !empty($ticket['cancellation_data'])) {
            $data = json_decode($ticket['cancellation_data'], true);
            if ($data) {
                echo "<p><strong>مبلغ الاسترداد:</strong> <span class='success'>{$data['refund_amount']} <i class='fas fa-coins'></i></span></p>";
            }
        }
        
        echo "</div>";
    }
}

echo "</div>";

// Section 5: Quick Actions
echo "<div class='section'>
    <h2><i class='fas fa-bolt'></i> إجراءات سريعة</h2>
    <div style='display: flex; flex-wrap: wrap; gap: 10px;'>
        <a href='debug_subscription.php' class='btn'><i class='fas fa-bug'></i> اختبار الاشتراك</a>
        <a href='debug_cancellation.php' class='btn'><i class='fas fa-bug'></i> اختبار الإلغاء</a>
        <a href='force_verify.php' class='btn btn-success'><i class='fas fa-sync'></i> إصلاح التوثيق</a>
        <a href='subscriptions.php' class='btn'><i class='fas fa-crown'></i> صفحة الاشتراكات</a>
        <a href='my_subscription.php' class='btn'><i class='fas fa-user'></i> اشتراكي</a>
        <a href='admin/tickets.php' class='btn'><i class='fas fa-ticket-alt'></i> التذاكر (Admin)</a>
        <a href='home.php' class='btn'><i class='fas fa-home'></i> الرئيسية</a>
    </div>
</div>";

echo "</div>
</body>
</html>";
?>
