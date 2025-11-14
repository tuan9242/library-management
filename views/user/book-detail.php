<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../functions/book.php';
require_once __DIR__ . '/../../functions/borrow.php';

$pageTitle = 'Chi tiết sách - Thư viện Số';
$currentPage = 'book-detail';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    redirect('index.php');
}

$bookId = (int)$_GET['id'];

// Lấy thông tin sách
$book = book_get_by_id($bookId);
if (!$book) {
    $_SESSION['alert'] = alert('Không tìm thấy sách!', 'error');
    redirect('index.php');
}

// Xử lý thêm vào giỏ hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add-to-cart') {
    if (!isLoggedIn()) {
        $_SESSION['alert'] = alert('Vui lòng đăng nhập để thêm vào giỏ hàng!', 'warning');
        redirect('index.php?page=login');
    }
    
    require_once __DIR__ . '/../../functions/cart.php';
    $quantity = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;
    $durationDays = isset($_POST['duration_days']) ? max(1, (int)$_POST['duration_days']) : 30;
    $result = cart_add($_SESSION['user_id'], $bookId, $quantity, $durationDays);
    
    if ($result['success']) {
        $_SESSION['alert'] = alert($result['message'], 'success');
    } else {
        $_SESSION['alert'] = alert($result['message'], 'error');
    }
    redirect('index.php?page=book-detail&id=' . $bookId);
}

// Xử lý mượn sách trực tiếp
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'borrow') {
    if (!isLoggedIn()) {
        $_SESSION['alert'] = alert('Vui lòng đăng nhập để mượn sách!', 'warning');
        redirect('index.php?page=login');
    }
    
    if ($book['available_quantity'] <= 0) {
        $_SESSION['alert'] = alert('Sách này hiện không có sẵn!', 'error');
    } else {
        $quantity = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;
        $durationDays = isset($_POST['duration_days']) ? max(1, (int)$_POST['duration_days']) : 30;
        $result = borrow_create($_SESSION['user_id'], $bookId, $quantity, $durationDays);
        if ($result['success']) {
            $_SESSION['alert'] = alert($result['message'], 'success');
        } else {
            $_SESSION['alert'] = alert($result['message'], 'error');
        }
    }
    redirect('index.php?page=book-detail&id=' . $bookId);
}

// Lấy sách liên quan
$relatedBooks = !empty($book['category_id'])
    ? book_get_by_category((int)$book['category_id'], 4, $bookId)
    : [];

// Kiểm tra xem người dùng đã mượn sách này chưa
$alreadyBorrowed = false;
if (isLoggedIn()) {
    $conn = get_db_connection();
    if ($conn) {
        $checkBorrowStmt = $conn->prepare(
            "SELECT id FROM borrows 
             WHERE user_id = :user_id AND book_id = :book_id 
               AND (status = 'borrowed' OR status = 'pending') 
             LIMIT 1"
        );
        $checkBorrowStmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $checkBorrowStmt->bindValue(':book_id', $bookId, PDO::PARAM_INT);
        $checkBorrowStmt->execute();
        $alreadyBorrowed = $checkBorrowStmt->fetch() ? true : false;
    }
}

include __DIR__ . '/../layout/header.php';
?>

