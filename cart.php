<?php
require_once 'config.php';
requireUser();

$user_id = $_SESSION['user_id'];
$conn = getDBConnection();
$message = '';

// Handle cart updates
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_quantity'])) {
        $cart_id = $_POST['cart_id'];
        $quantity = max(1, $_POST['quantity']);
        
        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("iii", $quantity, $cart_id, $user_id);
        $stmt->execute();
        $stmt->close();
        $message = 'Cart updated!';
    }
    
    if (isset($_POST['remove_item'])) {
        $cart_id = $_POST['cart_id'];
        
        $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $cart_id, $user_id);
        $stmt->execute();
        $stmt->close();
        $message = 'Item removed from cart!';
    }
}

// Get cart items
$cart_items = $conn->query("
    SELECT c.*, p.name, p.price, p.image, p.stock_count
    FROM cart c
    LEFT JOIN products p ON c.product_id = p.id
    WHERE c.user_id = $user_id
");

$total = 0;
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart</title>
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
        .cart-container {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
        }
        .cart-items {
            background: white;
            border-radius: 8px;
            padding: 20px;
        }
        .cart-item {
            display: flex;
            gap: 20px;
            padding: 20px 0;
            border-bottom: 1px solid #ecf0f1;
        }
        .cart-item:last-child { border-bottom: none; }
        .item-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }
        .item-details { flex: 1; }
        .item-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #2c3e50;
        }
        .item-price {
            font-size: 16px;
            color: #3498db;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .quantity-controls input {
            width: 60px;
            padding: 5px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-update { background: #3498db; color: white; }
        .btn-update:hover { background: #2980b9; }
        .btn-remove { background: #e74c3c; color: white; }
        .btn-remove:hover { background: #c0392b; }
        .cart-summary {
            background: white;
            border-radius: 8px;
            padding: 30px;
            height: fit-content;
        }
        .cart-summary h2 {
            margin-bottom: 20px;
            color: #2c3e50;
        }
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
        .checkout-btn {
            width: 100%;
            padding: 15px;
            background: #27ae60;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: block;
            text-align: center;
        }
        .checkout-btn:hover { background: #229954; }
        .empty-cart {
            background: white;
            border-radius: 8px;
            padding: 60px;
            text-align: center;
            color: #7f8c8d;
        }
        .empty-cart h2 { margin-bottom: 15px; }
        .empty-cart a {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 30px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        @media (max-width: 768px) {
            .cart-container { grid-template-columns: 1fr; }
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
        <h1>Shopping Cart</h1>
        
        <?php if ($message): ?>
            <div class="success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($cart_items->num_rows > 0): ?>
            <div class="cart-container">
                <div class="cart-items">
                    <?php while($item = $cart_items->fetch_assoc()): 
                        $subtotal = $item['price'] * $item['quantity'];
                        $total += $subtotal;
                    ?>
                        <div class="cart-item">
                            <img src="<?php echo UPLOAD_DIR . $item['image']; ?>" alt="<?php echo $item['name']; ?>" class="item-image">
                            <div class="item-details">
                                <div class="item-name"><?php echo $item['name']; ?></div>
                                <div class="item-price"><?php echo formatPrice($item['price']); ?> each</div>
                                <div class="quantity-controls">
                                    <form method="POST" style="display: inline-flex; gap: 10px; align-items: center;">
                                        <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                        <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock_count']; ?>">
                                        <button type="submit" name="update_quantity" class="btn btn-update">Update</button>
                                        <button type="submit" name="remove_item" class="btn btn-remove">Remove</button>
                                    </form>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 20px; font-weight: bold; color: #2c3e50;">
                                    <?php echo formatPrice($subtotal); ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                
                <div class="cart-summary">
                    <h2>Order Summary</h2>
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span><?php echo formatPrice($total); ?></span>
                    </div>
                    <div class="summary-row total">
                        <span>Total</span>
                        <span><?php echo formatPrice($total); ?></span>
                    </div>
                    <a href="checkout.php" class="checkout-btn">Proceed to Checkout</a>
                </div>
            </div>
        <?php else: ?>
            <div class="empty-cart">
                <h2>Your cart is empty</h2>
                <p>Add some products to your cart to continue shopping</p>
                <a href="index.php">Continue Shopping</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>