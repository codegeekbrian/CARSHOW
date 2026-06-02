<?php
require_once __DIR__ . '/admin_db.php';
require_permission('dashboard.view');

function countValue(PDO $db, string $sql): int
{
    return (int)$db->query($sql)->fetchColumn();
}

$metrics = [
    'available' => countValue($db, "SELECT COUNT(*) FROM vehicles WHERE is_inventory = 1 AND status = 'available'"),
    'reserved' => countValue($db, "SELECT COUNT(*) FROM vehicles WHERE is_inventory = 1 AND status = 'reserved'"),
    'sold' => countValue($db, "SELECT COUNT(*) FROM vehicles WHERE is_inventory = 1 AND status = 'sold'"),
    'leads' => countValue($db, "SELECT COUNT(*) FROM sales_leads WHERE status IN ('new', 'contacted', 'appointment', 'reserved')"),
    'appointments' => countValue($db, "SELECT COUNT(*) FROM appointments WHERE status IN ('new', 'confirmed')"),
    'messages' => countValue($db, "SELECT COUNT(*) FROM messages WHERE status = 'unread'"),
    'users' => countValue($db, "SELECT COUNT(*) FROM users"),
    'uploads' => countValue($db, "SELECT COUNT(*) FROM uploaded_media"),
    'audit' => countValue($db, "SELECT COUNT(*) FROM admin_audit_logs"),
    'trade_ins' => countValue($db, "SELECT COUNT(*) FROM trade_in_requests WHERE status IN ('new', 'reviewing', 'valued')"),
    'finance_requests' => countValue($db, "SELECT COUNT(*) FROM finance_requests WHERE status IN ('new', 'reviewing')"),
    'followups' => countValue($db, "SELECT COUNT(*) FROM sales_leads WHERE next_follow_up_at IS NOT NULL AND next_follow_up_at <= DATE_ADD(NOW(), INTERVAL 2 DAY) AND status IN ('new', 'contacted', 'appointment', 'reserved')"),
];

