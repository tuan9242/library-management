<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/user.php';

function handle_delete_user(): void {
    header('Content-Type: application/json');

    if (!isAdmin()) {
        echo json_encode(['success' => false, 'message' => 'Không có quyền']);
        exit;
    }

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Thiếu ID']);
        exit;
    }

    $ok = user_delete($id);
    echo json_encode(['success' => (bool)$ok]);
    exit;
}
?>

