-- news.sql
-- 作業 7：Minecraft 主題會員論壇 + Gmail SMTP 寄信系統
-- 匯入方式：mysql -u root -p < news.sql

CREATE DATABASE IF NOT EXISTS hw7
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE hw7;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS email_logs;
DROP TABLE IF EXISTS replies;
DROP TABLE IF EXISTS news;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    nickname VARCHAR(40) NOT NULL,
    favorite_color VARCHAR(20) NOT NULL DEFAULT '#c8e6a0',
    avatar VARCHAR(30) NOT NULL DEFAULT 'steve',
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    status ENUM('active', 'locked') NOT NULL DEFAULT 'active',
    favorite_playstyle VARCHAR(50) DEFAULT 'builder',
    favorite_biomes VARCHAR(255),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_users_username (username),
    UNIQUE KEY uk_users_email (email),
    KEY idx_users_role (role),
    KEY idx_users_status (status),
    KEY idx_users_playstyle (favorite_playstyle)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_news_created_at (created_at),
    INDEX idx_news_user_id (user_id),
    CONSTRAINT fk_news_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE replies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    news_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_replies_news_id (news_id),
    INDEX idx_replies_user_id (user_id),
    CONSTRAINT fk_replies_news
        FOREIGN KEY (news_id) REFERENCES news(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_replies_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE email_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email_type ENUM('direct', 'notification', 'test') NOT NULL DEFAULT 'direct',
    recipient_email VARCHAR(255) NOT NULL,
    recipient_name VARCHAR(100) NULL,
    subject VARCHAR(255) NOT NULL,
    body_text MEDIUMTEXT NOT NULL,
    status ENUM('queued', 'sent', 'failed') NOT NULL DEFAULT 'queued',
    error_message TEXT NULL,
    saved_path VARCHAR(500) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sent_at DATETIME NULL,
    INDEX idx_email_logs_status (status),
    INDEX idx_email_logs_type (email_type),
    INDEX idx_email_logs_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 預設管理員：帳號 admin，密碼 admin12345
-- Demo 後可以登入後台修改密碼。
INSERT INTO users (username, email, password_hash, nickname, favorite_color, avatar, role, status) VALUES
('admin', 'admin@example.com', '$2y$12$GnCAUWbU1lg8TPL7tOfSveefouQDlq627dUJfbp8Bvp.KttKwme2.', 'Server OP', '#90caf9', 'diamond', 'admin', 'active'),
('steve01', 'steve01@example.com', '$2y$12$GnCAUWbU1lg8TPL7tOfSveefouQDlq627dUJfbp8Bvp.KttKwme2.', '草原史蒂夫', '#c8e6a0', 'steve', 'user', 'active'),
('creeper01', 'creeper01@example.com', '$2y$12$GnCAUWbU1lg8TPL7tOfSveefouQDlq627dUJfbp8Bvp.KttKwme2.', '安靜苦力怕', '#a5d6a7', 'creeper', 'user', 'active');

INSERT INTO news (title, content, user_id) VALUES
('【公告】hw7 伺服器開張', '這是一個 Minecraft 主題的 Dcard 風格論壇 Demo。\n會員登入後可以發文、留言，留言會顯示大頭貼與個人喜歡的顏色。', 1),
('有人知道 SMTP 設定要怎麼測嗎？', '我想用 Gmail SMTP 寄出系統通知，目前設定在 config.php。\n如果 MAIL_DRIVER 改成 log，會先把信件存到 storage/outbox。', 2);

INSERT INTO replies (news_id, user_id, content) VALUES
(1, 2, '畫面有方塊感，demo 時很好解釋會員資料怎麼套進留言。'),
(1, 3, '嘶嘶嘶...留言背景色有出現。'),
(2, 1, '先用 log 模式測 UI，再填 Gmail 應用程式密碼切到 smtp。');
