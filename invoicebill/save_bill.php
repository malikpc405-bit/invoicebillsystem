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

// Set timezone to Pakistan (UTC+5) so created_at matches local time
date_default_timezone_set('Asia/Karachi');
$mysqli->query("SET time_zone = '+05:00'");

$createBills = "CREATE TABLE IF NOT EXISTS bills (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bill_number VARCHAR(100),
    bill_date VARCHAR(50),
    bill_time VARCHAR(50),
    salesman VARCHAR(100),
    sub_total DECIMAL(12,2) DEFAULT 0,
    discount_percent DECIMAL(5,2) DEFAULT 0,
    discount_amount DECIMAL(12,2) DEFAULT 0,
    payment DECIMAL(12,2) DEFAULT 0,
    change_amount DECIMAL(12,2) DEFAULT 0,
    grand_total DECIMAL(12,2) DEFAULT 0,
    total_profit DECIMAL(12,2) DEFAULT 0,
    user_id INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
$mysqli->query($createBills);

$createItems = "CREATE TABLE IF NOT EXISTS bill_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bill_id INT NOT NULL,
    description TEXT,
    size VARCHAR(50),
    price DECIMAL(12,2) DEFAULT 0,
    quantity INT DEFAULT 0,
    discount_percent DECIMAL(5,2) DEFAULT 0,
    amount DECIMAL(12,2) DEFAULT 0,
    purchase_price DECIMAL(12,2) DEFAULT 0,
    profit DECIMAL(12,2) DEFAULT 0,
    FOREIGN KEY (bill_id) REFERENCES bills(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
$mysqli->query($createItems);

function ensureColumnExists($mysqli, $table, $column, $definition) {
    $result = $mysqli->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    if ($result && $result->num_rows === 0) {
        $mysqli->query("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
    }
}

ensureColumnExists($mysqli, 'bills', 'discount_percent', 'DECIMAL(5,2) DEFAULT 0');
ensureColumnExists($mysqli, 'bills', 'discount_amount', 'DECIMAL(12,2) DEFAULT 0');
ensureColumnExists($mysqli, 'bills', 'payment', 'DECIMAL(12,2) DEFAULT 0');
ensureColumnExists($mysqli, 'bills', 'change_amount', 'DECIMAL(12,2) DEFAULT 0');
ensureColumnExists($mysqli, 'bills', 'grand_total', 'DECIMAL(12,2) DEFAULT 0');
ensureColumnExists($mysqli, 'bills', 'total_profit', 'DECIMAL(12,2) DEFAULT 0');
ensureColumnExists($mysqli, 'bills', 'user_id', 'INT DEFAULT 0');
ensureColumnExists($mysqli, 'bill_items', 'discount_percent', 'DECIMAL(5,2) DEFAULT 0');
ensureColumnExists($mysqli, 'bill_items', 'purchase_price', 'DECIMAL(12,2) DEFAULT 0');
ensureColumnExists($mysqli, 'bill_items', 'profit', 'DECIMAL(12,2) DEFAULT 0');
ensureColumnExists($mysqli, 'bill_items', 'image', 'VARCHAR(255) DEFAULT ""');

$body = file_get_contents('php://input');
if (empty($body)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'error' => 'Empty request body']);
    exit;
}

$data = json_decode($body, true);
if ($data === null) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'error' => 'Invalid JSON payload', 'debug' => $body]);
    exit;
}

$billNumber = isset($data['billNumber']) ? $mysqli->real_escape_string($data['billNumber']) : '';
$billDate = isset($data['date']) ? $mysqli->real_escape_string($data['date']) : '';
$billTime = isset($data['time']) ? $mysqli->real_escape_string($data['time']) : '';
$salesman = isset($data['salesman']) ? $mysqli->real_escape_string($data['salesman']) : '';
$subTotal = isset($data['subTotal']) ? floatval($data['subTotal']) : 0;
$discountPercent = isset($data['discountPercent']) ? floatval($data['discountPercent']) : 0;
$discountAmount = isset($data['discountAmount']) ? floatval($data['discountAmount']) : 0;
$payment = isset($data['payment']) ? floatval($data['payment']) : 0;
$changeAmount = isset($data['change']) ? floatval($data['change']) : 0;
$grandTotal = isset($data['grandTotal']) ? floatval($data['grandTotal']) : 0;
$totalProfit = isset($data['totalProfit']) ? floatval($data['totalProfit']) : 0;
$user_id = $_SESSION['user_id'] ?? 0;

$insertBill = $mysqli->prepare('INSERT INTO bills (bill_number, bill_date, bill_time, salesman, sub_total, discount_percent, discount_amount, payment, change_amount, grand_total, total_profit, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
$insertBill->bind_param('ssssdddddddi', $billNumber, $billDate, $billTime, $salesman, $subTotal, $discountPercent, $discountAmount, $payment, $changeAmount, $grandTotal, $totalProfit, $user_id);
if (!$insertBill->execute()) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'error' => 'Failed to save bill: ' . $insertBill->error]);
    exit;
}

$billId = $mysqli->insert_id;
$insertBill->close();

if (!empty($data['items']) && is_array($data['items'])) {
    $insertItem = $mysqli->prepare('INSERT INTO bill_items (bill_id, description, size, price, quantity, discount_percent, amount, purchase_price, profit, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    foreach ($data['items'] as $item) {
        $description = isset($item['description']) ? $mysqli->real_escape_string($item['description']) : '';
        $size = isset($item['size']) ? $mysqli->real_escape_string($item['size']) : '';
        $price = floatval($item['price'] ?? 0);
        $quantity = intval($item['quantity'] ?? 0);
        $discountPercent = floatval($item['discountPercent'] ?? 0);
        $amount = floatval($item['amount'] ?? 0);
        $purchasePrice = floatval($item['purchasePrice'] ?? 0);
        $profit = floatval($item['profit'] ?? 0);
        $image = isset($item['image']) ? $mysqli->real_escape_string($item['image']) : '';
        $insertItem->bind_param('issiddddds', $billId, $description, $size, $price, $quantity, $discountPercent, $amount, $purchasePrice, $profit, $image);
        $insertItem->execute();
    }
    $insertItem->close();
}

echo json_encode(['status' => 'success', 'bill_id' => $billId]);
$mysqli->close();
