<?php
/**
 * Database connection helpers (function-based)
 */
function get_db_connection(): ?PDO {
    static $connection = null;

    if ($connection instanceof PDO) {
        return $connection;
    }

    $host = "localhost";
    $dbName = "library_management";
    $username = "root";
    $password = "tuan9242";

    try {
        $connection = new PDO(
            "mysql:host={$host};dbname={$dbName};charset=utf8mb4",
            $username,
            $password
            );
        $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
            echo "Connection Error: " . $e->getMessage();
        return null;
        }
        
    return $connection;
}

/**
 * Session bootstrap
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Auth helper functions
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function isAdmin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isLibrarian(): bool {
    return isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'librarian'], true);
}

function redirect(string $url): void {
    header("Location: {$url}");
    exit();
}

/**
 * General helpers
 */
function sanitize(string $data): string {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function formatDate(string $date): string {
    return date('d/m/Y', strtotime($date));
}

function calculateFine(string $dueDate, ?string $returnDate = null): int {
    $return = $returnDate ? new DateTime($returnDate) : new DateTime();
    $due = new DateTime($dueDate);
    
    if ($return <= $due) {
        return 0;
    }
    
    $daysLate = $return->diff($due)->days;
    $finePerDay = 5000; // 5,000 VNĐ per day
    
    return $daysLate * $finePerDay;
}

function alert(string $message, string $type = 'info'): string {
    $icons = [
        'success' => '✓',
        'error' => '✗',
        'warning' => '⚠',
        'info' => 'ℹ'
    ];

    $icon = $icons[$type] ?? $icons['info'];
    
    return "<div class='alert alert-{$type}'>
                <span class='alert-icon'>{$icon}</span>
                <span class='alert-message'>{$message}</span>
            </div>";
}

/**
 * Auto migration - tự động tạo các bảng cần thiết khi chưa có
 */
function auto_migrate_database(): void {
    $conn = get_db_connection();
    if (!$conn) {
        return;
    }

    try {
        // 1. Thêm cột quantity vào bảng borrows (nếu chưa có)
        $checkColumn = $conn->query("SHOW COLUMNS FROM `borrows` LIKE 'quantity'");
        if ($checkColumn && $checkColumn->rowCount() == 0) {
            $conn->exec("ALTER TABLE `borrows` ADD COLUMN `quantity` INT DEFAULT 1 AFTER `book_id`");
        }
        
        // 2. Tạo bảng cart (nếu chưa có)
        $checkTable = $conn->query("SHOW TABLES LIKE 'cart'");
        if ($checkTable && $checkTable->rowCount() == 0) {
            $sql = "CREATE TABLE `cart` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `user_id` INT NOT NULL,
              `book_id` INT NOT NULL,
              `quantity` INT DEFAULT 1,
              `duration_days` INT DEFAULT 30,
              `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
              FOREIGN KEY (`book_id`) REFERENCES `books`(`id`) ON DELETE CASCADE,
              UNIQUE KEY `unique_cart_item` (`user_id`, `book_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $conn->exec($sql);
        }
        
        // 3. Tạo bảng notifications (nếu chưa có)
        $checkTable = $conn->query("SHOW TABLES LIKE 'notifications'");
        if ($checkTable && $checkTable->rowCount() == 0) {
            $sql = "CREATE TABLE `notifications` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `user_id` INT NOT NULL,
              `title` VARCHAR(255) NOT NULL,
              `message` TEXT NOT NULL,
              `type` VARCHAR(50) DEFAULT 'info',
              `is_read` TINYINT(1) DEFAULT 0,
              `related_id` INT NULL,
              `related_type` VARCHAR(50) NULL,
              `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
              FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
              INDEX `idx_user_read` (`user_id`, `is_read`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            $conn->exec($sql);
        }
        
        // 4. Đảm bảo cột status trong borrows hỗ trợ đầy đủ giá trị
        $checkStatus = $conn->query("SHOW COLUMNS FROM borrows LIKE 'status'")->fetch();
        if ($checkStatus) {
            $columnType = strtolower($checkStatus['Type']);
            if (strpos($columnType, 'enum') !== false && strpos($columnType, 'pending') === false) {
                $conn->exec("ALTER TABLE borrows MODIFY COLUMN status ENUM('pending', 'borrowed', 'returned', 'overdue', 'rejected') DEFAULT 'pending'");
            }
            if (strpos($columnType, 'varchar') !== false) {
                preg_match('/varchar\((\d+)\)/', $columnType, $matches);
                if (!empty($matches[1]) && (int)$matches[1] < 20) {
                    $conn->exec("ALTER TABLE borrows MODIFY COLUMN status VARCHAR(20) DEFAULT 'pending'");
                }
            }
        }
        
    } catch (PDOException $e) {
        // Bỏ qua lỗi migration - không ảnh hưởng ứng dụng
    }
}

// Tự động chạy migration khi load config
auto_migrate_database();
?>