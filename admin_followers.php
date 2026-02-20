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

// Get user stats
$stats = $conn->query("
    SELECT 
        (SELECT COUNT(*) FROM followers) as total_follows,
        (SELECT COUNT(DISTINCT follower_id) FROM followers) as users_following,
        (SELECT COUNT(DISTINCT following_id) FROM followers) as users_followed
");
$statsData = $stats->fetch_assoc();

// Get top users by followers
$topUsers = $conn->query("
    SELECT 
        u.id,
        u.name,
        u.profile_image,
        u.is_verified,
        (SELECT COUNT(*) FROM followers WHERE following_id = u.id) as followers_count,
        (SELECT COUNT(*) FROM followers WHERE follower_id = u.id) as following_count
    FROM users u
    ORDER BY followers_count DESC
    LIMIT 20
");
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Followers Management - Admin Panel</title>
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

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-main);
            color: var(--text-main);
            min-height: 100vh;
        }

        .app-container { display: flex; min-height: 100vh; }

        /* Sidebar */
        .sidebar {
            position: fixed; left: 0; top: 0; width: 260px; height: 100vh;
            background: var(--bg-sidebar); border-right: 1px solid var(--border);
            padding: 25px 0; display: flex; flex-direction: column; z-index: 1000;
        }

        .sidebar-brand {
            padding: 0 25px 30px; border-bottom: 1px solid var(--border);
            margin-bottom: 20px;
        }

        .sidebar-brand h2 {
            display: flex; align-items: center; gap: 12px;
            font-size: 22px; font-weight: 700; color: var(--text-main);
        }

        .sidebar-brand .brand-icon {
            width: 40px; height: 40px; background: var(--primary);
            border-radius: 12px; display: flex; align-items: center;
            justify-content: center; font-size: 18px; color: white;
        }

        .sidebar-nav { flex: 1; padding: 0 15px; }

        .nav-section-title {
            font-size: 11px; text-transform: uppercase;
            letter-spacing: 1.5px; color: var(--text-muted);
            padding: 10px 15px; font-weight: 600;
        }

        .nav-item {
            display: flex; align-items: center; gap: 14px;
            padding: 14px 18px; color: var(--text-secondary);
            text-decoration: none; border-radius: var(--radius);
            margin-bottom: 6px; transition: all 0.2s ease;
            font-weight: 500;
        }

        .nav-item i { font-size: 18px; width: 24px; text-align: center; }
        .nav-item:hover { background: var(--bg-hover); color: var(--text-main); }
        .nav-item.active { background: var(--primary); color: white; }

        .sidebar-footer {
            padding: 20px 15px; border-top: 1px solid var(--border);
        }

        .logout-btn {
            display: flex; align-items: center; gap: 14px;
            padding: 14px 18px; color: var(--danger);
            text-decoration: none; border-radius: var(--radius);
            transition: all 0.2s ease; font-weight: 600;
        }

        .logout-btn:hover { background: rgba(239, 68, 68, 0.1); }

        /* Main Content */
        .main-content {
            margin-left: 260px; flex: 1; padding: 35px 40px;
            background: var(--bg-main);
        }

        .page-header {
            margin-bottom: 35px;
        }

        .header-title h1 {
            font-size: 28px; font-weight: 800;
            color: var(--text-main); margin-bottom: 5px;
        }

        .header-title p {
            color: var(--text-muted); font-size: 14px;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px; margin-bottom: 30px;
        }

        .stat-card {
            background: var(--bg-card); border: 1px solid var(--border);
            border-radius: var(--radius); padding: 25px;
            box-shadow: var(--shadow);
        }

        .stat-icon {
            width: 50px; height: 50px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px; margin-bottom: 15px;
        }

        .stat-icon.total { background: rgba(24, 119, 242, 0.15); color: var(--primary); }
        .stat-icon.following { background: rgba(66, 183, 42, 0.15); color: var(--success); }
        .stat-icon.followed { background: rgba(245, 158, 11, 0.15); color: var(--warning); }

        .stat-value {
            font-size: 32px; font-weight: 800;
            color: var(--text-main); margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px; color: var(--text-muted);
            font-weight: 500;
        }

        /* Users Table */
        .users-card {
            background: var(--bg-card); border: 1px solid var(--border);
            border-radius: var(--radius); padding: 25px;
            box-shadow: var(--shadow);
        }

        .card-header {
            display: flex; justify-content: space-between;
            align-items: center; margin-bottom: 20px;
        }

        .card-title {
            font-size: 20px; font-weight: 700;
        }

        table {
            width: 100%; border-collapse: collapse;
        }

        th {
            background: var(--bg-hover); padding: 12px;
            text-align: left; font-weight: 600;
            font-size: 13px; color: var(--text-secondary);
            border-bottom: 2px solid var(--border);
        }

        td {
            padding: 15px 12px; border-bottom: 1px solid var(--border);
        }

        .user-cell {
            display: flex; align-items: center; gap: 12px;
        }

        .user-avatar {
            width: 40px; height: 40px; border-radius: 50%;
            object-fit: cover;
        }

        .user-name {
            font-weight: 600; display: flex;
            align-items: center; gap: 6px;
        }

        .verified-badge {
            color: var(--primary); font-size: 14px;
        }

        .btn-boost {
            padding: 8px 16px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; border: none; border-radius: 6px;
            cursor: pointer; font-weight: 600; font-size: 13px;
            transition: all 0.2s ease;
        }

        .btn-boost:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-boost:disabled {
            opacity: 0.6; cursor: not-allowed;
        }
    </style>
