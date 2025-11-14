<?php
require_once __DIR__ . '/../config/database.php';

function cart_add(int $userId, int $bookId, int $quantity = 1, int $durationDays = 30): array {
    $conn = get_db_connection();
    if (!$conn) {
        return ['success' => false, 'message' => 'Không thể kết nối cơ sở dữ liệu!'];
    }

    $quantity = max(1, $quantity);
    $durationDays = max(1, $durationDays);

    $checkQuery = "SELECT id, quantity FROM cart WHERE user_id = :user_id AND book_id = :book_id";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $checkStmt->bindValue(':book_id', $bookId, PDO::PARAM_INT);
    $checkStmt->execute();
    $existing = $checkStmt->fetch();

    if ($existing) {
        $newQuantity = $existing['quantity'] + $quantity;
        $updateQuery = "UPDATE cart
                        SET quantity = :quantity, duration_days = :duration_days
                        WHERE id = :id";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bindValue(':quantity', $newQuantity, PDO::PARAM_INT);
        $updateStmt->bindValue(':duration_days', $durationDays, PDO::PARAM_INT);
        $updateStmt->bindValue(':id', $existing['id'], PDO::PARAM_INT);

        if ($updateStmt->execute()) {
            return ['success' => true, 'message' => 'Đã cập nhật giỏ hàng!'];
        }
    } else {
        $insertQuery = "INSERT INTO cart (user_id, book_id, quantity, duration_days)
                        VALUES (:user_id, :book_id, :quantity, :duration_days)";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $insertStmt->bindValue(':book_id', $bookId, PDO::PARAM_INT);
        $insertStmt->bindValue(':quantity', $quantity, PDO::PARAM_INT);
        $insertStmt->bindValue(':duration_days', $durationDays, PDO::PARAM_INT);

        if ($insertStmt->execute()) {
            return ['success' => true, 'message' => 'Đã thêm vào giỏ hàng!'];
        }
    }

    return ['success' => false, 'message' => 'Có lỗi xảy ra!'];
}

function cart_get_by_user(int $userId): array {
    $conn = get_db_connection();
    if (!$conn) {
        return [];
    }

    $query = "SELECT c.*, b.title, b.author, b.isbn, b.cover_image,
                     b.available_quantity, b.location,
                     cat.name AS category_name
              FROM cart c
              JOIN books b ON c.book_id = b.id
              LEFT JOIN categories cat ON b.category_id = cat.id
              WHERE c.user_id = :user_id
              ORDER BY c.created_at DESC";

    $stmt = $conn->prepare($query);
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll() ?: [];
}

function cart_update_quantity(int $id, int $userId, int $quantity): bool {
    $conn = get_db_connection();
    if (!$conn) {
        return false;
    }

    $query = "UPDATE cart
              SET quantity = :quantity
              WHERE id = :id AND user_id = :user_id";

    $stmt = $conn->prepare($query);
    $stmt->bindValue(':quantity', max(1, $quantity), PDO::PARAM_INT);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);

    return $stmt->execute();
}

function cart_update_duration(int $id, int $userId, int $durationDays): bool {
    $conn = get_db_connection();
    if (!$conn) {
        return false;
    }

    $query = "UPDATE cart
              SET duration_days = :duration_days
              WHERE id = :id AND user_id = :user_id";

    $stmt = $conn->prepare($query);
    $stmt->bindValue(':duration_days', max(1, $durationDays), PDO::PARAM_INT);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);

    return $stmt->execute();
}

function cart_remove(int $id, int $userId): bool {
    $conn = get_db_connection();
    if (!$conn) {
        return false;
    }

    $stmt = $conn->prepare("DELETE FROM cart WHERE id = :id AND user_id = :user_id");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);

    return $stmt->execute();
}

function cart_clear(int $userId): bool {
    $conn = get_db_connection();
    if (!$conn) {
        return false;
    }

    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = :user_id");
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);

    return $stmt->execute();
}

function cart_get_count(int $userId): int {
    $conn = get_db_connection();
    if (!$conn) {
        return 0;
    }

    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = :user_id");
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();

    $result = $stmt->fetch();
    return $result ? (int)$result['count'] : 0;
}
?>
