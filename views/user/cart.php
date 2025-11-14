<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../functions/cart.php';
require_once __DIR__ . '/../../functions/borrow.php';

if (!isLoggedIn()) {
    redirect('index.php?page=login');
}

$pageTitle = 'Giỏ hàng - Thư viện Số';
$currentPage = 'cart';

// Xử lý cập nhật số lượng và thời gian mượn
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update') {
        $id = (int)$_POST['id'];
        $quantity = max(1, (int)$_POST['quantity']);
        cart_update_quantity($id, $_SESSION['user_id'], $quantity);
        $_SESSION['alert'] = alert('Đã cập nhật số lượng!', 'success');
        redirect('index.php?page=cart');
    } elseif ($_POST['action'] === 'update-duration') {
        $id = (int)$_POST['id'];
        $durationDays = max(1, (int)$_POST['duration_days']);
        if (cart_update_duration($id, $_SESSION['user_id'], $durationDays)) {
            $_SESSION['alert'] = alert('Đã cập nhật thời gian mượn!', 'success');
        } else {
            $_SESSION['alert'] = alert('Không thể cập nhật thời gian mượn!', 'error');
        }
        redirect('index.php?page=cart');
    } elseif ($_POST['action'] === 'remove') {
        $id = (int)$_POST['id'];
        cart_remove($id, $_SESSION['user_id']);
        $_SESSION['alert'] = alert('Đã xóa khỏi giỏ hàng!', 'success');
        redirect('index.php?page=cart');
    } elseif ($_POST['action'] === 'borrow-all') {
        // Mượn tất cả sách trong giỏ hàng
        $cartItems = cart_get_by_user($_SESSION['user_id']);
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($cartItems as $item) {
            $result = borrow_create(
                $_SESSION['user_id'],
                (int)$item['book_id'],
                (int)$item['quantity'],
                (int)$item['duration_days']
            );
            
            if ($result['success']) {
                $successCount++;
                cart_remove((int)$item['id'], $_SESSION['user_id']);
            } else {
                $errorCount++;
            }
        }
        
        if ($successCount > 0) {
            $_SESSION['alert'] = alert("Đã gửi {$successCount} yêu cầu mượn sách!" . ($errorCount > 0 ? " ({$errorCount} yêu cầu thất bại)" : ''), 'success');
        } else {
            $_SESSION['alert'] = alert('Không thể gửi yêu cầu mượn sách!', 'error');
        }
        redirect('index.php?page=cart');
    }
}

// Lấy giỏ hàng
$cartItems = cart_get_by_user($_SESSION['user_id']);

include __DIR__ . '/../layout/header.php';
?>

<div class="cart-page">
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-shopping-cart"></i> Giỏ hàng
            </h1>
        </div>

        <?php if (empty($cartItems)): ?>
            <div class="empty-cart">
                <div class="empty-cart-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h2>Giỏ hàng trống</h2>
                <p>Bạn chưa có sách nào trong giỏ hàng</p>
                <a href="index.php?page=search" class="btn btn-primary">
                    <i class="fas fa-search"></i> Tìm sách
                </a>
            </div>
        <?php else: ?>
            <div class="cart-content">
                <div class="cart-items">
                    <?php 
                    $totalPrice = 0;
                    foreach ($cartItems as $item): 
                        $itemPrice = $item['duration_days'] * 2000 * $item['quantity'];
                        $totalPrice += $itemPrice;
                    ?>
                        <div class="cart-item">
                            <div class="cart-item-cover">
                                <?php if (!empty($item['cover_image'])): ?>
                                    <img src="<?php echo $item['cover_image']; ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                                <?php else: ?>
                                    <img src="uploads/defaults/default-cover.svg" alt="<?php echo htmlspecialchars($item['title']); ?>">
                                <?php endif; ?>
                            </div>
                            
                            <div class="cart-item-info">
                                <h3 class="cart-item-title">
                                    <a href="index.php?page=book-detail&id=<?php echo $item['book_id']; ?>">
                                        <?php echo htmlspecialchars($item['title']); ?>
                                    </a>
                                </h3>
                                <p class="cart-item-author">
                                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($item['author']); ?>
                                </p>
                                <div class="cart-item-meta">
                                    <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($item['location']); ?></span>
                                    <span class="available-quantity">
                                        <i class="fas fa-check-circle"></i> Có sẵn: <?php echo $item['available_quantity']; ?> cuốn
                                    </span>
                                </div>
                            </div>
                            
                            <div class="cart-item-controls">
                                <div class="quantity-control">
                                    <label>Số lượng:</label>
                                    <form method="POST" style="display: inline-flex; gap: 0.5rem; align-items: center;">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                        <input type="number" 
                                               name="quantity" 
                                               value="<?php echo $item['quantity']; ?>" 
                                               min="1" 
                                               max="<?php echo $item['available_quantity']; ?>"
                                               class="quantity-input"
                                               onchange="this.form.submit()">
                                    </form>
                                </div>
                                
                                <div class="duration-control">
                                    <label>Thời gian mượn:</label>
                                    <form method="POST" style="display: inline-flex; gap: 0.5rem; align-items: center;">
                                        <input type="hidden" name="action" value="update-duration">
                                        <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                        <input type="number" 
                                               name="duration_days" 
                                               value="<?php echo $item['duration_days']; ?>" 
                                               min="1" 
                                               max="365"
                                               class="duration-input"
                                               onchange="this.form.submit()">
                                        <span style="font-size: 0.85rem; color: var(--gray);">ngày</span>
                                    </form>
                                </div>
                                
                                <div class="cart-item-price">
                                    <strong><?php echo number_format($itemPrice); ?> đ</strong>
                                </div>
                                
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc chắn muốn xóa?')">
                                        <i class="fas fa-trash"></i> Xóa
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="cart-summary">
                    <div class="summary-card">
                        <h3>Tóm tắt đơn mượn</h3>
                        <div class="summary-row">
                            <span>Tổng số sách:</span>
                            <strong><?php echo count($cartItems); ?> loại</strong>
                        </div>
                        <div class="summary-row">
                            <span>Tổng số lượng:</span>
                            <strong><?php echo array_sum(array_column($cartItems, 'quantity')); ?> cuốn</strong>
                        </div>
                        <div class="summary-row total">
                            <span>Tổng tiền (ước tính):</span>
                            <strong><?php echo number_format($totalPrice); ?> đ</strong>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="action" value="borrow-all">
                            <button type="submit" class="btn btn-primary btn-lg btn-block" onclick="return confirm('Bạn có chắc chắn muốn gửi yêu cầu mượn tất cả sách trong giỏ hàng?')">
                                <i class="fas fa-book-reader"></i> Gửi yêu cầu mượn tất cả
                            </button>
                        </form>
                        <a href="index.php?page=search" class="btn btn-outline btn-lg btn-block">
                            <i class="fas fa-plus"></i> Thêm sách khác
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.cart-page {
    padding: 2rem 0;
}

