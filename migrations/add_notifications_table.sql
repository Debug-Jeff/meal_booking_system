-- =====================================================
-- Migration: Add notifications table
-- Run this in TiDB / phpMyAdmin / MySQL CLI
-- =====================================================

CREATE TABLE IF NOT EXISTS `notifications` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `user_id`    INT NOT NULL,
  `type`       VARCHAR(50)  NOT NULL,
  `message`    VARCHAR(255) NOT NULL,
  `link`       VARCHAR(255) DEFAULT NULL,
  `is_read`    TINYINT(1)   DEFAULT 0,
  `created_at` TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Optional: index for fast unread-count queries
CREATE INDEX IF NOT EXISTS idx_notif_user_read ON `notifications` (`user_id`, `is_read`);
