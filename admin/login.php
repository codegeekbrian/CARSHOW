<?php
require_once __DIR__ . '/admin_db.php';

if (is_authenticated()) {
    header('Location: index.php');
    exit();
}

$error = '';

if (isset($_GET['timeout'])) {
    $error = 'Session expired. Please log in again.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $username = $_POST['username'] ?? '';
    if (admin_login_is_locked($db, $username)) {
        $error = 'Too many login attempts. Please wait 15 minutes and try again.';
    } elseif (authenticate($username, $_POST['password'] ?? '')) {
        clear_admin_login_failures($db, $username);
        header('Location: index.php');
        exit();
    } else {
        record_admin_login_failure($db, $username);
    }
    if ($error === '') {
        $error = 'Invalid username or password.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Own It & Drive It</title>
    <link rel="icon" type="image/svg+xml" href="../assets/images/favicon.svg">
    <link rel="stylesheet" href="../assets/css/tokens.css">
    <link rel="stylesheet" href="../assets/css/reset.css">
    <link rel="stylesheet" href="../assets/css/layout.css">
    <link rel="stylesheet" href="../assets/css/components.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-body admin-login-body">
    <div class="login-container">
        <form class="login-form" method="POST">
            <?php echo csrf_field(); ?>
            <p class="admin-kicker">Control center</p>
            <h2>Admin Login</h2>
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autocomplete="username">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required autocomplete="current-password">
                <button type="button" class="password-toggle admin-password-toggle" data-password-toggle="password" aria-pressed="false">Show</button>
            </div>
            <button type="submit" class="btn">Login</button>
        </form>
    </div>
    <script src="../assets/js/forms.js"></script>
    <script src="../assets/js/admin.js"></script>
</body>
</html>
