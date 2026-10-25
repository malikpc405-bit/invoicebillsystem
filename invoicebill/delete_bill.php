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

$body = file_get_contents('php://input');
$data = json_decode($body, true);
$id = isset($data['id']) ? intval($data['id']) : 0;

if ($id <= 0) {
    echo json_encode(['status' => 'error', 'error' => 'Invalid ID']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$stmt = $mysqli->prepare("DELETE FROM bills WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $user_id);
if ($stmt->execute()) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'error' => $mysqli->error]);
}
$stmt->close();
$mysqli->close();
?>
