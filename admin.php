<?php
require_once __DIR__ . "/backend/config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: /");
    exit;
}

$user_id = intval($_SESSION['user_id']);

/* ==============================
   CHECK ADMIN ROLE FROM USERS
============================== */

$stmt = $conn->prepare("
    SELECT role, name 
    FROM users 
    WHERE id=?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    session_destroy();
    exit;
}

$role = $user['role'];

/* السماح فقط للإدارة */
if (!in_array($role, ['sup','mod','cofounder'])) {
    die("Access Denied");
}
?>

<!DOCTYPE html>
<html>
<head>
<title>ZB Admin</title>
<link rel="stylesheet" href="style.css">
<style>

body{
    margin:0;
    font-family:system-ui;
    background:#0f0f0f;
    color:white;
}

.admin-container{
    padding:30px;
    max-width:900px;
    margin:auto;
}

.admin-card{
    background:#1a1a1a;
    padding:20px;
    margin-bottom:20px;
    border-radius:12px;
    transition:.2s;
}

.admin-card:hover{
    background:#222;
    transform:translateY(-3px);
}

.admin-card a{
    text-decoration:none;
    color:white;
    font-weight:bold;
    font-size:16px;
}

.badge{
    padding:6px 14px;
    border-radius:20px;
    font-size:13px;
    font-weight:bold;
}

.cofounder{
    background:linear-gradient(90deg,#d4af37,#ffd700,#d4af37);
    background-size:200% auto;
    animation:gold 3s linear infinite;
    color:black;
}

.sup{
    background:#1877f2;
}

.mod{
    background:#42b72a;
}

@keyframes gold{
    0%{background-position:0%}
    100%{background-position:200%}
}

</style>
</head>
<body>

<?php include "menu.php"; ?>

<div class="admin-container">

    <h2>
        Admin Panel

        <?php if($role == 'cofounder'): ?>
            <span class="badge cofounder">ZB | CO-FOUNDER</span>
        <?php elseif($role == 'sup'): ?>
            <span class="badge sup">ZB | SUP</span>
        <?php elseif($role == 'mod'): ?>
            <span class="badge mod">ZB | MOD</span>
        <?php endif; ?>

    </h2>

    <div class="admin-card">
        <a href="../admin/admin_users.php">👤 Manage Users</a>
    </div>

    <div class="admin-card">
        <a href="../admin/admin_groups.php">👥 Manage Groups</a>
    </div>

    <div class="admin-card">
        <a href="../admin/tickets.php">🎫 Support Tickets</a>
    </div>

    <div class="admin-card">
        <a href="../admin/admin_logs.php">📄 Logs</a>
    </div>

</div>

</body>
</html>