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

$admin_id = intval($_SESSION['user_id']);

$stmt = $conn->prepare("SELECT role FROM users WHERE id=? LIMIT 1");
$stmt->bind_param("i",$admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();

if(!$admin){
    session_destroy();
    exit;
}

$adminRole = $admin['role'];

$search = $_GET['search'] ?? '';
$filterBan = $_GET['ban'] ?? ''; // all, banned, active
$filterRole = $_GET['role'] ?? ''; // all, user, mod, sup, cofounder
$filterSub = $_GET['sub'] ?? ''; // all, free, basic, premium, elite
$filterCoins = $_GET['coins'] ?? ''; // all, low, medium, high
$page   = isset($_GET['page']) ? max(1,intval($_GET['page'])) : 1;
$limit  = 12; // Increased slightly for better grid view
$offset = ($page - 1) * $limit;

$where = "1=1";
$params = [];
$types  = "";

if(!empty($search)){
    $where .= " AND (name LIKE ? OR email LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $types .= "ss";
}

// Filter by ban status
if($filterBan === 'banned'){
    $where .= " AND is_banned = 1";
} elseif($filterBan === 'active'){
    $where .= " AND is_banned = 0";
}

// Filter by role
if(!empty($filterRole) && $filterRole !== 'all'){
    $where .= " AND role = ?";
    $params[] = $filterRole;
    $types .= "s";
}

// Filter by subscription
if(!empty($filterSub) && $filterSub !== 'all'){
    $where .= " AND subscription_tier = ?";
    $params[] = $filterSub;
    $types .= "s";
}

// Filter by coins
if($filterCoins === 'low'){
    $where .= " AND coins < 100";
} elseif($filterCoins === 'medium'){
    $where .= " AND coins >= 100 AND coins < 1000";
} elseif($filterCoins === 'high'){
    $where .= " AND coins >= 1000";
}

$countSql = "SELECT COUNT(*) as total FROM users WHERE $where";
$countStmt = $conn->prepare($countSql);

if(!empty($params)){
    $countStmt->bind_param($types,...$params);
}
$countStmt->execute();
$totalUsers = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalUsers / $limit);

$sql = "
SELECT id,name,email,role,coins,is_banned,timeout_expires_at,is_verified,subscription_tier,subscription_expires
FROM users
WHERE $where
ORDER BY id DESC
LIMIT $limit OFFSET $offset
";

$stmt = $conn->prepare($sql);
if(!empty($params)){
    $stmt->bind_param($types,...$params);
}
$stmt->execute();
$users = $stmt->get_result();

