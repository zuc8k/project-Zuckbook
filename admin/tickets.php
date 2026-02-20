<?php
session_start();
require_once __DIR__ . "/../backend/config.php";
require_once __DIR__ . "/../backend/middleware.php";

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

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

$countResult = $conn->query("SELECT COUNT(*) as total FROM support_tickets");
$totalTickets = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalTickets / $limit);

// Get ticket counts by status
$openCount = $conn->query("SELECT COUNT(*) as count FROM support_tickets WHERE status='open'")->fetch_assoc()['count'];
$claimedCount = $conn->query("SELECT COUNT(*) as count FROM support_tickets WHERE status='claimed'")->fetch_assoc()['count'];
$doneCount = $conn->query("SELECT COUNT(*) as count FROM support_tickets WHERE status='done'")->fetch_assoc()['count'];
$refusedCount = $conn->query("SELECT COUNT(*) as count FROM support_tickets WHERE status='refused'")->fetch_assoc()['count'];

$res = $conn->query("SELECT * FROM support_tickets ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Support Tickets</title>
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
        .logout-btn { display: flex; align-items: center; gap: 14px; padding: 14px 18px; color: var(--danger); text-decoration: none; border-radius: var(--radius); transition: all 0.2s ease; font-weight: 600; }
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
        
        /* Stats Summary */
        .stats-row {
            display: flex; gap: 15px; margin-bottom: 30px; flex-wrap: wrap;
        }
        .mini-stat {
            background: var(--bg-card); border: 1px solid var(--border);
            padding: 15px 25px; border-radius: var(--radius);
            display: flex; align-items: center; gap: 15px;
            box-shadow: var(--shadow);
        }
        .mini-stat i { font-size: 24px; }
        .mini-stat .value { font-size: 20px; font-weight: 800; color: var(--text-main); }

        /* Tickets List */
        .tickets-container {
            display: flex; flex-direction: column; gap: 15px;
        }

        .ticket-card {
            background: var(--bg-card); border: 1px solid var(--border);
            border-radius: var(--radius); padding: 20px;
            display: flex; justify-content: space-between; align-items: center;
            transition: all 0.2s ease; box-shadow: var(--shadow);
        }
        .ticket-card:hover { box-shadow: var(--shadow-hover); }

        .ticket-main { display: flex; align-items: center; gap: 20px; flex: 1; }
        
        .ticket-avatar {
            width: 48px; height: 48px; border-radius: 50%;
            background: var(--primary); color: white;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; font-weight: 700;
        }

        .ticket-info h3 { font-size: 16px; font-weight: 700; margin-bottom: 6px; color: var(--text-main); }
        .ticket-meta {
            display: flex; align-items: center; gap: 15px;
            color: var(--text-muted); font-size: 13px;
        }
        .meta-item { display: flex; align-items: center; gap: 6px; }

        /* Status Badges */
        .status-badge {
            padding: 6px 14px; border-radius: 20px;
            font-size: 12px; font-weight: 600; text-transform: capitalize;
            display: inline-flex; align-items: center; gap: 6px;
        }
        
        .status-open { 
            background: rgba(66, 183, 42, 0.1); color: var(--success); 
        }
        .status-claimed { 
            background: rgba(24, 119, 242, 0.1); color: var(--primary); 
        }
        .status-refused { 
            background: rgba(239, 68, 68, 0.1); color: var(--danger); 
        }
        .status-done { 
            background: rgba(138, 141, 145, 0.1); color: var(--text-muted); 
        }

        /* Action Button */
        .ticket-actions a {
            padding: 10px 20px; background: var(--bg-hover);
            border: 1px solid var(--border); color: var(--text-main);
            border-radius: var(--radius); font-weight: 600; text-decoration: none;
            transition: all 0.2s ease;
            display: flex; align-items: center; gap: 8px;
        }
        .ticket-actions a:hover {
            background: var(--primary); color: white;
            border-color: var(--primary);
        }

        /* Empty State */
        .empty-state {
            text-align: center; padding: 60px 20px; color: var(--text-muted);
            background: var(--bg-card); border-radius: var(--radius); 
            border: 1px solid var(--border); box-shadow: var(--shadow);
        }
        .empty-icon { font-size: 50px; margin-bottom: 20px; opacity: 0.5; }

        /* Pagination */
        .pagination {
            margin-top: 40px; display: flex; justify-content: center;
            gap: 10px; flex-wrap: wrap;
        }
        .page-link {
            width: 40px; height: 40px; display: flex; align-items: center;
            justify-content: center; border-radius: var(--radius); background: var(--bg-card);
            color: var(--text-secondary); text-decoration: none; font-weight: 600;
            border: 1px solid var(--border); transition: all 0.2s ease;
        }
        .page-link:hover { border-color: var(--primary); color: var(--text-main); background: var(--bg-hover); }
        .page-link.active { background: var(--primary); color: white; border-color: var(--primary); }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; }
        }
        @media (max-width: 768px) {
            .ticket-card { flex-direction: column; align-items: flex-start; gap: 15px; }
            .ticket-actions { width: 100%; }
            .ticket-actions a { width: 100%; justify-content: center; }
            .ticket-meta { flex-direction: column; align-items: flex-start; }
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
            <a href="/admin_logs.php" class="nav-item">
                <i class="fas fa-clock-rotate-left"></i>
                Activity Logs
            </a>
            <a href="/admin/tickets.php" class="nav-item active">
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
                <h1>Support Tickets</h1>
                <p>Manage and resolve user support requests</p>
            </div>
        </div>

        <!-- Mini Stats -->
        <div class="stats-row">
            <div class="mini-stat">
                <i class="fas fa-inbox" style="color: var(--primary);"></i>
                <div>
                    <div class="value"><?= $totalTickets ?></div>
                    <div style="font-size: 12px; color: var(--text-muted);">Total Tickets</div>
                </div>
            </div>
            
            <div class="mini-stat">
                <i class="fas fa-circle" style="color: var(--success); font-size: 12px;"></i>
                <div>
                    <div class="value"><?= $openCount ?></div>
                    <div style="font-size: 12px; color: var(--text-muted);">Open</div>
                </div>
            </div>
            
            <div class="mini-stat">
                <i class="fas fa-user-check" style="color: var(--primary);"></i>
                <div>
                    <div class="value"><?= $claimedCount ?></div>
                    <div style="font-size: 12px; color: var(--text-muted);">Claimed</div>
                </div>
            </div>
            
            <div class="mini-stat">
                <i class="fas fa-check-circle" style="color: var(--text-muted);"></i>
                <div>
                    <div class="value"><?= $doneCount ?></div>
                    <div style="font-size: 12px; color: var(--text-muted);">Done</div>
                </div>
            </div>
            
            <div class="mini-stat">
                <i class="fas fa-times-circle" style="color: var(--danger);"></i>
                <div>
                    <div class="value"><?= $refusedCount ?></div>
                    <div style="font-size: 12px; color: var(--text-muted);">Refused</div>
                </div>
            </div>
        </div>

        <!-- Tickets List -->
        <div class="tickets-container">
            <?php if($res->num_rows > 0): ?>
                <?php while($row = $res->fetch_assoc()): 
                    $statusClass = 'status-' . $row['status'];
                    $statusText = ucfirst($row['status']);
                    $initial = strtoupper(substr($row['username'] ?? 'U', 0, 1));
                ?>
                <div class="ticket-card">
                    <div class="ticket-main">
                        <div class="ticket-avatar">
                            <?= $initial ?>
                        </div>
                        <div class="ticket-info">
                            <h3><?= htmlspecialchars($row['username'] ?? 'Unknown User') ?></h3>
                            <div class="ticket-meta">
                                <span class="meta-item"><i class="fas fa-ticket"></i> #<?= $row['id'] ?></span>
                                <span class="meta-item"><i class="fas fa-envelope"></i> <?= htmlspecialchars(substr($row['email'] ?? 'N/A', 0, 25)) ?></span>
                                <span class="meta-item"><i class="fas fa-calendar"></i> <?= date('M d, Y • H:i', strtotime($row['created_at'])) ?></span>
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; gap: 20px;">
                        <span class="status-badge <?= $statusClass ?>">
                            <?php 
                            if($row['status'] == 'open') echo '<i class="fas fa-circle" style="font-size:6px;"></i>';
                            elseif($row['status'] == 'claimed') echo '<i class="fas fa-user-check"></i>';
                            elseif($row['status'] == 'refused') echo '<i class="fas fa-times-circle"></i>';
                            elseif($row['status'] == 'done') echo '<i class="fas fa-check-circle"></i>';
                            ?>
                            <?= $statusText ?>
                        </span>

                        <div class="ticket-actions">
                            <a href="/admin/ticket_view.php?id=<?= $row['id'] ?>">
                                <i class="fas fa-eye"></i> View Details
                            </a>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fas fa-inbox"></i></div>
                    <h3>No Tickets Found</h3>
                    <p>There are no support tickets at the moment.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if($totalPages > 1): ?>
        <div class="pagination">
            <?php for($i=1;$i<=$totalPages;$i++): ?>
                <a href="?page=<?= $i ?>" class="page-link <?= $i==$page?'active':'' ?>">
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