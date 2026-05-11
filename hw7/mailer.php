<?php
// mailer.php
// 不使用 Composer，也不依賴 PHPMailer，直接用 PHP socket 實作簡化版 SMTP。
// 這樣交作業時只要 XAMPP / Apache / PHP 有 openssl，就能連 Gmail SMTP。

require_once __DIR__ . '/functions.php';

function encode_mail_header(string $text): string
{
    return mb_encode_mimeheader($text, 'UTF-8', 'B', "\r\n");
}

function safe_mailbox(string $email, string $name = ''): string
{
    $email = trim($email);
    $name = trim($name);
    return $name === '' ? $email : encode_mail_header($name) . ' <' . $email . '>';
}

function normalize_newlines(string $text): string
{
    return str_replace(["\r\n", "\r"], "\n", $text);
}

function text_to_html(string $text): string
{
    return nl2br(h($text));
}

function build_email_message(string $toEmail, string $toName, string $subject, string $textBody, ?array $attachment = null): array
{
    $from = safe_mailbox(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
    $to = safe_mailbox($toEmail, $toName);
    $subjectEncoded = encode_mail_header($subject);
    $date = date('r');
    $host = parse_url('http://localhost/hw7', PHP_URL_HOST) ?: 'localhost';
    $messageId = '<' . bin2hex(random_bytes(16)) . '@' . $host . '>';

    $headers = [];
    $headers[] = 'From: ' . $from;
    $headers[] = 'To: ' . $to;
    $headers[] = 'Subject: ' . $subjectEncoded;
    $headers[] = 'Date: ' . $date;
    $headers[] = 'Message-ID: ' . $messageId;
    $headers[] = 'MIME-Version: 1.0';

    $textBody = normalize_newlines($textBody);
    $htmlBody = "<!DOCTYPE html>\n<html lang=\"zh-TW\">\n<head>\n<meta charset=\"UTF-8\">\n<title>" . h($subject) . "</title>\n</head>\n";
    $htmlBody .= "<body style=\"font-family:Arial,'Noto Sans TC',sans-serif;background:#f3f6ed;padding:24px;color:#243018;\">";
    $htmlBody .= "<div style=\"max-width:720px;margin:auto;background:#ffffff;border:4px solid #5b7f34;border-radius:14px;box-shadow:0 8px 0 #2e471b;padding:24px;\">";
    $htmlBody .= '<h2 style="margin-top:0;color:#2e5c1d">▣ ' . h(APP_NAME) . '</h2>';
    $htmlBody .= '<div style="line-height:1.8;font-size:15px">' . text_to_html($textBody) . '</div>';
    $htmlBody .= '<hr style="border:none;border-top:1px solid #d9e8c6;margin:24px 0">';
    $htmlBody .= '<p style="font-size:12px;color:#61724d">此信由 hw7 系統自動發送。若不是你操作，請忽略此信。</p>';
    $htmlBody .= '</div></body></html>';

    if ($attachment === null) {
        $altBoundary = 'ALT_' . bin2hex(random_bytes(12));
        $headers[] = 'Content-Type: multipart/alternative; boundary="' . $altBoundary . '"';

        $body = '';
        $body .= '--' . $altBoundary . "\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= str_replace("\n", "\r\n", $textBody) . "\r\n";
        $body .= '--' . $altBoundary . "\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= str_replace("\n", "\r\n", $htmlBody) . "\r\n";
        $body .= '--' . $altBoundary . "--\r\n";
    } else {
        $mixedBoundary = 'MIXED_' . bin2hex(random_bytes(12));
        $altBoundary = 'ALT_' . bin2hex(random_bytes(12));
        $headers[] = 'Content-Type: multipart/mixed; boundary="' . $mixedBoundary . '"';

        $body = '';
        $body .= '--' . $mixedBoundary . "\r\n";
        $body .= 'Content-Type: multipart/alternative; boundary="' . $altBoundary . "\"\r\n\r\n";
        $body .= '--' . $altBoundary . "\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= str_replace("\n", "\r\n", $textBody) . "\r\n";
        $body .= '--' . $altBoundary . "\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $body .= str_replace("\n", "\r\n", $htmlBody) . "\r\n";
        $body .= '--' . $altBoundary . "--\r\n";

        $fileData = chunk_split(base64_encode(file_get_contents($attachment['path'])));
        $fileName = str_replace(['"', "\r", "\n"], '', $attachment['name']);
        $mimeType = $attachment['mime'];

        $body .= '--' . $mixedBoundary . "\r\n";
        $body .= 'Content-Type: ' . $mimeType . '; name="' . $fileName . "\"\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n";
        $body .= 'Content-Disposition: attachment; filename="' . $fileName . "\"\r\n\r\n";
        $body .= $fileData . "\r\n";
        $body .= '--' . $mixedBoundary . "--\r\n";
    }

    $raw = implode("\r\n", $headers) . "\r\n\r\n" . $body;

    return [
        'to_email' => $toEmail,
        'subject' => $subject,
        'headers' => implode("\r\n", $headers),
        'body' => $body,
        'raw' => $raw,
    ];
}

function save_email_copy(string $toEmail, string $raw): string
{
    $dir = __DIR__ . '/storage/outbox';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    $safeEmail = preg_replace('/[^a-zA-Z0-9_.@-]/', '_', $toEmail);
    $file = $dir . '/' . date('Ymd_His') . '_' . $safeEmail . '_' . bin2hex(random_bytes(4)) . '.eml';
    file_put_contents($file, $raw);
    return $file;
}

function create_email_log(PDO $pdo, string $type, string $toEmail, string $toName, string $subject, string $body): int
{
    $stmt = $pdo->prepare('INSERT INTO email_logs (email_type, recipient_email, recipient_name, subject, body_text, status) VALUES (?, ?, ?, ?, ?, "queued")');
    $stmt->execute([$type, $toEmail, $toName, $subject, $body]);
    return (int)$pdo->lastInsertId();
}

function update_email_log(PDO $pdo, int $id, string $status, ?string $errorMessage, ?string $savedPath): void
{
    $stmt = $pdo->prepare('UPDATE email_logs SET status = ?, error_message = ?, saved_path = ?, sent_at = CASE WHEN ? = "sent" THEN NOW() ELSE sent_at END WHERE id = ?');
    $stmt->execute([$status, $errorMessage, $savedPath, $status, $id]);
}

class SimpleSmtpClient
{
    private $socket;

    public function send(string $fromEmail, string $toEmail, string $rawMessage): void
    {
        $this->connect();
        $this->command('EHLO localhost', [250]);

        if (SMTP_SECURE === 'tls') {
            $this->command('STARTTLS', [220]);
            $cryptoOk = stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if ($cryptoOk !== true) {
                throw new RuntimeException('STARTTLS 加密啟用失敗，請確認 PHP openssl extension 是否開啟。');
            }
            $this->command('EHLO localhost', [250]);
        }

        $this->command('AUTH LOGIN', [334]);
        $this->command(base64_encode(SMTP_USERNAME), [334]);
        $this->command(base64_encode(SMTP_PASSWORD), [235]);

        $this->command('MAIL FROM:<' . $fromEmail . '>', [250]);
        $this->command('RCPT TO:<' . $toEmail . '>', [250, 251]);
        $this->command('DATA', [354]);

        $this->write($this->dotStuff($rawMessage) . "\r\n.\r\n");
        $this->expect([250]);
        $this->command('QUIT', [221]);
        fclose($this->socket);
    }

    private function connect(): void
    {
        $target = (SMTP_SECURE === 'ssl' ? 'ssl://' : '') . SMTP_HOST . ':' . SMTP_PORT;
        $this->socket = @stream_socket_client($target, $errno, $errstr, SMTP_TIMEOUT);
        if (!$this->socket) {
            throw new RuntimeException("SMTP 連線失敗：{$errstr} ({$errno})");
        }
        stream_set_timeout($this->socket, SMTP_TIMEOUT);
        $this->expect([220]);
    }

    private function write(string $data): void
    {
        fwrite($this->socket, $data);
    }

    private function command(string $command, array $expectedCodes): string
    {
        $this->write($command . "\r\n");
        return $this->expect($expectedCodes);
    }

    private function expect(array $expectedCodes): string
    {
        $response = '';
        while (($line = fgets($this->socket, 515)) !== false) {
            $response .= $line;
            if (preg_match('/^([0-9]{3})\s/', $line, $m)) {
                $code = (int)$m[1];
                if (!in_array($code, $expectedCodes, true)) {
                    throw new RuntimeException('SMTP 回應錯誤：' . trim($response));
                }
                return $response;
            }
        }
        throw new RuntimeException('SMTP 沒有回應或連線中斷。');
    }

    private function dotStuff(string $message): string
    {
        $message = str_replace(["\r\n", "\r"], "\n", $message);
        $lines = explode("\n", $message);
        foreach ($lines as &$line) {
            if (isset($line[0]) && $line[0] === '.') {
                $line = '.' . $line;
            }
        }
        return implode("\r\n", $lines);
    }
}

function send_system_mail(PDO $pdo, string $type, string $toEmail, string $toName, string $subject, string $textBody, ?array $attachment = null): bool
{
    $logId = create_email_log($pdo, $type, $toEmail, $toName, $subject, $textBody);
    $mail = build_email_message($toEmail, $toName, $subject, $textBody, $attachment);
    $savedPath = null;

    if (MAIL_ALWAYS_SAVE_COPY || MAIL_DRIVER === 'log') {
        $savedPath = save_email_copy($toEmail, $mail['raw']);
    }

    try {
        if (MAIL_DRIVER === 'log') {
            update_email_log($pdo, $logId, 'sent', null, $savedPath);
            return true;
        }

        if (SMTP_USERNAME === '' || str_contains(SMTP_USERNAME, 'your_') || str_contains(SMTP_PASSWORD, 'your_')) {
            throw new RuntimeException('尚未設定 SMTP_USERNAME / SMTP_PASSWORD。請到 config.php 填入 Gmail 與應用程式密碼。');
        }

        $client = new SimpleSmtpClient();
        $client->send(MAIL_FROM_EMAIL, $toEmail, $mail['raw']);
        update_email_log($pdo, $logId, 'sent', null, $savedPath);
        return true;
    } catch (Throwable $e) {
        update_email_log($pdo, $logId, 'failed', $e->getMessage(), $savedPath);
        return false;
    }
}
