<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'error' => 'Method not allowed']);
    exit;
}

require_once 'db.php';
check_auth();

$mysqli->set_charset('utf8mb4');

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !isset($data['id'])) {
    echo json_encode(['status' => 'error', 'error' => 'Invalid data']);
    exit;
}

$id = intval($data['id']);
$desc = $mysqli->real_escape_string($data['description'] ?? '');
$price = floatval($data['price'] ?? 0);
$qty = intval($data['quantity'] ?? 0);
$discountPercent = floatval($data['discount_percent'] ?? ($data['discountPercent'] ?? 0));
$rawAmount = $price * $qty;
$amount = max(0, $rawAmount - ($rawAmount * $discountPercent / 100));

// Update item
$user_id = (int)$_SESSION['user_id'];
$update = $mysqli->query("UPDATE bill_items SET description = '$desc', price = $price, quantity = $qty, discount_percent = $discountPercent, amount = $amount WHERE id = $id AND bill_id IN (SELECT id FROM bills WHERE user_id = $user_id)");

if ($update) {
    // Recalculate bill total
    $res = $mysqli->query("SELECT bill_id FROM bill_items WHERE id = $id");
    if ($res && $row = $res->fetch_assoc()) {
        $billId = $row['bill_id'];
        $sumRes = $mysqli->query("SELECT SUM(amount) as total FROM bill_items WHERE bill_id = $billId");
        $total = ($sumRes && $sumRow = $sumRes->fetch_assoc()) ? $sumRow['total'] : 0;
        $mysqli->query("UPDATE bills SET sub_total = $total, grand_total = $total WHERE id = $billId");
    }
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'error' => $mysqli->error]);
}

$mysqli->close();
?>
