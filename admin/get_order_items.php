<?php
require_once '../config.php';
requireAdmin();

header('Content-Type: application/json');

$order_id = $_GET['order_id'];
$conn = getDBConnection();

$stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

echo json_encode($items);

$stmt->close();
$conn->close();
?>