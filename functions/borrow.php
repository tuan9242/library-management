<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/notification.php';

function borrow_get_all(?int $userId = null): array {
    $conn = get_db_connection();
    if (!$conn) {
        return [];
    }

    $query = "SELECT b.*, u.full_name, u.phone, bk.title, bk.author, bk.isbn
              FROM borrows b
              JOIN users u ON b.user_id = u.id
              JOIN books bk ON b.book_id = bk.id";

    if (!empty($userId)) {
        $query .= " WHERE b.user_id = :user_id";
    }

    $query .= " ORDER BY b.created_at DESC";
    $stmt = $conn->prepare($query);

    if (!empty($userId)) {
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    }

    $stmt->execute();
    return $stmt->fetchAll() ?: [];
}

function borrow_create(int $userId, int $bookId, int $quantity = 1, int $durationDays = 30, int $pricePerDay = 0): array {
    $conn = get_db_connection();
    if (!$conn) {
        return ['success' => false, 'message' => 'Không thể kết nối cơ sở dữ liệu!'];
    }

    $quantity = max(1, $quantity);

    $checkQuery = "SELECT id FROM borrows
                   WHERE user_id = :user_id AND book_id = :book_id
                     AND (status = 'borrowed' OR status = 'pending')";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $checkStmt->bindValue(':book_id', $bookId, PDO::PARAM_INT);
    $checkStmt->execute();
    if ($checkStmt->fetch()) {
        return ['success' => false, 'message' => 'Bạn đã có yêu cầu mượn sách này rồi!'];
    }

    $countQuery = "SELECT COUNT(*) as count FROM borrows
                   WHERE user_id = :user_id AND status = 'borrowed'";
    $countStmt = $conn->prepare($countQuery);
    $countStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $countStmt->execute();
    $count = $countStmt->fetch()['count'] ?? 0;
    if ($count >= 5) {
        return ['success' => false, 'message' => 'Bạn đã mượn tối đa 5 cuốn sách!'];
    }

    $bookStmt = $conn->prepare("SELECT available_quantity FROM books WHERE id = :book_id");
    $bookStmt->bindValue(':book_id', $bookId, PDO::PARAM_INT);
    $bookStmt->execute();
    $book = $bookStmt->fetch();
    if (!$book || $book['available_quantity'] < $quantity) {
        return ['success' => false, 'message' => 'Sách này không đủ số lượng để mượn!'];
    }

    $durationDays = max(1, $durationDays);
    $pricePerDay = max(0, $pricePerDay);
    if ($pricePerDay <= 0) {
        $pricePerDay = 2000;
    }
    $totalPrice = $durationDays * $pricePerDay * $quantity;
    $notes = $totalPrice > 0
        ? ("So luong: {$quantity} cuon; Thoi gian muon: {$durationDays} ngay; Gia/ngay: " . number_format($pricePerDay) . "; Tong: " . number_format($totalPrice) . " VND")
        : ("So luong: {$quantity} cuon; Thoi gian muon: {$durationDays} ngay");

    $status = 'pending';
    $stmt = $conn->prepare(
        "INSERT INTO borrows
            (user_id, book_id, quantity, borrow_date, due_date, status, notes)
         VALUES
            (:user_id, :book_id, :quantity, CURDATE(), DATE_ADD(CURDATE(), INTERVAL :duration DAY), :status, :notes)"
    );
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':book_id', $bookId, PDO::PARAM_INT);
    $stmt->bindValue(':quantity', $quantity, PDO::PARAM_INT);
    $stmt->bindValue(':duration', $durationDays, PDO::PARAM_INT);
    $stmt->bindValue(':status', $status);
    $stmt->bindValue(':notes', $notes);

    if (!$stmt->execute()) {
        $errorInfo = $stmt->errorInfo();
        return ['success' => false, 'message' => 'Có lỗi xảy ra khi gửi yêu cầu mượn sách! ' . ($errorInfo[2] ?? '')];
    }

    $borrowId = (int)$conn->lastInsertId();
    notification_create(
        $userId,
        'Yêu cầu mượn sách đã được gửi',
        'Yêu cầu mượn sách của bạn đang chờ thủ thư duyệt.',
        'info',
        $borrowId,
        'borrow'
    );

    return ['success' => true, 'message' => 'Yêu cầu mượn sách đã được gửi! Vui lòng chờ thủ thư duyệt.', 'borrow_id' => $borrowId];
}

