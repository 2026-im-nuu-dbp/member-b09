<?php
// db.php
// 統一建立 PDO 連線，所有頁面都 require 這個檔案，避免每支程式重複寫連線設定。

require_once __DIR__ . '/config.php';

date_default_timezone_set(TIMEZONE);

$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    exit('資料庫連線失敗：' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}
