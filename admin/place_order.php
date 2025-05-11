<?php
// Add CORS headers at the very top
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Accept");

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include database connection
include 'db.php';

// Validate database connection
if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(['status' => 'error', 'message' => "Database connection failed: " . $conn->connect_error]));
}

// Get and validate JSON input
$input = json_decode(file_get_contents('php://input'), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    die(json_encode(['status' => 'error', 'message' => 'Invalid JSON data']));
}

if (empty($input) || !is_array($input)) {
    http_response_code(400);
    die(json_encode(['status' => 'error', 'message' => 'Empty or invalid cart data']));
}

// Start transaction to ensure atomicity
$conn->begin_transaction();
$order_id = null;
$order_total = 0; // Initialize order total

try {
    // 1. Insert a new record into the 'orders' table
    $stmt_order = $conn->prepare("INSERT INTO orders (order_date, total_price) VALUES (NOW(), ?)");
    if ($stmt_order === false) {
        throw new Exception("Error preparing order statement: " . $conn->error);
    }

    // Calculate total price from items
    foreach ($input as $item) {
        $order_total += $item['price'] * $item['quantity'];
    }

    $stmt_order->bind_param("d", $order_total);  // "d" for double (total_price)
    $stmt_order->execute();
    if ($conn->errno) {
        throw new Exception("Error executing order statement: " . $conn->error);
    }
    $order_id = $conn->insert_id;
    $stmt_order->close();

    if ($order_id === null || $order_id === 0) {
        throw new Exception("Failed to retrieve last insert ID for order.");
    }

    // 2. Loop through the cart items and insert each product into 'order_items'
    $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_name, quantity, price, total_price) VALUES (?, ?, ?, ?, ?)");
    if ($stmt_item === false) {
        throw new Exception("Error preparing order item statement: " . $conn->error);
    }

    foreach ($input as $item) {
        $product_name = $item['name'];
        $product_price = $item['price'];
        $quantity = $item['quantity'];
        $item_total_price = $product_price * $quantity;

        $stmt_item->bind_param("isidd", $order_id, $product_name, $quantity, $product_price, $item_total_price);

        if (!$stmt_item->execute()) {
            throw new Exception("Failed to insert item: " . $stmt_item->error);
        }
    }
        // 3. Insert summary record into 'transactions' table
        $total_quantity = 0;
        $order_summary = [];
    
        foreach ($input as $item) {
            $order_summary[] = $item['name'];
            $total_quantity += $item['quantity'];
        }
    
        $order_text = implode(", ", $order_summary); // e.g., "Burger, Fries, Coke"
    
        $stmt_tx = $conn->prepare("INSERT INTO transactions (date, `order`, quantity, amount) VALUES (NOW(), ?, ?, ?)");
        if ($stmt_tx === false) {
            throw new Exception("Error preparing transaction statement: " . $conn->error);
        }
    
        $stmt_tx->bind_param("sid", $order_text, $total_quantity, $order_total);
    
        if (!$stmt_tx->execute()) {
            throw new Exception("Failed to insert transaction: " . $stmt_tx->error);
        }
    
        $stmt_tx->close();

    // Commit the transaction
    $conn->commit();

    // Return success response
    echo json_encode(['status' => 'success', 'message' => 'Order placed successfully', 'order_id' => $order_id, 'total_price' => $order_total]);

} catch (Exception $e) {
    // Rollback the transaction on error
    $conn->rollback();
    $error_message = 'Error placing order: ' . $e->getMessage();
    error_log($error_message);
    
    // Return error response
    echo json_encode(['status' => 'error', 'message' => $error_message]);
    http_response_code(500); // Set HTTP status code to 500 for server error
} finally {
    // Close the connection
    $conn->close();
}
?>