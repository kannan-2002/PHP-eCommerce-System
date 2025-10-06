<?php
require_once '../config.php';
requireAdmin();

$conn = getDBConnection();
$message = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $order_id);
    if ($stmt->execute()) {
        $message = 'Order status updated successfully';
    }
    $stmt->close();
}

// Get all orders with user details
$orders = $conn->query("
    SELECT o.*, u.name as user_name, u.email as user_email,
    a.full_name, a.phone, a.address_line1, a.address_line2, a.city, a.state, a.pincode
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN addresses a ON o.address_id = a.id
    ORDER BY o.created_at DESC
");

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders</title>
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
        table {
            width: 100%;
            background: white;
            border-collapse: collapse;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #34495e; color: white; }
        .btn {
            padding: 8px 15px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn:hover { background: #2980b9; }
        .status-badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-process { background: #fff3cd; color: #856404; }
        .status-shipped { background: #cce5ff; color: #004085; }
        .status-delivered { background: #d4edda; color: #155724; }
        select { padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .order-details { font-size: 13px; color: #555; line-height: 1.6; }
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
            max-width: 700px;
            margin: 20px;
            max-height: 90vh;
            overflow-y: auto;
        }
        .order-item {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
        }
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
        <h1>Manage Orders</h1>
        
        <?php if ($message): ?>
            <div class="success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <table>
            <thead>
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Total Amount</th>
                    <th>Status</th>
                    <th>Order Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while($order = $orders->fetch_assoc()): ?>
                <tr>
                    <td>#<?php echo $order['id']; ?></td>
                    <td>
                        <strong><?php echo $order['user_name']; ?></strong><br>
                        <small><?php echo $order['user_email']; ?></small>
                    </td>
                    <td><?php echo formatPrice($order['total_amount']); ?></td>
                    <td>
                        <?php 
                        $status_class = 'status-process';
                        if ($order['status'] == 'Shipped') $status_class = 'status-shipped';
                        if ($order['status'] == 'Delivered') $status_class = 'status-delivered';
                        ?>
                        <span class="status-badge <?php echo $status_class; ?>">
                            <?php echo $order['status']; ?>
                        </span>
                    </td>
                    <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                    <td>
                        <button class="btn" onclick='viewOrder(<?php echo json_encode($order); ?>)'>View Details</button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Order Details Modal -->
    <div id="orderModal" class="modal">
        <div class="modal-content">
            <h2>Order Details</h2>
            <div id="orderContent"></div>
            <button class="btn" onclick="closeModal()" style="margin-top: 20px;">Close</button>
        </div>
    </div>
    
    <script>
        function viewOrder(order) {
            // Fetch order items
            fetch('get_order_items.php?order_id=' + order.id)
                .then(response => response.json())
                .then(items => {
                    let html = `
                        <div style="margin-bottom: 20px;">
                            <h3>Order #${order.id}</h3>
                            <p><strong>Customer:</strong> ${order.user_name} (${order.user_email})</p>
                            <p><strong>Order Date:</strong> ${new Date(order.created_at).toLocaleDateString()}</p>
                            <p><strong>Total Amount:</strong> ${formatPrice(order.total_amount)}</p>
                        </div>
                        
                        <div style="margin-bottom: 20px;">
                            <h3>Delivery Address</h3>
                            <div class="order-details">
                                <strong>${order.full_name}</strong><br>
                                Phone: ${order.phone}<br>
                                ${order.address_line1}<br>
                                ${order.address_line2 ? order.address_line2 + '<br>' : ''}
                                ${order.city}, ${order.state} - ${order.pincode}
                            </div>
                        </div>
                        
                        <div style="margin-bottom: 20px;">
                            <h3>Order Items</h3>
                            ${items.map(item => `
                                <div class="order-item">
                                    <div>
                                        <strong>${item.product_name}</strong><br>
                                        <small>Quantity: ${item.quantity} × ${formatPrice(item.product_price)}</small>
                                    </div>
                                    <div><strong>${formatPrice(item.subtotal)}</strong></div>
                                </div>
                            `).join('')}
                        </div>
                        
                        <div>
                            <h3>Update Order Status</h3>
                            <form method="POST">
                                <input type="hidden" name="order_id" value="${order.id}">
                                <input type="hidden" name="update_status" value="1">
                                <select name="status" required style="margin-right: 10px;">
                                    <option value="On Process" ${order.status === 'On Process' ? 'selected' : ''}>On Process</option>
                                    <option value="Shipped" ${order.status === 'Shipped' ? 'selected' : ''}>Shipped</option>
                                    <option value="Delivered" ${order.status === 'Delivered' ? 'selected' : ''}>Delivered</option>
                                </select>
                                <button type="submit" class="btn">Update Status</button>
                            </form>
                        </div>
                    `;
                    
                    document.getElementById('orderContent').innerHTML = html;
                    document.getElementById('orderModal').classList.add('active');
                });
        }
        
        function closeModal() {
            document.getElementById('orderModal').classList.remove('active');
        }
        
        function formatPrice(price) {
            return '$' + parseFloat(price).toFixed(2);
        }
    </script>
</body>
</html>