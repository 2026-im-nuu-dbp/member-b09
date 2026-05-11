<?php
// config.sample.php
// 這是安全範本。實際使用請複製成 config.php，再填入自己的 Gmail 與資料庫設定。

define('APP_NAME', 'hw7');
define('BASE_URL', 'http://localhost/hw7');
define('TIMEZONE', 'Asia/Taipei');

define('DB_HOST', 'localhost');
define('DB_NAME', 'minecraft_forum_smtp');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

define('MAIL_DRIVER', 'smtp');
define('MAIL_ALWAYS_SAVE_COPY', true);

define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USERNAME', '你的Gmail@gmail.com');
define('SMTP_PASSWORD', '你的16碼Gmail應用程式密碼_不要有空格');
define('SMTP_TIMEOUT', 20);

define('MAIL_FROM_EMAIL', SMTP_USERNAME);
define('MAIL_FROM_NAME', 'hw7 系統通知');

define('TEACHER_TEST_EMAIL', 'hw.pcchen@google.com');
define('TEACHER_TEST_NAME', 'PC Chen 老師');

define('MAX_POST_LENGTH', 10000);
define('MAX_IMAGE_SIZE', 3 * 1024 * 1024);
define('ALLOWED_IMAGE_MIME', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
