<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../functions/borrow.php';

$pageTitle = 'Sách đã mượn - Thư viện Số';
$currentPage = 'my-borrows';

if (!isLoggedIn()) {
    redirect('index.php?page=login');
}

$userId = $_SESSION['user_id'];

// Xử lý trả sách
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'return') {
    $borrowId = (int)$_POST['borrow_id'];
    $result = borrow_return_book($borrowId);
    
    if ($result['success']) {
        $_SESSION['alert'] = alert('Trả sách thành công!', 'success');
    } else {
        $_SESSION['alert'] = alert($result['message'], 'error');
    }
    redirect('index.php?page=my-borrows');
}

// Lấy danh sách sách đã mượn
$borrows = borrow_get_by_user($userId);

// Phân loại sách theo trạng thái
$currentBorrows = array_filter($borrows, function($borrow) {
    return $borrow['status'] === 'borrowed';
});

$overdueBorrows = array_filter($borrows, function($borrow) {
    return $borrow['status'] === 'overdue';
});

$returnedBorrows = array_filter($borrows, function($borrow) {
    return $borrow['status'] === 'returned';
});

include __DIR__ . '/../layout/header.php';
?>

<div class="my-borrows-page">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-book-reader"></i> Sách đã mượn
            </h1>
            <p class="page-subtitle">Quản lý sách bạn đã mượn từ thư viện</p>
        </div>

        <!-- Stats Cards -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-book"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo count($currentBorrows); ?></div>
                    <div class="stat-label">Đang mượn</div>
                </div>
            </div>
            
            <div class="stat-card warning">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo count($overdueBorrows); ?></div>
                    <div class="stat-label">Quá hạn</div>
                </div>
            </div>
            
            <div class="stat-card success">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo count($returnedBorrows); ?></div>
                    <div class="stat-label">Đã trả</div>
                </div>
            </div>
        </div>

        <!-- Current Borrows -->
        <?php if (!empty($currentBorrows)): ?>
            <div class="borrow-section">
                <h2 class="section-title">
                    <i class="fas fa-clock"></i> Sách đang mượn
                </h2>
                
                <div class="borrows-grid">
                    <?php foreach ($currentBorrows as $borrow): ?>
                        <div class="borrow-card">
                            <div class="borrow-cover">
                                <?php if ($borrow['cover_image']): ?>
                                    <img src="<?php echo $borrow['cover_image']; ?>" 
                                         alt="<?php echo htmlspecialchars($borrow['title']); ?>">
                                <?php else: ?>
                                    <i class="fas fa-book"></i>
                                <?php endif; ?>
                            </div>
                            
                            <div class="borrow-info">
                                <h3 class="borrow-title"><?php echo htmlspecialchars($borrow['title']); ?></h3>
                                <p class="borrow-author">
                                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($borrow['author']); ?>
                                </p>
                                
                                <div class="borrow-dates">
                                    <div class="date-item">
                                        <i class="fas fa-calendar-plus"></i>
                                        <span><strong>Ngày mượn:</strong> <?php echo formatDate($borrow['borrow_date']); ?></span>
                                    </div>
                                    <div class="date-item">
                                        <i class="fas fa-calendar-check"></i>
                                        <span><strong>Hạn trả:</strong> <?php echo formatDate($borrow['due_date']); ?></span>
                                    </div>
                                </div>
                                
                                <?php
                                $daysLeft = (strtotime($borrow['due_date']) - time()) / (60 * 60 * 24);
                                if ($daysLeft < 0) {
                                    $statusClass = 'overdue';
                                    $statusText = 'Quá hạn ' . abs(floor($daysLeft)) . ' ngày';
                                } elseif ($daysLeft <= 3) {
                                    $statusClass = 'warning';
                                    $statusText = 'Sắp hết hạn (' . floor($daysLeft) . ' ngày)';
                                } else {
                                    $statusClass = 'success';
                                    $statusText = 'Còn ' . floor($daysLeft) . ' ngày';
                                }
                                ?>
                                
                                <div class="borrow-status <?php echo $statusClass; ?>">
                                    <i class="fas fa-<?php echo $statusClass === 'overdue' ? 'exclamation-triangle' : ($statusClass === 'warning' ? 'clock' : 'check-circle'); ?>"></i>
                                    <?php echo $statusText; ?>
                                </div>
                                
                                <div class="borrow-actions">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="return">
                                        <input type="hidden" name="borrow_id" value="<?php echo $borrow['id']; ?>">
                                        <button type="submit" class="btn btn-success btn-sm" 
                                                onclick="return confirm('Bạn có chắc chắn muốn trả sách này?')">
                                            <i class="fas fa-undo"></i> Trả sách
                                        </button>
                                    </form>
                                    
                                    <a href="index.php?page=book-detail&id=<?php echo $borrow['book_id']; ?>" 
                                       class="btn btn-outline btn-sm">
                                        <i class="fas fa-info-circle"></i> Chi tiết
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Overdue Borrows -->
        <?php if (!empty($overdueBorrows)): ?>
            <div class="borrow-section">
                <h2 class="section-title danger">
                    <i class="fas fa-exclamation-triangle"></i> Sách quá hạn
                </h2>
                
                <div class="borrows-grid">
                    <?php foreach ($overdueBorrows as $borrow): ?>
                        <div class="borrow-card overdue">
                            <div class="borrow-cover">
                                <?php if ($borrow['cover_image']): ?>
                                    <img src="<?php echo $borrow['cover_image']; ?>" 
                                         alt="<?php echo htmlspecialchars($borrow['title']); ?>">
                                <?php else: ?>
                                    <i class="fas fa-book"></i>
                                <?php endif; ?>
                            </div>
                            
                            <div class="borrow-info">
                                <h3 class="borrow-title"><?php echo htmlspecialchars($borrow['title']); ?></h3>
                                <p class="borrow-author">
                                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($borrow['author']); ?>
                                </p>
                                
                                <div class="borrow-dates">
                                    <div class="date-item">
                                        <i class="fas fa-calendar-plus"></i>
                                        <span><strong>Ngày mượn:</strong> <?php echo formatDate($borrow['borrow_date']); ?></span>
                                    </div>
                                    <div class="date-item">
                                        <i class="fas fa-calendar-times"></i>
                                        <span><strong>Hạn trả:</strong> <?php echo formatDate($borrow['due_date']); ?></span>
                                    </div>
                                </div>
                                
                                <?php
                                $daysOverdue = (time() - strtotime($borrow['due_date'])) / (60 * 60 * 24);
                                $fineAmount = calculateFine($borrow['due_date']);
                                ?>
                                
                                <div class="borrow-status overdue">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    Quá hạn <?php echo floor($daysOverdue); ?> ngày
                                </div>
                                
                                <?php if ($fineAmount > 0): ?>
                                    <div class="fine-amount">
                                        <i class="fas fa-money-bill-wave"></i>
                                        <span>Phí phạt: <?php echo number_format($fineAmount); ?> VNĐ</span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="borrow-actions">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="return">
                                        <input type="hidden" name="borrow_id" value="<?php echo $borrow['id']; ?>">
                                        <button type="submit" class="btn btn-danger btn-sm" 
                                                onclick="return confirm('Bạn có chắc chắn muốn trả sách này? Phí phạt sẽ được tính.')">
                                            <i class="fas fa-undo"></i> Trả sách
                                        </button>
                                    </form>
                                    
                                    <a href="index.php?page=book-detail&id=<?php echo $borrow['book_id']; ?>" 
                                       class="btn btn-outline btn-sm">
                                        <i class="fas fa-info-circle"></i> Chi tiết
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Returned Borrows -->
        <?php if (!empty($returnedBorrows)): ?>
            <div class="borrow-section">
                <h2 class="section-title success">
                    <i class="fas fa-check-circle"></i> Sách đã trả
                </h2>
                
                <div class="borrows-grid">
                    <?php foreach ($returnedBorrows as $borrow): ?>
                        <div class="borrow-card returned">
                            <div class="borrow-cover">
                                <?php if ($borrow['cover_image']): ?>
                                    <img src="<?php echo $borrow['cover_image']; ?>" 
                                         alt="<?php echo htmlspecialchars($borrow['title']); ?>">
                                <?php else: ?>
                                    <i class="fas fa-book"></i>
                                <?php endif; ?>
                            </div>
                            
                            <div class="borrow-info">
                                <h3 class="borrow-title"><?php echo htmlspecialchars($borrow['title']); ?></h3>
                                <p class="borrow-author">
                                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($borrow['author']); ?>
                                </p>
                                
                                <div class="borrow-dates">
                                    <div class="date-item">
                                        <i class="fas fa-calendar-plus"></i>
                                        <span><strong>Ngày mượn:</strong> <?php echo formatDate($borrow['borrow_date']); ?></span>
                                    </div>
                                    <div class="date-item">
                                        <i class="fas fa-calendar-check"></i>
                                        <span><strong>Hạn trả:</strong> <?php echo formatDate($borrow['due_date']); ?></span>
                                    </div>
                                    <div class="date-item">
                                        <i class="fas fa-calendar-minus"></i>
                                        <span><strong>Ngày trả:</strong> <?php echo formatDate($borrow['return_date']); ?></span>
                                    </div>
                                </div>
                                
                                <div class="borrow-status success">
                                    <i class="fas fa-check-circle"></i>
                                    Đã trả
                                </div>
                                
                                <?php if ($borrow['fine_amount'] > 0): ?>
                                    <div class="fine-amount">
                                        <i class="fas fa-money-bill-wave"></i>
                                        <span>Phí phạt đã trả: <?php echo number_format($borrow['fine_amount']); ?> VNĐ</span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="borrow-actions">
                                    <a href="index.php?page=book-detail&id=<?php echo $borrow['book_id']; ?>" 
                                       class="btn btn-outline btn-sm">
                                        <i class="fas fa-info-circle"></i> Chi tiết
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Empty State -->
        <?php if (empty($borrows)): ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-book-reader"></i>
                </div>
                <h3>Chưa có sách nào được mượn</h3>
                <p>Hãy khám phá thư viện và mượn những cuốn sách thú vị!</p>
                <a href="index.php?page=search" class="btn btn-primary">
                    <i class="fas fa-search"></i> Tìm kiếm sách
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.my-borrows-page {
    padding: 2rem 0;
}