// Helper function to build filter URLs
function buildFilterUrl($newFilters) {
    global $search, $filterBan, $filterRole, $filterSub, $filterCoins;
    
    $params = [
        'page' => 1,
        'search' => $search,
        'ban' => $filterBan,
        'role' => $filterRole,
        'sub' => $filterSub,
        'coins' => $filterCoins
    ];
    
    // Merge new filters
    $params = array_merge($params, $newFilters);
    
    // Remove empty values
    $params = array_filter($params, function($value) {
        return $value !== '' && $value !== null;
    });
    
    return '?' . http_build_query($params);
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - User Management</title>
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

        /* Sidebar (Identical to Dashboard) */
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

        .page-header { margin-bottom: 35px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; }
        .header-title h1 { font-size: 28px; font-weight: 800; color: var(--text-main); margin-bottom: 5px; }
        .header-title p { color: var(--text-muted); font-size: 14px; }

        /* Search Box */
        .search-card {
            background: var(--bg-card); border: 1px solid var(--border);
            border-radius: var(--radius); padding: 20px 25px; margin-bottom: 30px;
            display: flex; align-items: center; gap: 15px; box-shadow: var(--shadow);
            flex-wrap: wrap;
        }
        .search-card i { font-size: 20px; color: var(--text-muted); }
        .search-input {
            flex: 1; background: transparent; border: none;
            font-size: 16px; color: var(--text-main); outline: none;
            min-width: 250px;
        }
        .search-input::placeholder { color: var(--text-muted); }
        .search-btn {
            padding: 10px 25px; background: var(--primary);
            border: none; border-radius: var(--radius); color: white; font-weight: 600;
            cursor: pointer; transition: all 0.2s ease;
        }
        .search-btn:hover { background: var(--primary-light); }
        
        /* Filter Button */
        .filter-btn {
            padding: 10px 25px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none; border-radius: var(--radius); color: white; font-weight: 600;
            cursor: pointer; transition: all 0.2s ease; display: flex; align-items: center; gap: 8px;
            position: relative;
        }
        .filter-btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4); }
        .filter-btn.active { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        
        /* Filter Dropdown */
        .filter-dropdown {
            display: none;
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            background: var(--bg-card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            z-index: 100;
            min-width: 320px;
            padding: 20px;
        }
        .filter-dropdown.show { display: block; }
        
        .filter-section {
            margin-bottom: 20px;
        }
        .filter-section:last-child { margin-bottom: 0; }
        
        .filter-title {
            font-size: 13px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .filter-title i { color: var(--primary); }
        
        .filter-options {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        
        .filter-option {
            padding: 8px 14px;
            background: var(--bg-hover);
            border: 2px solid transparent;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        .filter-option:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        .filter-option.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        .filter-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
        }
        .filter-actions button {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .filter-clear {
            background: var(--bg-hover);
            color: var(--text-main);
        }
        .filter-clear:hover {
            background: var(--danger);
            color: white;
        }
        .filter-apply {
            background: var(--primary);
            color: white;
        }
        .filter-apply:hover {
            background: var(--primary-light);
        }
        
        /* Active Filters Display */
        .active-filters {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        .active-filter-tag {
            padding: 8px 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .active-filter-tag i {
            cursor: pointer;
            opacity: 0.8;
        }
        .active-filter-tag i:hover {
            opacity: 1;
        }

        /* User Grid */
        .users-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 20px;
        }

        .user-card {
            background: var(--bg-card); border: 1px solid var(--border);
            border-radius: var(--radius); padding: 25px; position: relative;
            transition: all 0.2s ease; overflow: hidden;
            box-shadow: var(--shadow);
        }
        .user-card:hover { box-shadow: var(--shadow-hover); }
        
        /* Card Top Section */
        .user-card-header { display: flex; align-items: center; gap: 15px; margin-bottom: 20px; }
        .user-avatar {
            width: 50px; height: 50px; border-radius: 12px;
            background: var(--primary); display: flex;
            align-items: center; justify-content: center; font-size: 20px; font-weight: 700;
            color: white; box-shadow: var(--shadow);
        }
        .user-meta h3 { font-size: 18px; font-weight: 700; margin-bottom: 4px; }
        .user-meta span { font-size: 12px; color: var(--text-muted); font-family: monospace; }

        /* Role Badge */
        .role-badge {
            position: absolute; top: 25px; right: 25px;
            padding: 6px 12px; border-radius: 6px; font-size: 11px;
            font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;
        }
        .role-cofounder { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
        .role-mod { background: rgba(99, 102, 241, 0.15); color: #818cf8; border: 1px solid rgba(99, 102, 241, 0.3); }
        .role-sup { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
        .role-user { background: rgba(148, 163, 184, 0.1); color: #94a3b8; border: 1px solid rgba(148, 163, 184, 0.2); }

        /* Stats & Status */
        .user-stats {
            display: grid; grid-template-columns: 1fr 1fr;
            gap: 15px; padding-bottom: 15px; margin-bottom: 15px;
            border-bottom: 1px solid var(--border);
        }
        .stat-item { display: flex; flex-direction: column; }
        .stat-label { font-size: 11px; color: var(--text-muted); margin-bottom: 4px; text-transform: uppercase; }
        .stat-value { font-size: 15px; font-weight: 600; color: var(--text-main); display: flex; align-items: center; gap: 6px; }
        .stat-value i { font-size: 12px; color: var(--warning); }

        .status-indicators { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px; }
        .status-tag {
            font-size: 11px; padding: 5px 10px; border-radius: 6px;
            display: flex; align-items: center; gap: 6px; font-weight: 600;
        }
        .tag-verified { background: rgba(16, 185, 129, 0.1); color: var(--success); }
        .tag-banned { background: rgba(239, 68, 68, 0.1); color: var(--danger); }
        .tag-timeout { background: rgba(245, 158, 11, 0.1); color: var(--warning); }

        /* Actions */
        .card-actions { display: flex; gap: 10px; flex-wrap: wrap; }
        .card-actions form { display: contents; } /* Fix for form wrapping */
        
        .btn-action {
            padding: 10px 16px; border-radius: 10px; border: none;
            font-size: 13px; font-weight: 600; cursor: pointer;
            transition: all 0.2s ease; text-decoration: none;
            display: inline-flex; align-items: center; gap: 8px;
            color: white; flex: 1; justify-content: center;
        }
        .btn-view { background: rgba(99, 102, 241, 0.15); color: var(--primary-light); border: 1px solid rgba(99, 102, 241, 0.3); }
        .btn-view:hover { background: var(--primary); color: white; }
        .btn-danger { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
        .btn-danger:hover { background: var(--danger); color: white; }
        .btn-success { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
        .btn-success:hover { background: var(--success); color: white; }
        .btn-warning { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
        .btn-warning:hover { background: var(--warning); color: black; }

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
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
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
            background: #f0f2f5;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 20px;
            color: #050505;
        }

        .mobile-brand {
            font-size: 18px;
            font-weight: 700;
            color: #1877f2;
            display: flex;
            align-items: center;
            gap: 8px;
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
            background: #f0f2f5;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 18px;
            color: #050505;
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
        }

        .sidebar-overlay.active {
            display: block;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .mobile-header { display: flex; }
            .sidebar { 
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                z-index: 999;
            }
            .sidebar.active { transform: translateX(0); }
            .main-content { 
                margin-left: 0;
                padding-top: 80px;
            }
        }
        @media (max-width: 768px) {
            .users-grid { grid-template-columns: 1fr; }
            .main-content { padding: 80px 16px 20px; }
            .page-header { 
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            .search-bar { width: 100%; }
        }
        @media (max-width: 480px) {
            .mobile-header { height: 56px; }
            .main-content { padding: 68px 12px 16px; }
            .user-card { padding: 12px; }
        }

        /* Role Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: #1a1a1a;
            border-radius: 12px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
            animation: modalSlide 0.3s ease;
        }

        @keyframes modalSlide {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #333;
        }

        .modal-header h3 {
            font-size: 20px;
            font-weight: 700;
            color: white;
        }

        .modal-close {
            background: none;
            border: none;
            color: #999;
            font-size: 24px;
            cursor: pointer;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: 0.2s;
        }

        .modal-close:hover {
            background: #333;
            color: white;
        }

        .role-options {
            display: grid;
            gap: 12px;
        }

        .role-option {
            padding: 18px 20px;
            background: #252525;
            border: 2px solid #333;
            border-radius: 10px;
            cursor: pointer;
            transition: 0.2s;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .role-option:hover {
            border-color: #1877f2;
            background: #2a2a2a;
        }

        .role-option input[type="radio"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .role-option-content {
            flex: 1;
        }

        .role-option-title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 4px;
            color: white;
        }

        .role-option-desc {
            font-size: 13px;
            color: #999;
        }

        .role-option-badge {
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge-user {
            background: #333;
            color: white;
        }

        .badge-mod {
            background: #1877f2;
            color: white;
        }

        .badge-sup {
            background: #42b72a;
            color: white;
        }

        .badge-cofounder {
            background: gold;
            color: black;
        }

        .modal-footer {
            margin-top: 25px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .modal-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
        }

        .modal-btn-cancel {
            background: #333;
            color: white;
        }

        .modal-btn-cancel:hover {
            background: #444;
        }

        .modal-btn-submit {
            background: linear-gradient(135deg, #1877f2, #0a66c2);
            color: white;
        }

        .modal-btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(24, 119, 242, 0.4);
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
            Users
        </div>
    </div>
    <div class="mobile-header-right">
        <a href="/home.php" class="mobile-icon-btn"><i class="fas fa-home"></i></a>
        <a href="/backend/logout.php" class="mobile-icon-btn"><i class="fas fa-sign-out-alt"></i></a>
    </div>
</div>

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
            
            <a href="/admin_dashboard.php" class="nav-item">
                <i class="fas fa-chart-pie"></i>
                Dashboard
            </a>
            <a href="/admin_users.php" class="nav-item active">
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

            <?php if($adminRole == 'cofounder'): ?>
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
                <h1>User Management</h1>
                <p>Total Registered: <?= formatCount($totalUsers) ?> users</p>
            </div>
        </div>

        <!-- Active Filters Display -->
        <?php 
        $hasFilters = !empty($filterBan) || !empty($filterRole) || !empty($filterSub) || !empty($filterCoins);
        if($hasFilters): 
        ?>
        <div class="active-filters">
            <?php if(!empty($filterBan)): ?>
                <span class="active-filter-tag">
                    <i class="fas fa-ban"></i>
                    Status: <?= ucfirst($filterBan) ?>
                    <i class="fas fa-times" onclick="removeFilter('ban')"></i>
                </span>
            <?php endif; ?>
            
            <?php if(!empty($filterRole) && $filterRole !== 'all'): ?>
                <span class="active-filter-tag">
                    <i class="fas fa-user-shield"></i>
                    Role: <?= ucfirst($filterRole) ?>
                    <i class="fas fa-times" onclick="removeFilter('role')"></i>
                </span>
            <?php endif; ?>
            
            <?php if(!empty($filterSub) && $filterSub !== 'all'): ?>
                <span class="active-filter-tag">
                    <i class="fas fa-crown"></i>
                    Subscription: <?= ucfirst($filterSub) ?>
                    <i class="fas fa-times" onclick="removeFilter('sub')"></i>
                </span>
            <?php endif; ?>
            
            <?php if(!empty($filterCoins)): ?>
                <span class="active-filter-tag">
                    <i class="fas fa-coins"></i>
                    Coins: <?= ucfirst($filterCoins) ?>
                    <i class="fas fa-times" onclick="removeFilter('coins')"></i>
                </span>
            <?php endif; ?>
            
            <a href="?page=1" class="active-filter-tag" style="background: var(--danger); text-decoration: none;">
                <i class="fas fa-times-circle"></i>
                Clear All
            </a>
        </div>
        <?php endif; ?>

        <!-- Search Box -->
        <form method="GET" class="search-card">
            <i class="fas fa-search"></i>
            <input type="text" name="search" class="search-input" placeholder="Search by name or email..." value="<?= htmlspecialchars($search) ?>">
            
            <!-- Hidden inputs to preserve filters -->
            <?php if(!empty($filterBan)): ?>
                <input type="hidden" name="ban" value="<?= htmlspecialchars($filterBan) ?>">
            <?php endif; ?>
            <?php if(!empty($filterRole)): ?>
                <input type="hidden" name="role" value="<?= htmlspecialchars($filterRole) ?>">
            <?php endif; ?>
            <?php if(!empty($filterSub)): ?>
                <input type="hidden" name="sub" value="<?= htmlspecialchars($filterSub) ?>">
            <?php endif; ?>
            <?php if(!empty($filterCoins)): ?>
                <input type="hidden" name="coins" value="<?= htmlspecialchars($filterCoins) ?>">
            <?php endif; ?>
            
            <button type="submit" class="search-btn">
                <i class="fas fa-search"></i> Search
            </button>
            
            <div style="position: relative;">
                <button type="button" class="filter-btn <?= $hasFilters ? 'active' : '' ?>" onclick="toggleFilter(event)">
                    <i class="fas fa-filter"></i> 
                    Filters
                    <?php if($hasFilters): ?>
                        <span style="background: white; color: #667eea; width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 800;">
                            <?= (int)(!empty($filterBan)) + (int)(!empty($filterRole) && $filterRole !== 'all') + (int)(!empty($filterSub) && $filterSub !== 'all') + (int)(!empty($filterCoins)) ?>
                        </span>
                    <?php endif; ?>
                </button>
                
                <div id="filterDropdown" class="filter-dropdown">
                    <!-- Ban Status Filter -->
                    <div class="filter-section">
                        <div class="filter-title">
                            <i class="fas fa-ban"></i>
                            Ban Status
                        </div>
                        <div class="filter-options">
                            <a href="<?= buildFilterUrl(['ban' => '']) ?>" class="filter-option <?= empty($filterBan) ? 'active' : '' ?>">
                                All
                            </a>
                            <a href="<?= buildFilterUrl(['ban' => 'active']) ?>" class="filter-option <?= $filterBan === 'active' ? 'active' : '' ?>">
                                Active
                            </a>
                            <a href="<?= buildFilterUrl(['ban' => 'banned']) ?>" class="filter-option <?= $filterBan === 'banned' ? 'active' : '' ?>">
                                Banned
                            </a>
                        </div>
                    </div>
                    
                    <!-- Role Filter -->
                    <div class="filter-section">
                        <div class="filter-title">
                            <i class="fas fa-user-shield"></i>
                            Role
                        </div>
                        <div class="filter-options">
                            <a href="<?= buildFilterUrl(['role' => 'all']) ?>" class="filter-option <?= empty($filterRole) || $filterRole === 'all' ? 'active' : '' ?>">
                                All
                            </a>
                            <a href="<?= buildFilterUrl(['role' => 'user']) ?>" class="filter-option <?= $filterRole === 'user' ? 'active' : '' ?>">
                                User
                            </a>
                            <a href="<?= buildFilterUrl(['role' => 'sup']) ?>" class="filter-option <?= $filterRole === 'sup' ? 'active' : '' ?>">
                                Support
                            </a>
                            <a href="<?= buildFilterUrl(['role' => 'mod']) ?>" class="filter-option <?= $filterRole === 'mod' ? 'active' : '' ?>">
                                Moderator
                            </a>
                            <a href="<?= buildFilterUrl(['role' => 'cofounder']) ?>" class="filter-option <?= $filterRole === 'cofounder' ? 'active' : '' ?>">
                                Co-Founder
                            </a>
                        </div>
                    </div>
                    
                    <!-- Subscription Filter -->
                    <div class="filter-section">
                        <div class="filter-title">
                            <i class="fas fa-crown"></i>
                            Subscription
                        </div>
                        <div class="filter-options">
                            <a href="<?= buildFilterUrl(['sub' => 'all']) ?>" class="filter-option <?= empty($filterSub) || $filterSub === 'all' ? 'active' : '' ?>">
                                All
                            </a>
                            <a href="<?= buildFilterUrl(['sub' => 'free']) ?>" class="filter-option <?= $filterSub === 'free' ? 'active' : '' ?>">
                                Free
                            </a>
                            <a href="<?= buildFilterUrl(['sub' => 'basic']) ?>" class="filter-option <?= $filterSub === 'basic' ? 'active' : '' ?>">
                                Basic
                            </a>
                            <a href="<?= buildFilterUrl(['sub' => 'premium']) ?>" class="filter-option <?= $filterSub === 'premium' ? 'active' : '' ?>">
                                Premium
                            </a>
                            <a href="<?= buildFilterUrl(['sub' => 'elite']) ?>" class="filter-option <?= $filterSub === 'elite' ? 'active' : '' ?>">
                                Elite
                            </a>
                        </div>
                    </div>
                    
                    <!-- Coins Filter -->
                    <div class="filter-section">
                        <div class="filter-title">
                            <i class="fas fa-coins"></i>
                            Coins Balance
                        </div>
                        <div class="filter-options">
                            <a href="<?= buildFilterUrl(['coins' => '']) ?>" class="filter-option <?= empty($filterCoins) ? 'active' : '' ?>">
                                All
                            </a>
                            <a href="<?= buildFilterUrl(['coins' => 'low']) ?>" class="filter-option <?= $filterCoins === 'low' ? 'active' : '' ?>">
                                Low (&lt;100)
                            </a>
                            <a href="<?= buildFilterUrl(['coins' => 'medium']) ?>" class="filter-option <?= $filterCoins === 'medium' ? 'active' : '' ?>">
                                Medium (100-999)
                            </a>
                            <a href="<?= buildFilterUrl(['coins' => 'high']) ?>" class="filter-option <?= $filterCoins === 'high' ? 'active' : '' ?>">
                                High (1000+)
                            </a>
                        </div>
                    </div>
                    
                    <div class="filter-actions">
                        <a href="?page=1" class="filter-clear" style="text-decoration: none; text-align: center;">
                            <i class="fas fa-times"></i> Clear All
                        </a>
                        <button type="button" class="filter-apply" onclick="toggleFilter(event)">
                            <i class="fas fa-check"></i> Close
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <!-- Users Grid -->
        <div class="users-grid">
            <?php while($row = $users->fetch_assoc()): 
                $initials = strtoupper(substr($row['name'], 0, 1));
            ?>
            <div class="user-card">
                <div class="role-badge role-<?= $row['role'] ?>">
                    <?= $row['role'] ?>
                </div>

                <div class="user-card-header">
                    <div class="user-avatar">
                        <?= $initials ?>
                    </div>
                    <div class="user-meta">
                        <h3><?= htmlspecialchars($row['name']) ?></h3>
                        <span>ID: <?= $row['id'] ?> • <?= htmlspecialchars($row['email']) ?></span>
                    </div>
                </div>

                <div class="user-stats">
                    <div class="stat-item">
                        <span class="stat-label">Coins Balance</span>
                        <span class="stat-value">
                            <i class="fas fa-coins"></i> <?= formatCoins($row['coins']) ?>
                        </span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Subscription</span>
                        <span class="stat-value" style="cursor: pointer;" onclick="showSubscriptionDetails(<?= $row['id'] ?>, '<?= htmlspecialchars($row['name']) ?>', '<?= $row['subscription_tier'] ?? 'free' ?>', '<?= $row['subscription_expires'] ?? '' ?>')">
                            <?php 
                            $subTier = $row['subscription_tier'] ?? 'free';
                            $subColors = [
                                'free' => '#94a3b8',
                                'basic' => '#1877f2',
                                'premium' => '#9333ea',
                                'elite' => '#dc2626'
                            ];
                            $subColor = $subColors[$subTier];
                            ?>
                            <i class="fas fa-crown" style="color: <?= $subColor ?>;"></i>
                            <?= ucfirst($subTier) ?>
                        </span>
                    </div>
                </div>

                <div class="status-indicators">
                    <?php 
                    // Check if user has active subscription
                    $hasActiveSubscription = false;
                    if($row['subscription_tier'] && $row['subscription_tier'] !== 'free' && $row['subscription_expires']) {
                        $hasActiveSubscription = strtotime($row['subscription_expires']) > time();
                    }
                    
                    // Only show verified badge if user has active subscription
                    if($row['is_verified'] && $hasActiveSubscription): 
                    ?>
                        <span class="status-tag tag-verified"><i class="fas fa-check-circle"></i> Verified</span>
                    <?php endif; ?>
                    <?php if($row['is_banned']): ?>
                        <span class="status-tag tag-banned"><i class="fas fa-gavel"></i> Banned</span>
                    <?php endif; ?>
                    <?php if($row['timeout_expires_at'] && $row['timeout_expires_at'] > date("Y-m-d H:i:s")): ?>
                        <span class="status-tag tag-timeout"><i class="fas fa-clock"></i> Timeout Active</span>
                    <?php endif; ?>
                </div>

                <div class="card-actions">
                    <a href="/profile.php?id=<?= $row['id'] ?>" class="btn-action btn-view">
                        <i class="fas fa-eye"></i> View
                    </a>

                    <?php if($adminRole == 'cofounder'): ?>
                        <button type="button" class="btn-action btn-warning" onclick="openRoleModal(<?= $row['id'] ?>, '<?= htmlspecialchars($row['name']) ?>', '<?= $row['role'] ?>')">
                            <i class="fas fa-user-shield"></i> Role
                        </button>

                        <button type="button" class="btn-action" style="background: linear-gradient(135deg, #ffc107, #ff9800); color: #000;" onclick="openCoinsModal(<?= $row['id'] ?>, '<?= htmlspecialchars($row['name']) ?>', <?= $row['coins'] ?>)">
                            <i class="fas fa-coins"></i> Coins
                        </button>
                    <?php endif; ?>

                    <?php if(!$row['is_banned']): ?>
                        <form method="POST" action="/backend/admin/ban_user.php">
                            <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                            <button type="submit" class="btn-action btn-danger">
                                <i class="fas fa-ban"></i> Ban
                            </button>
                        </form>
                    <?php else: ?>
                        <form method="POST" action="/backend/admin/unban_user.php">
                            <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                            <button type="submit" class="btn-action btn-success">
                                <i class="fas fa-unlock"></i> Unban
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        </div>

        <!-- Pagination -->
        <?php if($totalPages > 1): ?>
        <div class="pagination">
            <?php 
            $paginationParams = [
                'search' => $search,
                'ban' => $filterBan,
                'role' => $filterRole,
                'sub' => $filterSub,
                'coins' => $filterCoins
            ];
            $paginationParams = array_filter($paginationParams, function($value) {
                return $value !== '' && $value !== null;
            });
            
            for($i=1; $i<=$totalPages; $i++): 
                $paginationParams['page'] = $i;
                $paginationUrl = '?' . http_build_query($paginationParams);
            ?>
                <a href="<?= $paginationUrl ?>" class="page-link <?= $i==$page?'active':'' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </main>
</div>

<!-- Role Change Modal -->
<div id="roleModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-user-shield"></i> Change User Role</h3>
            <button class="modal-close" onclick="closeRoleModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div style="margin-bottom: 20px; padding: 15px; background: #252525; border-radius: 8px;">
            <div style="font-size: 14px; color: #999; margin-bottom: 5px;">Changing role for:</div>
            <div style="font-size: 18px; font-weight: 700; color: white;" id="modalUserName"></div>
        </div>

        <form id="roleForm" method="POST" action="/backend/admin/promote_user.php">
            <input type="hidden" name="user_id" id="modalUserId">
            
            <div class="role-options">
                <label class="role-option">
                    <input type="radio" name="role" value="user" required>
                    <div class="role-option-content">
                        <div class="role-option-title">User</div>
                        <div class="role-option-desc">Regular user with no admin privileges</div>
                    </div>
                    <span class="role-option-badge badge-user">USER</span>
                </label>

                <label class="role-option">
                    <input type="radio" name="role" value="sup" required>
                    <div class="role-option-content">
                        <div class="role-option-title">Support</div>
                        <div class="role-option-desc">Can manage tickets and help users</div>
                    </div>
                    <span class="role-option-badge badge-sup">SUPPORT</span>
                </label>

                <label class="role-option">
                    <input type="radio" name="role" value="mod" required>
                    <div class="role-option-content">
                        <div class="role-option-title">Moderator</div>
                        <div class="role-option-desc">Can moderate content and manage users</div>
                    </div>
                    <span class="role-option-badge badge-mod">MODERATOR</span>
                </label>

                <label class="role-option">
                    <input type="radio" name="role" value="cofounder" required>
                    <div class="role-option-content">
                        <div class="role-option-title">Co-Founder</div>
                        <div class="role-option-desc">Full admin access to all features</div>
                    </div>
                    <span class="role-option-badge badge-cofounder">CO-FOUNDER</span>
                </label>
            </div>

            <div class="modal-footer">
                <button type="button" class="modal-btn modal-btn-cancel" onclick="closeRoleModal()">
                    Cancel
                </button>
                <button type="submit" class="modal-btn modal-btn-submit">
                    <i class="fas fa-check"></i> Update Role
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Coins Management Modal -->
<div id="coinsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-coins"></i> Manage User Coins</h3>
            <button class="modal-close" onclick="closeCoinsModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div style="margin-bottom: 20px; padding: 15px; background: linear-gradient(135deg, #ffc107, #ff9800); border-radius: 8px; color: #000;">
            <div style="font-size: 14px; font-weight: 600; margin-bottom: 5px;">Managing coins for:</div>
            <div style="font-size: 18px; font-weight: 700;" id="coinsModalUserName"></div>
            <div style="font-size: 24px; font-weight: 800; margin-top: 10px; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-coins"></i>
                <span id="coinsModalCurrentCoins">0</span> Coins
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
            <button type="button" class="modal-btn" style="background: linear-gradient(135deg, #10b981, #059669); color: white; padding: 15px; font-size: 16px;" onclick="showCoinsForm('add')">
                <i class="fas fa-plus-circle"></i> Add Coins
            </button>
            <button type="button" class="modal-btn" style="background: linear-gradient(135deg, #ef4444, #dc2626); color: white; padding: 15px; font-size: 16px;" onclick="showCoinsForm('remove')">
                <i class="fas fa-minus-circle"></i> Remove Coins
            </button>
        </div>

        <form id="coinsForm" method="POST" action="/backend/admin/manage_coins.php" style="display: none;">
            <input type="hidden" name="user_id" id="coinsModalUserId">
            <input type="hidden" name="action" id="coinsAction">
            
            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; color: white; font-weight: 600; font-size: 14px;">
                    <i class="fas fa-coins"></i> Amount
                </label>
                <input type="number" name="amount" id="coinsAmount" min="1" required 
                       style="width: 100%; padding: 12px 15px; border: 2px solid #333; border-radius: 8px; background: #252525; color: white; font-size: 16px; font-weight: 600;">
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; margin-bottom: 8px; color: white; font-weight: 600; font-size: 14px;">
                    <i class="fas fa-comment"></i> Reason (Optional)
                </label>
                <textarea name="reason" rows="3" 
                          style="width: 100%; padding: 12px 15px; border: 2px solid #333; border-radius: 8px; background: #252525; color: white; font-size: 14px; resize: vertical;" 
                          placeholder="Enter reason for this transaction..."></textarea>
            </div>

            <div class="modal-footer">
                <button type="button" class="modal-btn modal-btn-cancel" onclick="hideCoinsForm()">
                    Cancel
                </button>
                <button type="submit" class="modal-btn modal-btn-submit" id="coinsSubmitBtn">
                    <i class="fas fa-check"></i> Confirm
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Subscription Details Modal -->
<div id="subscriptionModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-crown"></i> Subscription Details</h3>
            <button class="modal-close" onclick="closeSubscriptionModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div style="margin-bottom: 20px; padding: 15px; background: #252525; border-radius: 8px;">
            <div style="font-size: 14px; color: #999; margin-bottom: 5px;">User:</div>
            <div style="font-size: 18px; font-weight: 700; color: white;" id="subModalUserName"></div>
        </div>

        <div id="subModalContent" style="padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; color: white; text-align: center;">
            <!-- Content will be filled by JavaScript -->
        </div>

        <div class="modal-footer">
            <button type="button" class="modal-btn modal-btn-cancel" onclick="closeSubscriptionModal()">
                Close
            </button>
        </div>
    </div>
</div>

<script>
function toggleFilter(event) {
    event.stopPropagation();
    const dropdown = document.getElementById('filterDropdown');
    dropdown.classList.toggle('show');
}

function removeFilter(filterType) {
    const url = new URL(window.location.href);
    url.searchParams.delete(filterType);
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('filterDropdown');
    const filterBtn = document.querySelector('.filter-btn');
    
    if (!dropdown.contains(event.target) && !filterBtn.contains(event.target)) {
        dropdown.classList.remove('show');
    }
});

function openRoleModal(userId, userName, currentRole) {
    document.getElementById('modalUserId').value = userId;
    document.getElementById('modalUserName').textContent = userName;
    
    // Select current role
    const roleInputs = document.querySelectorAll('input[name="role"]');
    roleInputs.forEach(input => {
        if(input.value === currentRole) {
            input.checked = true;
        }
    });
    
    document.getElementById('roleModal').classList.add('show');
}

function showSubscriptionDetails(userId, userName, tier, expiresAt) {
    document.getElementById('subModalUserName').textContent = userName;
    
    const tierColors = {
        'free': '#94a3b8',
        'basic': '#1877f2',
        'premium': '#9333ea',
        'elite': '#dc2626'
    };
    
    const tierNames = {
        'free': 'Free',
        'basic': 'Basic',
        'premium': 'Premium',
        'elite': 'Elite'
    };
    
    const tierIcons = {
        'free': 'fa-user',
        'basic': 'fa-star',
        'premium': 'fa-crown',
        'elite': 'fa-gem'
    };
    
    const color = tierColors[tier] || '#94a3b8';
    const name = tierNames[tier] || 'Free';
    const icon = tierIcons[tier] || 'fa-user';
    
    let content = `
        <div style="margin-bottom: 20px;">
            <div style="width: 80px; height: 80px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; font-size: 36px;">
                <i class="fas ${icon}"></i>
            </div>
            <div style="font-size: 28px; font-weight: 800; margin-bottom: 10px;">${name} Plan</div>
        </div>
    `;
    
    if (tier !== 'free' && expiresAt) {
        const expiresDate = new Date(expiresAt);
        const now = new Date();
        const diffTime = expiresDate - now;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        if (diffDays > 0) {
            const hours = Math.floor((diffTime % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            
            content += `
                <div style="background: rgba(255,255,255,0.15); padding: 20px; border-radius: 10px; margin-bottom: 15px;">
                    <div style="font-size: 14px; opacity: 0.9; margin-bottom: 8px;">Time Remaining</div>
                    <div style="font-size: 32px; font-weight: 800; margin-bottom: 5px;">
                        ${diffDays} Days ${hours} Hours
                    </div>
                    <div style="font-size: 13px; opacity: 0.8;">
                        <i class="fas fa-calendar-alt"></i> 
                        Expires: ${expiresDate.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}
                    </div>
                </div>
                <div style="font-size: 13px; opacity: 0.9;">
                    <i class="fas fa-check-circle"></i> Active Subscription
                </div>
            `;
        } else {
            content += `
                <div style="background: rgba(239, 68, 68, 0.2); padding: 20px; border-radius: 10px; margin-bottom: 15px;">
                    <div style="font-size: 18px; font-weight: 700; margin-bottom: 5px;">
                        <i class="fas fa-exclamation-triangle"></i> Expired
                    </div>
                    <div style="font-size: 13px; opacity: 0.9;">
                        Expired on: ${expiresDate.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}
                    </div>
                </div>
            `;
        }
    } else {
        content += `
            <div style="background: rgba(255,255,255,0.15); padding: 20px; border-radius: 10px;">
                <div style="font-size: 16px; opacity: 0.9;">
                    <i class="fas fa-info-circle"></i> No active subscription
                </div>
            </div>
        `;
    }
    
    document.getElementById('subModalContent').innerHTML = content;
    document.getElementById('subModalContent').style.background = `linear-gradient(135deg, ${color} 0%, ${color}dd 100%)`;
    document.getElementById('subscriptionModal').classList.add('show');
}

function closeSubscriptionModal() {
    document.getElementById('subscriptionModal').classList.remove('show');
}

function openRoleModal(userId, userName, currentRole) {
    document.getElementById('modalUserId').value = userId;
    document.getElementById('modalUserName').textContent = userName;
    
    // Select current role
    const roleInputs = document.querySelectorAll('input[name="role"]');
    roleInputs.forEach(input => {
        if(input.value === currentRole) {
            input.checked = true;
        }
    });
    
    document.getElementById('roleModal').classList.add('show');
}

function closeRoleModal() {
    document.getElementById('roleModal').classList.remove('show');
}

// Coins Modal Functions
function openCoinsModal(userId, userName, currentCoins) {
    document.getElementById('coinsModalUserId').value = userId;
    document.getElementById('coinsModalUserName').textContent = userName;
    document.getElementById('coinsModalCurrentCoins').textContent = currentCoins;
    
    // Reset form
    document.getElementById('coinsForm').style.display = 'none';
    document.getElementById('coinsAmount').value = '';
    
    document.getElementById('coinsModal').classList.add('show');
}

function closeCoinsModal() {
    document.getElementById('coinsModal').classList.remove('show');
    hideCoinsForm();
}

function showCoinsForm(action) {
    document.getElementById('coinsAction').value = action;
    document.getElementById('coinsForm').style.display = 'block';
    
    const submitBtn = document.getElementById('coinsSubmitBtn');
    if(action === 'add') {
        submitBtn.innerHTML = '<i class="fas fa-plus"></i> Add Coins';
        submitBtn.style.background = 'linear-gradient(135deg, #10b981, #059669)';
    } else {
        submitBtn.innerHTML = '<i class="fas fa-minus"></i> Remove Coins';
        submitBtn.style.background = 'linear-gradient(135deg, #ef4444, #dc2626)';
    }
    
    document.getElementById('coinsAmount').focus();
}

function hideCoinsForm() {
    document.getElementById('coinsForm').style.display = 'none';
    document.getElementById('coinsAmount').value = '';
}

// Close modal when clicking outside
document.getElementById('roleModal').addEventListener('click', function(e) {
    if(e.target === this) {
        closeRoleModal();
    }
});

document.getElementById('coinsModal').addEventListener('click', function(e) {
    if(e.target === this) {
        closeCoinsModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if(e.key === 'Escape') {
        closeRoleModal();
        closeCoinsModal();
    }
});

// Mobile sidebar toggle
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('active');
    document.querySelector('.sidebar-overlay').classList.toggle('active');
}

document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('click', () => {
        if (window.innerWidth <= 1024) toggleSidebar();
    });
});

window.addEventListener('resize', () => {
    if (window.innerWidth > 1024) {
        document.getElementById('sidebar').classList.remove('active');
        document.querySelector('.sidebar-overlay').classList.remove('active');
    }
});
</script>

</body>
</html>