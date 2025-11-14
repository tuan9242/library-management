<?php
require_once __DIR__ . '/../config/database.php';

function book_get_all(?int $limit = null, int $offset = 0): array {
    $conn = get_db_connection();
    if (!$conn) {
        return [];
    }

    $query = "SELECT b.*, c.name AS category_name
              FROM books b
              LEFT JOIN categories c ON b.category_id = c.id
              ORDER BY b.created_at DESC";
    if ($limit !== null) {
        $query .= " LIMIT :limit OFFSET :offset";
    }

    $stmt = $conn->prepare($query);
    if ($limit !== null) {
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    }

    $stmt->execute();
    return $stmt->fetchAll() ?: [];
}

function book_get_by_id(int $id): ?array {
    $conn = get_db_connection();
    if (!$conn) {
        return null;
    }

    $query = "SELECT b.*, c.name AS category_name
              FROM books b
              LEFT JOIN categories c ON b.category_id = c.id
              WHERE b.id = :id LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    $result = $stmt->fetch();
    return $result ?: null;
}

function book_create(array $data): bool {
    $conn = get_db_connection();
    if (!$conn) {
        return false;
    }

    $statusValue = book_normalize_status($data['status'] ?? 'available');
    $categoryId = empty($data['category_id']) ? null : (int)$data['category_id'];
    $publishedYear = empty($data['published_year']) ? null : (int)$data['published_year'];
    $availableQuantity = isset($data['available_quantity']) ? (int)$data['available_quantity'] : 0;
    $coverImage = $data['cover_image'] ?? null;

    $query = "INSERT INTO books
              (isbn, title, author, publisher, published_year, category_id, available_quantity, description, cover_image, location, status)
              VALUES
              (:isbn, :title, :author, :publisher, :published_year, :category_id, :available_quantity, :description, :cover_image, :location, :status)";
    $stmt = $conn->prepare($query);

    $stmt->bindValue(':isbn', $data['isbn'] ?? null);
    $stmt->bindValue(':title', $data['title'] ?? null);
    $stmt->bindValue(':author', $data['author'] ?? null);
    $stmt->bindValue(':publisher', $data['publisher'] ?? null);
    if ($publishedYear === null) {
        $stmt->bindValue(':published_year', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':published_year', $publishedYear, PDO::PARAM_INT);
    }
    if ($categoryId === null) {
        $stmt->bindValue(':category_id', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
    }
    $stmt->bindValue(':available_quantity', $availableQuantity, PDO::PARAM_INT);
    $stmt->bindValue(':description', $data['description'] ?? null);
    if ($coverImage === null || $coverImage === '') {
        $stmt->bindValue(':cover_image', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':cover_image', $coverImage);
    }
    $stmt->bindValue(':location', $data['location'] ?? null);
    $stmt->bindValue(':status', $statusValue, PDO::PARAM_INT);

    return $stmt->execute();
}

function book_update(int $id, array $data): bool {
    $conn = get_db_connection();
    if (!$conn) {
        return false;
    }

    $statusValue = book_normalize_status($data['status'] ?? 'available');
    $categoryId = empty($data['category_id']) ? null : (int)$data['category_id'];
    $publishedYear = empty($data['published_year']) ? null : (int)$data['published_year'];
    $availableQuantity = isset($data['available_quantity']) ? (int)$data['available_quantity'] : 0;
    $coverImage = $data['cover_image'] ?? null;

    $query = "UPDATE books
              SET
                isbn = :isbn,
                title = :title,
                author = :author,
                publisher = :publisher,
                published_year = :published_year,
                category_id = :category_id,
                available_quantity = :available_quantity,
                description = :description,
                cover_image = :cover_image,
                location = :location,
                status = :status
              WHERE id = :id";
    $stmt = $conn->prepare($query);

    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->bindValue(':isbn', $data['isbn'] ?? null);
    $stmt->bindValue(':title', $data['title'] ?? null);
    $stmt->bindValue(':author', $data['author'] ?? null);
    $stmt->bindValue(':publisher', $data['publisher'] ?? null);
    if ($publishedYear === null) {
        $stmt->bindValue(':published_year', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':published_year', $publishedYear, PDO::PARAM_INT);
    }
    if ($categoryId === null) {
        $stmt->bindValue(':category_id', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
    }
    $stmt->bindValue(':available_quantity', $availableQuantity, PDO::PARAM_INT);
    $stmt->bindValue(':description', $data['description'] ?? null);
    if ($coverImage === null || $coverImage === '') {
        $stmt->bindValue(':cover_image', null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(':cover_image', $coverImage);
    }
    $stmt->bindValue(':location', $data['location'] ?? null);
    $stmt->bindValue(':status', $statusValue, PDO::PARAM_INT);

    return $stmt->execute();
}

function book_delete(int $id): bool {
    $conn = get_db_connection();
    if (!$conn) {
        return false;
    }

    $stmt = $conn->prepare("DELETE FROM books WHERE id = :id");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    return $stmt->execute();
}

function book_search(string $keyword, ?int $category = null, ?int $limit = null, int $offset = 0): array {
    $conn = get_db_connection();
    if (!$conn) {
        return [];
    }

    $query = "SELECT b.*, c.name AS category_name
              FROM books b
              LEFT JOIN categories c ON b.category_id = c.id
              WHERE (b.title LIKE :kw OR b.author LIKE :kw OR b.isbn LIKE :kw)";
    if (!empty($category)) {
        $query .= " AND b.category_id = :category";
    }
    $query .= " ORDER BY b.title ASC";
    if ($limit !== null) {
        $query .= " LIMIT :limit OFFSET :offset";
    }

    $stmt = $conn->prepare($query);
    $kw = "%" . $keyword . "%";
    $stmt->bindValue(':kw', $kw);
    if (!empty($category)) {
        $stmt->bindValue(':category', $category, PDO::PARAM_INT);
    }
    if ($limit !== null) {
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    }

    $stmt->execute();
    return $stmt->fetchAll() ?: [];
}

function book_get_search_count(string $keyword, ?int $category = null): int {
    $conn = get_db_connection();
    if (!$conn) {
        return 0;
    }

    $query = "SELECT COUNT(*) AS total
              FROM books b
              WHERE (b.title LIKE :kw OR b.author LIKE :kw OR b.isbn LIKE :kw)";
    if (!empty($category)) {
        $query .= " AND b.category_id = :category";
    }

    $stmt = $conn->prepare($query);
    $kw = "%" . $keyword . "%";
    $stmt->bindValue(':kw', $kw);
    if (!empty($category)) {
        $stmt->bindValue(':category', $category, PDO::PARAM_INT);
    }
    $stmt->execute();

    $row = $stmt->fetch();
    return $row ? (int)$row['total'] : 0;
}

function book_get_total_count(): int {
    $conn = get_db_connection();
    if (!$conn) {
        return 0;
    }

    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM books");
    $stmt->execute();
    $row = $stmt->fetch();
    return $row ? (int)$row['total'] : 0;
}

function book_get_by_category(int $categoryId, ?int $limit = null, ?int $excludeId = null): array {
    $conn = get_db_connection();
    if (!$conn) {
        return [];
    }

    $query = "SELECT b.*, c.name AS category_name
              FROM books b
              LEFT JOIN categories c ON b.category_id = c.id
              WHERE b.category_id = :category_id";
    if (!empty($excludeId)) {
        $query .= " AND b.id != :exclude_id";
    }
    $query .= " ORDER BY b.created_at DESC";
    if ($limit !== null) {
        $query .= " LIMIT :limit";
    }

    $stmt = $conn->prepare($query);
    $stmt->bindValue(':category_id', $categoryId, PDO::PARAM_INT);
    if (!empty($excludeId)) {
        $stmt->bindValue(':exclude_id', $excludeId, PDO::PARAM_INT);
    }
    if ($limit !== null) {
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    }

    $stmt->execute();
    return $stmt->fetchAll() ?: [];
}

function book_get_popular(int $limit = 6): array {
    $conn = get_db_connection();
    if (!$conn) {
        return [];
    }

    $query = "SELECT b.*, c.name AS category_name, COUNT(br.id) AS borrow_count
              FROM books b
              LEFT JOIN categories c ON b.category_id = c.id
              LEFT JOIN borrows br ON b.id = br.book_id
              GROUP BY b.id
              ORDER BY borrow_count DESC, b.created_at DESC
              LIMIT :limit";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll() ?: [];
}

function book_normalize_status($status): int {
    $allowedStatus = ['available', 'unavailable', '0', '1', 0, 1];
    if (!in_array($status, $allowedStatus, true)) {
        $status = 'available';
    }

    return ($status === 'unavailable' || $status === '0' || $status === 0) ? 0 : 1;
}
?>