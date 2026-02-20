<?php
session_start();
require_once __DIR__ . "/backend/config.php";
require_once __DIR__ . "/backend/middleware.php";
require_once __DIR__ . "/includes/helpers.php";

requireRole(['cofounder']);

if(!isset($_SESSION['user_id'])){
    header("Location: /");
    exit;
}

// Check if PIN is verified
if (!isset($_SESSION['admin_pin_verified']) || $_SESSION['admin_pin_verified'] !== true) {
    header("Location: /admin_pin_login.php");
    exit;
}

$admin_id = intval($_SESSION['user_id']);

$stmt = $conn->prepare("SELECT role FROM users WHERE id=? LIMIT 1");
$stmt->bind_param("i",$admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

if(!$admin || $admin['role'] !== 'cofounder'){
    header("Location: /admin_dashboard.php");
    exit;
}

$search = $_GET['search'] ?? '';
$page   = isset($_GET['page']) ? max(1,intval($_GET['page'])) : 1;
$limit  = 12;
$offset = ($page - 1) * $limit;

$where = "role IN ('cofounder','mod','sup')";
$params = [];
$types  = "";

if(!empty($search)){
    $where .= " AND (name LIKE ? OR email LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $types .= "ss";
}

$countSql = "SELECT COUNT(*) as total FROM users WHERE $where";
$countStmt = $conn->prepare($countSql);

if(!empty($params)){
    $countStmt->bind_param($types,...$params);
}
$countStmt->execute();
$totalAdmins = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalAdmins / $limit);

$sql = "
SELECT id,name,email,role,coins,is_banned
FROM users
WHERE $where
ORDER BY 
    CASE role 
        WHEN 'cofounder' THEN 1 
        WHEN 'mod' THEN 2 
        WHEN 'sup' THEN 3 
    END ASC, id DESC
LIMIT $limit OFFSET $offset
";

