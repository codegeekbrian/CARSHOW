<?php
require_once 'db_admin.php';
requireAdminAuth();

// Get stats for dashboard
$stmt = $db->prepare("SELECT COUNT(*) as total_cars FROM vehicles");
$stmt->execute();
$total_cars = $stmt->fetch(PDO::FETCH_ASSOC)['total_cars'];

$stmt = $db->prepare("SELECT COUNT(*) as total_brands FROM brands");
$stmt->execute();
$total_brands = $stmt->fetch(PDO::FETCH_ASSOC)['total_brands'];

$stmt = $db->prepare("SELECT COUNT(*) as total_heritage FROM heritage_details");
$stmt->execute();
$total_heritage = $stmt->fetch(PDO::FETCH_ASSOC)['total_heritage'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CARSHOW</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f8f9fa;
        }
        .admin-header {
            background-color: #343a40;
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .admin-nav {
            background-color: #495057;
            padding: 0.5rem 2rem;
        }
        .admin-nav a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            margin-right: 0.5rem;
            border-radius: 4px;
        }
        .admin-nav a:hover, .admin-nav a.active {
            background-color: #007bff;
        }
        .dashboard-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 1.5rem;
            text-align: center;
        }
        .stat-card h3 {
            margin-top: 0;
            color: #6c757d;
        }
        .stat-card .count {
            font-size: 2.5rem;
            font-weight: bold;
            color: #007bff;
        }
        .admin-content {
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 2rem;
        }
        .section-title {
            border-bottom: 2px solid #007bff;
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
            color: #343a40;
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <h1>Admin Dashboard</h1>
        <div>
            Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>! 
            <a href="logout.php" style="color: #ff6b6b; text-decoration: none; margin-left: 1rem;">Logout</a>
        </div>
    </header>
    
    <nav class="admin-nav">
        <a href="index.php" class="active">Dashboard</a>
        <a href="cars.php">Manage Cars</a>
        <a href="brands.php">Manage Brands</a>
        <a href="heritage.php">Manage Heritage Details</a>
    </nav>
    
    <div class="dashboard-container">
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Vehicles</h3>
                <div class="count"><?php echo $total_cars; ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Brands</h3>
                <div class="count"><?php echo $total_brands; ?></div>
            </div>
            <div class="stat-card">
                <h3>Heritage Details</h3>
                <div class="count"><?php echo $total_heritage; ?></div>
            </div>
        </div>
        
        <div class="admin-content">
            <h2 class="section-title">Quick Actions</h2>
            <p>Welcome to the CARSHOW Admin Dashboard. Here you can manage your car inventory, brands, and heritage details.</p>
            
            <div style="margin-top: 2rem;">
                <a href="cars.php" style="display: inline-block; background: #007bff; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 4px; margin-right: 1rem;">Manage Cars</a>
                <a href="brands.php" style="display: inline-block; background: #28a745; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 4px; margin-right: 1rem;">Manage Brands</a>
                <a href="heritage.php" style="display: inline-block; background: #6f42c1; color: white; padding: 0.75rem 1.5rem; text-decoration: none; border-radius: 4px;">Manage Heritage</a>
            </div>
        </div>
    </div>
</body>
</html>