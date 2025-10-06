<?php
require_once 'config.php';
requireUser();

if (!isset($_SESSION['order_success'])) {
    redirect('index.php');
}

$order_id = $_SESSION['order_success'];
unset($_SESSION['order_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed</title>
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
        }
        .logo { font-size: 24px; font-weight: bold; }
        .container {
            max-width: 600px;
            margin: 80px auto;
            padding: 0 20px;
        }
        .success-card {
            background: white;
            border-radius: 8px;
            padding: 60px 40px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .success-icon {
            width: 80px;
            height: 80px;
            background: #27ae60;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            font-size: 40px;
            color: white;
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 32px;
        }
        .order-id {
            color: #7f8c8d;
            margin-bottom: 30px;
            font-size: 18px;
        }
        .message {
            color: #555;
            line-height: 1.8;
            margin-bottom: 40px;
        }
        .actions {
            display: flex;
            gap: 15px;
            justify-content: center;
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
        }
        .btn-primary {
            background: #3498db;
            color: white;
        }
        .btn-primary:hover { background: #2980b9; }
        .btn-secondary {
            background: #ecf0f1;
            color: #2c3e50;
        }
        .btn-secondary:hover { background: #bdc3c7; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo">E-commerce Store</div>
        </div>
    </div>
    
    <div class="container">
        <div class="success-card">
            <div class="success-icon">✓</div>
            <h1>Order Placed Successfully!</h1>
            <div class="order-id">Order ID: #<?php echo $order_id; ?></div>
            <div class="message">
                Thank you for your order! Your order has been placed successfully and is now being processed.
                You will receive a confirmation email shortly with your order details.
            </div>
            <div class="actions">
                <a href="dashboard.php" class="btn btn-primary">View My Orders</a>
                <a href="index.php" class="btn btn-secondary">Continue Shopping</a>
            </div>
        </div>
    </div>
</body>
</html>