$stmt = $conn->prepare($sql);
if(!empty($params)){
    $stmt->bind_param($types,...$params);
}
$stmt->execute();
$admins = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الصلاحيات - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700;800&display=swap" rel="stylesheet">
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
            font-family: 'Cairo', sans-serif;
            background: var(--bg-main);
            color: var(--text-main);
            min-height: 100vh;
        }

        /* Sidebar */
        .app-container { display: flex; min-height: 100vh; }
        .sidebar {
            position: fixed; right: 0; top: 0; width: 260px; height: 100vh;
            background: var(--bg-sidebar); border-left: 1px solid var(--border);
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
            margin-right: 260px; flex: 1; padding: 35px 40px;
            background: var(--bg-main);
        }

        /* Header */
        .page-header { margin-bottom: 35px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; }
        .header-title h1 { font-size: 28px; font-weight: 800; color: var(--text-main); margin-bottom: 5px; }
        .header-title p { color: var(--text-muted); font-size: 14px; }

        /* Search Box */
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

        /* Grid */
        .admins-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 20px;
        }

        .admin-card {
            background: var(--bg-card); border: 1px solid var(--border);
            border-radius: var(--radius); padding: 25px; position: relative;
            transition: all 0.2s ease; overflow: hidden;
            box-shadow: var(--shadow);
        }
        .admin-card:hover { box-shadow: var(--shadow-hover); }

        /* Card Header */
        .admin-card-header { display: flex; align-items: center; gap: 15px; margin-bottom: 20px; }
        .admin-avatar {
            width: 50px; height: 50px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; font-weight: 700; color: white;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }
        .avatar-cofounder { background: #f59e0b; }
        .avatar-mod { background: var(--primary); }
        .avatar-sup { background: var(--success); }

        .admin-meta h3 { font-size: 18px; font-weight: 700; margin-bottom: 4px; }
        .admin-meta span { font-size: 12px; color: var(--text-muted); font-family: monospace; }

        /* Role Badge */
        .role-badge {
            position: absolute; top: 25px; left: 25px;
            padding: 6px 12px; border-radius: 6px; font-size: 11px;
            font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;
        }
        .role-cofounder { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
        .role-mod { background: rgba(99, 102, 241, 0.15); color: #818cf8; border: 1px solid rgba(99, 102, 241, 0.3); }
        .role-sup { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }

        /* Stats */
        .admin-stats {
            display: grid; grid-template-columns: 1fr;
            gap: 15px; padding-bottom: 15px; margin-bottom: 15px;
            border-bottom: 1px solid var(--border);
        }
        .stat-item { display: flex; flex-direction: column; }
        .stat-label { font-size: 11px; color: var(--text-muted); margin-bottom: 4px; text-transform: uppercase; }
        .stat-value { font-size: 15px; font-weight: 600; color: var(--text-main); display: flex; align-items: center; gap: 6px; }
        .stat-value i { font-size: 12px; color: var(--primary-light); }

        /* Actions */
        .card-actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .card-actions form { display: contents; }
        
        .btn-action {
            padding: 10px 16px; border-radius: 10px; border: none;
            font-size: 13px; font-weight: 600; cursor: pointer;
            transition: all 0.2s ease; text-decoration: none;
            display: inline-flex; align-items: center; gap: 8px;
            color: white; flex: 1; justify-content: center;
        }
        .btn-manage { background: var(--bg-hover); color: var(--primary); border: 1px solid var(--border); }
        .btn-manage:hover { background: var(--primary); color: white; border-color: var(--primary); }
        .btn-danger { background: var(--bg-hover); color: var(--danger); border: 1px solid var(--border); }
        .btn-danger:hover { background: var(--danger); color: white; border-color: var(--danger); }

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

        /* Modal */
        .modal {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.8); z-index: 9999; align-items: center; justify-content: center;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: var(--bg-card); border: 1px solid var(--border);
            border-radius: var(--radius); padding: 30px; max-width: 500px; width: 90%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
        }
        .modal-header { margin-bottom: 25px; }
        .modal-header h2 { font-size: 24px; font-weight: 700; margin-bottom: 8px; }
        .modal-header p { color: var(--text-muted); font-size: 14px; }
        .modal-body { margin-bottom: 25px; }
        .modal-footer { display: flex; gap: 10px; justify-content: flex-end; }
        .btn-modal {
            padding: 12px 24px; border-radius: 10px; border: none;
            font-size: 14px; font-weight: 600; cursor: pointer;
            transition: all 0.2s ease;
        }
        .btn-cancel { background: var(--bg-hover); color: var(--text-main); }
        .btn-cancel:hover { background: var(--border); }
        .btn-confirm { background: var(--primary); color: white; }
        .btn-confirm:hover { background: var(--primary-light); }

        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar { display: none; }
            .main-content { margin-right: 0; }
        }
        @media (max-width: 768px) {
            .admins-grid { grid-template-columns: 1fr; }
            .main-content { padding: 20px; }
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
                لوحة التحكم
            </h2>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section-title">القائمة الرئيسية</div>
            <a href="/admin_dashboard.php" class="nav-item">
                <i class="fas fa-chart-pie"></i>
                لوحة المعلومات
            </a>
            <a href="/admin_users.php" class="nav-item">
                <i class="fas fa-users"></i>
                إدارة المستخدمين
            </a>
            <a href="/admin_groups.php" class="nav-item">
                <i class="fas fa-layer-group"></i>
                المجموعات
            </a>
            <a href="/admin_logs.php" class="nav-item">
                <i class="fas fa-clock-rotate-left"></i>
                سجل الأنشطة
            </a>
            <a href="/admin/tickets.php" class="nav-item">
                <i class="fas fa-headset"></i>
                تذاكر الدعم
            </a>

            <div class="nav-section-title" style="margin-top: 25px;">الإدارة</div>
            <a href="/admin_manage.php" class="nav-item active">
                <i class="fas fa-user-shield"></i>
                إدارة الصلاحيات
            </a>
        </nav>

        <div class="sidebar-footer">
            <a href="/backend/admin_logout.php" class="logout-btn" style="margin-bottom: 10px; background: rgba(239, 68, 68, 0.1); color: #ef4444;">
                <i class="fas fa-lock"></i>
                الخروج من لوحة التحكم
            </a>
            <a href="/backend/logout.php" class="logout-btn">
                <i class="fas fa-right-from-bracket"></i>
                تسجيل الخروج
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <div class="page-header">
            <div class="header-title">
                <h1>إدارة الصلاحيات الإدارية</h1>
                <p>الإشراف على الصلاحيات الإدارية وتعديلها</p>
            </div>
        </div>

        <!-- Search -->
        <form method="GET" class="search-card">
            <i class="fas fa-search"></i>
            <input type="text" name="search" class="search-input" placeholder="ابحث بالاسم أو البريد الإلكتروني..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="search-btn">
                <i class="fas fa-filter"></i> بحث
            </button>
        </form>

        <!-- Admins Grid -->
        <div class="admins-grid">
            <?php while($row = $admins->fetch_assoc()): 
                $initial = strtoupper(substr($row['name'], 0, 1));
                $roleNames = [
                    'cofounder' => 'مؤسس مشارك',
                    'mod' => 'مشرف',
                    'sup' => 'دعم فني'
                ];
            ?>
            <div class="admin-card">
                <div class="role-badge role-<?= $row['role'] ?>">
                    <?= $roleNames[$row['role']] ?? $row['role'] ?>
                </div>

                <div class="admin-card-header">
                    <div class="admin-avatar avatar-<?= $row['role'] ?>">
                        <?= $initial ?>
                    </div>
                    <div class="admin-meta">
                        <h3><?= htmlspecialchars($row['name']) ?></h3>
                        <span>ID: <?= $row['id'] ?> • <?= htmlspecialchars($row['email']) ?></span>
                    </div>
                </div>

                <div class="admin-stats">
                    <div class="stat-item">
                        <span class="stat-label">رصيد العملات</span>
                        <span class="stat-value">
                            <i class="fas fa-coins"></i> <?= formatCoins($row['coins']) ?>
                        </span>
                    </div>
                </div>

                <div class="card-actions">
                    <form method="POST" action="/backend/admin/promote_user.php">
                        <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                        <button type="submit" class="btn-action btn-manage">
                            <i class="fas fa-user-shield"></i> تغيير الصلاحية
                        </button>
                    </form>

                    <?php if($row['role'] !== 'cofounder'): ?>
                    <button type="button" class="btn-action btn-danger" onclick="confirmRemove(<?= $row['id'] ?>, '<?= htmlspecialchars($row['name']) ?>')">
                        <i class="fas fa-user-minus"></i> إزالة الصلاحية
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        </div>

        <!-- Pagination -->
        <?php if($totalPages > 1): ?>
        <div class="pagination">
            <?php for($i=1;$i<=$totalPages;$i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" class="page-link <?= $i==$page?'active':'' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </main>
</div>

<!-- Remove Confirmation Modal -->
<div id="removeModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>تأكيد إزالة الصلاحية</h2>
            <p>هل أنت متأكد من إزالة الصلاحيات الإدارية؟</p>
        </div>
        <div class="modal-body">
            <p style="color: var(--text-muted);">سيتم إزالة صلاحيات <strong id="removeName"></strong> وتحويله إلى مستخدم عادي.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn-modal btn-cancel" onclick="closeRemoveModal()">إلغاء</button>
            <form id="removeForm" method="POST" action="/backend/admin/remove_admin.php" style="display: inline;">
                <input type="hidden" name="user_id" id="removeUserId">
                <button type="submit" class="btn-modal btn-confirm">تأكيد الإزالة</button>
            </form>
        </div>
    </div>
</div>

<script>
function confirmRemove(userId, userName) {
    document.getElementById('removeUserId').value = userId;
    document.getElementById('removeName').textContent = userName;
    document.getElementById('removeModal').classList.add('active');
}

function closeRemoveModal() {
    document.getElementById('removeModal').classList.remove('active');
}

// Close modal on outside click
document.getElementById('removeModal').addEventListener('click', function(e) {
    if(e.target === this) {
        closeRemoveModal();
    }
});
</script>

</body>
</html>
