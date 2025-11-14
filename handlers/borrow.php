<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/borrow.php';

function handle_borrow_book(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['book_id'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit;
    }
    
    if (!isLoggedIn()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
        exit;
    }
    
    $quantity = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;
    $durationDays = isset($_POST['duration_days']) ? max(1, (int)$_POST['duration_days']) : 30;
    $pricePerDay = isset($_POST['price_per_day']) ? max(0, (int)$_POST['price_per_day']) : 0;
    $result = borrow_create($_SESSION['user_id'], (int)$_POST['book_id'], $quantity, $durationDays, $pricePerDay);
    
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

function handle_approve_borrow(): void {
    if (!isLibrarian()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Không có quyền']);
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['borrow_id'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit;
    }
    
    $result = borrow_approve((int)$_POST['borrow_id']);
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

function handle_reject_borrow(): void {
    if (!isLibrarian()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Không có quyền']);
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['borrow_id'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit;
    }
    
    $reason = isset($_POST['reason']) ? sanitize($_POST['reason']) : '';
    $result = borrow_reject((int)$_POST['borrow_id'], $reason);
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

function handle_return_borrow(): void {
    if (!isLibrarian()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Không có quyền']);
        exit;
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['borrow_id'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Invalid request']);
        exit;
    }
    
    $result = borrow_return_book((int)$_POST['borrow_id']);
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}
?>

