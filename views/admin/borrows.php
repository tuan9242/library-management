<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../functions/borrow.php';

if (!isLibrarian()) {
    redirect('index.php');
}

$pageTitle = 'Quản lý mượn trả - Admin';
$currentPage = 'admin';
$page = 'admin-borrows';

$conn = get_db_connection();

// Lấy danh sách mượn sách
$filter = $_GET['filter'] ?? 'all';
$keyword = $_GET['search'] ?? '';

$borrows = [];
if ($conn) {
$query = "SELECT b.*, u.full_name, u.phone, u.email, bk.title, bk.author, bk.isbn
          FROM borrows b
          JOIN users u ON b.user_id = u.id
          JOIN books bk ON b.book_id = bk.id
          WHERE 1=1";

if ($filter !== 'all') {
    $query .= " AND b.status = :status";
}

if ($keyword) {
    $query .= " AND (u.full_name LIKE :keyword OR bk.title LIKE :keyword OR bk.isbn LIKE :keyword)";
}

$query .= " ORDER BY b.created_at DESC";

$stmt = $conn->prepare($query);

if ($filter !== 'all') {
        $stmt->bindValue(':status', $filter);
}

if ($keyword) {
    $searchTerm = "%{$keyword}%";
        $stmt->bindValue(':keyword', $searchTerm);
}

$stmt->execute();
$borrows = $stmt->fetchAll();
}

// Thống kê
$statsData = borrow_get_statistics();
$stats = [
    'all'      => $statsData['total'] ?? 0,
    'pending'  => $statsData['pending'] ?? 0,
    'borrowed' => $statsData['borrowed'] ?? 0,
    'returned' => $statsData['returned'] ?? 0,
    'overdue'  => $statsData['overdue'] ?? 0,
];

include __DIR__ . '/../layout/header.php';
?>

<div class="admin-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>
    
    <main class="admin-main">
        <div class="container">
            <div class="page-header">
                <div>
                    <h1 class="page-title">Quản lý mượn trả</h1>
                    <p class="page-subtitle">Theo dõi và quản lý mượn trả sách</p>
                </div>
            </div>
            
            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <a href="?page=admin-borrows&filter=all" 
                   class="filter-tab <?php echo $filter === 'all' ? 'active' : ''; ?>">
                    <i class="fas fa-list"></i> Tất cả
                    <span class="badge"><?php echo $stats['all']; ?></span>
                </a>
                <a href="?page=admin-borrows&filter=pending" 
                   class="filter-tab <?php echo $filter === 'pending' ? 'active' : ''; ?>">
                    <i class="fas fa-clock"></i> Chờ duyệt
                    <span class="badge badge-info"><?php echo $stats['pending']; ?></span>
                </a>
                <a href="?page=admin-borrows&filter=borrowed" 
                   class="filter-tab <?php echo $filter === 'borrowed' ? 'active' : ''; ?>">
                    <i class="fas fa-hand-holding"></i> Đang mượn
                    <span class="badge badge-warning"><?php echo $stats['borrowed']; ?></span>
                </a>
                <a href="?page=admin-borrows&filter=overdue" 
                   class="filter-tab <?php echo $filter === 'overdue' ? 'active' : ''; ?>">
                    <i class="fas fa-exclamation-triangle"></i> Quá hạn
                    <span class="badge badge-danger"><?php echo $stats['overdue']; ?></span>
                </a>
                <a href="?page=admin-borrows&filter=returned" 
                   class="filter-tab <?php echo $filter === 'returned' ? 'active' : ''; ?>">
                    <i class="fas fa-check-circle"></i> Đã trả
                    <span class="badge badge-success"><?php echo $stats['returned']; ?></span>
                </a>
            </div>
            
            <!-- Search Bar -->
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="search-bar">
                        <input type="hidden" name="page" value="admin-borrows">
                        <input type="hidden" name="filter" value="<?php echo $filter; ?>">
                        <input type="text" 
                               name="search" 
                               class="search-input" 
                               placeholder="Tìm theo người mượn, tên sách, ISBN..."
                               value="<?php echo htmlspecialchars($keyword); ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Tìm kiếm
                        </button>
                        <?php if ($keyword): ?>
                            <a href="index.php?page=admin-borrows&filter=<?php echo $filter; ?>" class="btn btn-outline">
                                <i class="fas fa-times"></i> Xóa
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            
            <!-- Borrows Table -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-list"></i> Danh sách mượn trả
                    </h2>
                    <button class="btn btn-outline btn-sm" onclick="exportToCSV('borrowsTable', 'muon-tra-sach.csv')">
                        <i class="fas fa-download"></i> Xuất CSV
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive table-wrapper" style="overflow-x: auto;">
                        <table class="table" id="borrowsTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Người mượn</th>
                                    <th>Liên hệ</th>
                                    <th>Sách</th>
                                    <th>ISBN</th>
                                    <th>Số lượng</th>
                                    <th>Ngày mượn</th>
                                    <th>Hạn trả</th>
                                    <th>Ngày trả</th>
                                    <th>Phí phạt</th>
                                    <th>Trạng thái</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($borrows as $borrow): 
                                    $currentFine = $borrow['status'] !== 'returned' ? calculateFine($borrow['due_date']) : ($borrow['fine_amount'] ?? 0);
                                    $quantity = isset($borrow['quantity']) ? (int)$borrow['quantity'] : 1;
                                ?>
                                <tr>
                                    <td>#<?php echo $borrow['id']; ?></td>
                                    <td class="col-borrower-name">
                                        <strong><?php echo htmlspecialchars($borrow['full_name']); ?></strong>
                                    </td>
                                    <td class="col-contact allow-wrap">
                                        <div style="font-size: 0.85rem; line-height: 1.4;">
                                            <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($borrow['phone']); ?></div>
                                            <div style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($borrow['email']); ?></div>
                                        </div>
                                    </td>
                                    <td class="col-book-title">
                                        <?php echo htmlspecialchars($borrow['title']); ?>
                                    </td>
                                    <td class="col-isbn"><code><?php echo $borrow['isbn']; ?></code></td>
                                    <td class="col-quantity"><?php echo $quantity; ?> cuốn</td>
                                    <td class="col-date"><?php echo $borrow['borrow_date'] ? formatDate($borrow['borrow_date']) : '-'; ?></td>
                                    <td class="col-date"><?php echo formatDate($borrow['due_date']); ?></td>
                                    <td class="col-date">
                                        <?php if ($borrow['return_date']): ?>
                                            <?php echo formatDate($borrow['return_date']); ?>
                                        <?php else: ?>
                                            <span class="text-gray">Chưa trả</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="col-fine">
                                        <?php if ($currentFine > 0): ?>
                                            <span class="text-danger"><strong><?php echo number_format($currentFine); ?>đ</strong></span>
                                        <?php else: ?>
                                            <span class="text-success">0đ</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="col-status">
                                        <?php if ($borrow['status'] === 'pending'): ?>
                                            <span class="badge badge-info">Chờ duyệt</span>
                                        <?php elseif ($borrow['status'] === 'borrowed'): ?>
                                            <span class="badge badge-warning">Đang mượn</span>
                                        <?php elseif ($borrow['status'] === 'returned'): ?>
                                            <span class="badge badge-success">Đã trả</span>
                                        <?php elseif ($borrow['status'] === 'rejected'): ?>
                                            <span class="badge badge-danger">Đã từ chối</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Quá hạn</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="col-actions">
                                        <?php if ($borrow['status'] === 'pending'): ?>
                                            <button class="btn btn-success btn-sm" 
                                                    onclick="approveBorrow(<?php echo $borrow['id']; ?>)"
                                                    title="Duyệt">
                                                <i class="fas fa-check"></i> Duyệt
                                            </button>
                                            <button class="btn btn-danger btn-sm" 
                                                    onclick="rejectBorrow(<?php echo $borrow['id']; ?>)"
                                                    title="Từ chối">
                                                <i class="fas fa-times"></i> Từ chối
                                            </button>
                                        <?php elseif ($borrow['status'] === 'borrowed'): ?>
                                            <button class="btn btn-primary btn-sm" 
                                                    onclick="returnBook(<?php echo $borrow['id']; ?>)"
                                                    title="Xác nhận trả">
                                                <i class="fas fa-undo"></i> Trả
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<style>
.filter-tabs {
    display: flex;
    gap: 1rem;
    margin-bottom: 2rem;
    flex-wrap: wrap;
}

