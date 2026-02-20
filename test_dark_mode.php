<?php
session_start();
require_once __DIR__ . "/backend/config.php";

if (!isset($_SESSION['user_id'])) {
    die("Please login first: <a href='/'>Login</a>");
}

$user_id = $_SESSION['user_id'];

// Check if dark_mode column exists
$checkColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'dark_mode'");
$columnExists = $checkColumn->num_rows > 0;

// Get current dark mode status
$darkMode = false;
if ($columnExists) {
    $stmt = $conn->prepare("SELECT dark_mode FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $darkMode = $result['dark_mode'] == 1;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Dark Mode Test</title>
    <link rel="stylesheet" href="/assets/dark-mode.css">
    <script src="/assets/dark-mode.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 40px;
            max-width: 800px;
            margin: 0 auto;
        }
        .status-box {
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            background: #f0f2f5;
        }
        .dark-mode .status-box {
            background: #3a3b3c;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 15px;
            margin: 5px;
        }
        .btn-primary {
            background: #1877f2;
            color: white;
        }
        .btn-secondary {
            background: #e4e6eb;
            color: #050505;
        }
        .dark-mode .btn-secondary {
            background: #3a3b3c;
            color: #e4e6eb;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        .dark-mode th,
        .dark-mode td {
            border-color: #3a3b3c;
        }
        .success {
            color: #10b981;
        }
        .error {
            color: #ef4444;
        }
    </style>
</head>
<body>
    <h1>🌙 Dark Mode System Test</h1>
    
    <div class="status-box">
        <h2>Current Status</h2>
        <table>
            <tr>
                <th>Item</th>
                <th>Status</th>
            </tr>
            <tr>
                <td>User ID</td>
                <td><?= $user_id ?></td>
            </tr>
            <tr>
                <td>Dark Mode Column</td>
                <td class="<?= $columnExists ? 'success' : 'error' ?>">
                    <?= $columnExists ? '✅ Exists' : '❌ Not Found' ?>
                </td>
            </tr>
            <tr>
                <td>Dark Mode Status</td>
                <td class="<?= $darkMode ? 'success' : '' ?>">
                    <?= $darkMode ? '🌙 Enabled' : '☀️ Disabled' ?>
                </td>
            </tr>
            <tr>
                <td>CSS File</td>
                <td class="<?= file_exists('assets/dark-mode.css') ? 'success' : 'error' ?>">
                    <?= file_exists('assets/dark-mode.css') ? '✅ Found' : '❌ Not Found' ?>
                </td>
            </tr>
            <tr>
                <td>JS File</td>
                <td class="<?= file_exists('assets/dark-mode.js') ? 'success' : 'error' ?>">
                    <?= file_exists('assets/dark-mode.js') ? '✅ Found' : '❌ Not Found' ?>
                </td>
            </tr>
        </table>
    </div>

    <div class="status-box">
        <h2>Actions</h2>
        <button class="btn btn-primary" onclick="toggleDarkMode()">
            🌓 Toggle Dark Mode
        </button>
        <button class="btn btn-secondary" onclick="location.reload()">
            🔄 Refresh Page
        </button>
        <a href="/settings.php" class="btn btn-secondary">⚙️ Go to Settings</a>
        <a href="/home.php" class="btn btn-secondary">🏠 Go to Home</a>
    </div>

    <div class="status-box">
        <h2>Test Elements</h2>
        <p>This text should change color in dark mode.</p>
        <button class="btn btn-primary">Primary Button</button>
        <button class="btn btn-secondary">Secondary Button</button>
        <input type="text" placeholder="Test input" style="padding: 10px; margin: 10px 0; width: 100%;">
    </div>

    <script>
        console.log('Dark Mode Test Page Loaded');
        console.log('Current dark mode class:', document.body.classList.contains('dark-mode'));
    </script>
</body>
</html>
