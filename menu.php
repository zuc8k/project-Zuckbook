<?php
require_once __DIR__ . "/backend/config.php";
require_once __DIR__ . "/includes/helpers.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: /");
    exit;
}

$user_id = intval($_SESSION['user_id']);

$stmt = $conn->prepare("
    SELECT name, profile_image, coins, is_verified
    FROM users
    WHERE id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$profileImage = $user['profile_image'] 
    ? "/uploads/".$user['profile_image'] 
    : "/assets/zuckuser.png";
?>

<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Menu</title>

<style>
body{
    margin:0;
    font-family:system-ui;
    background:#0f0f0f;
    color:white;
}

/* HEADER */
.menu-header{
    padding:22px 20px 10px;
    font-size:26px;
    font-weight:600;
}

/* PROFILE CARD */
.profile-card{
    margin:0 15px 25px;
    padding:18px;
    background:linear-gradient(145deg,#1a1a1a,#111);
    border-radius:18px;
    display:flex;
    align-items:center;
    justify-content:space-between;
}

.profile-left{
    display:flex;
    align-items:center;
    gap:14px;
}

.profile-left img{
    width:60px;
    height:60px;
    border-radius:50%;
    object-fit:cover;
    border:2px solid #222;
}

.profile-name{
    font-weight:600;
    font-size:16px;
    display:flex;
    align-items:center;
    gap:6px;
}

.profile-coins{
    font-size:13px;
    color:#aaa;
    margin-top:4px;
}

.verified{
    width:16px;
    height:16px;
    fill:#1DA1F2;
}

/* GRID */
.grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:14px;
    padding:0 15px;
}

.menu-card{
    background:#161616;
    padding:20px;
    border-radius:18px;
    display:flex;
    align-items:center;
    gap:14px;
    cursor:pointer;
    transition:.25s;
    position:relative;
}

.menu-card:hover{
    background:#202020;
    transform:translateY(-3px);
}

.menu-card svg{
    width:24px;
    height:24px;
    fill:#1877f2;
}

.menu-title{
    font-size:14px;
    font-weight:500;
}

.badge{
    position:absolute;
    top:10px;
    right:12px;
    background:#1877f2;
    padding:4px 8px;
    border-radius:20px;
    font-size:11px;
}

/* SECTION */
.section{
    margin-top:30px;
    padding:0 15px;
}

.section-title{
    font-size:13px;
    color:#777;
    margin-bottom:10px;
    text-transform:uppercase;
    letter-spacing:1px;
}

.section-item{
    padding:16px;
    background:#161616;
    border-radius:16px;
    margin-bottom:12px;
    cursor:pointer;
    display:flex;
    align-items:center;
    gap:14px;
    transition:.2s;
}

.section-item:hover{
    background:#202020;
}

.section-item svg{
    width:20px;
    height:20px;
    fill:#aaa;
}

/* LOGOUT */
.logout{
    margin:30px 15px;
    padding:16px;
    background:#1a1a1a;
    border-radius:16px;
    text-align:center;
    cursor:pointer;
    transition:.2s;
}

.logout:hover{
    background:#2a0000;
}
</style>
</head>
<body>

<div class="menu-header">Menu</div>

<!-- PROFILE -->
<div class="profile-card">

    <div class="profile-left">
        <img src="<?= $profileImage ?>">
        <div>
            <div class="profile-name">
                <?= htmlspecialchars($user['name']) ?>

                <?php if($user['is_verified']): ?>
                    <svg class="verified" viewBox="0 0 24 24">
                        <path d="M12 2l3 3 4-1 1 4 3 3-3 3-1 4-4-1-3 3-3-3-4 1-1-4-3-3 3-3 1-4 4 1z"/>
                    </svg>
                <?php endif; ?>
            </div>

            <div class="profile-coins">
                <?= formatCoins($user['coins']) ?> Coins
            </div>
        </div>
    </div>

</div>

<!-- MAIN GRID -->
<div class="grid">

    <div class="menu-card" onclick="window.location='./groups.php'">
        <svg viewBox="0 0 24 24">
            <path d="M16 11c1.7 0 3-1.3 3-3S17.7 5 16 5s-3 1.3-3 3 1.3 3 3 3zM8 11c1.7 0 3-1.3 3-3S9.7 5 8 5 5 6.3 5 8s1.3 3 3 3z"/>
        </svg>
        <div class="menu-title">Groups</div>
    </div>

    <div class="menu-card" onclick="window.location='./subscriptions.php'">
        <svg viewBox="0 0 24 24">
            <path d="M12 1l3 6 6 1-4.5 4.5L17 20l-5-3-5 3 1.5-7.5L4 8l6-1z"/>
        </svg>
        <div class="menu-title">Subscriptions</div>
    </div>

    <div class="menu-card" onclick="window.location='./coins.php'">
        <svg viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="9"/>
        </svg>
        <div class="menu-title">Coins Center</div>
    </div>

    <div class="menu-card" onclick="window.location='./security.php'">
        <svg viewBox="0 0 24 24">
            <path d="M12 2l8 4v6c0 5-3.5 9-8 10-4.5-1-8-5-8-10V6z"/>
        </svg>
        <div class="menu-title">Security</div>
    </div>

</div>

<!-- SETTINGS SECTION -->
<div class="section">

    <div class="section-title">Support & Settings</div>

    <div class="section-item" onclick="window.location='./help.php'">
        <svg viewBox="0 0 24 24">
            <path d="M12 2a10 10 0 100 20 10 10 0 000-20z"/>
        </svg>
        Help & Support
    </div>

    <div class="section-item" onclick="window.location='./settings.php'">
        <svg viewBox="0 0 24 24">
            <path d="M12 8a4 4 0 100 8 4 4 0 000-8z"/>
        </svg>
        Settings & Privacy
    </div>

</div>

<div class="logout"
     onclick="window.location='/backend/logout.php'">
    Log Out
</div>

</body>
</html>