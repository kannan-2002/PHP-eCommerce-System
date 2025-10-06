<?php
require_once 'config.php';
requireUser();

header('Content-Type: application/json');

$order_id = $_GET['order_id'];
$user_id = $_SESSION['user_id'];
$conn = getDBConnection();

// Get order details
$stmt = $conn->prepare("
    SELECT o.*, 
    a.full_name, a.phone, a.address_line1, a.address_line2, a.city, a.state, a.pincode
    FROM orders o
    LEFT JOIN addresses a ON o.address_id = a.id
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->bind_param("ii", $order_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo json_encode(['error' => 'Order not found']);
    exit;
}

$order = $result->fetch_assoc();
$stmt->close();

// Get order items
$stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

$stmt->close();
$conn->close();

// Prepare response
$response = [
    'order' => [
        'id' => $order['id'],
        'status' => $order['status'],
        'total_amount' => $order['total_amount'],
        'created_at' => $order['created_at']
    ],
    'address' => [
        'full_name' => $order['full_name'],
        'phone' => $order['phone'],
        'address_line1' => $order['address_line1'],
        'address_line2' => $order['address_line2'],
        'city' => $order['city'],
        'state' => $order['state'],
        'pincode' => $order['pincode']
    ],
    'items' => $items
];

echo json_encode($response);
?>