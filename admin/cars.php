<?php
require_once 'db_admin.php';
require_once dirname(__DIR__) . '/include/cache.php';
requireAdminAuth();

// Handle form submissions
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $brand_id = $_POST['brand_id'];
                $model = $_POST['model'];
                $year = $_POST['year'];
                $price = $_POST['price'];
                $description = $_POST['description'];
                $engine = $_POST['engine'];
                $power = $_POST['power'];
                $drive = $_POST['drive'];
                $image_url = $_POST['image_url'];
                $is_featured = isset($_POST['is_featured']) ? 1 : 0;
                $is_heritage = isset($_POST['is_heritage']) ? 1 : 0;
                
                $stmt = $db->prepare("INSERT INTO vehicles (brand_id, model, year, price, description, engine, power, drive, image_url, is_featured, is_heritage) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                if ($stmt->execute([$brand_id, $model, $year, $price, $description, $engine, $power, $drive, $image_url, $is_featured, $is_heritage])) {
                    clearAllCache();
                    $message = "Car added successfully!";
                } else {
                    $message = "Error adding car.";
                }
                break;
                
            case 'delete':
                $id = $_POST['id'];
                $stmt = $db->prepare("DELETE FROM vehicles WHERE id = ?");
                if ($stmt->execute([$id])) {
                    clearAllCache();
                    $message = "Car deleted successfully!";
                } else {
                    $message = "Error deleting car.";
                }
                break;
                
            case 'edit':
                $id = $_POST['id'];
                $brand_id = $_POST['brand_id'];
                $model = $_POST['model'];
                $year = $_POST['year'];
                $price = $_POST['price'];
                $description = $_POST['description'];
                $engine = $_POST['engine'];
                $power = $_POST['power'];
                $drive = $_POST['drive'];
                $image_url = $_POST['image_url'];
                $is_featured = isset($_POST['is_featured']) ? 1 : 0;
                $is_heritage = isset($_POST['is_heritage']) ? 1 : 0;
                
                $stmt = $db->prepare("UPDATE vehicles SET brand_id = ?, model = ?, year = ?, price = ?, description = ?, engine = ?, power = ?, drive = ?, image_url = ?, is_featured = ?, is_heritage = ? WHERE id = ?");
                if ($stmt->execute([$brand_id, $model, $year, $price, $description, $engine, $power, $drive, $image_url, $is_featured, $is_heritage, $id])) {
                    clearAllCache();
                    $message = "Car updated successfully!";
                } else {
                    $message = "Error updating car.";
                }
                break;
        }
    }
}

// Fetch cars and brands for display
        $cars_stmt = $db->prepare("SELECT v.id, v.brand_id, v.model, v.year, v.price, v.description, v.engine, v.power, v.drive, v.image_url, v.is_featured, v.is_heritage, b.name as brand_name FROM vehicles v JOIN brands b ON v.brand_id = b.id ORDER BY v.brand_id, v.model");
$cars_stmt->execute();
$cars = $cars_stmt->fetchAll(PDO::FETCH_ASSOC);

