<?php
session_start();
require_once __DIR__ . "/backend/config.php";
require_once __DIR__ . "/backend/middleware.php";
require_once __DIR__ . "/backend/ensure_admin_logs.php";

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

ensureAdminLogsTable($conn);

$actionFilter = $_GET['action'] ?? '';
$adminFilter  = $_GET['admin'] ?? '';
$page         = isset($_GET['page']) ? max(1,intval($_GET['page'])) : 1;
$limit        = 15;
$offset       = ($page - 1) * $limit;

$where = "1=1";
$params = [];
$types  = "";

if(!empty($actionFilter)){
    $where .= " AND al.action=?";
    $params[] = $actionFilter;
    $types .= "s";
}

if(!empty($adminFilter)){
    $where .= " AND u.name LIKE ?";
    $params[] = "%$adminFilter%";
    $types .= "s";
}

$countSql = "
SELECT COUNT(*) as total
FROM admin_logs al
JOIN users u ON u.id = al.admin_id
WHERE $where
";

$stmt = $conn->prepare($countSql);
if(!empty($params)){
    $stmt->bind_param($types,...$params);
}

// Check if admin_logs table exists
$checkTable = $conn->query("SHOW TABLES LIKE 'admin_logs'");
if ($checkTable && $checkTable->num_rows > 0) {
    $stmt->execute();
    $totalLogs = $stmt->get_result()->fetch_assoc()['total'];
} else {
    $totalLogs = 0;
}
$totalPages = ceil($totalLogs / $limit);

$sql = "
SELECT al.*, u.name AS admin_name, u.role
FROM admin_logs al
JOIN users u ON u.id = al.admin_id
WHERE $where
ORDER BY al.created_at DESC
LIMIT $limit OFFSET $offset
";

$stmt = $conn->prepare($sql);
if(!empty($params)){
    $stmt->bind_param($types,...$params);
}

