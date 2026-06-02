<?php
/**
 * Admin: Message Manager (MySQL)
 */
require_once __DIR__ . '/admin_db.php';
require_once dirname(__DIR__) . '/include/message_replies.php';
require_permission('messages.view');

$message_status = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    require_csrf();
    $id = (int)($_POST['id'] ?? 0);

    if ($_POST['action'] === 'delete') {
        $stmt = $db->prepare("DELETE FROM messages WHERE id = ?");
        if ($stmt->execute([$id])) {
            $message_status = "Message deleted successfully!";
        }
    } elseif ($_POST['action'] === 'mark_read') {
        $stmt = $db->prepare("UPDATE messages SET status = 'read' WHERE id = ?");
        if ($stmt->execute([$id])) {
            $message_status = "Message marked as read.";
        }
    } elseif ($_POST['action'] === 'reply') {
        require_permission('messages.reply');
        $body = trim($_POST['reply_body'] ?? '');
        if ($body === '') {
            $message_status = 'Reply cannot be empty.';
        } else {
            $stmt = $db->prepare('SELECT * FROM messages WHERE id = ? LIMIT 1');
            $stmt->execute([$id]);
            $targetMessage = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$targetMessage) {
                $message_status = 'Message not found.';
            } else {
                $result = sendCustomerReply($db, $targetMessage, current_admin_user_id(), $body);
                $message_status = $result['sent']
                    ? 'Reply sent and saved.'
                    : 'Reply saved. Email is not configured yet.';
            }
        }
    }
}

// Fetch all messages
$stmt = $db->query("
    SELECT m.*, v.model, v.year, b.name AS brand_name, br.name AS branch_name
    FROM messages m
    LEFT JOIN vehicles v ON m.vehicle_id = v.id
    LEFT JOIN brands b ON v.brand_id = b.id
    LEFT JOIN branches br ON m.branch_id = br.id
    ORDER BY m.created_at DESC
");
$messages = $stmt->fetchAll();

$messageIds = array_map(fn($row) => (int)$row['id'], $messages);
$repliesByMessage = [];
if (!empty($messageIds)) {
    $placeholders = implode(',', array_fill(0, count($messageIds), '?'));
    $replyStmt = $db->prepare("
        SELECT r.*, u.display_name, u.username
        FROM message_replies r
        LEFT JOIN admin_users u ON u.id = r.admin_user_id
        WHERE r.message_id IN ($placeholders)
        ORDER BY r.created_at ASC
    ");
    $replyStmt->execute($messageIds);
    foreach ($replyStmt->fetchAll(PDO::FETCH_ASSOC) as $reply) {
        $repliesByMessage[(int)$reply['message_id']][] = $reply;
    }
}

$pageTitle = 'Manage Messages';
require_once __DIR__ . '/include/header.php';
?>

<main class="admin-container">
    <div class="admin-content">
        <header>
            <h2 class="section-title">Customer Inquiries</h2>
        </header>

        <?php if ($message_status): ?>
            <div class="message success"><?php echo h($message_status); ?></div>
        <?php endif; ?>

        <?php if (empty($messages)): ?>
            <div class="empty-state">No customer inquiries yet.</div>
        <?php else: ?>
            <div class="admin-inbox">
                <?php foreach ($messages as $msg):
                    $is_unread = $msg['status'] === 'unread';
                    $replies = $repliesByMessage[(int)$msg['id']] ?? [];
                ?>
                    <article class="message-thread <?php echo $is_unread ? 'message-unread' : ''; ?>">
                        <header class="message-thread-head">
                            <div>
                                <span class="status-badge <?php echo $is_unread ? 'status-unread' : 'status-read'; ?>"><?php echo h(strtoupper($msg['status'])); ?></span>
                                <h3><?php echo h($msg['subject'] ?: 'Customer inquiry'); ?></h3>
                                <p><?php echo h($msg['name']); ?> / <?php echo h($msg['email']); ?> / <?php echo h($msg['created_at']); ?></p>
                            </div>
                            <div class="message-thread-meta">
                                <span><?php echo h(ucfirst($msg['inquiry_type'] ?? 'general')); ?></span>
                                <?php if (!empty($msg['model'])): ?><span><?php echo h($msg['brand_name'] . ' ' . $msg['model']); ?></span><?php endif; ?>
                                <?php if (!empty($msg['branch_name'])): ?><span><?php echo h($msg['branch_name']); ?></span><?php endif; ?>
                            </div>
                        </header>
                        <div class="message-bubble customer">
                            <?php echo nl2br(h($msg['message'])); ?>
                        </div>
                        <?php foreach ($replies as $reply): ?>
                            <div class="message-bubble admin-reply">
                                <small>
                                    <?php echo h($reply['display_name'] ?: $reply['username'] ?: 'Admin'); ?>
                                    / <?php echo h($reply['email_status']); ?>
                                    / <?php echo h($reply['created_at']); ?>
                                </small>
                                <p><?php echo nl2br(h($reply['reply_body'])); ?></p>
                                <?php if (!empty($reply['error_message'])): ?><em><?php echo h($reply['error_message']); ?></em><?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        <footer class="message-thread-actions">
                            <?php if ($is_unread): ?>
                                <form method="POST">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="mark_read">
                                    <input type="hidden" name="id" value="<?php echo (int)$msg['id']; ?>">
                                    <button type="submit" class="btn btn-sm">Mark read</button>
                                </form>
                            <?php endif; ?>
                            <?php if (admin_can('messages.reply')): ?>
                                <form method="POST" class="message-reply-form">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="action" value="reply">
                                    <input type="hidden" name="id" value="<?php echo (int)$msg['id']; ?>">
                                    <textarea name="reply_body" rows="3" placeholder="Write a clear reply to the customer" required></textarea>
                                    <button type="submit" class="btn btn-success btn-sm">Reply</button>
                                </form>
                            <?php endif; ?>
                            <form method="POST" onsubmit="return confirm('Delete this message?')">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo (int)$msg['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </footer>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php require_once __DIR__ . '/include/footer.php'; ?>
