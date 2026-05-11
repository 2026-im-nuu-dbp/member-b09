<?php
// send_notification.php
// 一般通知寄信功能已移除。寄信只保留在 register_process.php 的註冊驗證流程。

require_once __DIR__ . '/functions.php';
start_session_once();
flash_set('error', '一般寄信功能已移除。系統只會在會員註冊時自動寄出驗證信。');
redirect('index.php');
