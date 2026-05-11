<?php
// config.php
// 集中管理網站、資料庫與 Gmail SMTP 設定。
// 注意：Gmail 應用程式密碼不要上傳到 GitHub，也不要貼給別人。

// ===== 網站基本設定 =====
define('APP_NAME', 'hw7 Minecraft 會員論壇');
define('BASE_URL', 'http://localhost/hw7');
define('TIMEZONE', 'Asia/Taipei');
date_default_timezone_set(TIMEZONE);

// ===== 資料庫設定 =====
define('DB_HOST', 'localhost');
define('DB_NAME', 'hw7');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ===== Gmail SMTP 設定 =====
// 這版只會在「註冊會員」時寄出驗證信，不保留一般通知寄信功能。
// Gmail SMTP 常用設定：smtp.gmail.com + 587 + STARTTLS。
// SMTP_PASSWORD 請填 Google「應用程式密碼」，不是 Gmail 登入密碼。
// Google 產生的 16 碼密碼若有空格，請移除空格後再貼上。
define('MAIL_DRIVER', 'smtp'); // smtp = 真正寄信；log = 本機只存 .eml 測試
define('MAIL_ALWAYS_SAVE_COPY', true);
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls'); // tls = STARTTLS 587；ssl = 465
define('SMTP_USERNAME', 'your_gmail@gmail.com');
define('SMTP_PASSWORD', 'your_16_digit_app_password_without_spaces');
define('SMTP_TIMEOUT', 20);

define('MAIL_FROM_EMAIL', SMTP_USERNAME);
define('MAIL_FROM_NAME', APP_NAME . ' 驗證中心');

// ===== 驗證信設定 =====
define('TOKEN_EXPIRE_HOURS', 24);

// ===== 上傳與限制 =====
define('MAX_POST_LENGTH', 10000);
define('MAX_IMAGE_SIZE', 3 * 1024 * 1024);
define('ALLOWED_IMAGE_MIME', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
