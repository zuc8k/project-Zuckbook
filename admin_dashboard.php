<?php
session_start();
require_once __DIR__ . "/backend/config.php";
require_once __DIR__ . "/backend/middleware.php";
require_once __DIR__ . "/includes/helpers.php";

requireRole(['cofounder','mod','sup']);

if(!isset($_SESSION['user_id'])){
    header("Location: /");
    exit;
}

// Check if PIN is verified
if (!isset($_SESSION['admin_pin_verified']) || $_SESSION['admin_pin_verified'] !== true) {
    header("Location: /admin_pin_login.php");
    exit;
}

 $user_id = intval($_SESSION['user_id']);

 $stmt = $conn->prepare("SELECT id,name,coins,role FROM users WHERE id=? LIMIT 1");
 $stmt->bind_param("i",$user_id);
 $stmt->execute();
 $user = $stmt->get_result()->fetch_assoc();

if(!$user){
    session_destroy();
    exit;
}

function getCount($conn,$table,$where="1=1"){
    $result = $conn->query("SELECT COUNT(*) as total FROM $table WHERE $where");
    return $result->fetch_assoc()['total'] ?? 0;
}

 $totalUsers     = getCount($conn,"users","1=1");
 $totalGroups    = getCount($conn,"groups","1=1");
 $totalPosts     = getCount($conn,"posts","1=1");
 $onlineUsers    = getCount($conn,"users","is_online=1");
 $openTickets    = getCount($conn,"support_tickets","status='open'");
 $pendingTickets = getCount($conn,"support_tickets","status='pending'");
 $bannedUsers    = getCount($conn,"users","is_banned=1");
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ZuckBook Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-main: #f0f2f5;
            --bg-sidebar: #ffffff;
            --bg-card: #ffffff;
            --bg-hover: #f2f3f5;
            --primary: #1877f2;
            --primary-light: #4a90e2;
            --accent: #42b72a;
            --success: #42b72a;
            --warning: #f59e0b;
            --danger: #ef4444;
            --text-main: #050505;
            --text-secondary: #65676b;
            --text-muted: #8a8d91;
            --border: #e4e6eb;
            --shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            --shadow-hover: 0 2px 4px rgba(0, 0, 0, 0.15);
            --radius: 8px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-main);
            color: var(--text-main);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Layout */
        .app-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 260px;
            height: 100vh;
            background: var(--bg-sidebar);
            border-right: 1px solid var(--border);
            padding: 25px 0;
            display: flex;
            flex-direction: column;
            z-index: 1000;
            transition: transform 0.3s ease;
        }

        .sidebar-brand {
            padding: 0 25px 30px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 20px;
        }

        .sidebar-brand h2 {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 22px;
            font-weight: 700;
            color: var(--text-main);
        }

        .sidebar-brand .brand-icon {
            width: 40px;
            height: 40px;
            background: var(--primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .sidebar-nav {
            flex: 1;
            padding: 0 15px;
        }

        .nav-section-title {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--text-muted);
            padding: 10px 15px;
            font-weight: 600;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 18px;
            color: var(--text-muted);
            text-decoration: none;
            border-radius: 12px;
            margin-bottom: 6px;
            transition: all 0.3s ease;
            font-weight: 500;
            position: relative;
        }

        .nav-item i {
            font-size: 18px;
            width: 24px;
            text-align: center;
        }

        .nav-item:hover {
            background: var(--bg-hover);
            color: var(--text-main);
        }

        .nav-item.active {
            background: var(--primary);
            color: white;
        }

        .nav-item.active i {
            color: white;
        }

        .sidebar-footer {
            padding: 20px 15px;
            border-top: 1px solid var(--border);
        }

        .logout-btn {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 18px;
            color: var(--danger);
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .logout-btn:hover {
            background: rgba(239, 68, 68, 0.1);
        }

        /* Main Content */
        .main-content {
            margin-left: 260px;
            flex: 1;
            padding: 35px 40px;
            min-height: 100vh;
            background: radial-gradient(circle at top left, rgba(99, 102, 241, 0.08), transparent 40%),
                        radial-gradient(circle at bottom right, rgba(6, 182, 212, 0.05), transparent 40%);
        }

        /* Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
            animation: fadeIn 0.6s ease;
        }

        .header-welcome h1 {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 6px;
            color: var(--primary);
        }
        }

        .header-welcome p {
            color: var(--text-muted);
            font-size: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .header-welcome p i {
            color: var(--warning);
        }

        .user-badge {
            padding: 12px 24px;
            border-radius: 50px;
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .badge-cofounder { background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); color: #000; }
        .badge-mod { background: var(--gradient-primary); color: #fff; }
        .badge-sup { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #fff; }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: var(--bg-card);
            border-radius: var(--radius);
            padding: 28px;
            border: 1px solid var(--border);
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            animation: fadeInUp 0.6s ease backwards;
        }

        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }
        .stat-card:nth-child(5) { animation-delay: 0.5s; }
        .stat-card:nth-child(6) { animation-delay: 0.6s; }
        .stat-card:nth-child(7) { animation-delay: 0.7s; }

        .stat-card:hover {
            box-shadow: var(--shadow-hover);
        }
            border-color: var(--primary);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--gradient-primary);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .stat-card:hover::before {
            opacity: 1;
        }

        .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
        }

        .stat-icon.users { background: rgba(99, 102, 241, 0.15); color: var(--primary-light); }
        .stat-icon.online { background: rgba(16, 185, 129, 0.15); color: var(--success); }
        .stat-icon.banned { background: rgba(239, 68, 68, 0.15); color: var(--danger); }
        .stat-icon.groups { background: rgba(6, 182, 212, 0.15); color: var(--accent); }
        .stat-icon.posts { background: rgba(139, 92, 246, 0.15); color: #a78bfa; }
        .stat-icon.tickets { background: rgba(245, 158, 11, 0.15); color: var(--warning); }

        .stat-value {
            font-size: 36px;
            font-weight: 800;
            color: var(--text-main);
            line-height: 1;
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 14px;
            color: var(--text-muted);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .stat-trend {
            font-size: 12px;
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: 600;
        }

        .trend-up { background: rgba(16, 185, 129, 0.1); color: var(--success); }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Mobile Header */
        .mobile-header {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 60px;
            background: white;
            border-bottom: 1px solid var(--border);
            padding: 0 16px;
            align-items: center;
            justify-content: space-between;
            z-index: 999;
            box-shadow: var(--shadow);
        }

        .mobile-header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .menu-toggle {
            width: 40px;
            height: 40px;
            border: none;
            background: var(--bg-hover);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 20px;
            color: var(--text-main);
            transition: all 0.2s ease;
        }

        .menu-toggle:active {
            transform: scale(0.95);
            background: var(--border);
        }

        .mobile-brand {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .mobile-brand i {
            font-size: 20px;
        }

        .mobile-header-right {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .mobile-icon-btn {
            width: 40px;
            height: 40px;
            border: none;
            background: var(--bg-hover);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 18px;
            color: var(--text-main);
            text-decoration: none;
        }

        .sidebar-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 998;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .sidebar-overlay.active {
            display: block;
            opacity: 1;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .mobile-header {
                display: flex;
            }

            .sidebar {
                transform: translateX(-100%);
                top: 0;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                padding-top: 80px;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 80px 16px 20px;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
                margin-bottom: 24px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            
            .header-welcome h1 {
                font-size: 24px;
            }

            .header-welcome p {
                font-size: 14px;
            }

            .user-badge {
                padding: 8px 16px;
                font-size: 11px;
            }

            .stat-card {
                padding: 16px;
            }

            .stat-value {
                font-size: 28px;
            }

            .stat-label {
                font-size: 13px;
            }

            .quick-actions {
                grid-template-columns: 1fr;
                gap: 12px;
            }

            .action-card {
                padding: 16px;
            }
        }

        @media (max-width: 480px) {
            .mobile-header {
                height: 56px;
                padding: 0 12px;
            }

            .mobile-brand {
                font-size: 16px;
            }

            .menu-toggle,
            .mobile-icon-btn {
                width: 36px;
                height: 36px;
                font-size: 16px;
            }

            .main-content {
                padding: 68px 12px 16px;
            }

            .header-welcome h1 {
                font-size: 20px;
            }

            .stat-card {
                padding: 14px;
            }

            .stat-icon {
                width: 40px;
                height: 40px;
                font-size: 18px;
            }

            .stat-value {
                font-size: 24px;
            }
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--bg-dark);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary);
        }
    </style>
</head>
<body>

<!-- Mobile Header -->
<div class="mobile-header">
    <div class="mobile-header-left">
        <button class="menu-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <div class="mobile-brand">
            <i class="fas fa-shield-halved"></i>
            Admin
        </div>
    </div>
    <div class="mobile-header-right">
        <a href="/home.php" class="mobile-icon-btn" title="Home">
            <i class="fas fa-home"></i>
        </a>
        <a href="/backend/logout.php" class="mobile-icon-btn" title="Logout">
            <i class="fas fa-sign-out-alt"></i>
        </a>
    </div>
</div>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<div class="app-container">
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <h2>
                <span class="brand-icon"><i class="fas fa-shield-halved"></i></span>
                Admin Panel
            </h2>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section-title">Main Menu</div>
            
            <a href="/home.php" class="nav-item">
                <i class="fas fa-home"></i>
                Home
            </a>
            
            <a href="/admin_dashboard.php" class="nav-item active">
                <i class="fas fa-chart-pie"></i>
                Dashboard
            </a>

            <a href="/admin_users.php" class="nav-item">
                <i class="fas fa-users"></i>
                Users Management
            </a>

            <a href="/admin_groups.php" class="nav-item">
                <i class="fas fa-layer-group"></i>
                Groups
            </a>

            <a href="/admin_logs.php" class="nav-item">
                <i class="fas fa-clock-rotate-left"></i>
                Activity Logs
            </a>

            <a href="/admin/tickets.php" class="nav-item">
                <i class="fas fa-headset"></i>
                Support Tickets
            </a>

            <?php if($user['role'] == 'cofounder'): ?>
            <div class="nav-section-title" style="margin-top: 25px;">Administration</div>
            <a href="/admin_manage.php" class="nav-item">
                <i class="fas fa-user-shield"></i>
                Manage Roles
            </a>
            <?php endif; ?>
        </nav>

        <div class="sidebar-footer">
            <a href="/backend/admin_logout.php" class="logout-btn" style="margin-bottom: 10px; background: rgba(239, 68, 68, 0.1); color: #ef4444;">
                <i class="fas fa-lock"></i>
                Exit Admin Panel
            </a>
            <a href="/backend/logout.php" class="logout-btn">
                <i class="fas fa-right-from-bracket"></i>
                Logout
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <header class="page-header">
            <div class="header-welcome">
                <h1>Welcome, <?= htmlspecialchars($user['name']) ?></h1>
                <p>
                    <i class="fas fa-coins"></i>
                    Balance: <?= formatCoins($user['coins']) ?> Coins
                </p>
            </div>
            <div class="user-badge badge-<?= $user['role'] ?>">
                <i class="fas fa-gem" style="margin-right: 8px;"></i>
                <?= strtoupper($user['role']) ?>
            </div>
        </header>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon users">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-value"><?= formatCount($totalUsers) ?></div>
                <div class="stat-label">Total Users</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon online">
                    <i class="fas fa-signal"></i>
                </div>
                <div class="stat-value"><?= formatCount($onlineUsers) ?></div>
                <div class="stat-label">Online Now</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon banned">
                    <i class="fas fa-user-slash"></i>
                </div>
                <div class="stat-value"><?= formatCount($bannedUsers) ?></div>
                <div class="stat-label">Banned Accounts</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon groups">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="stat-value"><?= formatCount($totalGroups) ?></div>
                <div class="stat-label">Active Groups</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon posts">
                    <i class="fas fa-newspaper"></i>
                </div>
                <div class="stat-value"><?= formatCount($totalPosts) ?></div>
                <div class="stat-label">Total Posts</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon tickets">
                    <i class="fas fa-envelope-open-text"></i>
                </div>
                <div class="stat-value"><?= formatCount($openTickets) ?></div>
                <div class="stat-label">Open Tickets</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon users">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="stat-value"><?= formatCount($pendingTickets) ?></div>
                <div class="stat-label">Pending Tickets</div>
            </div>
        </div>
    </main>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.querySelector('.sidebar-overlay');
    
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
}

// Close sidebar when clicking on a nav item on mobile
document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('click', () => {
        if (window.innerWidth <= 1024) {
            toggleSidebar();
        }
    });
});

// Close sidebar on window resize if desktop
window.addEventListener('resize', () => {
    if (window.innerWidth > 1024) {
        document.getElementById('sidebar').classList.remove('active');
        document.querySelector('.sidebar-overlay').classList.remove('active');
    }
});
</script>

</body>
</html>