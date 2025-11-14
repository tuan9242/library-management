<?php
require_once __DIR__ . '/../config/database.php';

function notification_create(int $userId, string $title, string $message, string $type = 'info', ?int $relatedId = null, ?string $relatedType = null): bool {
    $conn = get_db_connection();
    if (!$conn) {
        return false;
    }

    $stmt = $conn->prepare(
        "INSERT INTO notifications (user_id, title, message, type, related_id, related_type)
         VALUES (:user_id, :title, :message, :type, :related_id, :related_type)"
    );

    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':title', $title);
    $stmt->bindValue(':message', $message);
    $stmt->bindValue(':type', $type);
    if ($relatedId === null) {
        $stmt->bindValue(':related_id', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':related_id', $relatedId, PDO::PARAM_INT);
    }
    if ($relatedType === null) {
        $stmt->bindValue(':related_type', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':related_type', $relatedType);
    }

    return $stmt->execute();
}

function notification_get_by_user(int $userId, int $limit = 20, bool $unreadOnly = false): array {
    $conn = get_db_connection();
    if (!$conn) {
        return [];
    }

    $query = "SELECT * FROM notifications WHERE user_id = :user_id";
    if ($unreadOnly) {
        $query .= " AND is_read = 0";
    }
    $query .= " ORDER BY created_at DESC LIMIT :limit";

    $stmt = $conn->prepare($query);
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll() ?: [];
}

function notification_mark_as_read(int $id, int $userId): bool {
    $conn = get_db_connection();
    if (!$conn) {
        return false;
    }

    $stmt = $conn->prepare(
        "UPDATE notifications
         SET is_read = 1
         WHERE id = :id AND user_id = :user_id"
    );
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);

    return $stmt->execute();
}

function notification_mark_all_as_read(int $userId): bool {
    $conn = get_db_connection();
    if (!$conn) {
        return false;
    }

    $stmt = $conn->prepare(
        "UPDATE notifications
         SET is_read = 1
         WHERE user_id = :user_id AND is_read = 0"
    );
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);

    return $stmt->execute();
}

function notification_get_unread_count(int $userId): int {
    $conn = get_db_connection();
    if (!$conn) {
        return 0;
    }

    $stmt = $conn->prepare(
        "SELECT COUNT(*) as count
         FROM notifications
         WHERE user_id = :user_id AND is_read = 0"
    );
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();

    $result = $stmt->fetch();
    return $result ? (int)$result['count'] : 0;
}

function notification_delete(int $id, int $userId): bool {
    $conn = get_db_connection();
    if (!$conn) {
        return false;
    }

    $stmt = $conn->prepare(
        "DELETE FROM notifications
         WHERE id = :id AND user_id = :user_id"
    );
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);

    return $stmt->execute();
}
?>
