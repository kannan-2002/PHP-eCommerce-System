<?php
require_once 'config.php';
requireUser();

$user_id = $_SESSION['user_id'];
$conn = getDBConnection();
$error = '';

// Get cart items
$cart_items = $conn->query("
    SELECT c.*, p.name, p.price, p.stock_count
    FROM cart c
    LEFT JOIN products p ON c.product_id = p.id
    WHERE c.user_id = $user_id
");

if ($cart_items->num_rows == 0) {
    redirect('cart.php');
}

// Calculate total
$total = 0;
$items_array = [];
while ($item = $cart_items->fetch_assoc()) {
    $total += $item['price'] * $item['quantity'];
    $items_array[] = $item;
}

// Get user addresses
$addresses = $conn->query("SELECT * FROM addresses WHERE user_id = $user_id ORDER BY is_default DESC, created_at DESC");

// Handle order placement
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    $address_id = $_POST['address_id'];
    
    if (!$address_id) {
        $error = 'Please select a delivery address';
    } else {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Create order
            $stmt = $conn->prepare("INSERT INTO orders (user_id, address_id, total_amount, status) VALUES (?, ?, ?, 'On Process')");
            $stmt->bind_param("iid", $user_id, $address_id, $total);
            $stmt->execute();
            $order_id = $conn->insert_id;
            $stmt->close();
            
            // Add order items and reduce stock
            foreach ($items_array as $item) {
                // Check stock availability
                $stock_check = $conn->query("SELECT stock_count FROM products WHERE id = " . $item['product_id']);
                $current_stock = $stock_check->fetch_assoc()['stock_count'];
                
                if ($current_stock < $item['quantity']) {
                    throw new Exception('Insufficient stock for ' . $item['name']);
                }
                
                // Add order item
                $subtotal = $item['price'] * $item['quantity'];
                $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, product_price, quantity, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iisdid", $order_id, $item['product_id'], $item['name'], $item['price'], $item['quantity'], $subtotal);
                $stmt->execute();
                $stmt->close();
                
                // Reduce stock
                $new_stock = $current_stock - $item['quantity'];
                $stmt = $conn->prepare("UPDATE products SET stock_count = ? WHERE id = ?");
                $stmt->bind_param("ii", $new_stock, $item['product_id']);
                $stmt->execute();
                $stmt->close();
            }
            
            // Clear cart
            $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->close();
            
            // Commit transaction
            $conn->commit();
            
            // Redirect to success page
            $_SESSION['order_success'] = $order_id;
            redirect('order_success.php');
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
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
        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        h1 { margin-bottom: 30px; color: #2c3e50; }
        .error { padding: 15px; background: #f8d7da; color: #721c24; border-radius: 4px; margin-bottom: 20px; }
        .checkout-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }
        .section {
            background: white;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 20px;
        }
        .section h2 {
            margin-bottom: 20px;
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        .address-list { display: grid; gap: 15px; }
        .address-card {
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
        }
        .address-card:hover { border-color: #3498db; }
        .address-card.selected {
            border-color: #3498db;
            background: #ebf5fb;
        }
        .address-card input[type="radio"] {
            position: absolute;
            top: 20px;
            right: 20px;
        }
        .address-name {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 5px;
            color: #2c3e50;
        }
        .address-details {
            font-size: 14px;
            color: #555;
            line-height: 1.6;
        }
        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 15px 0;
            border-bottom: 1px solid #ecf0f1;
        }
        .order-item:last-child { border-bottom: none; }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #ecf0f1;
        }
        .summary-row.total {
            font-size: 24px;
            font-weight: bold;
            color: #3498db;
            border-bottom: none;
        }
        .btn {
            width: 100%;
            padding: 15px;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
        }
        .btn:hover { background: #229954; }
        .btn:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
        }
        .add-address-link {
            display: block;
            text-align: center;
            padding: 15px;
            background: #ecf0f1;
            color: #3498db;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            margin-top: 15px;
        }
        .add-address-link:hover { background: #bdc3c7; }
        @media (max-width: 768px) {
            .checkout-container { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <a href="index.php" class="logo">E-commerce Store</a>
        </div>
    </div>
    
    <div class="container">
        <h1>Checkout</h1>
        
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="checkout-container">
                <div>
                    <div class="section">
                        <h2>Select Delivery Address</h2>
                        
                        <?php if ($addresses->num_rows > 0): ?>
                            <div class="address-list">
                                <?php while($address = $addresses->fetch_assoc()): ?>
                                    <label class="address-card">
                                        <input type="radio" name="address_id" value="<?php echo $address['id']; ?>" 
                                               <?php echo $address['is_default'] ? 'checked' : ''; ?>>
                                        <div class="address-name"><?php echo $address['full_name']; ?></div>
                                        <div class="address-details">
                                            <?php echo $address['phone']; ?><br>
                                            <?php echo $address['address_line1']; ?><br>
                                            <?php if($address['address_line2']): ?>
                                                <?php echo $address['address_line2']; ?><br>
                                            <?php endif; ?>
                                            <?php echo $address['city']; ?>, <?php echo $address['state']; ?> - <?php echo $address['pincode']; ?>
                                        </div>
                                    </label>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p style="color: #7f8c8d; margin-bottom: 15px;">No saved addresses found. Please add an address to continue.</p>
                        <?php endif; ?>
                        
                        <a href="manage_addresses.php" class="add-address-link">+ Add New Address</a>
                    </div>
                </div>
                
                <div>
                    <div class="section">
                        <h2>Order Summary</h2>
                        
                        <?php foreach($items_array as $item): ?>
                            <div class="order-item">
                                <div>
                                    <strong><?php echo $item['name']; ?></strong><br>
                                    <small>Qty: <?php echo $item['quantity']; ?> × <?php echo formatPrice($item['price']); ?></small>
                                </div>
                                <div><strong><?php echo formatPrice($item['price'] * $item['quantity']); ?></strong></div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="summary-row total">
                            <span>Total</span>
                            <span><?php echo formatPrice($total); ?></span>
                        </div>
                        
                        <button type="submit" name="place_order" class="btn" 
                                <?php echo $addresses->num_rows == 0 ? 'disabled' : ''; ?>>
                            Place Order
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
    
    <script>
        // Highlight selected address
        document.querySelectorAll('.address-card').forEach(card => {
            card.addEventListener('click', function() {
                document.querySelectorAll('.address-card').forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
            });
            
            if (card.querySelector('input[type="radio"]').checked) {
                card.classList.add('selected');
            }
        });
    </script>
</body>
</html>