.filter-tab {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.5rem;
    background: white;
    border-radius: var(--border-radius);
    text-decoration: none;
    color: var(--dark);
    box-shadow: var(--shadow);
    transition: var(--transition);
}

.filter-tab:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}

.filter-tab.active {
    background: var(--primary);
    color: white;
}

.filter-tab .badge {
    background: rgba(0, 0, 0, 0.1);
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.85rem;
}

.filter-tab.active .badge {
    background: rgba(255, 255, 255, 0.2);
}

.text-gray {
    color: var(--gray);
}

.badge-info {
    background: var(--info);
    color: white;
}

.badge-warning {
    background: var(--warning);
    color: white;
}

.badge-success {
    background: var(--success);
    color: white;
}

.badge-danger {
    background: var(--danger);
    color: white;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.85rem;
    margin: 0.25rem;
}

/* Table column styles to prevent text wrapping */
.col-borrower-name {
    max-width: 180px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.col-contact {
    max-width: 220px;
}

.col-book-title {
    max-width: 280px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.col-isbn {
    max-width: 140px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.col-quantity {
    width: 100px;
    text-align: center;
    white-space: nowrap;
}

.col-date {
    width: 120px;
    white-space: nowrap;
}

.col-fine {
    width: 100px;
    text-align: right;
    white-space: nowrap;
}

.col-status {
    width: 120px;
    white-space: nowrap;
}

.col-actions {
    width: auto;
    white-space: nowrap;
    max-width: none;
}

@media (max-width: 768px) {
    .col-borrower-name { max-width: 140px; }
    .col-contact { max-width: 180px; }
    .col-book-title { max-width: 200px; }
    .col-isbn { max-width: 100px; }
    .col-date { width: 100px; }
}
</style>

<script>
function approveBorrow(borrowId) {
    if (confirm('Bạn có chắc chắn muốn duyệt yêu cầu mượn sách này?')) {
        const formData = new FormData();
        formData.append('borrow_id', borrowId);
        
        fetch('index.php?page=approve-borrow', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            alert('Có lỗi xảy ra!');
        });
    }
}

function rejectBorrow(borrowId) {
    const reason = prompt('Nhập lý do từ chối (có thể để trống):');
    if (reason !== null) {
        const formData = new FormData();
        formData.append('borrow_id', borrowId);
        formData.append('reason', reason);
        
        fetch('index.php?page=reject-borrow', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            alert('Có lỗi xảy ra!');
        });
    }
}

function returnBook(borrowId) {
    if (confirm('Bạn có chắc chắn muốn xác nhận trả sách này?')) {
        const formData = new FormData();
        formData.append('borrow_id', borrowId);
        
        fetch('index.php?page=return-borrow', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            alert('Có lỗi xảy ra!');
        });
    }
}
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>