.page-header {
    margin-bottom: 2rem;
}

.page-title {
    font-size: 2rem;
    font-weight: 700;
    color: var(--dark);
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.empty-cart {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
}

.empty-cart-icon {
    font-size: 5rem;
    color: var(--gray);
    margin-bottom: 1.5rem;
}

.empty-cart h2 {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
    color: var(--dark);
}

.empty-cart p {
    color: var(--gray);
    margin-bottom: 2rem;
}

.cart-content {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: 2rem;
}

.cart-items {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.cart-item {
    background: white;
    border-radius: var(--border-radius);
    padding: 1.5rem;
    box-shadow: var(--shadow);
    display: grid;
    grid-template-columns: 120px 1fr auto;
    gap: 1.5rem;
    align-items: start;
}

.cart-item-cover {
    width: 120px;
    height: 160px;
    border-radius: var(--border-radius);
    overflow: hidden;
    background: linear-gradient(135deg, var(--primary-light), var(--primary));
}

.cart-item-cover img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.cart-item-info {
    flex: 1;
}

.cart-item-title {
    margin-bottom: 0.5rem;
    font-size: 1.1rem;
    font-weight: 600;
}

.cart-item-title a {
    color: var(--dark);
    text-decoration: none;
}

.cart-item-title a:hover {
    color: var(--primary);
}

.cart-item-author {
    color: var(--gray);
    font-size: 0.9rem;
    margin-bottom: 0.75rem;
}

.cart-item-meta {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    font-size: 0.85rem;
    color: var(--gray);
}

.available-quantity {
    color: var(--success);
    font-weight: 600;
}

.cart-item-controls {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    min-width: 200px;
}

.quantity-control, .duration-control {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.quantity-control label, .duration-control label {
    font-size: 0.85rem;
    color: var(--gray);
    font-weight: 600;
}

.quantity-input, .duration-input {
    width: 80px;
    padding: 0.5rem;
    border: 2px solid var(--light-gray);
    border-radius: var(--border-radius);
    font-size: 0.9rem;
}

.duration-input {
    width: 100px;
}

.cart-item-price {
    font-size: 1.1rem;
    color: var(--primary);
}

.summary-card {
    background: white;
    border-radius: var(--border-radius);
    padding: 1.5rem;
    box-shadow: var(--shadow);
    position: sticky;
    top: 2rem;
}

.summary-card h3 {
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: var(--dark);
}

.summary-row {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--light-gray);
}

.summary-row.total {
    border-bottom: none;
    border-top: 2px solid var(--primary);
    margin-top: 0.5rem;
    padding-top: 1rem;
    font-size: 1.1rem;
}

.btn-block {
    width: 100%;
    margin-top: 1rem;
}

@media (max-width: 992px) {
    .cart-content {
        grid-template-columns: 1fr;
    }
    
    .cart-item {
        grid-template-columns: 100px 1fr;
    }
    
    .cart-item-controls {
        grid-column: 1 / -1;
        flex-direction: row;
        flex-wrap: wrap;
    }
}
</style>

<?php include __DIR__ . '/../layout/footer.php'; ?>

