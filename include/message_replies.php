<?php
require_once __DIR__ . '/db.php';

function createMessageReply(PDO $db, int $messageId, ?int $adminUserId, string $body, string $status = 'draft', ?string $errorMessage = null): int
{
    $allowed = ['draft', 'queued', 'sent', 'failed'];
    if (!in_array($status, $allowed, true)) {
        $status = 'draft';
    }

    $stmt = $db->prepare('
        INSERT INTO message_replies (message_id, admin_user_id, reply_body, email_status, sent_at, error_message)
        VALUES (?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        $messageId,
        $adminUserId,
        trim($body),
        $status,
        $status === 'sent' ? date('Y-m-d H:i:s') : null,
        $errorMessage,
    ]);

    $replyId = (int)$db->lastInsertId();
    $db->prepare("UPDATE messages SET status = 'replied' WHERE id = ?")->execute([$messageId]);

    return $replyId;
}

function sendCustomerReply(PDO $db, array $message, ?int $adminUserId, string $body): array
{
    $to = trim((string)($message['email'] ?? ''));
    $subject = 'Re: ' . trim((string)($message['subject'] ?? 'Your inquiry'));
    $from = $_ENV['MAIL_FROM'] ?? NOREPLY_EMAIL;
    $headers = [
        'From: ' . $from,
        'Reply-To: ' . ($_ENV['CONTACT_EMAIL'] ?? CONTACT_EMAIL),
        'Content-Type: text/plain; charset=UTF-8',
    ];

    $mailEnabled = filter_var($_ENV['MAIL_ENABLED'] ?? 'false', FILTER_VALIDATE_BOOL);
    $sent = false;
    $error = null;

    if ($mailEnabled && filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $sent = @mail($to, $subject, $body, implode("\r\n", $headers));
        if (!$sent) {
            $error = 'PHP mail() did not accept the message. Configure server SMTP or approve a mail provider library.';
        }
    } else {
        $error = 'Email sending is disabled. Set MAIL_ENABLED=true after configuring server mail.';
    }

    $replyId = createMessageReply($db, (int)$message['id'], $adminUserId, $body, $sent ? 'sent' : 'failed', $error);

    return [
        'sent' => $sent,
        'reply_id' => $replyId,
        'error' => $error,
    ];
}
