<?php
require_once 'config.php';

$product_id = $_GET['id'];
$conn = getDBConnection();
$message = '';

// Handle Add to Cart
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    if (!isUserLoggedIn()) {
        redirect('login.php');
    }
    
    $user_id = $_SESSION['user_id'];
    
    // Check if already in cart
    $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update quantity
        $cart_item = $result->fetch_assoc();
        $new_quantity = $cart_item['quantity'] + 1;
        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_quantity, $cart_item['id']);
        $stmt->execute();
        $message = 'Product quantity updated in cart!';
    } else {
        // Add new item
        $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, 1)");
        $stmt->bind_param("ii", $user_id, $product_id);
        $stmt->execute();
        $message = 'Product added to cart!';
    }
    $stmt->close();
}

// Handle Add to Wishlist
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_wishlist'])) {
    if (!isUserLoggedIn()) {
        redirect('login.php');
    }
    
    $user_id = $_SESSION['user_id'];
    
    // Check if already in wishlist
    $stmt = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $product_id);
        $stmt->execute();
        $message = 'Product added to wishlist!';
    } else {
        $message = 'Product already in wishlist!';
    }
    $stmt->close();
}

// Get product details
$stmt = $conn->prepare("SELECT p.*, c.name as category_name FROM products p 
                        LEFT JOIN categories c ON p.category_id = c.id 
                        WHERE p.id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    redirect('index.php');
}

$product = $result->fetch_assoc();
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $product['name']; ?> - E-commerce</title>
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
        .nav { display: flex; gap: 20px; align-items: center; }
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
        .breadcrumb {
            margin-bottom: 30px;
            font-size: 14px;
            color: #7f8c8d;
        }
        .breadcrumb a { color: #3498db; text-decoration: none; }
        .product-detail {
            background: white;
            border-radius: 8px;
            padding: 40px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
        }
        .product-image {
            width: 100%;
            height: 500px;
            object-fit: cover;
            border-radius: 8px;
        }
        .product-info h1 {
            font-size: 32px;
            margin-bottom: 15px;
            color: #2c3e50;
        }
        .category-badge {
            display: inline-block;
            padding: 5px 15px;
            background: #ecf0f1;
            border-radius: 20px;
            font-size: 14px;
            color: #7f8c8d;
            margin-bottom: 20px;
        }
        .price {
            font-size: 36px;
            font-weight: bold;
            color: #3498db;
            margin-bottom: 20px;
        }
        .stock-info {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: bold;
        }
        .in-stock { background: #d4edda; color: #155724; }
        .out-of-stock { background: #f8d7da; color: #721c24; }
        .description {
            color: #555;
            line-height: 1.8;
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 1px solid #ecf0f1;
        }
        .actions {
            display: flex;
            gap: 15px;
        }
        .btn {
            padding: 15px 30px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .btn-primary {
            background: #3498db;
            color: white;
            flex: 1;
        }
        .btn-primary:hover { background: #2980b9; }
        .btn-secondary {
            background: #ecf0f1;
            color: #2c3e50;
        }
        .btn-secondary:hover { background: #bdc3c7; }
        .btn:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
            opacity: 0.6;
        }
        .success-message {
            padding: 15px;
            background: #d4edda;
            color: #155724;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        @media (max-width: 768px) {
            .product-detail { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <a href="index.php" class="logo">E-commerce Store</a>
            <nav class="nav">
                <?php if (isUserLoggedIn()): ?>
                    <a href="wishlist.php">Wishlist</a>
                    <a href="cart.php">Cart</a>
                    <a href="dashboard.php">My Account</a>
                    <a href="logout.php">Logout</a>
                <?php else: ?>
                    <a href="login.php">Login</a>
                    <a href="register.php">Register</a>
                <?php endif; ?>
            </nav>
        </div>
    </div>
    
    <div class="container">
        <div class="breadcrumb">
            <a href="index.php">Home</a> / <a href="index.php?category=<?php echo $product['category_id']; ?>"><?php echo $product['category_name']; ?></a> / <?php echo $product['name']; ?>
        </div>
        
        <?php if ($message): ?>
            <div class="success-message"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <div class="product-detail">
            <div>
                <img src="<?php echo UPLOAD_DIR . $product['image']; ?>" alt="<?php echo $product['name']; ?>" class="product-image">
            </div>
            
            <div class="product-info">
                <div class="category-badge"><?php echo $product['category_name']; ?></div>
                <h1><?php echo $product['name']; ?></h1>
                <div class="price"><?php echo formatPrice($product['price']); ?></div>
                
                <?php if ($product['stock_count'] > 0): ?>
                    <div class="stock-info in-stock">✓ In Stock (<?php echo $product['stock_count']; ?> available)</div>
                <?php else: ?>
                    <div class="stock-info out-of-stock">✗ Out of Stock</div>
                <?php endif; ?>
                
                <div class="description">
                    <h3 style="margin-bottom: 15px;">Description</h3>
                    <p><?php echo nl2br($product['description']); ?></p>
                </div>
                
                <div class="actions">
                    <form method="POST" style="flex: 1;">
                        <button type="submit" name="add_to_cart" class="btn btn-primary" 
                                <?php echo $product['stock_count'] == 0 ? 'disabled' : ''; ?>>
                            Add to Cart
                        </button>
                    </form>
                    <form method="POST">
                        <button type="submit" name="add_to_wishlist" class="btn btn-secondary"
                                <?php echo $product['stock_count'] == 0 ? 'disabled' : ''; ?>>
                            Add to Wishlist
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>
</html>