-- migration_add_email_verification.sql
-- 如果你不想重建資料庫，請在 phpMyAdmin 裡執行這份升級 SQL。

USE hw7;

ALTER TABLE users
  MODIFY status ENUM('pending', 'active', 'locked') NOT NULL DEFAULT 'pending';

ALTER TABLE users
  ADD COLUMN email_verified_at DATETIME NULL AFTER status;

UPDATE users
SET email_verified_at = NOW()
WHERE status = 'active' AND email_verified_at IS NULL;

CREATE TABLE IF NOT EXISTS verification_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token_hash CHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    requested_ip VARCHAR(45) NULL,
    requested_ua VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_verification_tokens_hash (token_hash),
    KEY idx_verification_tokens_user (user_id),
    KEY idx_verification_tokens_active (token_hash, used_at, expires_at),
    CONSTRAINT fk_verification_tokens_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE email_logs
  MODIFY email_type ENUM('direct', 'notification', 'test', 'activation') NOT NULL DEFAULT 'activation';