$todayAppointments = $db->query("
    SELECT a.*, v.model, b.name AS brand_name, br.name AS branch_name
    FROM appointments a
    JOIN vehicles v ON a.vehicle_id = v.id
    JOIN brands b ON v.brand_id = b.id
    LEFT JOIN branches br ON a.branch_id = br.id
    WHERE a.status IN ('new', 'confirmed')
    ORDER BY COALESCE(a.preferred_date, DATE(a.created_at)), a.preferred_time, a.created_at DESC
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

$hotLeads = $db->query("
    SELECT l.*, c.name AS customer_name, v.model, b.name AS brand_name
    FROM sales_leads l
    JOIN customers c ON l.customer_id = c.id
    LEFT JOIN vehicles v ON l.vehicle_id = v.id
    LEFT JOIN brands b ON v.brand_id = b.id
    WHERE l.status IN ('new', 'contacted', 'appointment', 'reserved')
    ORDER BY FIELD(l.priority, 'high', 'normal', 'low'), COALESCE(l.last_activity_at, l.created_at) DESC
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

$inventoryHealth = [
    'missing_price' => countValue($db, "SELECT COUNT(*) FROM vehicles WHERE is_inventory = 1 AND (price IS NULL OR price <= 0)"),
    'missing_image' => countValue($db, "SELECT COUNT(*) FROM vehicles WHERE is_inventory = 1 AND (image_url IS NULL OR image_url = '')"),
    'featured' => countValue($db, "SELECT COUNT(*) FROM vehicles WHERE is_inventory = 1 AND is_featured = 1"),
    'uploads' => $metrics['uploads'],
];

$attentionCards = [
    [
        'label' => 'Leads need follow-up',
        'count' => countValue($db, "SELECT COUNT(*) FROM sales_leads WHERE next_follow_up_at IS NULL AND status IN ('new', 'contacted', 'appointment', 'reserved')"),
        'href' => admin_can('sales.manage') ? 'leads.php' : null,
    ],
    [
        'label' => 'Reserved over 7 days',
        'count' => countValue($db, "
            SELECT COUNT(*)
            FROM vehicles v
            WHERE v.is_inventory = 1
              AND v.status = 'reserved'
              AND EXISTS (
                  SELECT 1
                  FROM vehicle_status_history h
                  WHERE h.vehicle_id = v.id
                    AND h.new_status = 'reserved'
                    AND h.created_at <= DATE_SUB(NOW(), INTERVAL 7 DAY)
              )
        "),
        'href' => admin_can('inventory.manage') ? 'cars.php' : null,
    ],
    [
        'label' => 'Unread messages',
        'count' => $metrics['messages'],
        'href' => admin_can('messages.view') ? 'messages.php' : null,
    ],
    [
        'label' => 'Inventory data gaps',
        'count' => countValue($db, "SELECT COUNT(*) FROM vehicles WHERE is_inventory = 1 AND ((price IS NULL OR price <= 0) OR (image_url IS NULL OR image_url = ''))"),
        'href' => admin_can('inventory.manage') ? 'cars.php' : null,
    ],
];

$brandDistribution = $db->query("
    SELECT b.name AS brand_name, COUNT(v.id) AS count
    FROM brands b
    JOIN vehicles v ON b.id = v.brand_id
    WHERE v.is_inventory = 1
    GROUP BY b.id, b.name
    ORDER BY count DESC
    LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);

$recentAudit = $db->query("
    SELECT l.*, u.username, u.display_name
    FROM admin_audit_logs l
    LEFT JOIN admin_users u ON u.id = l.admin_user_id
    ORDER BY l.created_at DESC
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);
$brandMax = max(array_map(fn($row) => (int)$row['count'], $brandDistribution ?: [['count' => 1]]));

$salesTrendRows = $db->query("
    SELECT DATE(updated_at) AS sold_date, COUNT(*) AS count
    FROM sales_leads
    WHERE status = 'won'
      AND updated_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY)
    GROUP BY DATE(updated_at)
")->fetchAll(PDO::FETCH_KEY_PAIR);
$salesTrend = [];
for ($day = 29; $day >= 0; $day--) {
    $date = date('Y-m-d', strtotime("-{$day} days"));
    $salesTrend[] = [
        'date' => $date,
        'count' => (int)($salesTrendRows[$date] ?? 0),
    ];
}
$salesTrendMax = max(array_column($salesTrend, 'count') ?: [1]);

$topVehicles = $db->query("
    SELECT v.model, b.name AS brand_name, COUNT(a.id) AS views
    FROM analytics a
    JOIN vehicles v ON a.target_id = v.id
    JOIN brands b ON v.brand_id = b.id
    WHERE a.type = 'view' AND a.target_id IS NOT NULL
    GROUP BY a.target_id, v.model, b.name
    ORDER BY views DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/include/header.php';
?>

<main class="dashboard-container control-center">
    <section class="admin-hero">
        <div>
            <p class="admin-kicker">Control center</p>
            <h2>Business overview</h2>
            <p>Track vehicles, users, buyer intent, messages, staff actions, and operational health from one place.</p>
        </div>
        <div class="admin-hero-actions">
            <?php if (admin_can('sales.manage')): ?><a href="leads.php" class="btn btn-success">Open Leads</a><?php endif; ?>
            <?php if (admin_can('inventory.manage')): ?><a href="cars.php" class="btn">Inventory</a><?php endif; ?>
        </div>
    </section>

    <section class="stats-grid compact-stats" aria-label="Business metrics">
        <article class="stat-card"><h3>Available</h3><div class="count"><?php echo $metrics['available']; ?></div></article>
        <article class="stat-card"><h3>Reserved</h3><div class="count"><?php echo $metrics['reserved']; ?></div></article>
        <article class="stat-card"><h3>Sold</h3><div class="count"><?php echo $metrics['sold']; ?></div></article>
        <article class="stat-card"><h3>Open Leads</h3><div class="count"><?php echo $metrics['leads']; ?></div></article>
        <article class="stat-card"><h3>Appointments</h3><div class="count"><?php echo $metrics['appointments']; ?></div></article>
        <article class="stat-card"><h3>Unread</h3><div class="count"><?php echo $metrics['messages']; ?></div></article>
        <article class="stat-card"><h3>Users</h3><div class="count"><?php echo $metrics['users']; ?></div></article>
        <article class="stat-card"><h3>Uploads</h3><div class="count"><?php echo $metrics['uploads']; ?></div></article>
        <article class="stat-card"><h3>Audit</h3><div class="count"><?php echo $metrics['audit']; ?></div></article>
        <article class="stat-card"><h3>Trade-ins</h3><div class="count"><?php echo $metrics['trade_ins']; ?></div></article>
        <article class="stat-card"><h3>Finance</h3><div class="count"><?php echo $metrics['finance_requests']; ?></div></article>
        <article class="stat-card"><h3>Follow-ups</h3><div class="count"><?php echo $metrics['followups']; ?></div></article>
    </section>

    <section class="dashboard-grid control-grid admin-signal-grid" aria-label="Attention required">
        <article class="admin-panel admin-attention-panel">
            <div class="panel-head">
                <h3>Attention required</h3>
                <span class="text-muted">Actionable signals</span>
            </div>
            <div class="attention-grid">
                <?php foreach ($attentionCards as $card): ?>
                    <div class="attention-card">
                        <strong><?php echo (int)$card['count']; ?></strong>
                        <span><?php echo h($card['label']); ?></span>
                        <?php if ($card['href']): ?><a href="<?php echo h($card['href']); ?>">Open</a><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>

        <article class="admin-panel admin-sales-trend-panel">
            <div class="panel-head">
                <h3>Sales trend</h3>
                <span class="text-muted">Last 30 days</span>
            </div>
            <div class="sales-trend-chart" aria-label="Sales won over the last 30 days">
                <?php foreach ($salesTrend as $point): ?>
                    <?php $level = (int)$point['count'] > 0 && $salesTrendMax > 0 ? max(1, min(10, (int)ceil(((int)$point['count'] / $salesTrendMax) * 10))) : 0; ?>
                    <span class="trend-level-<?php echo $level; ?>" title="<?php echo h($point['date'] . ': ' . $point['count'] . ' sales'); ?>">
                        <small><?php echo (int)$point['count']; ?></small>
                    </span>
                <?php endforeach; ?>
            </div>
        </article>
    </section>

    <section class="dashboard-grid control-grid">
        <article class="admin-panel">
            <div class="panel-head">
                <h3>Today and next appointments</h3>
                <?php if (admin_can('appointments.manage')): ?><a href="appointments.php">View all</a><?php endif; ?>
            </div>
            <?php if (empty($todayAppointments)): ?>
                <p class="text-muted">No open appointments.</p>
            <?php else: ?>
                <?php foreach ($todayAppointments as $appointment): ?>
                    <div class="admin-mini-row">
                        <span>
                            <strong><?php echo h($appointment['customer_name']); ?></strong>
                            <small><?php echo h($appointment['brand_name'] . ' ' . $appointment['model']); ?> / <?php echo h($appointment['branch_name'] ?: 'Any branch'); ?></small>
                        </span>
                        <span class="mini-date"><?php echo h(($appointment['preferred_date'] ?: 'Flexible') . ' ' . ($appointment['preferred_time'] ?: '')); ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </article>

        <article class="admin-panel">
            <div class="panel-head">
                <h3>Hot leads</h3>
                <?php if (admin_can('sales.manage')): ?><a href="leads.php">Manage</a><?php endif; ?>
            </div>
            <?php if (empty($hotLeads)): ?>
                <p class="text-muted">No active leads yet.</p>
            <?php else: ?>
                <?php foreach ($hotLeads as $lead): ?>
                    <div class="admin-mini-row">
                        <span>
                            <strong><?php echo h($lead['customer_name']); ?></strong>
                            <small><?php echo h(trim(($lead['brand_name'] ?? '') . ' ' . ($lead['model'] ?? '')) ?: 'No vehicle linked'); ?></small>
                        </span>
                        <span class="status-badge status-<?php echo h($lead['status']); ?>"><?php echo h(ucfirst($lead['status'])); ?></span>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </article>
    </section>

    <section class="dashboard-grid control-grid">
        <article class="admin-panel">
            <div class="panel-head">
                <h3>Inventory health</h3>
                <?php if (admin_can('inventory.manage')): ?><a href="cars.php">Fix inventory</a><?php endif; ?>
            </div>
            <div class="health-grid">
                <span><strong><?php echo $inventoryHealth['missing_price']; ?></strong> Missing price</span>
                <span><strong><?php echo $inventoryHealth['missing_image']; ?></strong> Missing image</span>
                <span><strong><?php echo $inventoryHealth['featured']; ?></strong> Featured</span>
                <span><strong><?php echo $inventoryHealth['uploads']; ?></strong> Uploads</span>
            </div>
        </article>

        <article class="admin-panel">
            <div class="panel-head">
                <h3>Inventory by brand</h3>
                <span class="text-muted">No external chart library</span>
            </div>
            <?php foreach ($brandDistribution as $row): ?>
                <div class="bar-row">
                    <span><?php echo h($row['brand_name']); ?></span>
                    <progress max="<?php echo (int)$brandMax; ?>" value="<?php echo (int)$row['count']; ?>"></progress>
                    <strong><?php echo (int)$row['count']; ?></strong>
                </div>
            <?php endforeach; ?>
        </article>
    </section>

    <section class="dashboard-grid control-grid">
        <article class="admin-content">
            <h2 class="section-title">Quick actions</h2>
            <div class="quick-action-grid">
                <?php if (admin_can('sales.manage')): ?><a href="leads.php" class="btn btn-success">Sales Leads</a><?php endif; ?>
                <?php if (admin_can('customers.manage')): ?><a href="customers.php" class="btn">Customers</a><?php endif; ?>
                <?php if (admin_can('sales.manage')): ?><a href="sales.php" class="btn">Sales Desk</a><?php endif; ?>
                <?php if (admin_can('inventory.manage')): ?><a href="cars.php" class="btn">Manage Cars</a><?php endif; ?>
                <?php if (admin_can('appointments.manage')): ?><a href="appointments.php" class="btn">Appointments</a><?php endif; ?>
                <?php if (admin_can('trade_ins.manage')): ?><a href="tradeins.php" class="btn">Trade-ins</a><?php endif; ?>
                <?php if (admin_can('finance_requests.manage')): ?><a href="finance_requests.php" class="btn">Finance</a><?php endif; ?>
                <?php if (admin_can('messages.view')): ?><a href="messages.php" class="btn btn-danger">Messages</a><?php endif; ?>
                <?php if (admin_can('testimonials.manage')): ?><a href="testimonials.php" class="btn">Testimonials</a><?php endif; ?>
                <?php if (admin_can('settings.manage')): ?><a href="settings.php" class="btn">Site Settings</a><?php endif; ?>
                <?php if (admin_can('app_users.view')): ?><a href="users.php" class="btn">App Users</a><?php endif; ?>
                <?php if (admin_can('logs.view')): ?><a href="logs.php" class="btn">Logs</a><?php endif; ?>
                <?php if (admin_can('users.manage')): ?><a href="access.php" class="btn">Team & Roles</a><?php endif; ?>
            </div>
        </article>

        <article class="admin-content">
            <h2 class="section-title">Recent admin actions</h2>
            <?php if (empty($recentAudit)): ?>
                <p class="text-muted">No admin actions recorded yet.</p>
            <?php else: ?>
                <div class="admin-scroll-panel">
                    <?php foreach ($recentAudit as $entry): ?>
                        <div class="admin-mini-row">
                            <span>
                                <strong><?php echo h($entry['action']); ?></strong>
                                <small><?php echo h($entry['summary'] ?: $entry['entity_type']); ?></small>
                            </span>
                            <span class="mini-date"><?php echo h(($entry['display_name'] ?: $entry['username'] ?: 'System') . ' / ' . $entry['created_at']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </article>
    </section>

    <section class="dashboard-grid control-grid">
        <article class="admin-content">
            <h2 class="section-title">Top viewed models</h2>
            <?php if (empty($topVehicles)): ?>
                <p class="text-muted">No view data yet.</p>
            <?php else: ?>
                <div class="table-responsive admin-scroll-panel">
                    <table class="admin-table-stack">
                        <thead><tr><th>Model</th><th class="text-right">Views</th></tr></thead>
                        <tbody>
                            <?php foreach ($topVehicles as $vehicle): ?>
                                <tr>
                                    <td data-label="Model"><?php echo h($vehicle['brand_name'] . ' ' . $vehicle['model']); ?></td>
                                    <td data-label="Views" class="text-right"><?php echo (int)$vehicle['views']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </article>
    </section>
</main>

<?php require_once __DIR__ . '/include/footer.php'; ?>
