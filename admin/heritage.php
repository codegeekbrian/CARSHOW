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
                $vehicle_id = $_POST['vehicle_id'];
                $generation_name = $_POST['generation_name'];
                $years = $_POST['years'];
                $history_text = $_POST['history_text'];
                $image_url = $_POST['image_url'];
                
                $stmt = $db->prepare("INSERT INTO heritage_details (vehicle_id, generation_name, years, history_text, image_url) VALUES (?, ?, ?, ?, ?)");
                if ($stmt->execute([$vehicle_id, $generation_name, $years, $history_text, $image_url])) {
                    clearAllCache();
                    $message = "Heritage detail added successfully!";
                } else {
                    $message = "Error adding heritage detail.";
                }
                break;
                
            case 'delete':
                $id = $_POST['id'];
                $stmt = $db->prepare("DELETE FROM heritage_details WHERE id = ?");
                if ($stmt->execute([$id])) {
                    clearAllCache();
                    $message = "Heritage detail deleted successfully!";
                } else {
                    $message = "Error deleting heritage detail.";
                }
                break;
                
            case 'edit':
                $id = $_POST['id'];
                $vehicle_id = $_POST['vehicle_id'];
                $generation_name = $_POST['generation_name'];
                $years = $_POST['years'];
                $history_text = $_POST['history_text'];
                $image_url = $_POST['image_url'];
                
                $stmt = $db->prepare("UPDATE heritage_details SET vehicle_id = ?, generation_name = ?, years = ?, history_text = ?, image_url = ? WHERE id = ?");
                if ($stmt->execute([$vehicle_id, $generation_name, $years, $history_text, $image_url, $id])) {
                    clearAllCache();
                    $message = "Heritage detail updated successfully!";
                } else {
                    $message = "Error updating heritage detail.";
                }
                break;
        }
    }
}

// Fetch heritage details and vehicles for display
$heritage_stmt = $db->prepare("
    SELECT hd.*, v.model, b.name as brand_name 
    FROM heritage_details hd 
    JOIN vehicles v ON hd.vehicle_id = v.id 
    JOIN brands b ON v.brand_id = b.id 
    ORDER BY b.name, v.model, hd.id
");
$heritage_stmt->execute();
$heritage_details = $heritage_stmt->fetchAll(PDO::FETCH_ASSOC);

$vehicles_stmt = $db->prepare("
    SELECT v.id, v.model, b.name as brand_name 
    FROM vehicles v 
    JOIN brands b ON v.brand_id = b.id 
    WHERE v.is_heritage = 1
    ORDER BY b.name, v.model
");
$vehicles_stmt->execute();
$vehicles = $vehicles_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Heritage Details - Admin Panel</title>
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
            max-width: 700px;
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
        <h1>Manage Heritage Details</h1>
        <div>
            Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>! 
            <a href="logout.php" style="color: #ff6b6b; text-decoration: none; margin-left: 1rem;">Logout</a>
        </div>
    </header>
    
    <nav class="admin-nav">
        <a href="index.php">Dashboard</a>
        <a href="cars.php">Manage Cars</a>
        <a href="brands.php">Manage Brands</a>
        <a href="heritage.php" class="active">Manage Heritage Details</a>
    </nav>
    
    <div class="admin-container">
        <div class="admin-content">
            <h2 class="section-title">
                Heritage Details
                <button class="btn btn-success" onclick="openAddModal()">Add New Detail</button>
            </h2>
            
            <?php if ($message): ?>
                <div class="message success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <table>
                <thead>
                    <tr>
                        <th>Vehicle</th>
                        <th>Generation</th>
                        <th>Years</th>
                        <th>Image URL</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($heritage_details as $detail): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($detail['brand_name'] . ' ' . $detail['model']); ?></td>
                        <td><?php echo htmlspecialchars($detail['generation_name']); ?></td>
                        <td><?php echo htmlspecialchars($detail['years']); ?></td>
                        <td><?php echo htmlspecialchars(substr($detail['image_url'], 0, 30)) . (strlen($detail['image_url']) > 30 ? '...' : ''); ?></td>
                        <td>
                            <button class="btn" onclick="openEditModal(
                                <?php echo $detail['id']; ?>, 
                                <?php echo $detail['vehicle_id']; ?>, 
                                '<?php echo addslashes($detail['generation_name']); ?>', 
                                '<?php echo addslashes($detail['years']); ?>', 
                                '<?php echo addslashes($detail['history_text']); ?>', 
                                '<?php echo addslashes($detail['image_url']); ?>'
                            )">Edit</button>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this heritage detail?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $detail['id']; ?>">
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
    <div id="heritageModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle">Add New Heritage Detail</h2>
            <form method="POST">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="detailId">
                
                <div class="form-group">
                    <label for="vehicle_id">Vehicle</label>
                    <select id="vehicle_id" name="vehicle_id" required>
                        <option value="">Select Vehicle</option>
                        <?php foreach ($vehicles as $vehicle): ?>
                            <option value="<?php echo $vehicle['id']; ?>"><?php echo htmlspecialchars($vehicle['brand_name'] . ' ' . $vehicle['model']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="generation_name">Generation Name</label>
                        <input type="text" id="generation_name" name="generation_name" required>
                    </div>
                    <div class="form-group">
                        <label for="years">Years</label>
                        <input type="text" id="years" name="years" placeholder="e.g., 1990-2001" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="history_text">History Text</label>
                    <textarea id="history_text" name="history_text" rows="4" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="image_url">Image URL</label>
                    <input type="text" id="image_url" name="image_url" placeholder="e.g., assets/images/gtr/gtr1.jpg" required>
                </div>
                
                <button type="submit" class="btn btn-success">Save Detail</button>
                <button type="button" class="btn" onclick="closeModal()">Cancel</button>
            </form>
        </div>
    </div>
    
    <script>
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New Heritage Detail';
            document.getElementById('formAction').value = 'add';
            document.getElementById('detailId').value = '';
            document.getElementById('vehicle_id').value = '';
            document.getElementById('generation_name').value = '';
            document.getElementById('years').value = '';
            document.getElementById('history_text').value = '';
            document.getElementById('image_url').value = '';
            document.getElementById('heritageModal').style.display = 'flex';
        }
        
        function openEditModal(id, vehicle_id, generation_name, years, history_text, image_url) {
            document.getElementById('modalTitle').textContent = 'Edit Heritage Detail';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('detailId').value = id;
            document.getElementById('vehicle_id').value = vehicle_id;
            document.getElementById('generation_name').value = generation_name;
            document.getElementById('years').value = years;
            document.getElementById('history_text').value = history_text;
            document.getElementById('image_url').value = image_url;
            document.getElementById('heritageModal').style.display = 'flex';
        }
        
        function closeModal() {
            document.getElementById('heritageModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('heritageModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>