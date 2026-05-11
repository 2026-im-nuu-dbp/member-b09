# 作業 7：Minecraft 主題會員論壇 + Gmail SMTP 寄信系統

這份專案以老師提供的群組討論應用為基礎，加入：

1. 會員註冊、登入、登出。
2. 註冊欄位：帳號、密碼、Email、暱稱、喜歡顏色、大頭貼圖案。
3. 討論區貼文與留言。
4. 留言顯示登入者暱稱、大頭貼，背景使用會員喜歡的顏色。
5. 管理員可以新增、修改、刪除會員。
6. SMTP 寄信系統，支援 Gmail 應用程式密碼。
7. UI 以 Minecraft 主題設計，論壇列表參考 Dcard 網頁版的資訊流卡片結構。

---

## 一、檔案結構

```text
minecraft_forum_smtp/
├── assets/
│   └── style.css              # Minecraft 主題 UI
├── storage/
│   ├── outbox/                # 儲存 .eml 信件副本
│   └── uploads/               # 儲存寄信附件圖片
├── admin.php                  # 管理員會員管理頁
├── admin_user_delete.php      # 刪除會員
├── admin_user_save.php        # 新增 / 修改會員
├── config.php                 # 正式設定檔，填 Gmail SMTP 驗證碼
├── config.sample.php          # 安全設定範本
├── db.php                     # PDO 資料庫連線
├── functions.php              # 共用函式、登入檢查、CSRF、UI header/footer
├── index.php                  # 論壇首頁 / Dcard 風格貼文列表
├── login.php                  # 登入頁
├── login_process.php          # 登入處理
├── logout.php                 # 登出
├── mailer.php                 # SMTP 寄信核心
├── mailer_panel.php           # 寄信系統頁面
├── news.sql                   # 完整資料庫匯出檔
├── post.php                   # 新增貼文
├── post_reply.php             # 新增留言
├── profile.php                # 個人資料修改
├── register.php               # 註冊頁
├── register_process.php       # 註冊處理
├── send_notification.php      # 寄信處理
└── show_news.php              # 單篇貼文與留言頁
```

---

## 二、安裝步驟

### 1. 放到 XAMPP 網站目錄

把整個資料夾放到：

```text
C:\xampp\htdocs\minecraft_forum_smtp
```

### 2. 匯入資料庫

開啟 phpMyAdmin，匯入 `news.sql`。

或使用 MySQL CLI：

```bash
mysql -u root -p < news.sql
```

### 3. 修改 config.php

確認資料庫設定：

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'minecraft_forum_smtp');
define('DB_USER', 'root');
define('DB_PASS', '');
```

設定網站網址：

```php
define('BASE_URL', 'http://localhost/hw7');
```

---

## 三、Gmail SMTP 設定

Gmail SMTP 使用：

```php
define('MAIL_DRIVER', 'smtp');
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_USERNAME', '你的Gmail@gmail.com');
define('SMTP_PASSWORD', '你的16碼Gmail應用程式密碼_不要有空格');
define('MAIL_FROM_EMAIL', SMTP_USERNAME);
```

注意：

1. `SMTP_PASSWORD` 要填 Google 應用程式密碼，不是 Gmail 登入密碼。
2. Google 顯示的 16 碼密碼可能長這樣：`abcd efgh ijkl mnop`。
3. 貼進 config.php 時請拿掉空格，變成：`abcdefghijklmnop`。
4. PHP 需要啟用 `openssl` extension，Gmail 的 STARTTLS 才能正常加密。

如果本機還沒設定好 Gmail，可以先改成：

```php
define('MAIL_DRIVER', 'log');
```

這樣系統不會真的寄出，而是把信件存成 `.eml` 到：

```text
storage/outbox
```

---

## 四、預設帳號

匯入 `news.sql` 後有 3 個測試帳號，密碼都是：

```text
admin12345
```

| 帳號 | 角色 | 說明 |
|---|---|---|
| admin | admin | 管理員，可以管理會員與群發信 |
| steve01 | user | 一般會員 |
| creeper01 | user | 一般會員 |

---

## 五、Demo 流程建議

1. 用 `steve01 / admin12345` 登入。
2. 修改個人資料：暱稱、喜歡顏色、大頭貼。
3. 回論壇發一篇貼文。
4. 到貼文頁新增留言，展示留言背景色與大頭貼。
5. 登出後用 `admin / admin12345` 登入。
6. 進入「管理員」頁，新增、修改、刪除會員。
7. 進入「寄信系統」頁：
   - 單寄一封測試信。
   - 或勾選「寄給所有啟用會員」，系統會強制加入 `hw.pcchen@google.com`。
8. 到下方寄信紀錄查看成功或失敗原因。

---

## 六、教授可能會問的問題

### Q1：為什麼貼文不用手動輸入作者？

因為發文和留言都必須登入，系統可以從 `$_SESSION['user_id']` 找到目前會員。這樣可以避免使用者假冒別人的名稱。

### Q2：密碼怎麼保護？

註冊時使用 `password_hash()` 儲存密碼雜湊，登入時用 `password_verify()` 驗證，不會在資料庫存明文密碼。

### Q3：怎麼避免表單被偽造送出？

每個重要表單都加上 CSRF token，送出時會用 `verify_csrf()` 檢查。

### Q4：SMTP 為什麼不用 PHP mail()？

`mail()` 需要主機設定 sendmail 或 SMTP relay，本機 XAMPP 常常不能直接寄。SMTP 直接登入 Gmail 寄信，demo 和除錯比較明確。

### Q5：為什麼 Gmail 要用應用程式密碼？

Gmail SMTP 需要認證。若帳號啟用兩步驟驗證，第三方程式通常使用應用程式密碼登入 SMTP。

---

## 七、Git commit 建議

每位組員至少 commit 一次，可以這樣分：

```text
組員 A：完成會員註冊登入
組員 B：完成論壇貼文與留言
組員 C：完成 SMTP 寄信系統
組員 D：完成 Minecraft UI 與管理員頁面
```

範例指令：

```bash
git add .
git commit -m "Add member login system"
git push
```
