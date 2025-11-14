<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../functions/book.php';
require_once __DIR__ . '/../../functions/user.php';
require_once __DIR__ . '/../../functions/borrow.php';

if (!isLibrarian()) {
    redirect('index.php');
}

$pageTitle = 'Bảng điều khiển - Admin';
$currentPage = 'admin';

$conn = get_db_connection();

// Thống kê tổng quan
$stats = [
    'total_books'    => book_get_total_count(),
    'total_users'    => 0,
    'total_borrows'  => 0,
    'overdue_borrows'=> 0
];

$userStats = user_get_statistics();
$borrowStats = borrow_get_statistics();

$stats['total_users']    = $userStats['students'] ?? 0;
$stats['total_borrows']  = $borrowStats['borrowed'] ?? 0;
$stats['overdue_borrows']= $borrowStats['overdue'] ?? 0;

// Sách mượn gần đây và sắp hết hạn
$recentBorrows = [];
$upcomingDue = [];

if ($conn) {
    $stmt = $conn->query(
        "SELECT b.*, u.full_name, bk.title, bk.isbn
         FROM borrows b
         JOIN users u ON b.user_id = u.id
         JOIN books bk ON b.book_id = bk.id
         ORDER BY b.created_at DESC
         LIMIT 10"
    );
    $recentBorrows = $stmt ? $stmt->fetchAll() : [];

    $stmt = $conn->query(
        "SELECT b.*, u.full_name, u.phone, bk.title, bk.isbn
         FROM borrows b
         JOIN users u ON b.user_id = u.id
         JOIN books bk ON b.book_id = bk.id
         WHERE b.status = 'borrowed' 
           AND b.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
         ORDER BY b.due_date ASC
         LIMIT 10"
    );
    $upcomingDue = $stmt ? $stmt->fetchAll() : [];
}

include __DIR__ . '/../layout/header.php';
?>

<div class="admin-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>
    
    <main class="admin-main">
        <div class="container">
            <div class="page-header">
                <div>
                    <h1 class="page-title">Bảng điều khiển</h1>
                    <p class="page-subtitle">Tổng quan hệ thống thư viện</p>
                </div>
                <div class="page-actions">
                    <button class="btn btn-primary" onclick="location.reload()">
                        <i class="fas fa-sync-alt"></i> Làm mới
                    </button>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo number_format($stats['total_books']); ?></div>
                        <div class="stat-label">Tổng số sách</div>
                    </div>
                </div>
                
                <div class="stat-card success">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo number_format($stats['total_users']); ?></div>
                        <div class="stat-label">Người dùng</div>
                    </div>
                </div>
                
                <div class="stat-card warning">
                    <div class="stat-icon">
                        <i class="fas fa-hand-holding"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo number_format($stats['total_borrows']); ?></div>
                        <div class="stat-label">Đang mượn</div>
                    </div>
                </div>
                
                <div class="stat-card danger">
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo number_format($stats['overdue_borrows']); ?></div>
                        <div class="stat-label">Quá hạn</div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Borrows -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-clock"></i> Mượn sách gần đây
                    </h2>
                    <a href="index.php?page=admin-borrows" class="btn btn-outline btn-sm">
                        Xem tất cả
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive table-wrapper">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Người mượn</th>
                                    <th>Sách</th>
                                    <th>ISBN</th>
                                    <th>Ngày mượn</th>
                                    <th>Hạn trả</th>
                                    <th>Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentBorrows as $borrow): ?>
                                <tr>
                                    <td class="col-id">#<?php echo $borrow['id']; ?></td>
                                    <td class="col-borrower-name">
                                        <strong><?php echo htmlspecialchars($borrow['full_name']); ?></strong>
                                    </td>
                                    <td class="col-book-title"><?php echo htmlspecialchars($borrow['title']); ?></td>
                                    <td class="col-isbn"><code><?php echo $borrow['isbn']; ?></code></td>
                                    <td class="col-date"><?php echo formatDate($borrow['borrow_date']); ?></td>
                                    <td class="col-date"><?php echo formatDate($borrow['due_date']); ?></td>
                                    <td class="col-status">
                                        <?php if ($borrow['status'] === 'borrowed'): ?>
                                            <span class="badge badge-warning">Đang mượn</span>
                                        <?php elseif ($borrow['status'] === 'returned'): ?>
                                            <span class="badge badge-success">Đã trả</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Quá hạn</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Upcoming Due Books -->
            <?php if (!empty($upcomingDue)): ?>
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-bell"></i> Sách sắp đến hạn (7 ngày tới)
                    </h2>
                </div>
                <div class="card-body">
                    <div class="table-responsive table-wrapper">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Người mượn</th>
                                    <th>Liên hệ</th>
                                    <th>Sách</th>
                                    <th>Hạn trả</th>
                                    <th>Còn lại</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($upcomingDue as $borrow): 
                                    $daysLeft = (new DateTime($borrow['due_date']))->diff(new DateTime())->days;
                                ?>
                                <tr>
                                    <td class="col-id">#<?php echo $borrow['id']; ?></td>
                                    <td class="col-borrower-name"><strong><?php echo htmlspecialchars($borrow['full_name']); ?></strong></td>
                                    <td class="col-phone"><?php echo htmlspecialchars($borrow['phone']); ?></td>
                                    <td class="col-book-title"><?php echo htmlspecialchars($borrow['title']); ?></td>
                                    <td class="col-date"><?php echo formatDate($borrow['due_date']); ?></td>
                                    <td class="col-days-left">
                                        <span class="badge <?php echo $daysLeft <= 2 ? 'badge-danger' : 'badge-warning'; ?>">
                                            <?php echo $daysLeft; ?> ngày
                                        </span>
                                    </td>
                                    <td class="col-actions">
                                        <button class="btn btn-sm btn-primary">
                                            <i class="fas fa-bell"></i> Nhắc nhở
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<style>
.col-id { width: 80px; white-space: nowrap; }
.col-borrower-name { max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.col-book-title { max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.col-isbn { max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.col-phone { max-width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.col-date { width: 120px; white-space: nowrap; }
.col-days-left { width: 100px; white-space: nowrap; text-align: center; }
.col-status { width: 120px; white-space: nowrap; }
.col-actions { width: auto; white-space: nowrap; max-width: none; }
</style>

<?php include __DIR__ . '/../layout/footer.php'; ?>
