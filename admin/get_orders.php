<?php
header('Content-Type: application/json');

// Database connection
$mysqli = new mysqli("localhost", "root", "", "momms_db");

if ($mysqli->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "Database connection failed: " . $mysqli->connect_error]);
    exit;
}

// Query
$sql = "SELECT id, date, `order`, quantity, amount FROM transactions ORDER BY date DESC";
$result = $mysqli->query($sql);

$transactions = [];

while ($row = $result->fetch_assoc()) {
    $transactions[] = [
        'id' => $row['id'],
        'date' => $row['date'],
        'order' => $row['order'],
        'quantity' => $row['quantity'],
        'amount' => $row['amount']
    ];
}

echo json_encode($transactions);
$mysqli->close();
?>
