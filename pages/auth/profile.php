<?php
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/db.php';
require_once __DIR__ . '/../../include/csrf.php';
require_once __DIR__ . '/../../include/session_helpers.php';
require_once __DIR__ . '/../../include/customer_journey.php';
enforce_user_session_timeout($db);

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . url('login'));
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';
$base_path = rtrim(BASE_PATH, '/') . '/';

$stmt = $db->prepare('SELECT name, email FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header('Location: ' . url('login'));
    exit;
}

$savedVehicles = getUserSavedVehicles($db, (int)$user_id);
$compareVehicles = getUserCompareVehicles($db, (int)$user_id);
$journeyActivity = getUserJourneyActivity($db, (int)$user_id, $user['email']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($name) || empty($email)) {
            $error = 'Name and email are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $stmt = $db->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetch()) {
                $error = 'Email address is already in use by another account.';
            } else {
                $db->beginTransaction();
                try {
                    $stmt = $db->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?');
                    $stmt->execute([$name, $email, $user_id]);

                    if (!empty($new_password)) {
                        if (strlen($new_password) < 6) {
                            throw new Exception('New password must be at least 6 characters long.');
                        }
                        if ($new_password !== $confirm_password) {
                            throw new Exception('Passwords do not match.');
                        }
                        $stmt = $db->prepare('UPDATE users SET password = ? WHERE id = ?');
                        $stmt->execute([password_hash($new_password, PASSWORD_DEFAULT), $user_id]);
                    }

                    $db->commit();
                    $_SESSION['user_name'] = $name;
                    $user['name'] = $name;
                    $user['email'] = $email;
                    $success = 'Profile updated.';
                } catch (Exception $e) {
                    $db->rollBack();
                    $error = $e->getMessage();
                }
            }
        }
    }
}

