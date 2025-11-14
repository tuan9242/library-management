<?php
require_once __DIR__ . '/../config/database.php';

function category_get_all(): array {
    $conn = get_db_connection();
    if (!$conn) {
        return [];
    }

    $stmt = $conn->prepare("SELECT * FROM categories ORDER BY name ASC");
    $stmt->execute();
    return $stmt->fetchAll() ?: [];
}

function category_get_by_id(int $id): ?array {
    $conn = get_db_connection();
    if (!$conn) {
        return null;
    }

    $stmt = $conn->prepare("SELECT * FROM categories WHERE id = :id");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch();

    return $result ?: null;
}

function category_get_name_by_id(int $id): string {
    $conn = get_db_connection();
    if (!$conn) {
        return 'Không xác định';
    }

    $stmt = $conn->prepare("SELECT name FROM categories WHERE id = :id");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    $result = $stmt->fetch();
    return $result ? $result['name'] : 'Không xác định';
}

function category_create(array $data): bool {
    $conn = get_db_connection();
    if (!$conn) {
        return false;
    }

    $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (:name, :description)");
    $stmt->bindValue(':name', $data['name'] ?? null);
    $stmt->bindValue(':description', $data['description'] ?? null);

    return $stmt->execute();
}

function category_update(int $id, array $data): bool {
    $conn = get_db_connection();
    if (!$conn) {
        return false;
    }

    $stmt = $conn->prepare("UPDATE categories SET name = :name, description = :description WHERE id = :id");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->bindValue(':name', $data['name'] ?? null);
    $stmt->bindValue(':description', $data['description'] ?? null);

    return $stmt->execute();
}

function category_delete(int $id): bool {
    $conn = get_db_connection();
    if (!$conn) {
        return false;
    }

    $stmt = $conn->prepare("DELETE FROM categories WHERE id = :id");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    return $stmt->execute();
}

function category_get_book_count(int $id): int {
    $conn = get_db_connection();
    if (!$conn) {
        return 0;
    }

    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM books WHERE category_id = :category_id");
    $stmt->bindValue(':category_id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch();

    return $result ? (int)$result['count'] : 0;
}
?>
