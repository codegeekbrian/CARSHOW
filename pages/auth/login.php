<?php
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/csrf.php';
require_once __DIR__ . '/../../include/session_helpers.php';
require_once __DIR__ . '/../../include/db.php';
enforce_user_session_timeout($db);

if (isset($_SESSION['user_id'])) {
    header('Location: ' . url(''));
    exit;
}

$error = '';
$notice = $_SESSION['account_notice'] ?? '';
unset($_SESSION['account_notice']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'Both email and password are required.';
        } else {
            $user = fetchPublicUserForLogin($db, $email);

            if ($user && password_verify($password, $user['password'])) {
                if (!publicUserCanAccess($user)) {
                    $error = 'This account is not currently available. Please contact the dealership team.';
                } else {
                    start_user_session((int) $user['id'], $user['name']);
                    header('Location: ' . url(''));
                    exit;
                }
            } else {
                $error = 'Invalid email or password.';
            }
        }
    }
}
$current_page = 'login';
$pageTitle = 'Login';
require_once __DIR__ . '/../../include/header.php';
?>
<main>
    <div class="page-content">
        <div class="auth-wrap">
            <h1>User Login</h1>

            <?php if ($error): ?>
                <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($notice): ?>
                <div class="error-msg"><?php echo htmlspecialchars($notice); ?></div>
            <?php endif; ?>

            <form method="POST">
                <?php echo csrfField(); ?>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" autocomplete="email"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-field">
                        <input type="password" id="password" name="password" required autocomplete="current-password">
                        <button type="button" class="password-toggle" data-password-toggle="password" aria-pressed="false">Show</button>
                    </div>
                </div>

                <button type="submit" class="btn-auth">Login</button>

                <div class="auth-link">
                    Don't have an account? <a href="<?php echo url('register'); ?>">Register here</a>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../../include/footer.php'; ?>
