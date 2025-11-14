<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../functions/notification.php';

function handle_notifications(): void {
    if (!isLoggedIn()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Chưa đăng nhập']);
        exit;
    }
    
    $action = $_GET['action'] ?? '';
    
    if ($action === 'get') {
        $notifications = notification_get_by_user($_SESSION['user_id'], 10, false);
        // Format time ago
        foreach ($notifications as &$notif) {
            $created = new DateTime($notif['created_at']);
            $now = new DateTime();
            $diff = $now->diff($created);
            
            if ($diff->days > 0) {
                $notif['time_ago'] = $diff->days . ' ngày trước';
            } elseif ($diff->h > 0) {
                $notif['time_ago'] = $diff->h . ' giờ trước';
            } elseif ($diff->i > 0) {
                $notif['time_ago'] = $diff->i . ' phút trước';
            } else {
                $notif['time_ago'] = 'Vừa xong';
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'notifications' => $notifications]);
        exit;
    } elseif ($action === 'mark-read') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $result = notification_mark_as_read($id, $_SESSION['user_id']);
        header('Content-Type: application/json');
        echo json_encode(['success' => $result]);
        exit;
    } elseif ($action === 'mark-all-read') {
        $result = notification_mark_all_as_read($_SESSION['user_id']);
        header('Content-Type: application/json');
        echo json_encode(['success' => $result]);
        exit;
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
    exit;
}
?>