.page-header {
    text-align: center;
    margin-bottom: 3rem;
}

.page-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
}

.page-subtitle {
    font-size: 1.1rem;
    color: var(--gray);
}

.stats-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
    margin-bottom: 3rem;
}

.stat-card {
    background: white;
    border-radius: var(--border-radius);
    padding: 1.5rem;
    box-shadow: var(--shadow);
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: var(--transition);
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}

.stat-card.warning {
    border-left: 4px solid var(--warning);
}

.stat-card.success {
    border-left: 4px solid var(--success);
}

.stat-icon {
    font-size: 2.5rem;
    color: var(--primary);
}

.stat-card.warning .stat-icon {
    color: var(--warning);
}

.stat-card.success .stat-icon {
    color: var(--success);
}

.stat-info {
    flex: 1;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.9rem;
    color: var(--gray);
}

.borrow-section {
    margin-bottom: 3rem;
}

.section-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.section-title.danger {
    color: var(--danger);
}

.section-title.success {
    color: var(--success);
}

.borrows-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
}

.borrow-card {
    background: white;
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: var(--shadow);
    transition: var(--transition);
}

.borrow-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}

.borrow-card.overdue {
    border-left: 4px solid var(--danger);
}

.borrow-card.returned {
    border-left: 4px solid var(--success);
}

