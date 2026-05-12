# 作業 7：Minecraft 主題會員論壇 + Gmail SMTP 註冊驗證

這版已經把原本的一般寄信系統拔掉，只保留「註冊會員時自動寄出驗證信」功能。

## 功能流程

1. 使用者到 `register.php` 註冊。
2. 系統建立會員資料，`users.status = pending`。
3. 系統產生一次性 token，資料庫只存 `sha256(token)`。
4. 系統透過 Gmail SMTP 寄驗證信到使用者填入的 Email。
5. 使用者點擊 `activate.php?token=...`。
6. 系統將帳號改成 `active`，並寫入 `email_verified_at`。
7. 使用者才能登入、發文與留言。

## 安裝

1. 把專案放到：

```text
C:\xampp\htdocs\hw7
```

2. 匯入資料庫：

```bash
mysql -u root -p < news.sql
```

或用 phpMyAdmin 匯入 `news.sql`。

3. 修改 `config.php`：

```php
define('BASE_URL', 'http://localhost/hw7');
define('SMTP_USERNAME', '你的 Gmail');
define('SMTP_PASSWORD', '你的 16 碼 Google 應用程式密碼，不要有空格');
```

Gmail SMTP 設定使用：

```text
smtp.gmail.com
Port 587
STARTTLS
```

4. 確認 PHP 有開啟 `openssl` extension。

## 預設帳號

```text
帳號：admin
密碼：admin12345
```

## Demo 建議

1. 用新 Email 註冊。
2. 不點驗證信，直接登入，應該會被擋下。
3. 到信箱點擊驗證連結。
4. 再登入，應該可以進論壇。
5. 管理員到 `mailer_panel.php` 看驗證信寄送紀錄。

## 主要檔案

```text
config.php              Gmail SMTP 設定
register_process.php    註冊後建立 pending 帳號並寄驗證信
activate.php            處理驗證連結
login_process.php       擋下 pending / locked 帳號
mailer.php              SMTP 核心，只保留驗證信寄送
mailer_panel.php        驗證信紀錄，不再提供一般寄信表單
news.sql                完整資料庫
```
