<?php
require_once __DIR__ . '/admin_db.php';
require_permission('customers.manage');
require_csrf();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_note' && admin_can('customer_notes.manage')) {
    $customerId = (int)($_POST['customer_id'] ?? 0);
    $note = trim($_POST['note'] ?? '');
    if ($customerId > 0 && $note !== '') {
        $stmt = $db->prepare('INSERT INTO customer_notes (customer_id, admin_user_id, note) VALUES (?, ?, ?)');
        $stmt->execute([$customerId, current_admin_user_id(), $note]);
        recordAdminAuditLog($db, 'customer_note.created', 'customer', $customerId, "Added customer note #{$customerId}");
        $message = 'Customer note added.';
    }
}

$customers = $db->query("
    SELECT
        c.*,
        COUNT(l.id) AS lead_count,
        MAX(COALESCE(l.last_activity_at, l.created_at)) AS last_activity,
        SUM(l.status IN ('new', 'contacted', 'appointment', 'reserved')) AS open_leads,
        (
            SELECT note
            FROM customer_notes cn
            WHERE cn.customer_id = c.id
            ORDER BY cn.created_at DESC
            LIMIT 1
        ) AS latest_note
    FROM customers c
    LEFT JOIN sales_leads l ON l.customer_id = c.id
    GROUP BY c.id
    ORDER BY COALESCE(MAX(l.last_activity_at), c.updated_at, c.created_at) DESC
")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Customers';
require_once __DIR__ . '/include/header.php';
?>

<main class="admin-container">
    <section class="admin-content">
        <header class="admin-page-head">
            <div>
                <p class="admin-kicker">Sales desk</p>
                <h2 class="section-title">Customers</h2>
            </div>
            <a href="leads.php" class="btn btn-success">View Leads</a>
        </header>

        <?php if ($message): ?><div class="message success"><?php echo h($message); ?></div><?php endif; ?>

        <?php if (empty($customers)): ?>
            <div class="empty-state admin-empty">
                <h3>No customers yet</h3>
                <p>Customers appear here when an inquiry or appointment is converted into a lead.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="admin-table-stack">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Open Leads</th>
                            <th>Total Leads</th>
                            <th>Last Activity</th>
                            <th>Timeline</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td data-label="Name"><?php echo h($customer['name']); ?></td>
                                <td data-label="Email"><?php echo h($customer['email']); ?></td>
                                <td data-label="Phone"><?php echo h($customer['phone'] ?: '-'); ?></td>
                                <td data-label="Open Leads"><?php echo (int)$customer['open_leads']; ?></td>
                                <td data-label="Total Leads"><?php echo (int)$customer['lead_count']; ?></td>
                                <td data-label="Last Activity"><?php echo h($customer['last_activity'] ?: $customer['created_at']); ?></td>
                                <td data-label="Timeline">
                                    <?php if (!empty($customer['latest_note'])): ?><small><?php echo h($customer['latest_note']); ?></small><?php endif; ?>
                                    <?php if (admin_can('customer_notes.manage')): ?>
                                        <form method="POST" class="admin-search-form">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="action" value="add_note">
                                            <input type="hidden" name="customer_id" value="<?php echo (int)$customer['id']; ?>">
                                            <input name="note" placeholder="Add note">
                                            <button type="submit" class="btn btn-sm">Note</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php require_once __DIR__ . '/include/footer.php'; ?>
