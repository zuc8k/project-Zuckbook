-- ============================================
-- ZuckBook Enterprise Clean Install
-- FULL SOCIAL + GROUPS + REALTIME + SUPPORT
-- CLEAN VERSION (NO ERRORS)
-- ============================================

DROP DATABASE IF EXISTS zuckbook;

CREATE DATABASE zuckbook
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE zuckbook;

-- =================================================
-- USERS
-- =================================================
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    username VARCHAR(50) UNIQUE,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    profile_image VARCHAR(255),
    cover_image VARCHAR(255),
    cover_position INT DEFAULT 50,
    bio TEXT DEFAULT NULL,
    tagline VARCHAR(255) DEFAULT NULL,
    job_title VARCHAR(100) DEFAULT NULL,
    city VARCHAR(100) DEFAULT NULL,
    from_city VARCHAR(100) DEFAULT NULL,
    relationship_status VARCHAR(50) DEFAULT NULL,
    is_online TINYINT(1) DEFAULT 0,
    last_seen DATETIME,
    role ENUM('user','support','mod','sup','cofounder') DEFAULT 'user',
    is_verified TINYINT(1) DEFAULT 0,
    verified_type ENUM('none','user','group','gold') DEFAULT 'none',
    coins BIGINT DEFAULT 0,
    is_banned TINYINT(1) DEFAULT 0,
    ban_expires_at DATETIME,
    timeout_expires_at DATETIME,
    is_deleted TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_online (is_online),
    INDEX idx_role (role)
) ENGINE=InnoDB;

-- =================================================
-- FRIENDS
-- =================================================
CREATE TABLE friends (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sender_id BIGINT UNSIGNED NOT NULL,
    receiver_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pending','accepted','declined','blocked') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_friend (sender_id,receiver_id),
    CONSTRAINT fk_friend_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_friend_receiver FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =================================================
-- GROUPS
-- =================================================
CREATE TABLE groups (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    cover_image VARCHAR(255),
    category VARCHAR(100),
    owner_id BIGINT UNSIGNED NOT NULL,
    privacy ENUM('public','private','secret') DEFAULT 'public',
    members_count INT DEFAULT 0,
    posts_count INT DEFAULT 0,
    verification_status ENUM('none','pending','verified','rejected') DEFAULT 'none',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_group_owner FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE group_members (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    group_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    role ENUM('member','moderator','admin','owner') DEFAULT 'member',
    status ENUM('pending','approved','rejected','blocked') DEFAULT 'approved',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_member (group_id,user_id),
    CONSTRAINT fk_group_member_group FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
    CONSTRAINT fk_group_member_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =================================================
-- POSTS
-- =================================================
CREATE TABLE posts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    content TEXT,
    image VARCHAR(255),
    video VARCHAR(255),
    privacy ENUM('public','friends','private') DEFAULT 'public',
    reactions_count INT DEFAULT 0,
    comments_count INT DEFAULT 0,
    shares_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FULLTEXT KEY ft_content (content),
    CONSTRAINT fk_post_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE post_comments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    content TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_comment_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    CONSTRAINT fk_comment_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =================================================
-- REACTIONS
-- =================================================
CREATE TABLE post_reactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    reaction ENUM('like','love','haha','wow','sad','angry') DEFAULT 'like',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_reaction (post_id,user_id),
    CONSTRAINT fk_reaction_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
    CONSTRAINT fk_reaction_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =================================================
-- MESSENGER
-- =================================================
CREATE TABLE conversations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_one BIGINT UNSIGNED NOT NULL,
    user_two BIGINT UNSIGNED NOT NULL,
    last_message_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_chat (user_one,user_two),
    CONSTRAINT fk_conv_user1 FOREIGN KEY (user_one) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_conv_user2 FOREIGN KEY (user_two) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conversation_id BIGINT UNSIGNED NOT NULL,
    sender_id BIGINT UNSIGNED NOT NULL,
    message TEXT,
    image VARCHAR(255),
    video VARCHAR(255),
    voice VARCHAR(255),
    seen TINYINT(1) DEFAULT 0,
    delivered TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_seen (seen),
    CONSTRAINT fk_msg_conversation FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
    CONSTRAINT fk_msg_sender FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =================================================
-- SUPPORT SYSTEM
-- =================================================
CREATE TABLE support_tickets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    username VARCHAR(100),
    email VARCHAR(150),
    status ENUM('open','claimed','refused','done') DEFAULT 'open',
    claimed_by BIGINT UNSIGNED NULL,
    ticket_code VARCHAR(50) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ticket_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_ticket_claimed FOREIGN KEY (claimed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE ticket_messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id BIGINT UNSIGNED NOT NULL,
    sender_type ENUM('user','support') DEFAULT 'user',
    message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ticket_msg FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =================================================
-- NOTIFICATIONS
-- =================================================
CREATE TABLE notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    type VARCHAR(50),
    message TEXT,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_read (is_read),
    CONSTRAINT fk_notification_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =================================================
-- SUBSCRIPTION PLANS
-- =================================================
CREATE TABLE subscription_plans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    monthly_price DECIMAL(10, 2) NOT NULL,
    yearly_price DECIMAL(10, 2) NOT NULL,
    features JSON,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Insert default subscription plans
INSERT INTO subscription_plans (name, monthly_price, yearly_price, features) VALUES
('Basic', 9.99, 99.99, '["Verified badge", "Priority support", "Profile boost"]'),
('Pro', 19.99, 199.99, '["Verified badge", "Priority support", "Profile boost", "Impersonation protection", "Exclusive features"]'),
('Premium', 29.99, 299.99, '["Verified badge", "Priority support", "Profile boost", "Impersonation protection", "Exclusive features", "Advanced analytics"]');

-- =================================================
-- USER SUBSCRIPTIONS
-- =================================================
CREATE TABLE user_subscriptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    plan_id BIGINT UNSIGNED NOT NULL,
    billing_type ENUM('monthly', 'yearly') DEFAULT 'monthly',
    amount DECIMAL(10, 2) NOT NULL,
    status ENUM('active', 'cancelled', 'expired') DEFAULT 'active',
    expires_at DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_sub_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_sub_plan FOREIGN KEY (plan_id) REFERENCES subscription_plans(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =================================================
-- ADMIN LOGS
-- =================================================
CREATE TABLE admin_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id BIGINT UNSIGNED NOT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_admin_log_user FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_admin (admin_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- =================================================
-- TRIGGER
-- =================================================
DELIMITER $$

CREATE TRIGGER reaction_insert
AFTER INSERT ON post_reactions
FOR EACH ROW
BEGIN
    UPDATE posts
    SET reactions_count = reactions_count + 1
    WHERE id = NEW.post_id;
END$$

DELIMITER ;
