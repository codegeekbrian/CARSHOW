<?php
require_once 'db_admin.php';
requireAdminAuth();

// Handle form submissions
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $name = $_POST['name'];
                $stmt = $db->prepare("INSERT INTO brands (name) VALUES (?)");
                if ($stmt->execute([$name])) {
                    $message = "Brand added successfully!";
                } else {
                    $message = "Error adding brand.";
                }
                break;
                
            case 'delete':
                $id = $_POST['id'];
                // Check if brand is used by any vehicles
                $check_stmt = $db->prepare("SELECT COUNT(*) as count FROM vehicles WHERE brand_id = ?");
                $check_stmt->execute([$id]);
                $count = $check_stmt->fetch(PDO::FETCH_ASSOC)['count'];
                
                if ($count > 0) {
                    $message = "Cannot delete brand because it is used by $count vehicle(s).";
                } else {
                    $stmt = $db->prepare("DELETE FROM brands WHERE id = ?");
                    if ($stmt->execute([$id])) {
                        $message = "Brand deleted successfully!";
                    } else {
                        $message = "Error deleting brand.";
                    }
                }
                break;
                
            case 'edit':
                $id = $_POST['id'];
                $name = $_POST['name'];
                $stmt = $db->prepare("UPDATE brands SET name = ? WHERE id = ?");
                if ($stmt->execute([$name, $id])) {
                    $message = "Brand updated successfully!";
                } else {
                    $message = "Error updating brand.";
                }
                break;
        }
    }
}

// Fetch brands for display
$brands_stmt = $db->prepare("SELECT b.*, COUNT(v.id) as vehicle_count FROM brands b LEFT JOIN vehicles v ON b.id = v.brand_id GROUP BY b.id ORDER BY b.name");
$brands_stmt->execute();
$brands = $brands_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Brands - Admin Panel</title>
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
        .form-group input {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ced4da;
            border-radius: 4px;
            box-sizing: border-box;
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
            max-width: 500px;
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
        <h1>Manage Brands</h1>
        <div>
            Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>! 
            <a href="logout.php" style="color: #ff6b6b; text-decoration: none; margin-left: 1rem;">Logout</a>
        </div>
    </header>
    
    <nav class="admin-nav">
        <a href="index.php">Dashboard</a>
        <a href="cars.php">Manage Cars</a>
        <a href="brands.php" class="active">Manage Brands</a>
        <a href="heritage.php">Manage Heritage Details</a>
    </nav>
    
    <div class="admin-container">
        <div class="admin-content">
            <h2 class="section-title">
                Car Brands
                <button class="btn btn-success" onclick="openAddModal()">Add New Brand</button>
            </h2>
            
            <?php if ($message): ?>
                <div class="message <?php echo strpos($message, 'Error') !== false || strpos($message, 'Cannot') !== false ? 'error' : 'success'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <table>
                <thead>
                    <tr>
                        <th>Brand Name</th>
                        <th>Vehicles Count</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($brands as $brand): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($brand['name']); ?></td>
                        <td><?php echo $brand['vehicle_count']; ?></td>
                        <td>
                            <button class="btn" onclick="openEditModal(<?php echo $brand['id']; ?>, '<?php echo addslashes($brand['name']); ?>')">Edit</button>
                            <?php if ($brand['vehicle_count'] == 0): ?>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this brand?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?php echo $brand['id']; ?>">
                                    <button type="submit" class="btn btn-danger">Delete</button>
                                </form>
                            <?php else: ?>
                                <button class="btn btn-danger" disabled title="Cannot delete brand with associated vehicles">Delete</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Add/Edit Modal -->
    <div id="brandModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle">Add New Brand</h2>
            <form method="POST">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="brandId">
                
                <div class="form-group">
                    <label for="name">Brand Name</label>
                    <input type="text" id="name" name="name" required>
                </div>
                
                <button type="submit" class="btn btn-success">Save Brand</button>
                <button type="button" class="btn" onclick="closeModal()">Cancel</button>
            </form>
        </div>
    </div>
    
    <script>
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New Brand';
            document.getElementById('formAction').value = 'add';
            document.getElementById('brandId').value = '';
            document.getElementById('name').value = '';
            document.getElementById('brandModal').style.display = 'flex';
        }
        
        function openEditModal(id, name) {
            document.getElementById('modalTitle').textContent = 'Edit Brand';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('brandId').value = id;
            document.getElementById('name').value = name;
            document.getElementById('brandModal').style.display = 'flex';
        }
        
        function closeModal() {
            document.getElementById('brandModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('brandModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>