$brands_stmt = $db->prepare("SELECT * FROM brands ORDER BY name");
$brands_stmt->execute();
$brands = $brands_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Cars - Admin Panel</title>
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
        .admin-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .btn-danger {
            background-color: #dc3545;
        }
        .btn-danger:hover {
            background-color: #bd2130;
        }
        .btn-success {
            background-color: #28a745;
        }
        .btn-success:hover {
            background-color: #218838;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
        }
        th, td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        th {
            background-color: #e9ecef;
            font-weight: bold;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ced4da;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .form-row {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .form-row .form-group {
            flex: 1;
        }
        .checkbox-group {
            display: flex;
            gap: 1rem;
        }
        .checkbox-group label {
            font-weight: normal;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
        }
        .close {
            float: right;
            cursor: pointer;
            font-size: 1.5rem;
        }
        .message {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <header class="admin-header">
        <h1>Manage Cars</h1>
        <div>
            Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>! 
            <a href="logout.php" style="color: #ff6b6b; text-decoration: none; margin-left: 1rem;">Logout</a>
        </div>
    </header>
    
    <nav class="admin-nav">
        <a href="index.php">Dashboard</a>
        <a href="cars.php" class="active">Manage Cars</a>
        <a href="brands.php">Manage Brands</a>
        <a href="heritage.php">Manage Heritage Details</a>
    </nav>
    
    <div class="admin-container">
        <div class="admin-content">
            <h2 class="section-title">
                Car Inventory
                <button class="btn btn-success" onclick="openAddModal()">Add New Car</button>
            </h2>
            
            <?php if ($message): ?>
                <div class="message success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <table>
                <thead>
                    <tr>
                        <th>Brand</th>
                        <th>Model</th>
                        <th>Year</th>
                        <th>Price</th>
                        <th>Engine</th>
                        <th>Power</th>
                        <th>Drive</th>
                        <th>Featured</th>
                        <th>Heritage</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cars as $car): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($car['brand_name']); ?></td>
                        <td><?php echo htmlspecialchars($car['model']); ?></td>
                        <td><?php echo htmlspecialchars($car['year']); ?></td>
                        <td>$<?php echo number_format($car['price']); ?></td>
                        <td><?php echo htmlspecialchars($car['engine'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($car['power'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($car['drive'] ?? '-'); ?></td>
                        <td><?php echo $car['is_featured'] ? 'Yes' : 'No'; ?></td>
                        <td><?php echo $car['is_heritage'] ? 'Yes' : 'No'; ?></td>
                        <td>
                            <button class="btn" onclick="openEditModal(<?php echo $car['id']; ?>, '<?php echo addslashes($car['brand_id']); ?>', '<?php echo addslashes($car['model']); ?>', '<?php echo addslashes($car['year']); ?>', '<?php echo addslashes($car['price']); ?>', '<?php echo addslashes($car['description']); ?>', '<?php echo addslashes($car['engine'] ?? ''); ?>', '<?php echo addslashes($car['power'] ?? ''); ?>', '<?php echo addslashes($car['drive'] ?? ''); ?>', '<?php echo addslashes($car['image_url'] ?? ''); ?>', <?php echo $car['is_featured']; ?>, <?php echo $car['is_heritage']; ?>)">Edit</button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this car?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $car['id']; ?>">
                                <button type="submit" class="btn btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Add/Edit Modal -->
    <div id="carModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle">Add New Car</h2>
            <form method="POST">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="carId">
                
                <div class="form-group">
                    <label for="brand_id">Brand</label>
                    <select id="brand_id" name="brand_id" required>
                        <option value="">Select Brand</option>
                        <?php foreach ($brands as $brand): ?>
                            <option value="<?php echo $brand['id']; ?>"><?php echo htmlspecialchars($brand['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="model">Model</label>
                        <input type="text" id="model" name="model" required>
                    </div>
                    <div class="form-group">
                        <label for="year">Year</label>
                        <input type="number" id="year" name="year" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="price">Price</label>
                    <input type="number" id="price" name="price" step="0.01" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" rows="3"></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="engine">Engine</label>
                        <input type="text" id="engine" name="engine">
                    </div>
                    <div class="form-group">
                        <label for="power">Power</label>
                        <input type="text" id="power" name="power">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="drive">Drive Type</label>
                        <input type="text" id="drive" name="drive">
                    </div>
                    <div class="form-group">
                        <label for="image_url">Image URL</label>
                        <input type="text" id="image_url" name="image_url">
                    </div>
                </div>
                
                <div class="checkbox-group">
                    <label>
                        <input type="checkbox" id="is_featured" name="is_featured" value="1">
                        Featured Car
                    </label>
                    <label>
                        <input type="checkbox" id="is_heritage" name="is_heritage" value="1">
                        Heritage Model
                    </label>
                </div>
                
                <button type="submit" class="btn btn-success">Save Car</button>
                <button type="button" class="btn" onclick="closeModal()">Cancel</button>
            </form>
        </div>
    </div>
    
    <script>
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New Car';
            document.getElementById('formAction').value = 'add';
            document.getElementById('carId').value = '';
            document.getElementById('brand_id').value = '';
            document.getElementById('model').value = '';
            document.getElementById('year').value = '';
            document.getElementById('price').value = '';
            document.getElementById('description').value = '';
            document.getElementById('engine').value = '';
            document.getElementById('power').value = '';
            document.getElementById('drive').value = '';
            document.getElementById('image_url').value = '';
            document.getElementById('is_featured').checked = false;
            document.getElementById('is_heritage').checked = false;
            document.getElementById('carModal').style.display = 'flex';
        }
        
        function openEditModal(id, brand_id, model, year, price, description, engine, power, drive, image_url, is_featured, is_heritage) {
            document.getElementById('modalTitle').textContent = 'Edit Car';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('carId').value = id;
            document.getElementById('brand_id').value = brand_id;
            document.getElementById('model').value = model;
            document.getElementById('year').value = year;
            document.getElementById('price').value = price;
            document.getElementById('description').value = description;
            document.getElementById('engine').value = engine;
            document.getElementById('power').value = power;
            document.getElementById('drive').value = drive;
            document.getElementById('image_url').value = image_url;
            document.getElementById('is_featured').checked = is_featured == 1;
            document.getElementById('is_heritage').checked = is_heritage == 1;
            document.getElementById('carModal').style.display = 'flex';
        }
        
        function closeModal() {
            document.getElementById('carModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('carModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>