// Check if admin_logs table exists
$checkTable = $conn->query("SHOW TABLES LIKE 'admin_logs'");
if ($checkTable && $checkTable->num_rows > 0) {
    $stmt->execute();
    $logs = $stmt->get_result();
} else {
    $logs = false;
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Activity Logs</title>
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
        .page-header { margin-bottom: 35px; }
        .header-title h1 { font-size: 28px; font-weight: 800; color: var(--text-main); margin-bottom: 5px; }
        .header-title p { color: var(--text-muted); font-size: 14px; }

        /* Filter Card */
        .filter-card {
            background: var(--bg-card); border: 1px solid var(--border);
            border-radius: var(--radius); padding: 20px 25px; margin-bottom: 30px;
            display: flex; align-items: center; gap: 15px; box-shadow: var(--shadow);
            flex-wrap: wrap;
        }
        .input-group {
            display: flex; align-items: center; gap: 10px;
            background: var(--bg-dark); padding: 0 15px; border-radius: 10px; border: 1px solid var(--border);
            flex: 1; min-width: 200px;
        }
        .input-group i { color: var(--text-muted); }
        .filter-input {
            background: transparent; border: none;
            font-size: 14px; color: var(--text-main); outline: none;
            padding: 12px 0; width: 100%;
        }
        .filter-btn {
            padding: 12px 25px; background: var(--primary);
            border: none; border-radius: var(--radius); color: white; font-weight: 600;
            cursor: pointer; transition: all 0.2s ease;
        }
        .filter-btn:hover { background: var(--primary-light); }
        }
        .filter-btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(99, 102, 241, 0.4); }

        /* Timeline Style */
        .timeline {
            position: relative;
            padding-right: 20px;
        }
        
        /* The vertical line */
        .timeline::before {
            content: '';
            position: absolute;
            right: 24px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--border);
        }

        .timeline-item {
            position: relative;
            margin-bottom: 25px;
            padding-right: 60px; /* Space for the dot and line */
        }

        /* The dot */
        .timeline-dot {
            position: absolute;
            right: 16px;
            top: 25px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: var(--bg-dark);
            border: 3px solid var(--primary);
            z-index: 1;
        }

        .timeline-content {
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 20px;
            transition: all 0.3s ease;
        }

        .timeline-content:hover {
            border-color: rgba(99, 102, 241, 0.5);
            transform: translateX(-5px);
        }

        .log-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--border);
        }

        .admin-info {
            display: flex; align-items: center; gap: 12px;
        }
        .admin-avatar {
            width: 36px; height: 36px; border-radius: 8px;
            background: var(--primary); color: white;
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 14px;
            box-shadow: var(--shadow);
        }
        }
        .admin-name { font-weight: 700; font-size: 15px; }
        
        .role-badge {
            padding: 4px 10px; border-radius: 6px;
            font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;
        }
        .role-cofounder { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
        .role-mod { background: rgba(99, 102, 241, 0.15); color: #818cf8; border: 1px solid rgba(99, 102, 241, 0.3); }
        .role-sup { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }

        .log-time {
            font-size: 12px; color: var(--text-muted); font-weight: 500;
            display: flex; align-items: center; gap: 6px;
        }

        .log-body {
            margin-bottom: 15px;
        }

        .action-title {
            font-size: 16px; font-weight: 700; color: var(--text-main); margin-bottom: 5px;
            display: flex; align-items: center; gap: 10px;
        }
        .action-icon {
            width: 24px; height: 24px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 12px;
        }
        .icon-ban { background: rgba(239, 68, 68, 0.15); color: var(--danger); }
        .icon-verify { background: rgba(16, 185, 129, 0.15); color: var(--success); }
        .icon-delete { background: rgba(245, 158, 11, 0.15); color: var(--warning); }
        .icon-edit { background: rgba(99, 102, 241, 0.15); color: var(--primary-light); }

        .log-desc {
            color: var(--text-muted); font-size: 14px; line-height: 1.5;
        }

        .log-footer {
            display: flex; justify-content: space-between; align-items: center;
            padding-top: 12px; border-top: 1px solid var(--border);
        }
        
        .log-ip {
            font-size: 12px; color: var(--text-muted); font-family: monospace;
            background: var(--bg-dark); padding: 4px 8px; border-radius: 6px;
        }

        /* Empty State */
        .empty-state {
            text-align: center; padding: 60px 20px; color: var(--text-muted);
        }
        .empty-icon { font-size: 50px; margin-bottom: 20px; opacity: 0.5; }

        /* Pagination */
        .pagination {
            margin-top: 40px; display: flex; justify-content: center;
            gap: 10px; flex-wrap: wrap;
        }
        .page-link {
            width: 40px; height: 40px; display: flex; align-items: center;
            justify-content: center; border-radius: 10px; background: var(--bg-card);
            color: var(--text-muted); text-decoration: none; font-weight: 600;
            border: 1px solid var(--border); transition: all 0.2s ease;
        }
        .page-link:hover { border-color: var(--primary); color: var(--text-main); }
        .page-link.active { background: var(--primary); color: white; border-color: var(--primary); }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
        }
        @media (max-width: 768px) {
            .timeline::before { right: 10px; }
            .timeline-item { padding-right: 35px; }
            .timeline-dot { right: 2px; }
            .filter-card { flex-direction: column; align-items: stretch; }
            .log-header { flex-direction: column; align-items: flex-start; gap: 10px; }
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
            <a href="/admin_logs.php" class="nav-item active">
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
                <h1>Activity Logs</h1>
                <p>Track all administrative actions and system events</p>
            </div>
        </div>

        <!-- Filters -->
        <form method="GET" class="filter-card">
            <div class="input-group">
                <i class="fas fa-user-shield"></i>
                <input type="text" name="admin" class="filter-input" placeholder="Search Admin Name..." value="<?= htmlspecialchars($adminFilter) ?>">
            </div>
            
            <div class="input-group" style="max-width: 250px;">
                <i class="fas fa-filter"></i>
                <select name="action" class="filter-input" style="cursor: pointer;">
                    <option value="">All Actions</option>
                    <option value="verify_group" <?= $actionFilter=='verify_group'?'selected':'' ?>>Verify Group</option>
                    <option value="ban_user" <?= $actionFilter=='ban_user'?'selected':'' ?>>Ban User</option>
                    <option value="delete_post" <?= $actionFilter=='delete_post'?'selected':'' ?>>Delete Post</option>
                    <!-- Add more options as needed -->
                </select>
            </div>

            <button type="submit" class="filter-btn">
                <i class="fas fa-search"></i> Search
            </button>
        </form>

        <!-- Timeline Logs -->
        <?php if($logs && $logs->num_rows > 0): ?>
        <div class="timeline">
            <?php while($row = $logs->fetch_assoc()): 
                $initial = strtoupper(substr($row['admin_name'], 0, 1));
                
                // Determine icon for action
                $iconClass = 'icon-edit';
                $iconName = 'fas fa-cog';
                if(strpos($row['action'], 'ban') !== false) { $iconClass = 'icon-ban'; $iconName = 'fas fa-gavel'; }
                if(strpos($row['action'], 'verify') !== false) { $iconClass = 'icon-verify'; $iconName = 'fas fa-check-circle'; }
                if(strpos($row['action'], 'delete') !== false) { $iconClass = 'icon-delete'; $iconName = 'fas fa-trash-alt'; }
            ?>
            <div class="timeline-item">
                <div class="timeline-dot"></div>
                <div class="timeline-content">
                    <div class="log-header">
                        <div class="admin-info">
                            <div class="admin-avatar"><?= $initial ?></div>
                            <div>
                                <div class="admin-name"><?= htmlspecialchars($row['admin_name']) ?></div>
                                <span class="role-badge role-<?= $row['role'] ?>"><?= $row['role'] ?></span>
                            </div>
                        </div>
                        <div class="log-time">
                            <i class="fas fa-clock"></i>
                            <?= date('M d, Y - H:i', strtotime($row['created_at'])) ?>
                        </div>
                    </div>

                    <div class="log-body">
                        <div class="action-title">
                            <span class="action-icon <?= $iconClass ?>"><i class="<?= $iconName ?>"></i></span>
                            Action: <?= htmlspecialchars(ucwords(str_replace('_', ' ', $row['action']))) ?>
                        </div>
                        <div class="log-desc">
                            <?= htmlspecialchars($row['description']) ?>
                        </div>
                    </div>

                    <?php if(!empty($row['ip_address'])): ?>
                    <div class="log-footer">
                        <div class="log-ip">
                            <i class="fas fa-globe" style="margin-left: 5px; color: var(--text-muted);"></i>
                            IP: <?= htmlspecialchars($row['ip_address']) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon"><i class="fas fa-inbox"></i></div>
            <h3>No Logs Found</h3>
            <p>There are no administrative actions recorded yet.</p>
        </div>
        <?php endif; ?>

        <!-- Pagination -->
        <?php if($totalPages > 1): ?>
        <div class="pagination">
            <?php for($i=1;$i<=$totalPages;$i++): ?>
                <a href="?page=<?= $i ?>&admin=<?= urlencode($adminFilter) ?>&action=<?= urlencode($actionFilter) ?>"
                   class="page-link <?= $i==$page?'active':'' ?>">
                   <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </main>
</div>

</body>
</html>
```