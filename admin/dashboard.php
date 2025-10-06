<?php
require_once '../config.php';
requireAdmin();

$conn = getDBConnection();

// Get statistics
$total_products = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];
$total_categories = $conn->query("SELECT COUNT(*) as count FROM categories")->fetch_assoc()['count'];
$total_orders = $conn->query("SELECT COUNT(*) as count FROM orders")->fetch_assoc()['count'];
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; }
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
        .logout { background: #e74c3c; padding: 8px 20px; border-radius: 4px; }
        .container { padding: 30px; }
        h1 { margin-bottom: 30px; color: #2c3e50; }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-card h3 { color: #7f8c8d; font-size: 14px; margin-bottom: 10px; }
        .stat-card .number { font-size: 36px; font-weight: bold; color: #2c3e50; }
        .quick-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        .quick-link {
            background: #3498db;
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-decoration: none;
            text-align: center;
            font-weight: bold;
            transition: background 0.3s;
        }
        .quick-link:hover { background: #2980b9; }
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
            <a href="logout.php" class="logout">Logout</a>
        </nav>
    </div>
    
    <div class="container">
        <h1>Dashboard Overview</h1>
        
        <div class="stats">
            <div class="stat-card">
                <h3>Total Products</h3>
                <div class="number"><?php echo $total_products; ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Categories</h3>
                <div class="number"><?php echo $total_categories; ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Orders</h3>
                <div class="number"><?php echo $total_orders; ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Users</h3>
                <div class="number"><?php echo $total_users; ?></div>
            </div>
        </div>
        
        <h2 style="margin-bottom: 20px;">Quick Links</h2>
        <div class="quick-links">
            <a href="categories.php" class="quick-link">Manage Categories</a>
            <a href="products.php" class="quick-link">Manage Products</a>
            <a href="orders.php" class="quick-link">View Orders</a>
        </div>
    </div>
</body>
</html>