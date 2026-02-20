<?php
require_once __DIR__ . "/backend/config.php";

// Create support_tickets table
$sql1 = "CREATE TABLE IF NOT EXISTS support_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    username VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    status ENUM('open', 'claimed', 'refused', 'done') DEFAULT 'open',
    ticket_code VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

// Create ticket_messages table
$sql2 = "CREATE TABLE IF NOT EXISTS ticket_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    sender_type ENUM('user', 'support') NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ticket_id (ticket_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

try {
    if($conn->query($sql1)) {
        echo "✓ جدول support_tickets تم إنشاؤه بنجاح<br>";
    }
    
    if($conn->query($sql2)) {
        echo "✓ جدول ticket_messages تم إنشاؤه بنجاح<br>";
    }
    
    echo "<br><strong>تم إنشاء جميع الجداول بنجاح!</strong><br>";
    echo "<a href='/create_ticket.php'>انتقل لإنشاء تذكرة</a>";
    
} catch(Exception $e) {
    echo "خطأ: " . $e->getMessage();
}
?>
