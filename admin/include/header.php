<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'Admin Panel'); ?> - CARSHOW</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <header class="admin-header">
        <h1><?php echo htmlspecialchars($pageTitle ?? 'Admin Panel'); ?></h1>
        <div>
            Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!
            <a href="logout.php">Logout</a>
        </div>
    </header>

    <nav class="admin-nav">
        <a href="index.php" class="<?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">Dashboard</a>
        <a href="cars.php" class="<?php echo $currentPage === 'cars.php' ? 'active' : ''; ?>">Manage Cars</a>
        <a href="brands.php" class="<?php echo $currentPage === 'brands.php' ? 'active' : ''; ?>">Manage Brands</a>
        <a href="legacy.php" class="<?php echo $currentPage === 'legacy.php' ? 'active' : ''; ?>">Manage Legacy</a>
        <a href="messages.php" class="<?php echo $currentPage === 'messages.php' ? 'active' : ''; ?>">Messages</a>
    </nav>