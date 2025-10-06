<?php
require_once 'config.php';
requireUser();

$user_id = $_SESSION['user_id'];
$conn = getDBConnection();
$message = '';

// Handle Add/Edit/Delete/Set Default
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_address'])) {
        $full_name = sanitize($_POST['full_name']);
        $phone = sanitize($_POST['phone']);
        $address_line1 = sanitize($_POST['address_line1']);
        $address_line2 = sanitize($_POST['address_line2']);
        $city = sanitize($_POST['city']);
        $state = sanitize($_POST['state']);
        $pincode = sanitize($_POST['pincode']);
        $is_default = isset($_POST['is_default']) ? 1 : 0;
        
        if ($is_default) {
            $conn->query("UPDATE addresses SET is_default = 0 WHERE user_id = $user_id");
        }
        
        $stmt = $conn->prepare("INSERT INTO addresses (user_id, full_name, phone, address_line1, address_line2, city, state, pincode, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssssi", $user_id, $full_name, $phone, $address_line1, $address_line2, $city, $state, $pincode, $is_default);
        $stmt->execute();
        $stmt->close();
        $message = 'Address added successfully!';
    }
    
    if (isset($_POST['edit_address'])) {
        $id = $_POST['address_id'];
        $full_name = sanitize($_POST['full_name']);
        $phone = sanitize($_POST['phone']);
        $address_line1 = sanitize($_POST['address_line1']);
        $address_line2 = sanitize($_POST['address_line2']);
        $city = sanitize($_POST['city']);
        $state = sanitize($_POST['state']);
        $pincode = sanitize($_POST['pincode']);
        $is_default = isset($_POST['is_default']) ? 1 : 0;
        
        if ($is_default) {
            $conn->query("UPDATE addresses SET is_default = 0 WHERE user_id = $user_id");
        }
        
        $stmt = $conn->prepare("UPDATE addresses SET full_name = ?, phone = ?, address_line1 = ?, address_line2 = ?, city = ?, state = ?, pincode = ?, is_default = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ssssssssii", $full_name, $phone, $address_line1, $address_line2, $city, $state, $pincode, $is_default, $id, $user_id);
        $stmt->execute();
        $stmt->close();
        $message = 'Address updated successfully!';
    }
    
    if (isset($_POST['delete_address'])) {
        $id = $_POST['address_id'];
        $stmt = $conn->prepare("DELETE FROM addresses WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $user_id);
        $stmt->execute();
        $stmt->close();
        $message = 'Address deleted successfully!';
    }
    
    if (isset($_POST['set_default'])) {
        $id = $_POST['address_id'];
        $conn->query("UPDATE addresses SET is_default = 0 WHERE user_id = $user_id");
        $stmt = $conn->prepare("UPDATE addresses SET is_default = 1 WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $id, $user_id);
        $stmt->execute();
        $stmt->close();
        $message = 'Default address updated!';
    }
}

// Get all addresses
$addresses = $conn->query("SELECT * FROM addresses WHERE user_id = $user_id ORDER BY is_default DESC, created_at DESC");

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Addresses</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f4f4; }
        .header {
            background: #2c3e50;
            color: white;
            padding: 15px 0;
        }
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo { font-size: 24px; font-weight: bold; text-decoration: none; color: white; }
        .nav { display: flex; gap: 20px; }
        .nav a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 4px;
        }
        .nav a:hover { background: #34495e; }
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        h1 { margin-bottom: 30px; color: #2c3e50; }
        .success { padding: 15px; background: #d4edda; color: #155724; border-radius: 4px; margin-bottom: 20px; }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary { background: #3498db; color: white; }
        .btn-primary:hover { background: #2980b9; }
        .btn-edit { background: #f39c12; color: white; }
        .btn-edit:hover { background: #e67e22; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn-danger:hover { background: #c0392b; }
        .address-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .address-card {
            background: white;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            position: relative;
        }
        .address-card.default {
            border: 2px solid #27ae60;
        }
        .default-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #27ae60;
            color: white;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .address-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        .address-details {
            color: #555;
            line-height: 1.8;
            margin-bottom: 20px;
        }
        .address-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
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
        input, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .checkbox-group input[type="checkbox"] {
            width: auto;
        }
        .modal-buttons { display: flex; gap: 10px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <a href="index.php" class="logo">E-commerce Store</a>
            <nav class="nav">
                <a href="wishlist.php">Wishlist</a>
                <a href="cart.php">Cart</a>
                <a href="dashboard.php">My Account</a>
                <a href="logout.php">Logout</a>
            </nav>
        </div>
    </div>
    
    <div class="container">
        <h1>Manage Addresses</h1>
        
        <?php if ($message): ?>
            <div class="success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <button class="btn btn-primary" onclick="openAddModal()">+ Add New Address</button>
        
        <div class="address-grid">
            <?php while($address = $addresses->fetch_assoc()): ?>
                <div class="address-card <?php echo $address['is_default'] ? 'default' : ''; ?>">
                    <?php if($address['is_default']): ?>
                        <span class="default-badge">Default</span>
                    <?php endif; ?>
                    
                    <div class="address-name"><?php echo $address['full_name']; ?></div>
                    <div class="address-details">
                        <strong>Phone:</strong> <?php echo $address['phone']; ?><br>
                        <?php echo $address['address_line1']; ?><br>
                        <?php if($address['address_line2']): ?>
                            <?php echo $address['address_line2']; ?><br>
                        <?php endif; ?>
                        <?php echo $address['city']; ?>, <?php echo $address['state']; ?><br>
                        PIN: <?php echo $address['pincode']; ?>
                    </div>
                    
                    <div class="address-actions">
                        <button class="btn btn-edit" onclick='openEditModal(<?php echo json_encode($address); ?>)'>Edit</button>
                        
                        <?php if(!$address['is_default']): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="address_id" value="<?php echo $address['id']; ?>">
                                <button type="submit" name="set_default" class="btn btn-primary">Set as Default</button>
                            </form>
                        <?php endif; ?>
                        
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this address?');">
                            <input type="hidden" name="address_id" value="<?php echo $address['id']; ?>">
                            <button type="submit" name="delete_address" class="btn btn-danger">Delete</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
    
    <!-- Add Address Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <h2>Add New Address</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" required>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone" required>
                </div>
                <div class="form-group">
                    <label>Address Line 1</label>
                    <input type="text" name="address_line1" required>
                </div>
                <div class="form-group">
                    <label>Address Line 2 (Optional)</label>
                    <input type="text" name="address_line2">
                </div>
                <div class="form-group">
                    <label>City</label>
                    <input type="text" name="city" required>
                </div>
                <div class="form-group">
                    <label>State</label>
                    <input type="text" name="state" required>
                </div>
                <div class="form-group">
                    <label>Pincode</label>
                    <input type="text" name="pincode" required>
                </div>
                <div class="form-group checkbox-group">
                    <input type="checkbox" name="is_default" id="add_default">
                    <label for="add_default" style="margin: 0;">Set as default address</label>
                </div>
                <div class="modal-buttons">
                    <button type="submit" name="add_address" class="btn btn-primary">Add Address</button>
                    <button type="button" class="btn btn-danger" onclick="closeModal('addModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Address Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <h2>Edit Address</h2>
            <form method="POST" id="editForm">
                <input type="hidden" name="address_id" id="edit_id">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" id="edit_full_name" required>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" name="phone" id="edit_phone" required>
                </div>
                <div class="form-group">
                    <label>Address Line 1</label>
                    <input type="text" name="address_line1" id="edit_address_line1" required>
                </div>
                <div class="form-group">
                    <label>Address Line 2 (Optional)</label>
                    <input type="text" name="address_line2" id="edit_address_line2">
                </div>
                <div class="form-group">
                    <label>City</label>
                    <input type="text" name="city" id="edit_city" required>
                </div>
                <div class="form-group">
                    <label>State</label>
                    <input type="text" name="state" id="edit_state" required>
                </div>
                <div class="form-group">
                    <label>Pincode</label>
                    <input type="text" name="pincode" id="edit_pincode" required>
                </div>
                <div class="form-group checkbox-group">
                    <input type="checkbox" name="is_default" id="edit_default">
                    <label for="edit_default" style="margin: 0;">Set as default address</label>
                </div>
                <div class="modal-buttons">
                    <button type="submit" name="edit_address" class="btn btn-primary">Update Address</button>
                    <button type="button" class="btn btn-danger" onclick="closeModal('editModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openAddModal() {
            document.getElementById('addModal').classList.add('active');
        }
        
        function openEditModal(address) {
            document.getElementById('edit_id').value = address.id;
            document.getElementById('edit_full_name').value = address.full_name;
            document.getElementById('edit_phone').value = address.phone;
            document.getElementById('edit_address_line1').value = address.address_line1;
            document.getElementById('edit_address_line2').value = address.address_line2;
            document.getElementById('edit_city').value = address.city;
            document.getElementById('edit_state').value = address.state;
            document.getElementById('edit_pincode').value = address.pincode;
            document.getElementById('edit_default').checked = address.is_default == 1;
            document.getElementById('editModal').classList.add('active');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
    </script>
</body>
</html>