function borrow_approve(int $id): array {
    $conn = get_db_connection();
    if (!$conn) {
        return ['success' => false, 'message' => 'Không thể kết nối cơ sở dữ liệu!'];
    }

    $stmt = $conn->prepare("SELECT * FROM borrows WHERE id = :id AND status = 'pending'");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $borrow = $stmt->fetch();

    if (!$borrow) {
        return ['success' => false, 'message' => 'Không tìm thấy yêu cầu mượn sách!'];
    }

    $bookStmt = $conn->prepare("SELECT available_quantity FROM books WHERE id = :book_id");
    $bookStmt->bindValue(':book_id', $borrow['book_id'], PDO::PARAM_INT);
    $bookStmt->execute();
    $book = $bookStmt->fetch();

    $quantity = isset($borrow['quantity']) ? (int)$borrow['quantity'] : 1;
    if (!$book || $book['available_quantity'] < $quantity) {
        borrow_reject($id, 'Không đủ số lượng sách có sẵn');
        return ['success' => false, 'message' => 'Không đủ số lượng sách có sẵn!'];
    }

    $originalBorrowDate = new DateTime($borrow['borrow_date']);
    $originalDueDate = new DateTime($borrow['due_date']);
    $durationDays = $originalDueDate->diff($originalBorrowDate)->days;
    if ($durationDays <= 0) {
        $durationDays = 30;
    }

    $updateStmt = $conn->prepare(
        "UPDATE borrows
         SET status = 'borrowed',
             borrow_date = CURDATE(),
             due_date = DATE_ADD(CURDATE(), INTERVAL :duration DAY)
         WHERE id = :id"
    );
    $updateStmt->bindValue(':id', $id, PDO::PARAM_INT);
    $updateStmt->bindValue(':duration', $durationDays, PDO::PARAM_INT);

    if (!$updateStmt->execute()) {
        return ['success' => false, 'message' => 'Có lỗi xảy ra khi duyệt yêu cầu!'];
    }

    $updateBookStmt = $conn->prepare(
        "UPDATE books
         SET available_quantity = available_quantity - :quantity
         WHERE id = :book_id"
    );
    $updateBookStmt->bindValue(':quantity', $quantity, PDO::PARAM_INT);
    $updateBookStmt->bindValue(':book_id', $borrow['book_id'], PDO::PARAM_INT);
    $updateBookStmt->execute();

    notification_create(
        (int)$borrow['user_id'],
        'Yêu cầu mượn sách đã được duyệt',
        'Yêu cầu mượn sách của bạn đã được thủ thư duyệt. Vui lòng đến thư viện để nhận sách.',
        'success',
        $id,
        'borrow'
    );

    return ['success' => true, 'message' => 'Đã duyệt yêu cầu mượn sách!'];
}

function borrow_reject(int $id, string $reason = ''): array {
    $conn = get_db_connection();
    if (!$conn) {
        return ['success' => false, 'message' => 'Không thể kết nối cơ sở dữ liệu!'];
    }

    $stmt = $conn->prepare("SELECT * FROM borrows WHERE id = :id AND status = 'pending'");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $borrow = $stmt->fetch();

    if (!$borrow) {
        return ['success' => false, 'message' => 'Không tìm thấy yêu cầu mượn sách!'];
    }

    $updateStmt = $conn->prepare("UPDATE borrows SET status = 'rejected' WHERE id = :id");
    $updateStmt->bindValue(':id', $id, PDO::PARAM_INT);
    if (!$updateStmt->execute()) {
        return ['success' => false, 'message' => 'Có lỗi xảy ra khi từ chối yêu cầu!'];
    }

    $message = !empty($reason)
        ? "Yêu cầu mượn sách của bạn đã bị từ chối. Lý do: {$reason}"
        : "Yêu cầu mượn sách của bạn đã bị từ chối.";

    notification_create(
        (int)$borrow['user_id'],
        'Yêu cầu mượn sách bị từ chối',
        $message,
        'error',
        $id,
        'borrow'
    );

    return ['success' => true, 'message' => 'Đã từ chối yêu cầu mượn sách!'];
}

