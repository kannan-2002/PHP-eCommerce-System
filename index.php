<?php
require_once 'config.php';

$conn = getDBConnection();

// Get search and sort parameters
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';

// Build query
$query = "SELECT p.*, c.name as category_name FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.stock_count > 0";

if ($search) {
    $query .= " AND (p.name LIKE '%$search%' OR p.description LIKE '%$search%')";
}

if ($category_filter) {
    $query .= " AND p.category_id = '$category_filter'";
}

// Add sorting
switch($sort) {
    case 'price_low':
        $query .= " ORDER BY p.price ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY p.price DESC";
        break;
    case 'newest':
    default:
        $query .= " ORDER BY p.created_at DESC";
        break;
}

$products = $conn->query($query);
$categories = $conn->query("SELECT * FROM categories ORDER BY name");

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-commerce Store</title>
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
        .logo { font-size: 24px; font-weight: bold; }
        .nav { display: flex; gap: 20px; align-items: center; }
        .nav a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 4px;
        }
        .nav a:hover { background: #34495e; }
        .search-section {
            background: white;
            padding: 30px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .search-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .search-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .search-bar input {
            flex: 1;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .search-bar select {
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .btn {
            padding: 12px 24px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover { background: #2980b9; }
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 25px;
        }
        .product-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .product-card:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .product-image {
            width: 100%;
            height: 250px;
            object-fit: cover;
        }
        .product-info { padding: 20px; }
        .product-category {
            font-size: 12px;
            color: #7f8c8d;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .product-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        .product-price {
            font-size: 24px;
            font-weight: bold;
            color: #3498db;
            margin-bottom: 10px;
        }
        .product-description {
            font-size: 14px;
            color: #7f8c8d;
            margin-bottom: 15px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #7f8c8d;
        }
        .empty-state h2 { margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">E-commerce Store</div>
            <nav class="nav">
                <?php if (isUserLoggedIn()): ?>
                    <span>Welcome, <?php echo $_SESSION['user_name']; ?></span>
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
    
    <div class="search-section">
        <div class="search-content">
            <form method="GET" class="search-bar">
                <input type="text" name="search" placeholder="Search products..." value="<?php echo $search; ?>">
                <select name="category">
                    <option value="">All Categories</option>
                    <?php while($cat = $categories->fetch_assoc()): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo $category_filter == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo $cat['name']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                <select name="sort">
                    <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                    <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                    <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                </select>
                <button type="submit" class="btn">Search</button>
            </form>
        </div>
    </div>
    
    <div class="container">
        <?php if ($products->num_rows > 0): ?>
            <div class="products-grid">
                <?php while($product = $products->fetch_assoc()): ?>
                    <a href="product.php?id=<?php echo $product['id']; ?>" class="product-card">
                        <img src="<?php echo UPLOAD_DIR . $product['image']; ?>" alt="<?php echo $product['name']; ?>" class="product-image">
                        <div class="product-info">
                            <div class="product-category"><?php echo $product['category_name']; ?></div>
                            <div class="product-name"><?php echo $product['name']; ?></div>
                            <div class="product-price"><?php echo formatPrice($product['price']); ?></div>
                            <div class="product-description"><?php echo $product['description']; ?></div>
                        </div>
                    </a>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <h2>No products found</h2>
                <p>Try adjusting your search or filters</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>