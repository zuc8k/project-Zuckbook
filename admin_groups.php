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

function getCount($conn,$where="1=1"){
    $result = $conn->query("SELECT COUNT(*) as total FROM groups WHERE $where");
    return $result->fetch_assoc()['total'] ?? 0;
}

$totalGroups   = getCount($conn,"1=1");
$verifiedGroups= getCount($conn,"verification_status='verified'");
$pendingGroups = getCount($conn,"verification_status='pending'");

$search = $_GET['search'] ?? '';
$searchSql = "";

if(!empty($search)){
    $searchSql = "WHERE name LIKE ?";
    $stmt = $conn->prepare("SELECT * FROM groups $searchSql ORDER BY id DESC");
    $like = "%$search%";
    $stmt->bind_param("s",$like);
    $stmt->execute();
    $groups = $stmt->get_result();
}else{
    $groups = $conn->query("SELECT * FROM groups ORDER BY id DESC");
}

if(empty($_SESSION['csrf'])){
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Group Management</title>
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
            background: var(--bg-dark);
            color: var(--text-main);
            min-height: 100vh;
        }

        /* Sidebar */
        .app-container { display: flex; min-height: 100vh; }
        .sidebar {
            position: fixed; left: 0; top: 0; width: 260px; height: 100vh;
            background: var(--bg-sidebar); border-right: 1px solid var(--border);
            padding: 25px 0; display: flex; flex-direction: column; z-index: 1000;
            box-shadow: var(--shadow);
        }
        .sidebar-brand { padding: 0 25px 30px; border-bottom: 1px solid var(--border); margin-bottom: 20px; }
        .sidebar-brand h2 { display: flex; align-items: center; gap: 12px; font-size: 22px; font-weight: 700; color: var(--text-main); }
        .sidebar-brand .brand-icon { width: 40px; height: 40px; background: var(--primary); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 18px; color: white; }
        .sidebar-nav { flex: 1; padding: 0 15px; }
        .nav-section-title { font-size: 11px; text-transform: uppercase; letter-spacing: 1.5px; color: var(--text-muted); padding: 10px 15px; font-weight: 600; }
        .nav-item { display: flex; align-items: center; gap: 14px; padding: 14px 18px; color: var(--text-secondary); text-decoration: none; border-radius: var(--radius); margin-bottom: 6px; transition: all 0.2s ease; font-weight: 500; }
        .nav-item i { font-size: 18px; width: 24px; text-align: center; }
        .nav-item:hover { background: var(--bg-hover); color: var(--text-main); }
        .nav-item.active { background: var(--primary); color: white; }
        .sidebar-footer { padding: 20px 15px; border-top: 1px solid var(--border); }
        .logout-btn { display: flex; align-items: center; gap: 14px; padding: 14px 18px; color: var(--danger); text-decoration: none; border-radius: 12px; transition: all 0.3s ease; font-weight: 600; }
        .logout-btn:hover { background: rgba(239, 68, 68, 0.1); }

        /* Main Content */
        .main-content {
            margin-left: 260px; flex: 1; padding: 35px 40px;
            background: var(--bg-main);
        }

        /* Header */
        .page-header { margin-bottom: 35px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; }
        .header-title h1 { font-size: 28px; font-weight: 800; color: var(--text-main); margin-bottom: 5px; }
        .header-title p { color: var(--text-muted); font-size: 14px; }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
            margin-bottom: 35px;
        }
        .stat-card {
            background: var(--bg-card); border: 1px solid var(--border);
            border-radius: var(--radius); padding: 24px;
            transition: all 0.2s ease; box-shadow: var(--shadow);
        }
        .stat-card:hover { box-shadow: var(--shadow-hover); }
        .stat-icon {
            width: 48px; height: 48px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; margin-bottom: 15px;
        }
        .icon-total { background: rgba(99, 102, 241, 0.15); color: var(--primary-light); }
        .icon-verified { background: rgba(16, 185, 129, 0.15); color: var(--success); }
        .icon-pending { background: rgba(245, 158, 11, 0.15); color: var(--warning); }
        
        .stat-value { font-size: 28px; font-weight: 800; margin-bottom: 5px; }
        .stat-label { font-size: 13px; color: var(--text-muted); font-weight: 500; }

        /* Search */
        .search-card {
            background: var(--bg-card); border: 1px solid var(--border);
            border-radius: var(--radius); padding: 20px 25px; margin-bottom: 30px;
            display: flex; align-items: center; gap: 15px; box-shadow: var(--shadow);
        }
        .search-card i { font-size: 20px; color: var(--text-muted); }
        .search-input {
            flex: 1; background: transparent; border: none;
            font-size: 16px; color: var(--text-main); outline: none;
        }
        .search-input::placeholder { color: var(--text-muted); }
        .search-btn {
            padding: 10px 25px; background: var(--primary);
            border: none; border-radius: var(--radius); color: white; font-weight: 600;
            cursor: pointer; transition: all 0.2s ease;
        }
        .search-btn:hover { background: var(--primary-light); }
        }
        .search-btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(99, 102, 241, 0.4); }

        /* Group List */
        .groups-container {
            display: grid; gap: 15px;
        }

        .group-card {
            background: var(--bg-card); border: 1px solid var(--border);
            border-radius: var(--radius); padding: 20px 25px;
            display: flex; justify-content: space-between; align-items: center;
            transition: all 0.2s ease; box-shadow: var(--shadow);
        }
        .group-card:hover { box-shadow: var(--shadow-hover); }

        .group-info { display: flex; align-items: center; gap: 20px; }
        .group-avatar {
            width: 50px; height: 50px; border-radius: 12px;
            background: var(--primary); color: white;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; font-weight: 700;
            box-shadow: var(--shadow);
        }
        }
        .group-details h3 { font-size: 17px; font-weight: 700; margin-bottom: 6px; }
        
        /* Badges */
        .badge {
            padding: 5px 12px; border-radius: 6px;
            font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;
        }
        .badge-verified { background: rgba(16, 185, 129, 0.15); color: var(--success); border: 1px solid rgba(16, 185, 129, 0.3); }
        .badge-pending { background: rgba(245, 158, 11, 0.15); color: var(--warning); border: 1px solid rgba(245, 158, 11, 0.3); }
        .badge-normal { background: rgba(148, 163, 184, 0.1); color: var(--text-muted); border: 1px solid rgba(148, 163, 184, 0.2); }
        .badge-rejected { background: rgba(239, 68, 68, 0.15); color: var(--danger); border: 1px solid rgba(239, 68, 68, 0.3); }

        /* Actions */
        .card-actions { display: flex; gap: 10px; }
        .card-actions form { display: contents; }
        
        .btn-action {
            padding: 8px 16px; border-radius: 8px; border: none;
            font-size: 13px; font-weight: 600; cursor: pointer;
            transition: all 0.2s ease; color: white;
            display: flex; align-items: center; gap: 6px;
        }
        
        .btn-verify { background: rgba(16, 185, 129, 0.15); color: var(--success); border: 1px solid rgba(16, 185, 129, 0.2); }
        .btn-verify:hover { background: var(--success); color: white; }
        
        .btn-reject { background: rgba(245, 158, 11, 0.15); color: var(--warning); border: 1px solid rgba(245, 158, 11, 0.2); }
        .btn-reject:hover { background: var(--warning); color: #000; }
        
        .btn-delete { background: rgba(239, 68, 68, 0.15); color: var(--danger); border: 1px solid rgba(239, 68, 68, 0.2); }
        .btn-delete:hover { background: var(--danger); color: white; }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar { display: none; } /* Hidden for mobile view in snippet */
            .main-content { margin-left: 0; }
        }
        @media (max-width: 768px) {
            .group-card { flex-direction: column; align-items: flex-start; gap: 15px; }
            .card-actions { width: 100%; justify-content: flex-end; }
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
            <a href="/admin_groups.php" class="nav-item active">
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
                <h1>Group Management</h1>
                <p>Monitor and verify community groups</p>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon icon-total">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="stat-value"><?= formatCount($totalGroups) ?></div>
                <div class="stat-label">Total Groups</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon icon-verified">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-value"><?= formatCount($verifiedGroups) ?></div>
                <div class="stat-label">Verified</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon icon-pending">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="stat-value"><?= formatCount($pendingGroups) ?></div>
                <div class="stat-label">Pending Review</div>
            </div>
        </div>

        <!-- Search -->
        <form method="GET" class="search-card">
            <i class="fas fa-search"></i>
            <input type="text" name="search" class="search-input" placeholder="Search by group name..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="search-btn">
                Filter
            </button>
        </form>

        <!-- Groups List -->
        <div class="groups-container">
            <?php if($groups && $groups->num_rows > 0): ?>
                <?php while($row = $groups->fetch_assoc()): 
                    $status = $row['verification_status'];
                    $badgeClass = 'normal';
                    $badgeText = 'Normal';
                    
                    if($status == 'verified') { $badgeClass = 'verified'; $badgeText = 'Verified'; }
                    elseif($status == 'pending') { $badgeClass = 'pending'; $badgeText = 'Pending'; }
                    elseif($status == 'rejected') { $badgeClass = 'rejected'; $badgeText = 'Rejected'; }
                    
                    $initial = strtoupper(substr($row['name'], 0, 1));
                ?>
                <div class="group-card">
                    <div class="group-info">
                        <div class="group-avatar">
                            <?= $initial ?>
                        </div>
                        <div class="group-details">
                            <h3><?= htmlspecialchars($row['name']) ?></h3>
                            <span class="badge badge-<?= $badgeClass ?>">
                                <?php 
                                if($badgeClass == 'verified') echo '<i class="fas fa-shield-check" style="margin-right:4px;"></i>';
                                if($badgeClass == 'pending') echo '<i class="fas fa-clock" style="margin-right:4px;"></i>';
                                if($badgeClass == 'rejected') echo '<i class="fas fa-times-circle" style="margin-right:4px;"></i>';
                                ?>
                                <?= $badgeText ?>
                            </span>
                        </div>
                    </div>

                    <div class="card-actions">
                        <form method="POST" action="/backend/admin/verify_group.php">
                            <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
                            <input type="hidden" name="group_id" value="<?= $row['id'] ?>">
                            <button type="submit" class="btn-action btn-verify">
                                <i class="fas fa-check"></i> Verify
                            </button>
                        </form>

                        <form method="POST" action="/backend/admin/reject_group.php">
                            <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
                            <input type="hidden" name="group_id" value="<?= $row['id'] ?>">
                            <button type="submit" class="btn-action btn-reject">
                                <i class="fas fa-ban"></i> Reject
                            </button>
                        </form>

                        <form method="POST" action="/backend/admin/delete_group.php" onsubmit="return confirm('Delete this group?')">
                            <input type="hidden" name="csrf" value="<?= $_SESSION['csrf'] ?>">
                            <input type="hidden" name="group_id" value="<?= $row['id'] ?>">
                            <button type="submit" class="btn-action btn-delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 60px 20px; background: var(--bg-card); border-radius: var(--radius); border: 1px solid var(--border);">
                    <i class="fas fa-layer-group" style="font-size: 64px; color: var(--text-muted); margin-bottom: 20px; opacity: 0.3;"></i>
                    <h3 style="margin-bottom: 10px; color: var(--text-main);">No Groups Found</h3>
                    <p style="color: var(--text-muted); margin-bottom: 20px;">There are no groups in the system yet.</p>
                    <a href="/add_sample_data.php" style="display: inline-block; padding: 12px 24px; background: var(--gradient-primary); color: white; text-decoration: none; border-radius: 10px; font-weight: 600;">
                        <i class="fas fa-plus"></i> Add Sample Data
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

</body>
</html>
```