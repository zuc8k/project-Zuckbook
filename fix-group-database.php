<?php
session_start();
require_once __DIR__ . "/backend/config.php";

// Only allow admins to run this script
if (!isset($_SESSION['user_id'])) {
    die("Login required");
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!in_array($user['role'], ['cofounder', 'mod', 'sup'])) {
    die("Admin access required");
}

echo "<h2>Fixing Group Database Tables</h2>";
echo "<pre>";

// Create group_posts table if not exists
$sql = "CREATE TABLE IF NOT EXISTS group_posts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    group_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    content TEXT,
    image VARCHAR(255),
    video VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_group_post_group FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
    CONSTRAINT fk_group_post_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_group (group_id),
    INDEX idx_user (user_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB";

if ($conn->query($sql)) {
    echo "✓ Created group_posts table\n";
} else {
    echo "✗ Error creating group_posts table: " . $conn->error . "\n";
}

// Check if privacy column exists in groups table
$result = $conn->query("SHOW COLUMNS FROM groups LIKE 'privacy'");
if ($result->num_rows == 0) {
    // Add privacy column
    $sql = "ALTER TABLE groups ADD COLUMN privacy ENUM('public','private','secret') DEFAULT 'public' AFTER category";
    if ($conn->query($sql)) {
        echo "✓ Added privacy column to groups table\n";
    } else {
        echo "✗ Error adding privacy column: " . $conn->error . "\n";
    }
} else {
    echo "✓ Privacy column already exists\n";
}

// Check if visibility column exists (old column name)
$result = $conn->query("SHOW COLUMNS FROM groups LIKE 'visibility'");
if ($result->num_rows > 0) {
    // Migrate data from visibility to privacy
    $sql = "UPDATE groups SET privacy = visibility WHERE visibility IS NOT NULL";
    if ($conn->query($sql)) {
        echo "✓ Migrated data from visibility to privacy column\n";
        
        // Drop old visibility column
        $sql = "ALTER TABLE groups DROP COLUMN visibility";
        if ($conn->query($sql)) {
            echo "✓ Dropped old visibility column\n";
        }
    }
}

// Update groups table to have proper default values
$sql = "UPDATE groups SET members_count = COALESCE(members_count, 0), posts_count = COALESCE(posts_count, 0)";
if ($conn->query($sql)) {
    echo "✓ Updated group counters\n";
}

// Create sample data if no groups exist
$result = $conn->query("SELECT COUNT(*) as count FROM groups");
$row = $result->fetch_assoc();
if ($row['count'] == 0) {
    echo "Creating sample groups...\n";
    
    // Get first user as owner
    $result = $conn->query("SELECT id FROM users ORDER BY id LIMIT 1");
    if ($result->num_rows > 0) {
        $owner = $result->fetch_assoc()['id'];
        
        // Insert sample groups
        $groups = [
            ['name' => 'Tech Enthusiasts', 'description' => 'Discussion about latest technology trends', 'privacy' => 'public'],
            ['name' => 'Gaming Community', 'description' => 'For gamers to share experiences and tips', 'privacy' => 'public'],
            ['name' => 'Music Lovers', 'description' => 'Share your favorite music and artists', 'privacy' => 'private'],
            ['name' => 'Sports Fans', 'description' => 'Talk about your favorite teams and players', 'privacy' => 'public'],
            ['name' => 'Book Club', 'description' => 'Monthly book discussions and recommendations', 'privacy' => 'secret']
        ];
        
        foreach ($groups as $group) {
            $stmt = $conn->prepare("INSERT INTO groups (name, description, owner_id, privacy, verification_status) VALUES (?, ?, ?, ?, 'pending')");
            $stmt->bind_param("ssis", $group['name'], $group['description'], $owner, $group['privacy']);
            if ($stmt->execute()) {
                $group_id = $stmt->insert_id;
                echo "  ✓ Created group: " . $group['name'] . "\n";
                
                // Add owner as admin member
                $stmt2 = $conn->prepare("INSERT INTO group_members (group_id, user_id, role, status) VALUES (?, ?, 'admin', 'approved')");
                $stmt2->bind_param("ii", $group_id, $owner);
                $stmt2->execute();
                
                // Update members count
                $conn->query("UPDATE groups SET members_count = 1 WHERE id = $group_id");
            }
        }
    }
}

echo "\n✅ Database fix completed successfully!\n";
echo "</pre>";
echo "<p><a href='/groups.php'>Go to Groups</a> | <a href='/admin_groups.php'>Admin Groups</a></p>";