<div class="book-detail-page">
    <div class="container">
        <!-- Breadcrumb -->
        <nav class="breadcrumb">
            <a href="index.php"><i class="fas fa-home"></i> Trang chủ</a>
            <i class="fas fa-chevron-right"></i>
            <a href="index.php?page=search">Tìm kiếm sách</a>
            <i class="fas fa-chevron-right"></i>
            <span><?php echo htmlspecialchars($book['title']); ?></span>
        </nav>

        <div class="book-detail-content">
            <!-- Book Info -->
            <div class="book-main-info">
                <div class="book-cover-large">
                    <?php if (!empty($book['cover_image'])): ?>
                        <img src="<?php echo $book['cover_image']; ?>" 
                             alt="<?php echo htmlspecialchars($book['title']); ?>">
                    <?php else: ?>
                        <img src="uploads/defaults/default-cover.svg" alt="<?php echo htmlspecialchars($book['title']); ?>">
                    <?php endif; ?>
                    
                    <?php if ($book['available_quantity'] > 0): ?>
                        <div class="book-status available">
                            <i class="fas fa-check-circle"></i> Có sẵn (<?php echo $book['available_quantity']; ?> cuốn)
                        </div>
                    <?php else: ?>
                        <div class="book-status unavailable">
                            <i class="fas fa-times-circle"></i> Hết sách
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="book-info">
                    <h1 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h1>
                    
                    <div class="book-author">
                        <i class="fas fa-user"></i>
                        <span><?php echo htmlspecialchars($book['author']); ?></span>
                    </div>
                    
                    <div class="book-meta">
                        <div class="meta-item">
                            <i class="fas fa-tag"></i>
                            <span><strong>Danh mục:</strong> <?php echo htmlspecialchars($book['category_name']); ?></span>
                        </div>
                        
                        
                        
                        <?php if ($book['publisher']): ?>
                            <div class="meta-item">
                                <i class="fas fa-building"></i>
                                <span><strong>Nhà xuất bản:</strong> <?php echo htmlspecialchars($book['publisher']); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($book['published_year']): ?>
                            <div class="meta-item">
                                <i class="fas fa-calendar"></i>
                                <span><strong>Năm xuất bản:</strong> <?php echo $book['published_year']; ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="meta-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span><strong>Vị trí:</strong> <?php echo htmlspecialchars($book['location']); ?></span>
                        </div>
                        
                        <div class="meta-item">
                            <i class="fas fa-check-circle"></i>
                            <span><strong>Có sẵn:</strong> <?php echo $book['available_quantity']; ?> cuốn</span>
                        </div>
                        
                        <div class="meta-item">
                            <i class="fas fa-money-bill-wave"></i>
                            <span><strong>Giá mượn/ngày:</strong> <?php echo number_format(2000); ?> đ</span>
                        </div>
                    </div>
                    
                    <?php if ($book['description']): ?>
                        <div class="book-description">
                            <h3><i class="fas fa-info-circle"></i> Mô tả</h3>
                            <p><?php echo nl2br(htmlspecialchars($book['description'])); ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="book-actions">
                        <?php if (isLoggedIn()): ?>
                            <?php if ($alreadyBorrowed): ?>
                                <div class="already-borrowed-notice">
                                    <i class="fas fa-info-circle"></i>
                                    <span>Bạn đã có yêu cầu mượn hoặc đang mượn sách này.</span>
                                </div>
                            <?php elseif ($book['available_quantity'] > 0): ?>
                                <div class="borrow-form-wrapper">
                                    <form method="POST" class="borrow-form">
                                        <input type="hidden" name="action" value="borrow">
                                        <div class="form-group">
                                            <label for="quantity" class="form-label">
                                                <i class="fas fa-book"></i> Số lượng mượn
                                            </label>
                                            <input type="number" 
                                                   name="quantity" 
                                                   id="quantity" 
                                                   class="form-control" 
                                                   min="1" 
                                                   max="<?php echo $book['available_quantity']; ?>"
                                                   value="1" 
                                                   required>
                                            <small class="form-text">Có sẵn: <?php echo $book['available_quantity']; ?> cuốn</small>
                                        </div>
                                        <div class="form-group">
                                            <label for="duration_days" class="form-label">
                                                <i class="fas fa-calendar"></i> Thời gian mượn
                                            </label>
                                            <select name="duration_days" id="duration_days" class="form-control" required>
                                                <option value="7">7 ngày</option>
                                                <option value="14">14 ngày</option>
                                                <option value="30" selected>30 ngày</option>
                                                <option value="60">60 ngày</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label class="form-label">
                                                <i class="fas fa-money-bill-wave"></i> Thành tiền (ước tính)
                                            </label>
                                            <input type="text" id="total_price_display" class="form-control" value="60.000 đ" readonly>
                                        </div>
                                        <div class="action-buttons">
                                            <button type="submit" class="btn btn-primary btn-lg">
                                                <i class="fas fa-book-reader"></i> Mượn sách ngay
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-outline btn-lg" 
                                                    onclick="addToCart()">
                                                <i class="fas fa-shopping-cart"></i> Thêm vào giỏ hàng
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-lg" disabled>
                                    <i class="fas fa-times-circle"></i> Không có sẵn
                                </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="index.php?page=login" class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt"></i> Đăng nhập để mượn sách
                            </a>
                        <?php endif; ?>
                        
                        <a href="index.php?page=search" class="btn btn-outline btn-lg">
                            <i class="fas fa-search"></i> Tìm sách khác
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Related Books -->
            <?php if (!empty($relatedBooks)): ?>
                <div class="related-books">
                    <h2><i class="fas fa-book"></i> Sách liên quan</h2>
                    <div class="related-books-grid">
                        <?php foreach ($relatedBooks as $relatedBook): ?>
                            <div class="related-book-card">
                                <div class="related-book-cover">
                                    <?php if (!empty($relatedBook['cover_image'])): ?>
                                        <img src="<?php echo $relatedBook['cover_image']; ?>" 
                                             alt="<?php echo htmlspecialchars($relatedBook['title']); ?>">
                                    <?php else: ?>
                                        <div class="cover-placeholder"><i class="fas fa-book"></i></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="related-book-info">
                                    <h4 class="related-book-title">
                                        <a href="index.php?page=book-detail&id=<?php echo $relatedBook['id']; ?>">
                                            <?php echo htmlspecialchars($relatedBook['title']); ?>
                                        </a>
                                    </h4>
                                    <p class="related-book-author">
                                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($relatedBook['author']); ?>
                                    </p>
                                    
                                    <div class="related-book-meta">
                                        <span class="related-book-year"><?php echo $relatedBook['published_year']; ?></span>
                                        <span class="related-book-status <?php echo $relatedBook['available_quantity'] > 0 ? 'available' : 'unavailable'; ?>">
                                            <?php echo $relatedBook['available_quantity'] > 0 ? 'Có sẵn' : 'Hết sách'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.book-detail-page {
    padding: 2rem 0;
}

