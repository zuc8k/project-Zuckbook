-- =================================================
-- GROUP POSTS TABLE
-- =================================================
CREATE TABLE IF NOT EXISTS group_posts (
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
) ENGINE=InnoDB;

-- =================================================
-- UPDATE GROUPS TABLE (if needed)
-- =================================================
-- Check if privacy column exists, if not add it
-- ALTER TABLE groups ADD COLUMN IF NOT EXISTS privacy ENUM('public','private','secret') DEFAULT 'public' AFTER category;

-- =================================================
-- SAMPLE DATA FOR TESTING
-- =================================================
-- Insert sample groups if needed
INSERT IGNORE INTO groups (name, description, owner_id, privacy, verification_status) VALUES
('Tech Enthusiasts', 'Discussion about latest technology trends', 1, 'public', 'verified'),
('Gaming Community', 'For gamers to share experiences and tips', 1, 'public', 'pending'),
('Music Lovers', 'Share your favorite music and artists', 1, 'private', 'none');

-- Insert sample group members
INSERT IGNORE INTO group_members (group_id, user_id, role, status) VALUES
(1, 1, 'admin', 'approved'),
(2, 1, 'admin', 'approved'),
(3, 1, 'admin', 'approved');

-- Insert sample group posts
INSERT IGNORE INTO group_posts (group_id, user_id, content) VALUES
(1, 1, 'What do you think about the new AI developments?'),
(1, 1, 'Just got the new smartphone, amazing features!'),
(2, 1, 'Anyone playing the new RPG game?'),
(2, 1, 'Looking for teammates for competitive gaming'),
(3, 1, 'What''s your favorite album of the year?');