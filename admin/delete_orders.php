<?php
// CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Accept");
header("Content-Type: application/json");

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// DB connection
include 'db.php';

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => "Database connection failed: " . $conn->connect_error]);
    exit;
}

// Get input JSON
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['order_ids']) || !is_array($input['order_ids'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid or missing order IDs']);
    exit;
}

$order_ids = $input['order_ids'];

// Start transaction
$conn->begin_transaction();

try {
    // Delete from transactions (optional)
    $in = implode(',', array_fill(0, count($order_ids), '?'));
    $stmt_tx = $conn->prepare("DELETE FROM transactions WHERE id IN ($in)");
    $types = str_repeat('i', count($order_ids));
    $stmt_tx->bind_param($types, ...$order_ids);
    $stmt_tx->execute();
    $stmt_tx->close();

    // Delete from order_items
    $stmt_items = $conn->prepare("DELETE FROM order_items WHERE order_id IN ($in)");
    $stmt_items->bind_param($types, ...$order_ids);
    $stmt_items->execute();
    $stmt_items->close();

    // Delete from orders
    $stmt_orders = $conn->prepare("DELETE FROM orders WHERE id IN ($in)");
    $stmt_orders->bind_param($types, ...$order_ids);
    $stmt_orders->execute();
    $stmt_orders->close();

    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