function borrow_return_book(int $id): array {
    $conn = get_db_connection();
    if (!$conn) {
        return ['success' => false, 'message' => 'Không thể kết nối cơ sở dữ liệu!'];
    }

    $stmt = $conn->prepare("SELECT * FROM borrows WHERE id = :id");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $borrow = $stmt->fetch();

    if (!$borrow) {
        return ['success' => false, 'message' => 'Không tìm thấy bản ghi mượn sách!'];
    }

    if ($borrow['status'] === 'returned') {
        return ['success' => false, 'message' => 'Sách này đã được trả rồi!'];
    }

    $fineAmount = calculateFine($borrow['due_date']);
    $quantity = isset($borrow['quantity']) ? (int)$borrow['quantity'] : 1;

    $updateStmt = $conn->prepare(
        "UPDATE borrows
         SET status = 'returned',
             return_date = CURDATE(),
             fine_amount = :fine_amount
         WHERE id = :id"
    );
    $updateStmt->bindValue(':id', $id, PDO::PARAM_INT);
    $updateStmt->bindValue(':fine_amount', $fineAmount, PDO::PARAM_INT);

    if (!$updateStmt->execute()) {
        return ['success' => false, 'message' => 'Có lỗi xảy ra khi trả sách!'];
    }

    $bookStmt = $conn->prepare(
        "UPDATE books
         SET available_quantity = available_quantity + :quantity
         WHERE id = :book_id"
    );
    $bookStmt->bindValue(':quantity', $quantity, PDO::PARAM_INT);
    $bookStmt->bindValue(':book_id', $borrow['book_id'], PDO::PARAM_INT);
    $bookStmt->execute();

    return ['success' => true, 'message' => 'Trả sách thành công!'];
}

function borrow_get_by_user(int $userId): array {
    $conn = get_db_connection();
    if (!$conn) {
        return [];
    }

    $stmt = $conn->prepare(
        "SELECT b.*, bk.title, bk.author, bk.isbn, bk.cover_image, bk.location
         FROM borrows b
         JOIN books bk ON b.book_id = bk.id
         WHERE b.user_id = :user_id
         ORDER BY b.created_at DESC"
    );
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll() ?: [];
}

function borrow_check_overdue(): bool {
    $conn = get_db_connection();
    if (!$conn) {
        return false;
    }

    $stmt = $conn->prepare(
        "UPDATE borrows
         SET status = 'overdue'
         WHERE due_date < CURDATE() AND status = 'borrowed'"
    );

    return $stmt->execute();
}

function borrow_get_statistics(): array {
    $conn = get_db_connection();
    if (!$conn) {
        return [];
    }

    $stmt = $conn->prepare(
        "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'borrowed' THEN 1 ELSE 0 END) as borrowed,
            SUM(CASE WHEN status = 'returned' THEN 1 ELSE 0 END) as returned,
            SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue,
            SUM(fine_amount) as total_fine
         FROM borrows"
    );
    $stmt->execute();

    return $stmt->fetch() ?: [];
}

function borrow_get_pending_requests(): array {
    $conn = get_db_connection();
    if (!$conn) {
        return [];
    }

    $stmt = $conn->prepare(
        "SELECT b.*, u.full_name, u.phone, u.email,
                bk.title, bk.author, bk.isbn, bk.available_quantity
         FROM borrows b
         JOIN users u ON b.user_id = u.id
         JOIN books bk ON b.book_id = bk.id
         WHERE b.status = 'pending'
         ORDER BY b.created_at ASC"
    );
    $stmt->execute();

    return $stmt->fetchAll() ?: [];
}

function borrow_get_overdue_books(): array {
    $conn = get_db_connection();
    if (!$conn) {
        return [];
    }

    $stmt = $conn->prepare(
        "SELECT b.*, u.full_name, u.phone, bk.title
         FROM borrows b
         JOIN users u ON b.user_id = u.id
         JOIN books bk ON b.book_id = bk.id
         WHERE b.status = 'overdue'
            OR (b.status = 'borrowed' AND b.due_date < CURDATE())
         ORDER BY b.due_date ASC"
    );
    $stmt->execute();

    return $stmt->fetchAll() ?: [];
}
?>