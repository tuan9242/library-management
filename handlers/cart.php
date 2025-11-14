<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/cart.php';

function handle_cart(): void {
    if (!isLoggedIn()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
        exit;
    }
    
    $action = $_GET['action'] ?? '';
    
    if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $bookId = isset($_POST['book_id']) ? (int)$_POST['book_id'] : 0;
        $quantity = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;
        $durationDays = isset($_POST['duration_days']) ? max(1, (int)$_POST['duration_days']) : 30;
        $result = cart_add($_SESSION['user_id'], $bookId, $quantity, $durationDays);
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
    exit;
}
?>

