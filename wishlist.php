<?php
require_once 'config.php';
requireUser();

$user_id = $_SESSION['user_id'];
$conn = getDBConnection();
$message = '';

// Handle remove from wishlist
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['remove_item'])) {
    $wishlist_id = $_POST['wishlist_id'];
    
    $stmt = $conn->prepare("DELETE FROM wishlist WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $wishlist_id, $user_id);
    $stmt->execute();
    $stmt->close();
    $message = 'Item removed from wishlist!';
}

// Get wishlist items
$wishlist_items = $conn->query("
    SELECT w.*, p.name, p.price, p.image, p.stock_count, p.id as product_id
    FROM wishlist w
    LEFT JOIN products p ON w.product_id = p.id
    WHERE w.user_id = $user_id
    ORDER BY w.created_at DESC
");

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist</title>
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
        .wishlist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }
        .wishlist-item {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .item-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
        }
        .item-info { padding: 20px; }
        .item-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        .item-price {
            font-size: 24px;
            font-weight: bold;
            color: #3498db;
            margin-bottom: 15px;
        }
        .stock-badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 15px;
            display: inline-block;
        }
        .in-stock { background: #d4edda; color: #155724; }
        .out-stock { background: #f8d7da; color: #721c24; }
        .actions {
            display: flex;
            gap: 10px;
        }
        .btn {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            text-decoration: none;
            text-align: center;
            display: block;
        }
        .btn-view {
            background: #3498db;
            color: white;
        }
        .btn-view:hover { background: #2980b9; }
        .btn-remove {
            background: #e74c3c;
            color: white;
        }
        .btn-remove:hover { background: #c0392b; }
        .btn:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
            opacity: 0.6;
        }
        .empty-wishlist {
            background: white;
            border-radius: 8px;
            padding: 60px;
            text-align: center;
            color: #7f8c8d;
        }
        .empty-wishlist h2 { margin-bottom: 15px; }
        .empty-wishlist a {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 30px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 4px;
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
        <h1>My Wishlist</h1>
        
        <?php if ($message): ?>
            <div class="success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($wishlist_items->num_rows > 0): ?>
            <div class="wishlist-grid">
                <?php while($item = $wishlist_items->fetch_assoc()): ?>
                    <div class="wishlist-item">
                        <img src="<?php echo UPLOAD_DIR . $item['image']; ?>" alt="<?php echo $item['name']; ?>" class="item-image">
                        <div class="item-info">
                            <div class="item-name"><?php echo $item['name']; ?></div>
                            <div class="item-price"><?php echo formatPrice($item['price']); ?></div>
                            
                            <?php if($item['stock_count'] > 0): ?>
                                <span class="stock-badge in-stock">In Stock</span>
                            <?php else: ?>
                                <span class="stock-badge out-stock">Out of Stock</span>
                            <?php endif; ?>
                            
                            <div class="actions">
                                <a href="product.php?id=<?php echo $item['product_id']; ?>" class="btn btn-view">View Product</a>
                                <form method="POST" style="flex: 1;">
                                    <input type="hidden" name="wishlist_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" name="remove_item" class="btn btn-remove">Remove</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-wishlist">
                <h2>Your wishlist is empty</h2>
                <p>Save your favorite items here for later</p>
                <a href="index.php">Browse Products</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>