.breadcrumb {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 2rem;
    font-size: 0.9rem;
}

.breadcrumb a {
    color: var(--primary);
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.breadcrumb a:hover {
    text-decoration: underline;
}

.breadcrumb i.fa-chevron-right {
    color: var(--gray);
    font-size: 0.8rem;
}

.breadcrumb span {
    color: var(--gray);
}

.book-detail-content {
    background: white;
    border-radius: var(--border-radius);
    padding: 2rem;
    box-shadow: var(--shadow);
}

.book-main-info {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 3rem;
    margin-bottom: 3rem;
}

.book-cover-large {
    position: relative;
    background: linear-gradient(135deg, var(--primary-light), var(--primary));
    border-radius: var(--border-radius);
    overflow: hidden;
    height: 400px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.book-cover-large img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.book-placeholder {
    font-size: 4rem;
    color: var(--white);
}

.book-status {
    position: absolute;
    top: 1rem;
    right: 1rem;
    padding: 0.75rem 1.5rem;
    border-radius: 25px;
    font-size: 0.9rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.book-status.available {
    background: rgba(16, 185, 129, 0.9);
    color: white;
}

.book-status.unavailable {
    background: rgba(239, 68, 68, 0.9);
    color: white;
}

.book-info {
    padding: 1rem 0;
}

.book-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 1rem;
    line-height: 1.3;
    word-wrap: break-word;
    word-break: break-word;
    overflow-wrap: break-word;
    hyphens: auto;
}

.book-author {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 1.2rem;
    color: var(--gray);
    margin-bottom: 2rem;
    word-wrap: break-word;
    word-break: break-word;
    overflow-wrap: break-word;
    flex-wrap: wrap;
}

.book-author i {
    color: var(--primary);
}

.book-meta {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 2rem;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 1rem;
    color: var(--dark);
    word-wrap: break-word;
    word-break: break-word;
    overflow-wrap: break-word;
    flex-wrap: wrap;
}

.meta-item i {
    color: var(--primary);
    width: 20px;
    text-align: center;
}

.book-description {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: var(--light-gray);
    border-radius: var(--border-radius);
}

.book-description h3 {
    font-size: 1.2rem;
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.book-description p {
    line-height: 1.6;
    color: var(--dark);
}

.book-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.related-books {
    border-top: 2px solid var(--light-gray);
    padding-top: 2rem;
}

.related-books h2 {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.related-books-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1.5rem;
}

.related-book-card {
    background: var(--light-gray);
    border-radius: var(--border-radius);
    overflow: hidden;
    transition: var(--transition);
}

.related-book-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow);
}

.related-book-cover {
    height: 150px;
    background: linear-gradient(135deg, var(--primary-light), var(--primary));
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: var(--white);
}

.related-book-cover img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.related-book-info {
    padding: 1rem;
}

.related-book-title {
    margin-bottom: 0.5rem;
}

.related-book-title a {
    color: var(--dark);
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 600;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    word-wrap: break-word;
    word-break: break-word;
    overflow-wrap: break-word;
}

.related-book-title a:hover {
    color: var(--primary);
}

.related-book-author {
    color: var(--gray);
    font-size: 0.8rem;
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.related-book-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.related-book-year {
    font-size: 0.8rem;
    color: var(--gray);
}

.related-book-status {
    font-size: 0.8rem;
    font-weight: 600;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
}

.related-book-status.available {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success);
}

.related-book-status.unavailable {
    background: rgba(239, 68, 68, 0.1);
    color: var(--danger);
}

@media (max-width: 768px) {
    .book-main-info {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
    
    .book-cover-large {
        height: 300px;
    }
    
    .book-title {
        font-size: 2rem;
    }
    
    .book-meta {
        grid-template-columns: 1fr;
    }
    
    .book-actions {
        flex-direction: column;
    }
    
    .related-books-grid {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    }
}
</style>

<style>
.borrow-form-wrapper {
    background: var(--light-gray);
    padding: 1.5rem;
    border-radius: var(--border-radius);
    margin-bottom: 1rem;
}

.borrow-form {
    display: grid;
    gap: 1rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-label {
    font-weight: 600;
    color: var(--dark);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.form-control {
    padding: 0.75rem;
    border: 2px solid var(--light-gray);
    border-radius: var(--border-radius);
    font-size: 1rem;
    transition: var(--transition);
}

.form-control:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.form-text {
    font-size: 0.85rem;
    color: var(--gray);
}

.action-buttons {
    display: flex;
    gap: 1rem;
    margin-top: 1rem;
}

.action-buttons .btn {
    flex: 1;
}

.already-borrowed-notice {
    background: rgba(59, 130, 246, 0.1);
    border: 2px solid var(--primary);
    border-radius: var(--border-radius);
    padding: 1rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: var(--primary);
    font-weight: 500;
    margin-bottom: 1rem;
}

.already-borrowed-notice i {
    font-size: 1.2rem;
}

@media (max-width: 768px) {
    .action-buttons {
        flex-direction: column;
    }
}
</style>

<script>
const FIXED_PRICE_PER_DAY = 2000;

function updateTotal() {
    const duration = document.getElementById('duration_days');
    const quantity = document.getElementById('quantity');
    const totalDisplay = document.getElementById('total_price_display');
    
    if (!duration || !quantity || !totalDisplay) return;
    
    const d = Math.max(1, parseInt(duration.value || '0', 10));
    const q = Math.max(1, parseInt(quantity.value || '1', 10));
    const t = d * FIXED_PRICE_PER_DAY * q;
    totalDisplay.value = t.toLocaleString('vi-VN') + ' đ';
}

function addToCart() {
    const form = document.querySelector('.borrow-form');
    const formData = new FormData(form);
    formData.set('action', 'add-to-cart');
    
    fetch('index.php?page=book-detail&id=<?php echo $bookId; ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (response.redirected) {
            window.location.href = response.url;
        } else {
            return response.text();
        }
    })
    .catch(error => {
        alert('Có lỗi xảy ra!');
    });
}

document.addEventListener('DOMContentLoaded', function(){
    const duration = document.getElementById('duration_days');
    const quantity = document.getElementById('quantity');
    
    if (duration) duration.addEventListener('change', updateTotal);
    if (quantity) quantity.addEventListener('change', updateTotal);
    if (quantity) quantity.addEventListener('input', updateTotal);
    
    updateTotal();
});
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>