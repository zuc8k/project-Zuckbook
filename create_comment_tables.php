<?php
session_start();
require_once __DIR__ . "/backend/config.php";

if (!isset($_SESSION['user_id'])) {
    die("Not authenticated");
}

try {
    // Create comment_likes table
    $conn->query("
        CREATE TABLE IF NOT EXISTS comment_likes (
            id INT PRIMARY KEY AUTO_INCREMENT,
            comment_id INT NOT NULL,
            user_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_like (comment_id, user_id),
            FOREIGN KEY (comment_id) REFERENCES post_comments(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    // Create comment_replies table
    $conn->query("
        CREATE TABLE IF NOT EXISTS comment_replies (
            id INT PRIMARY KEY AUTO_INCREMENT,
            comment_id INT NOT NULL,
            user_id INT NOT NULL,
            content TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (comment_id) REFERENCES post_comments(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    // Create reply_likes table
    $conn->query("
        CREATE TABLE IF NOT EXISTS reply_likes (
            id INT PRIMARY KEY AUTO_INCREMENT,
            reply_id INT NOT NULL,
            user_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_like (reply_id, user_id),
            FOREIGN KEY (reply_id) REFERENCES comment_replies(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");

    echo "✓ All tables created successfully!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