.borrow-cover {
    height: 150px;
    background: linear-gradient(135deg, var(--primary-light), var(--primary));
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: var(--white);
}

.borrow-cover img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.borrow-info {
    padding: 1.5rem;
}

.borrow-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 0.5rem;
    line-height: 1.3;
}

.borrow-author {
    color: var(--gray);
    font-size: 0.9rem;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.borrow-dates {
    margin-bottom: 1rem;
}

.date-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    color: var(--dark);
    margin-bottom: 0.5rem;
}

.date-item i {
    color: var(--primary);
    width: 16px;
}

.borrow-status {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    font-weight: 600;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    margin-bottom: 1rem;
}

.borrow-status.success {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success);
}

.borrow-status.warning {
    background: rgba(245, 158, 11, 0.1);
    color: var(--warning);
}

.borrow-status.overdue {
    background: rgba(239, 68, 68, 0.1);
    color: var(--danger);
}

.fine-amount {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    color: var(--danger);
    font-weight: 600;
    margin-bottom: 1rem;
    padding: 0.5rem 1rem;
    background: rgba(239, 68, 68, 0.1);
    border-radius: 8px;
}

.borrow-actions {
    display: flex;
    gap: 0.5rem;
}

.borrow-actions .btn {
    flex: 1;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
}

.empty-icon {
    font-size: 4rem;
    color: var(--gray);
    margin-bottom: 1rem;
}

.empty-state h3 {
    font-size: 1.5rem;
    color: var(--dark);
    margin-bottom: 0.5rem;
}

.empty-state p {
    color: var(--gray);
    margin-bottom: 2rem;
}

@media (max-width: 768px) {
    .borrows-grid {
        grid-template-columns: 1fr;
    }
    
    .borrow-actions {
        flex-direction: column;
    }
    
    .stats-cards {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include __DIR__ . '/../layout/footer.php'; ?>
