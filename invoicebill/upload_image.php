<?php
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'error' => 'Method not allowed']);
    exit;
}

require_once 'db.php';
check_auth();

if (!isset($_FILES['product_image']) || $_FILES['product_image']['error'] !== UPLOAD_ERR_OK) {
    $errCode = $_FILES['product_image']['error'] ?? 'no_file';
    echo json_encode(['status' => 'error', 'error' => 'No image uploaded or upload error: ' . $errCode]);
    exit;
}

$file = $_FILES['product_image'];
$maxSize = 10 * 1024 * 1024; // 10MB limit
if ($file['size'] > $maxSize) {
    echo json_encode(['status' => 'error', 'error' => 'File size exceeds 10MB limit']);
    exit;
}

// Validate image mime type using finfo
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$allowedMimes = [
    'image/jpeg' => 'jpg',
    'image/jpg'  => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/gif'  => 'gif'
];

if (!isset($allowedMimes[$mime])) {
    echo json_encode(['status' => 'error', 'error' => 'Invalid image format. Allowed: JPG, PNG, WEBP, GIF']);
    exit;
}

$ext = $allowedMimes[$mime];
$uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Generate unique safe filename
$filename = 'prod_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
$targetPath = $uploadDir . $filename;

if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    $relativeUrl = 'uploads/' . $filename;
    echo json_encode([
        'status' => 'success',
        'file_path' => $relativeUrl
    ]);
} else {
    echo json_encode(['status' => 'error', 'error' => 'Failed to save uploaded file']);
}
