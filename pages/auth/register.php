<?php
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/csrf.php';
require_once __DIR__ . '/../../include/session_helpers.php';
enforce_user_session_timeout();

if (isset($_SESSION['user_id'])) {
    header('Location: ' . url(''));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        require_once __DIR__ . '/../../include/input_helpers.php';
        $name             = sanitize_text($_POST['name'] ?? '');
        $email            = sanitize_email($_POST['email'] ?? '');
        $password         = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($name) || empty($email) || empty($password)) {
            $error = 'All fields are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } else {
            require_once __DIR__ . '/../../include/db.php';

            $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $error = 'Email address is already registered.';
            } else {
                $insert = $db->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
                if ($insert->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)])) {
                    start_user_session((int) $db->lastInsertId(), $name);
                    header('Location: ' . url(''));
                    exit;
                } else {
                    $error = 'Registration failed. Please try again.';
                }
            }
        }
    }
}
$current_page = 'register';
$pageTitle = 'Register';
require_once __DIR__ . '/../../include/header.php';
?>
<main>
    <div class="page-content">
        <div class="auth-wrap">
            <h1>User Registration</h1>

            <?php if ($error): ?>
                <div class="error-msg"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST">
                <?php echo csrfField(); ?>
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" autocomplete="name"
                           value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" autocomplete="email"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="password-field">
                        <input type="password" id="password" name="password" required autocomplete="new-password">
                        <button type="button" class="password-toggle" data-password-toggle="password" aria-pressed="false">Show</button>
                    </div>
                    <small class="auth-help-text d-block mt-1">At least 6 characters.</small>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="password-field">
                        <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password">
                        <button type="button" class="password-toggle" data-password-toggle="confirm_password" aria-pressed="false">Show</button>
                    </div>
                </div>

                <button type="submit" class="btn-auth">Register</button>

                <div class="auth-link">
                    Already have an account? <a href="<?php echo url('login'); ?>">Login here</a>
                </div>
            </form>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../../include/footer.php'; ?>