$current_page = 'profile';
$pageTitle = 'My Profile';
require_once __DIR__ . '/../../include/header.php';
?>
<main>
    <div class="page-content">
        <div class="profile-shell" data-profile-dashboard data-base-path="<?php echo h($base_path); ?>">
            <section class="profile-hero-panel">
                <div>
                    <p class="profile-kicker">Account</p>
                    <h1><?php echo h($user['name']); ?></h1>
                    <p><?php echo h($user['email']); ?></p>
                </div>
                <div class="profile-quick-stats" aria-label="Saved account activity">
                    <div>
                        <strong data-profile-saved-count><?php echo count($savedVehicles); ?></strong>
                        <span>Saved cars</span>
                    </div>
                    <div>
                        <strong data-profile-compare-count><?php echo count($compareVehicles); ?></strong>
                        <span>Comparing</span>
                    </div>
                    <div>
                        <strong><?php echo count($journeyActivity['appointments']); ?></strong>
                        <span>Appointments</span>
                    </div>
                </div>
            </section>

            <section class="profile-dashboard-grid" aria-label="Profile dashboard">
                <article class="profile-panel profile-panel-wide">
                    <div class="profile-panel-head">
                        <div>
                            <p class="profile-kicker">Garage</p>
                            <h2>Saved cars</h2>
                        </div>
                        <button type="button" class="text-button" data-clear-saved>Clear saved</button>
                    </div>
                    <div class="profile-vehicle-list" id="profileSavedList" data-profile-saved-list>
                        <?php if (empty($savedVehicles)): ?>
                            <p class="profile-empty">Saved vehicles will appear here after you tap Save in inventory.</p>
                        <?php else: ?>
                            <?php foreach ($savedVehicles as $vehicle): ?>
                                <article class="profile-vehicle-card">
                                    <img src="<?php echo getVehicleImage($vehicle['image_url'] ?? ''); ?>" alt="<?php echo h($vehicle['brand_name'] . ' ' . $vehicle['model']); ?>" loading="lazy">
                                    <div>
                                        <span><?php echo h(($vehicle['year'] ?? '') . ' / ' . ($vehicle['branch_name'] ?? 'Nairobi Showroom')); ?></span>
                                        <h3><?php echo h($vehicle['brand_name'] . ' ' . $vehicle['model']); ?></h3>
                                        <p><?php echo !empty($vehicle['price']) ? 'KES ' . number_format((float)$vehicle['price']) : 'Price on inquiry'; ?></p>
                                        <div class="profile-card-actions">
                                            <a href="<?php echo url('vehicle/' . (int)$vehicle['id']); ?>">View</a>
                                            <a href="<?php echo url('contact?type=appointment&vehicle_id=' . (int)$vehicle['id']); ?>">Book viewing</a>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </article>

                <article class="profile-panel">
                    <div class="profile-panel-head">
                        <div>
                            <p class="profile-kicker">Shortlist</p>
                            <h2>Compare list</h2>
                        </div>
                        <button type="button" class="text-button" data-clear-compare>Clear</button>
                    </div>
                    <div class="profile-compact-list" id="profileCompareList" data-profile-compare-list>
                        <?php if (empty($compareVehicles)): ?>
                            <p class="profile-empty">Add up to three cars to compare.</p>
                        <?php else: ?>
                            <?php foreach ($compareVehicles as $vehicle): ?>
                                <a class="profile-compare-item" href="<?php echo url('vehicle/' . (int)$vehicle['id']); ?>">
                                    <span><?php echo h($vehicle['brand_name'] . ' ' . $vehicle['model']); ?></span>
                                    <strong><?php echo !empty($vehicle['price']) ? 'KES ' . number_format((float)$vehicle['price']) : 'Inquiry'; ?></strong>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </article>

                <article class="profile-panel">
                    <p class="profile-kicker">Next step</p>
                    <h2>Book a viewing</h2>
                    <p class="profile-copy">Choose a saved vehicle and request a branch appointment. We will use the inquiry page to collect the preferred date, branch, and contact details.</p>
                    <a class="btn-outline profile-panel-action" href="<?php echo url('inventory'); ?>">Find a car</a>
                </article>
            </section>

            <section class="profile-dashboard-grid" aria-label="Buying requests">
                <article class="profile-panel">
                    <p class="profile-kicker">Trade-in</p>
                    <h2>Request valuation</h2>
                    <form method="POST" action="<?php echo url('api/customer-journey'); ?>" data-ajax-form data-required-fields="vehicle_make,vehicle_model,customer_name,customer_email" data-submit-label="Send trade-in">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="trade_in">
                        <input type="hidden" name="customer_name" value="<?php echo h($user['name']); ?>">
                        <input type="hidden" name="customer_email" value="<?php echo h($user['email']); ?>">
                        <div class="form-group"><label for="trade_make">Make</label><input id="trade_make" name="vehicle_make" required></div>
                        <div class="form-group"><label for="trade_model">Model</label><input id="trade_model" name="vehicle_model" required></div>
                        <div class="form-group"><label for="trade_year">Year</label><input id="trade_year" name="vehicle_year"></div>
                        <div class="form-group"><label for="trade_value">Expected value</label><input id="trade_value" name="expected_value" type="number" min="0" step="50000"></div>
                        <div class="form-response" aria-live="polite"></div>
                        <button type="submit" class="btn-submit btn-outline profile-panel-action">Send trade-in</button>
                    </form>
                </article>

                <article class="profile-panel">
                    <p class="profile-kicker">Finance</p>
                    <h2>Request pre-check</h2>
                    <form method="POST" action="<?php echo url('api/customer-journey'); ?>" data-ajax-form data-required-fields="customer_name,customer_email" data-submit-label="Send finance request">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="action" value="finance">
                        <input type="hidden" name="customer_name" value="<?php echo h($user['name']); ?>">
                        <input type="hidden" name="customer_email" value="<?php echo h($user['email']); ?>">
                        <div class="form-group"><label for="finance_deposit">Deposit amount</label><input id="finance_deposit" name="deposit_amount" type="number" min="0" step="50000"></div>
                        <div class="form-group"><label for="finance_term">Term months</label><input id="finance_term" name="term_months" type="number" min="1" max="120"></div>
                        <div class="form-group"><label for="finance_budget">Monthly budget</label><input id="finance_budget" name="monthly_budget" type="number" min="0" step="10000"></div>
                        <div class="form-response" aria-live="polite"></div>
                        <button type="submit" class="btn-submit btn-outline profile-panel-action">Send finance request</button>
                    </form>
                </article>

                <article class="profile-panel profile-panel-wide">
                    <p class="profile-kicker">Activity</p>
                    <h2>Your requests</h2>
                    <div class="profile-compact-list">
                        <?php
                        $activityRows = array_merge(
                            array_map(fn($row) => ['label' => 'Appointment', 'title' => trim(($row['brand_name'] ?? '') . ' ' . ($row['model'] ?? '')), 'status' => $row['status'] ?? 'new', 'date' => $row['created_at'] ?? ''], $journeyActivity['appointments']),
                            array_map(fn($row) => ['label' => 'Trade-in', 'title' => trim(($row['vehicle_make'] ?? '') . ' ' . ($row['vehicle_model'] ?? '')), 'status' => $row['status'] ?? 'new', 'date' => $row['created_at'] ?? ''], $journeyActivity['trade_ins']),
                            array_map(fn($row) => ['label' => 'Finance', 'title' => trim(($row['brand_name'] ?? '') . ' ' . ($row['model'] ?? 'Finance request')), 'status' => $row['status'] ?? 'new', 'date' => $row['created_at'] ?? ''], $journeyActivity['finance'])
                        );
                        ?>
                        <?php if (empty($activityRows)): ?>
                            <p class="profile-empty">Appointments, trade-ins, and finance requests will appear here.</p>
                        <?php else: ?>
                            <?php foreach (array_slice($activityRows, 0, 10) as $row): ?>
                                <div class="profile-compare-item">
                                    <span><?php echo h($row['label'] . ': ' . ($row['title'] ?: 'Request')); ?></span>
                                    <strong><?php echo h(ucfirst($row['status'])); ?></strong>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </article>
            </section>

            <section class="auth-wrap profile-settings-panel">
                <h2>Profile settings</h2>

            <?php if ($error): ?>
                <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="success-msg"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <form method="POST">
                <?php echo csrfField(); ?>
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name"
                           value="<?php echo htmlspecialchars($user['name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email"
                           value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>

                <hr class="hr-border mb-2 mt-2">
                <h3 class="auth-subheading">Change Password <small class="profile-password-note">(Leave blank to keep current)</small></h3>

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password">
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password">
                </div>

                <button type="submit" class="btn-auth">Update Profile</button>
            </form>
            </section>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../../include/footer.php'; ?>
