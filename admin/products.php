<?php
require_once '../config.php';
requireAdmin();

$conn = getDBConnection();
$message = '';

// Handle Add/Edit/Delete
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action == 'add') {
            $name = sanitize($_POST['name']);
            $category_id = $_POST['category_id'];
            $price = $_POST['price'];
            $description = sanitize($_POST['description']);
            $stock_count = $_POST['stock_count'];
            $image = 'default.jpg';
            
            // Handle image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $target_dir = "../" . UPLOAD_DIR;
                if (!file_exists($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                $image = time() . '_' . basename($_FILES['image']['name']);
                move_uploaded_file($_FILES['image']['tmp_name'], $target_dir . $image);
            }
            
            $stmt = $conn->prepare("INSERT INTO products (name, category_id, price, description, image, stock_count) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sidssi", $name, $category_id, $price, $description, $image, $stock_count);
            if ($stmt->execute()) {
                $message = 'Product added successfully';
            }
            $stmt->close();
        }
        
        if ($action == 'edit') {
            $id = $_POST['id'];
            $name = sanitize($_POST['name']);
            $category_id = $_POST['category_id'];
            $price = $_POST['price'];
            $description = sanitize($_POST['description']);
            $stock_count = $_POST['stock_count'];
            
            // Check if new image uploaded
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $target_dir = "../" . UPLOAD_DIR;
                $image = time() . '_' . basename($_FILES['image']['name']);
                move_uploaded_file($_FILES['image']['tmp_name'], $target_dir . $image);
                
                $stmt = $conn->prepare("UPDATE products SET name = ?, category_id = ?, price = ?, description = ?, image = ?, stock_count = ? WHERE id = ?");
                $stmt->bind_param("sidssii", $name, $category_id, $price, $description, $image, $stock_count, $id);
            } else {
                $stmt = $conn->prepare("UPDATE products SET name = ?, category_id = ?, price = ?, description = ?, stock_count = ? WHERE id = ?");
                $stmt->bind_param("sidsii", $name, $category_id, $price, $description, $stock_count, $id);
            }
            
            if ($stmt->execute()) {
                $message = 'Product updated successfully';
            }
            $stmt->close();
        }
        
        if ($action == 'delete') {
            $id = $_POST['id'];
            $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $message = 'Product deleted successfully';
            }
            $stmt->close();
        }
    }
}

// Get all products with category names
$products = $conn->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC");

// Get all categories for dropdowns
$categories = $conn->query("SELECT * FROM categories ORDER BY name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f4f4; }
        .header {
            background: #2c3e50;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .nav { display: flex; gap: 20px; }
        .nav a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 4px;
        }
        .nav a:hover { background: #34495e; }
        .container { padding: 30px; }
        h1 { margin-bottom: 30px; color: #2c3e50; }
        .success { padding: 15px; background: #d4edda; color: #155724; border-radius: 4px; margin-bottom: 20px; }
        .btn {
            padding: 10px 20px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover { background: #2980b9; }
        .btn-danger { background: #e74c3c; }
        .btn-danger:hover { background: #c0392b; }
        .btn-edit { background: #f39c12; }
        .btn-edit:hover { background: #e67e22; }
        table {
            width: 100%;
            background: white;
            border-collapse: collapse;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #34495e; color: white; }
        .product-img { width: 50px; height: 50px; object-fit: cover; border-radius: 4px; }
        .stock-badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .in-stock { background: #d4edda; color: #155724; }
        .out-stock { background: #f8d7da; color: #721c24; }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            overflow-y: auto;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
            margin: 20px;
        }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: #555; font-weight: bold; }
        input, textarea, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .modal-buttons { display: flex; gap: 10px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h2>Admin Dashboard</h2>
        <nav class="nav">
            <a href="dashboard.php">Dashboard</a>
            <a href="categories.php">Categories</a>
            <a href="products.php">Products</a>
            <a href="orders.php">Orders</a>
            <a href="logout.php">Logout</a>
        </nav>
    </div>
    
    <div class="container">
        <h1>Manage Products</h1>
        
        <?php if ($message): ?>
            <div class="success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <button class="btn" onclick="openAddModal()">Add New Product</button>
        
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($product = $products->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $product['id']; ?></td>
                    <td><img src="../<?php echo UPLOAD_DIR . $product['image']; ?>" class="product-img" alt="Product"></td>
                    <td><?php echo $product['name']; ?></td>
                    <td><?php echo $product['category_name']; ?></td>
                    <td><?php echo formatPrice($product['price']); ?></td>
                    <td><?php echo $product['stock_count']; ?></td>
                    <td>
                        <?php if($product['stock_count'] > 0): ?>
                            <span class="stock-badge in-stock">In Stock</span>
                        <?php else: ?>
                            <span class="stock-badge out-stock">Out of Stock</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="btn btn-edit" onclick='openEditModal(<?php echo json_encode($product); ?>)'>Edit</button>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this product?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
                            <button type="submit" class="btn btn-danger">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Add Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <h2>Add New Product</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                <div class="form-group">
                    <label>Product Name</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category_id" required>
                        <option value="">Select Category</option>
                        <?php 
                        $categories->data_seek(0);
                        while($cat = $categories->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Price</label>
                    <input type="number" name="price" step="0.01" required>
                </div>
                <div class="form-group">
                    <label>Stock Count</label>
                    <input type="number" name="stock_count" value="0" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="4"></textarea>
                </div>
                <div class="form-group">
                    <label>Product Image</label>
                    <input type="file" name="image" accept="image/*">
                </div>
                <div class="modal-buttons">
                    <button type="submit" class="btn">Add Product</button>
                    <button type="button" class="btn btn-danger" onclick="closeModal('addModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h2>Edit Product</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label>Product Name</label>
                    <input type="text" name="name" id="edit_name" required>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category_id" id="edit_category_id" required>
                        <?php 
                        $categories->data_seek(0);
                        while($cat = $categories->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Price</label>
                    <input type="number" name="price" id="edit_price" step="0.01" required>
                </div>
                <div class="form-group">
                    <label>Stock Count</label>
                    <input type="number" name="stock_count" id="edit_stock_count" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="edit_description" rows="4"></textarea>
                </div>
                <div class="form-group">
                    <label>Product Image (leave empty to keep current)</label>
                    <input type="file" name="image" accept="image/*">
                </div>
                <div class="modal-buttons">
                    <button type="submit" class="btn">Update Product</button>
                    <button type="button" class="btn btn-danger" onclick="closeModal('editModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openAddModal() {
            document.getElementById('addModal').classList.add('active');
        }
        
        function openEditModal(product) {
            document.getElementById('edit_id').value = product.id;
            document.getElementById('edit_name').value = product.name;
            document.getElementById('edit_category_id').value = product.category_id;
            document.getElementById('edit_price').value = product.price;
            document.getElementById('edit_stock_count').value = product.stock_count;
            document.getElementById('edit_description').value = product.description;
            document.getElementById('editModal').classList.add('active');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>