</head>
<body>

<div class="app-container">
    <!-- Sidebar -->
    <aside class="sidebar">
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
            
            <a href="/admin_dashboard.php" class="nav-item">
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

            <a href="/admin_followers.php" class="nav-item active">
                <i class="fas fa-user-friends"></i>
                Followers Management
            </a>

            <a href="/admin_logs.php" class="nav-item">
                <i class="fas fa-clock-rotate-left"></i>
                Activity Logs
            </a>

            <a href="/admin/tickets.php" class="nav-item">
                <i class="fas fa-headset"></i>
                Support Tickets
            </a>

            <?php if($_SESSION['role'] ?? '' == 'cofounder'): ?>
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
        <div class="page-header">
            <div class="header-title">
                <h1>Followers Management</h1>
                <p>Manage and boost user followers</p>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon total">
                    <i class="fas fa-heart"></i>
                </div>
                <div class="stat-value"><?= formatCount($statsData['total_follows']) ?></div>
                <div class="stat-label">Total Follow Relationships</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon following">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div class="stat-value"><?= formatCount($statsData['users_following']) ?></div>
                <div class="stat-label">Users Following Others</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon followed">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-value"><?= formatCount($statsData['users_followed']) ?></div>
                <div class="stat-label">Users Being Followed</div>
            </div>
        </div>

        <!-- Top Users Table -->
        <div class="users-card">
            <div class="card-header">
                <h2 class="card-title">Top Users by Followers</h2>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>User</th>
                        <th>Followers</th>
                        <th>Following</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $rank = 1;
                    while($user = $topUsers->fetch_assoc()): 
                        $userImg = $user['profile_image'] ? "/uploads/" . htmlspecialchars($user['profile_image']) : "/assets/zuckuser.png";
                    ?>
                    <tr>
                        <td><strong>#<?= $rank ?></strong></td>
                        <td>
                            <div class="user-cell">
                                <img src="<?= $userImg ?>" class="user-avatar" onerror="this.src='/assets/zuckuser.png';">
                                <div>
                                    <div class="user-name">
                                        <?= htmlspecialchars($user['name']) ?>
                                        <?php if($user['is_verified']): ?>
                                            <i class="fas fa-check-circle verified-badge"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div style="font-size: 12px; color: var(--text-muted);">ID: <?= $user['id'] ?></div>
                                </div>
                            </div>
                        </td>
                        <td><strong><?= formatCount($user['followers_count']) ?></strong></td>
                        <td><strong><?= formatCount($user['following_count']) ?></strong></td>
                        <td>
                            <button class="btn-boost" onclick="boostUser(<?= $user['id'] ?>, '<?= htmlspecialchars($user['name']) ?>')">
                                <i class="fas fa-rocket"></i> Boost +1K
                            </button>
                        </td>
                    </tr>
                    <?php 
                    $rank++;
                    endwhile; 
                    ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<script>
function boostUser(userId, userName) {
    if (!confirm(`🚀 Add 1000 followers to ${userName}?`)) {
        return;
    }
    
    const btn = event.target;
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Boosting...';
    
    fetch('/backend/admin/boost_user_followers.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `user_id=${userId}&count=1000`
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        
        if (data.status === 'success') {
            alert(`✅ Success!\n\nAdded: ${data.added} followers\nTotal: ${data.total}`);
            location.reload();
        } else {
            alert("Error: " + (data.message || "Unknown error"));
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        alert("Error boosting followers");
    });
}
</script>

</body>
</html>
