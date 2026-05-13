<?php
/**
 * Admin: Message Manager
 */
require_once __DIR__ . '/admin_db.php';
require_auth();

$messages_file = dirname(__DIR__) . '/include/storage/messages.json';
$messages = file_exists($messages_file) ? json_decode(file_get_contents($messages_file), true) : [];

$message_status = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'delete') {
        $id = $_POST['id'] ?? '';
        $messages = array_filter($messages, fn($m) => $m['id'] !== $id);
        file_put_contents($messages_file, json_encode(array_values($messages), JSON_PRETTY_PRINT));
        $message_status = "Message deleted successfully!";
    }
}

$pageTitle = 'Manage Messages';
require_once __DIR__ . '/include/header.php';
?>

<div class="admin-container">
    <div class="admin-content">
        <h2 class="section-title">Customer Inquiries</h2>

        <?php if ($message_status): ?>
            <div class="message success"><?php echo htmlspecialchars($message_status); ?></div>
        <?php endif; ?>

        <?php if (empty($messages)): ?>
            <p>No messages found.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>From</th>
                        <th>Subject</th>
                        <th>Message</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($messages as $msg): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($msg['date'] ?? '-'); ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($msg['name']); ?></strong><br>
                            <small><?php echo htmlspecialchars($msg['email']); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($msg['subject']); ?></td>
                        <td>
                            <div style="max-width: 300px; max-height: 100px; overflow-y: auto; font-size: 0.85rem;">
                                <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                            </div>
                        </td>
                        <td>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this message?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $msg['id']; ?>">
                                <button type="submit" class="btn btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/include/footer.php'; ?>
