<?php
require_once 'config.php';
requireUser();

$user_id = $_SESSION['user_id'];
$conn = getDBConnection();

// Get user orders
$orders = $conn->query("
    SELECT o.*, 
    (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
    FROM orders o
    WHERE o.user_id = $user_id
    ORDER BY o.created_at DESC
");

// Get wishlist count
$wishlist_count = $conn->query("SELECT COUNT(*) as count FROM wishlist WHERE user_id = $user_id")->fetch_assoc()['count'];

// Get saved addresses count
$address_count = $conn->query("SELECT COUNT(*) as count FROM addresses WHERE user_id = $user_id")->fetch_assoc()['count'];

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard</title>
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
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-card h3 { color: #7f8c8d; font-size: 14px; margin-bottom: 10px; }
        .stat-card .number { font-size: 36px; font-weight: bold; color: #2c3e50; margin-bottom: 10px; }
        .stat-card a {
            color: #3498db;
            text-decoration: none;
            font-weight: bold;
        }
        .section {
            background: white;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .section h2 {
            margin-bottom: 25px;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        .order-list { display: grid; gap: 20px; }
        .order-card {
            border: 1px solid #ecf0f1;
            border-radius: 8px;
            padding: 20px;
            transition: box-shadow 0.3s;
        }
        .order-card:hover { box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #ecf0f1;
        }
        .order-id {
            font-size: 18px;
            font-weight: bold;
            color: #2c3e50;
        }
        .order-date {
            color: #7f8c8d;
            font-size: 14px;
        }
        .order-details {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        .order-info {
            flex: 1;
        }
        .order-info p {
            color: #555;
            margin-bottom: 5px;
        }
        .status-badge {
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
        }
        .status-process { background: #fff3cd; color: #856404; }
        .status-shipped { background: #cce5ff; color: #004085; }
        .status-delivered { background: #d4edda; color: #155724; }
        .btn {
            padding: 10px 20px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-weight: bold;
        }
        .btn:hover { background: #2980b9; }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #7f8c8d;
        }
        .empty-state h3 { margin-bottom: 10px; }
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
            padding: 15px;
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
        <h1>Welcome, <?php echo $_SESSION['user_name']; ?>!</h1>
        
        <div class="stats">
            <div class="stat-card">
                <h3>Total Orders</h3>
                <div class="number"><?php echo $orders->num_rows; ?></div>
                <a href="#orders-section">View Orders</a>
            </div>
            <div class="stat-card">
                <h3>Wishlist Items</h3>
                <div class="number"><?php echo $wishlist_count; ?></div>
                <a href="wishlist.php">View Wishlist</a>
            </div>
            <div class="stat-card">
                <h3>Saved Addresses</h3>
                <div class="number"><?php echo $address_count; ?></div>
                <a href="manage_addresses.php">Manage Addresses</a>
            </div>
        </div>
        
        <div class="section" id="orders-section">
            <h2>Order History</h2>
            
            <?php if ($orders->num_rows > 0): ?>
                <div class="order-list">
                    <?php while($order = $orders->fetch_assoc()): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div>
                                    <div class="order-id">Order #<?php echo $order['id']; ?></div>
                                    <div class="order-date">Placed on <?php echo date('F d, Y', strtotime($order['created_at'])); ?></div>
                                </div>
                                <?php 
                                $status_class = 'status-process';
                                if ($order['status'] == 'Shipped') $status_class = 'status-shipped';
                                if ($order['status'] == 'Delivered') $status_class = 'status-delivered';
                                ?>
                                <span class="status-badge <?php echo $status_class; ?>">
                                    <?php echo $order['status']; ?>
                                </span>
                            </div>
                            <div class="order-details">
                                <div class="order-info">
                                    <p><strong>Total Amount:</strong> <?php echo formatPrice($order['total_amount']); ?></p>
                                    <p><strong>Items:</strong> <?php echo $order['item_count']; ?> item(s)</p>
                                </div>
                                <button class="btn" onclick="viewOrderDetails(<?php echo $order['id']; ?>)">View Details</button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No orders yet</h3>
                    <p>Start shopping to see your orders here</p>
                    <a href="index.php" class="btn" style="margin-top: 20px;">Browse Products</a>
                </div>
            <?php endif; ?>
        </div>
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
        function viewOrderDetails(orderId) {
            fetch('get_order_details.php?order_id=' + orderId)
                .then(response => response.json())
                .then(data => {
                    let statusClass = 'status-process';
                    if (data.order.status === 'Shipped') statusClass = 'status-shipped';
                    if (data.order.status === 'Delivered') statusClass = 'status-delivered';
                    
                    let html = `
                        <div style="margin-bottom: 20px;">
                            <h3>Order #${data.order.id}</h3>
                            <p><strong>Order Date:</strong> ${new Date(data.order.created_at).toLocaleDateString()}</p>
                            <p><strong>Status:</strong> <span class="status-badge ${statusClass}">${data.order.status}</span></p>
                            <p><strong>Total Amount:</strong> ${formatPrice(data.order.total_amount)}</p>
                        </div>
                        
                        <div style="margin-bottom: 20px;">
                            <h3>Delivery Address</h3>
                            <div style="color: #555; line-height: 1.6;">
                                <strong>${data.address.full_name}</strong><br>
                                Phone: ${data.address.phone}<br>
                                ${data.address.address_line1}<br>
                                ${data.address.address_line2 ? data.address.address_line2 + '<br>' : ''}
                                ${data.address.city}, ${data.address.state} - ${data.address.pincode}
                            </div>
                        </div>
                        
                        <div>
                            <h3>Order Items</h3>
                            ${data.items.map(item => `
                                <div class="order-item">
                                    <div>
                                        <strong>${item.product_name}</strong><br>
                                        <small>Quantity: ${item.quantity} × ${formatPrice(item.product_price)}</small>
                                    </div>
                                    <div><strong>${formatPrice(item.subtotal)}</strong></div>
                                </div>
                            `).join('')}
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
            return '₹' + parseFloat(price).toFixed(2);
        }
    </script>
</body>
</html>