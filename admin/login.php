<?php
// Start session first
session_start();

// Check if already logged in
if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$debug_info = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Database configuration
    $servername = "localhost";
    $username = "root";
    $password_db = "";
    $dbname = "ecommerce_db";
    
    // Create connection
    $conn = new mysqli($servername, $username, $password_db, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
        $error = "Connection failed: " . $conn->connect_error;
    } else {
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        
        $stmt = $conn->prepare("SELECT id, email, password FROM admins WHERE email = ?");
        
        if ($stmt === false) {
            $error = "Database error: " . $conn->error;
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $admin = $result->fetch_assoc();
                
                // Verify password
                if (password_verify($password, $admin['password'])) {
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_email'] = $admin['email'];
                    header("Location: dashboard.php");
                    exit();
                } else {
                    $error = 'Invalid email or password';
                    // For debugging - remove in production
                    $debug_info = "Email found but password incorrect";
                }
            } else {
                $error = 'Invalid email or password';
                $debug_info = "Email not found in database";
            }
            
            $stmt->close();
        }
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f4f4f4; }
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h2 { margin-bottom: 20px; color: #333; text-align: center; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; color: #555; }
        input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        .btn {
            width: 100%;
            padding: 12px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover { background: #0056b3; }
        .error { color: red; margin-bottom: 15px; text-align: center; }
        .info { margin-top: 15px; padding: 10px; background: #e9ecef; border-radius: 4px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Admin Login</h2>
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($debug_info && !empty($_POST)): ?>
            <div style="padding: 10px; background: #fff3cd; color: #856404; border-radius: 4px; margin-bottom: 15px; font-size: 12px;">
                Debug: <?php echo $debug_info; ?>
            </div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit" class="btn">Login</button>
        </form>
        <div class="info">
            <strong>Default Credentials:</strong><br>
            Email: admin@ecommerce.com<br>
            Password: admin123
        </div>
    </div>
</body>
</html>