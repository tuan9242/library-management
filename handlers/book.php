<?php
require_once __DIR__ . '/../config/database.php';

function handle_upload_cover(): void {
    header('Content-Type: application/json');

    if (!isLibrarian()) {
        echo json_encode(['success' => false, 'message' => 'Không có quyền']);
        return;
    }

    if (!isset($_FILES['cover_image']) || $_FILES['cover_image']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'Không có file tải lên']);
        return;
    }

    $file = $_FILES['cover_image'];
    $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
    if (!in_array($file['type'], $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Định dạng không hợp lệ']);
        return;
    }

    $uploadDir = __DIR__ . '/../public/uploads/books/';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0775, true);
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'cover_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = $uploadDir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        echo json_encode(['success' => false, 'message' => 'Không thể lưu file']);
        return;
    }

    $url = 'uploads/books/' . $filename;
    echo json_encode(['success' => true, 'url' => $url]);
}
?>

