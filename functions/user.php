<?php
require_once __DIR__ . '/../config/database.php';

function user_get_all(?int $limit = null, int $offset = 0): array {
    $conn = get_db_connection();
    if (!$conn) {
        return [];
    }

    $query = "SELECT * FROM users ORDER BY created_at DESC";
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

function user_get_by_id(int $id): ?array {
    $conn = get_db_connection();
    if (!$conn) {
        return null;
    }

    $stmt = $conn->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

function user_get_by_username(string $username): ?array {
    $conn = get_db_connection();
    if (!$conn) {
        return null;
    }

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
    $stmt->bindValue(':username', $username);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function user_get_by_email(string $email): ?array {
    $conn = get_db_connection();
    if (!$conn) {
        return null;
    }

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
    $stmt->bindValue(':email', $email);
    $stmt->execute();

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function user_authenticate(string $username, string $password): ?array {
    $conn = get_db_connection();
    if (!$conn) {
        return null;
    }

    $stmt = $conn->prepare(
        "SELECT * FROM users 
         WHERE (username = :username OR email = :username)
           AND status = 'active'
         LIMIT 1"
    );
    $stmt->bindValue(':username', $username);
    $stmt->execute();

    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($password, $user['password'])) {
        return $user;
    }

    return null;
}

function user_create(array $data): bool {
    $conn = get_db_connection();
    if (!$conn) {
        return false;
    }

    // Kiểm tra xem cột address có tồn tại không
    try {
        $checkAddress = $conn->query("SHOW COLUMNS FROM users LIKE 'address'")->fetch();
        $hasAddress = ($checkAddress !== false);
    } catch (Exception $e) {
        $hasAddress = false;
    }

    if ($hasAddress) {
        $stmt = $conn->prepare(
            "INSERT INTO users
                (username, password, email, full_name, phone, address, student_id, role, status)
             VALUES
                (:username, :password, :email, :full_name, :phone, :address, :student_id, :role, :status)"
        );
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO users
                (username, password, email, full_name, phone, student_id, role, status)
             VALUES
                (:username, :password, :email, :full_name, :phone, :student_id, :role, :status)"
        );
    }

    $stmt->bindValue(':username', sanitize($data['username'] ?? ''));
    $stmt->bindValue(':password', password_hash($data['password'] ?? '', PASSWORD_DEFAULT));
    $stmt->bindValue(':email', sanitize($data['email'] ?? ''));
    $stmt->bindValue(':full_name', sanitize($data['full_name'] ?? ''));
    $stmt->bindValue(':phone', sanitize($data['phone'] ?? ''));
    if ($hasAddress) {
        $stmt->bindValue(':address', sanitize($data['address'] ?? ''));
    }
    $stmt->bindValue(':student_id', sanitize($data['student_id'] ?? ''));
    $stmt->bindValue(':role', sanitize($data['role'] ?? 'student'));
    $stmt->bindValue(':status', sanitize($data['status'] ?? 'active'));

    return $stmt->execute();
}

function user_update(int $id, array $data): bool {
    $conn = get_db_connection();
    if (!$conn) {
        return false;
    }

    // Kiểm tra xem cột address có tồn tại không
    try {
        $checkAddress = $conn->query("SHOW COLUMNS FROM users LIKE 'address'")->fetch();
        $hasAddress = ($checkAddress !== false);
    } catch (Exception $e) {
        $hasAddress = false;
    }

    if ($hasAddress) {
        $stmt = $conn->prepare(
            "UPDATE users
                SET username = :username,
                    email = :email,
                    full_name = :full_name,
                    phone = :phone,
                    address = :address,
                    student_id = :student_id,
                    role = :role,
                    status = :status
              WHERE id = :id"
        );
    } else {
        $stmt = $conn->prepare(
            "UPDATE users
                SET username = :username,
                    email = :email,
                    full_name = :full_name,
                    phone = :phone,
                    student_id = :student_id,
                    role = :role,
                    status = :status
              WHERE id = :id"
        );
    }

    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->bindValue(':username', sanitize($data['username'] ?? ''));
    $stmt->bindValue(':email', sanitize($data['email'] ?? ''));
    $stmt->bindValue(':full_name', sanitize($data['full_name'] ?? ''));
    $stmt->bindValue(':phone', sanitize($data['phone'] ?? ''));
    if ($hasAddress) {
        $stmt->bindValue(':address', sanitize($data['address'] ?? ''));
    }
    $stmt->bindValue(':student_id', sanitize($data['student_id'] ?? ''));
    $stmt->bindValue(':role', sanitize($data['role'] ?? 'student'));
    $stmt->bindValue(':status', sanitize($data['status'] ?? 'active'));

    return $stmt->execute();
}

function user_change_password(int $userId, string $newPassword): bool {
    $conn = get_db_connection();
    if (!$conn) {
        return false;
    }

    $stmt = $conn->prepare("UPDATE users SET password = :password WHERE id = :id");
    $stmt->bindValue(':password', password_hash($newPassword, PASSWORD_DEFAULT));
    $stmt->bindValue(':id', $userId, PDO::PARAM_INT);

    return $stmt->execute();
}

function user_delete(int $id): bool {
    $conn = get_db_connection();
    if (!$conn) {
        return false;
    }

    $stmt = $conn->prepare("DELETE FROM users WHERE id = :id");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    return $stmt->execute();
}

function user_username_exists(string $username): bool {
    $conn = get_db_connection();
    if (!$conn) {
        return false;
    }

    $stmt = $conn->prepare("SELECT id FROM users WHERE username = :username");
    $stmt->bindValue(':username', $username);
    $stmt->execute();

    return $stmt->fetch() !== false;
}

function user_email_exists(string $email): bool {
    $conn = get_db_connection();
    if (!$conn) {
        return false;
    }

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->bindValue(':email', $email);
    $stmt->execute();

    return $stmt->fetch() !== false;
}

function user_student_id_exists(string $studentId): bool {
    $conn = get_db_connection();
    if (!$conn) {
        return false;
    }

    $stmt = $conn->prepare("SELECT id FROM users WHERE student_id = :student_id");
    $stmt->bindValue(':student_id', $studentId);
    $stmt->execute();

    return $stmt->fetch() !== false;
}

function user_get_statistics(): array {
    $conn = get_db_connection();
    if (!$conn) {
        return [
            'total' => 0,
            'students' => 0,
            'librarians' => 0,
            'admins' => 0,
            'active' => 0,
            'inactive' => 0,
        ];
    }

    $query = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN role = 'student' THEN 1 ELSE 0 END) as students,
                SUM(CASE WHEN role = 'librarian' THEN 1 ELSE 0 END) as librarians,
                SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admins,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive
              FROM users";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    return $stmt->fetch() ?: [];
}

function user_search(string $keyword, ?string $role = null, ?int $limit = null, int $offset = 0): array {
    $conn = get_db_connection();
    if (!$conn) {
        return [];
    }

    $query = "SELECT * FROM users
              WHERE (username LIKE :keyword
                     OR full_name LIKE :keyword
                     OR email LIKE :keyword
                     OR student_id LIKE :keyword)";

    if (!empty($role)) {
        $query .= " AND role = :role";
    }

    $query .= " ORDER BY created_at DESC";

    if ($limit !== null) {
        $query .= " LIMIT :limit OFFSET :offset";
    }

    $stmt = $conn->prepare($query);
    $searchTerm = '%' . $keyword . '%';
    $stmt->bindValue(':keyword', $searchTerm);

    if (!empty($role)) {
        $stmt->bindValue(':role', $role);
    }

    if ($limit !== null) {
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    }

    $stmt->execute();
    return $stmt->fetchAll() ?: [];
}
?>
