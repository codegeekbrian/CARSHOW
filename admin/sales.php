<?php
require_once __DIR__ . '/admin_db.php';
require_permission('sales.manage');

$summary = $db->query("
    SELECT
        SUM(status = 'reserved') AS reserved_count,
        SUM(status = 'won') AS won_count,
        SUM(status = 'lost') AS lost_count,
        SUM(status IN ('new', 'contacted', 'appointment')) AS active_count
    FROM sales_leads
")->fetch(PDO::FETCH_ASSOC) ?: [];

$salesRows = $db->query("
    SELECT
        l.*,
        c.name AS customer_name,
        c.email AS customer_email,
        v.model,
        v.year,
        v.price,
        b.name AS brand_name,
        br.name AS branch_name
    FROM sales_leads l
    JOIN customers c ON l.customer_id = c.id
    LEFT JOIN vehicles v ON l.vehicle_id = v.id
    LEFT JOIN brands b ON v.brand_id = b.id
    LEFT JOIN branches br ON l.branch_id = br.id
    WHERE l.status IN ('reserved', 'won', 'lost')
    ORDER BY l.updated_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Sales Desk';
require_once __DIR__ . '/include/header.php';
?>

<main class="admin-container">
    <section class="admin-content">
        <header class="admin-page-head">
            <div>
                <p class="admin-kicker">Control center</p>
                <h2 class="section-title">Sales Desk</h2>
            </div>
            <a href="leads.php" class="btn btn-success">Manage Leads</a>
        </header>

        <div class="stats-grid compact-stats">
            <article class="stat-card">
                <h3>Active Pipeline</h3>
                <div class="count"><?php echo (int)($summary['active_count'] ?? 0); ?></div>
            </article>
            <article class="stat-card">
                <h3>Reserved</h3>
                <div class="count"><?php echo (int)($summary['reserved_count'] ?? 0); ?></div>
            </article>
            <article class="stat-card">
                <h3>Won</h3>
                <div class="count"><?php echo (int)($summary['won_count'] ?? 0); ?></div>
            </article>
            <article class="stat-card">
                <h3>Lost</h3>
                <div class="count"><?php echo (int)($summary['lost_count'] ?? 0); ?></div>
            </article>
        </div>

        <?php if (empty($salesRows)): ?>
            <div class="empty-state admin-empty">
                <h3>No reserved or closed deals</h3>
                <p>Use Leads to reserve a vehicle or mark a sale as won.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="admin-table-stack">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Customer</th>
                            <th>Vehicle</th>
                            <th>Branch</th>
                            <th>Price</th>
                            <th>Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($salesRows as $row): ?>
                            <tr>
                                <td data-label="Status"><span class="status-badge status-<?php echo h($row['status']); ?>"><?php echo h(ucfirst($row['status'])); ?></span></td>
                                <td data-label="Customer">
                                    <strong><?php echo h($row['customer_name']); ?></strong><br>
                                    <small><?php echo h($row['customer_email']); ?></small>
                                </td>
                                <td data-label="Vehicle"><?php echo h(trim(($row['brand_name'] ?? '') . ' ' . ($row['model'] ?? '')) ?: '-'); ?></td>
                                <td data-label="Branch"><?php echo h($row['branch_name'] ?: '-'); ?></td>
                                <td data-label="Price"><?php echo $row['price'] !== null ? '$' . number_format((float)$row['price']) : 'Call for price'; ?></td>
                                <td data-label="Updated"><?php echo h($row['updated_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php require_once __DIR__ . '/include/footer.php'; ?>
