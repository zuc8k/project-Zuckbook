<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adding Sample Data - ZuckBook Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            max-width: 600px;
            width: 100%;
        }

        h2 {
            font-size: 28px;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        h2 i {
            color: #667eea;
            font-size: 32px;
        }

        .status-item {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 15px;
            animation: slideIn 0.4s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .status-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .status-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .status-item i {
            font-size: 20px;
        }

        .actions {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #e2e8f0;
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }

        .btn-secondary {
            background: #f7fafc;
            color: #4a5568;
            border: 2px solid #e2e8f0;
        }

        .btn-secondary:hover {
            background: #edf2f7;
            border-color: #cbd5e0;
        }

        .summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-top: 20px;
            text-align: center;
        }

        .summary h3 {
            font-size: 20px;
            margin-bottom: 10px;
        }

        .summary p {
            font-size: 14px;
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>
            <i class="fas fa-database"></i>
            Adding Sample Data
        </h2>

<?php
require_once __DIR__ . "/backend/config.php";

$groupsAdded = 0;
$logsAdded = 0;
$errors = [];

// Check if we already have data
$checkGroups = $conn->query("SELECT COUNT(*) as total FROM groups");
$groupCount = $checkGroups->fetch_assoc()['total'];

if($groupCount > 0) {
    echo '<div class="status-item status-warning">';
    echo '<i class="fas fa-info-circle"></i>';
    echo "Groups table already has {$groupCount} records. Skipping...";
    echo '</div>';
} else {
    // Get first user ID
    $userResult = $conn->query("SELECT id FROM users LIMIT 1");
    if($userResult && $userResult->num_rows > 0) {
        $userId = $userResult->fetch_assoc()['id'];
        
        // Add sample groups
        $sampleGroups = [
            ['name' => 'Tech Enthusiasts', 'description' => 'A community for technology lovers', 'category' => 'Technology', 'verification_status' => 'verified'],
            ['name' => 'Gaming Community', 'description' => 'Gamers unite!', 'category' => 'Gaming', 'verification_status' => 'pending'],
            ['name' => 'Photography Club', 'description' => 'Share your best shots', 'category' => 'Arts', 'verification_status' => 'verified'],
            ['name' => 'Fitness & Health', 'description' => 'Stay healthy together', 'category' => 'Health', 'verification_status' => 'none'],
            ['name' => 'Book Readers', 'description' => 'Discuss your favorite books', 'category' => 'Education', 'verification_status' => 'pending'],
        ];
        
        $stmt = $conn->prepare("INSERT INTO groups (name, description, category, owner_id, verification_status, privacy) VALUES (?, ?, ?, ?, ?, 'public')");
        
        foreach($sampleGroups as $group) {
            $stmt->bind_param("sssis", $group['name'], $group['description'], $group['category'], $userId, $group['verification_status']);
            if($stmt->execute()) {
                $groupsAdded++;
            }
        }
        
        echo '<div class="status-item status-success">';
        echo '<i class="fas fa-check-circle"></i>';
        echo "Successfully added {$groupsAdded} sample groups";
        echo '</div>';
    } else {
        echo '<div class="status-item status-error">';
        echo '<i class="fas fa-exclamation-triangle"></i>';
        echo 'No users found. Please create a user first.';
        echo '</div>';
        $errors[] = 'No users';
    }
}

// Check admin_logs table
$checkTable = $conn->query("SHOW TABLES LIKE 'admin_logs'");
if(!$checkTable || $checkTable->num_rows == 0) {
    echo '<div class="status-item status-warning">';
    echo '<i class="fas fa-tools"></i>';
    echo 'Creating admin_logs table...';
    echo '</div>';
    
    $createTable = "
    CREATE TABLE IF NOT EXISTS admin_logs (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        admin_id BIGINT UNSIGNED NOT NULL,
        action VARCHAR(100) NOT NULL,
        target_user BIGINT UNSIGNED,
        description TEXT,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_admin_logs_admin FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB;
    ";
    
    if($conn->query($createTable)) {
        echo '<div class="status-item status-success">';
        echo '<i class="fas fa-check-circle"></i>';
        echo 'admin_logs table created successfully';
        echo '</div>';
        
        // Add sample logs
        $userResult = $conn->query("SELECT id FROM users WHERE role IN ('cofounder', 'mod', 'sup') LIMIT 1");
        if($userResult && $userResult->num_rows > 0) {
            $adminId = $userResult->fetch_assoc()['id'];
            
            $sampleLogs = [
                ['action' => 'verify_group', 'description' => 'Verified Tech Enthusiasts group', 'ip' => '192.168.1.1'],
                ['action' => 'ban_user', 'description' => 'Banned user for spam', 'ip' => '192.168.1.2'],
                ['action' => 'delete_post', 'description' => 'Deleted inappropriate post', 'ip' => '192.168.1.1'],
                ['action' => 'promote_admin', 'description' => 'Promoted user to moderator', 'ip' => '192.168.1.3'],
            ];
            
            $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
            
            foreach($sampleLogs as $log) {
                $stmt->bind_param("isss", $adminId, $log['action'], $log['description'], $log['ip']);
                if($stmt->execute()) {
                    $logsAdded++;
                }
            }
            
            echo '<div class="status-item status-success">';
            echo '<i class="fas fa-check-circle"></i>';
            echo "Successfully added {$logsAdded} sample admin logs";
            echo '</div>';
        }
    } else {
        echo '<div class="status-item status-error">';
        echo '<i class="fas fa-exclamation-triangle"></i>';
        echo 'Failed to create admin_logs table: ' . $conn->error;
        echo '</div>';
        $errors[] = 'Table creation failed';
    }
} else {
    $checkLogs = $conn->query("SELECT COUNT(*) as total FROM admin_logs");
    $logCount = $checkLogs->fetch_assoc()['total'];
    
    echo '<div class="status-item status-warning">';
    echo '<i class="fas fa-info-circle"></i>';
    echo "admin_logs table already has {$logCount} records.";
    echo '</div>';
}

// Summary
if(count($errors) == 0 && ($groupsAdded > 0 || $logsAdded > 0)) {
    echo '<div class="summary">';
    echo '<h3><i class="fas fa-check-circle"></i> All Done!</h3>';
    echo '<p>Sample data has been added successfully to your database.</p>';
    echo '</div>';
}
?>

        <div class="actions">
            <a href="admin_groups.php" class="btn btn-primary">
                <i class="fas fa-layer-group"></i>
                Go to Groups Management
            </a>
            <a href="admin_logs.php" class="btn btn-secondary">
                <i class="fas fa-history"></i>
                Go to Admin Logs
            </a>
            <a href="admin_dashboard.php" class="btn btn-secondary">
                <i class="fas fa-home"></i>
                Dashboard
            </a>
        </div>
    </div>
</body>
</html>
