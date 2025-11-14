<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../functions/book.php';
require_once __DIR__ . '/../../functions/category.php';

$pageTitle = 'Tìm kiếm sách - Thư viện Số';
$currentPage = 'search';

// Lấy danh sách danh mục
$categories = category_get_all();

// Xử lý tìm kiếm
$keyword = isset($_GET['keyword']) ? sanitize($_GET['keyword']) : '';
$category = isset($_GET['category']) ? sanitize($_GET['category']) : '';
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

$books = [];
$totalBooks = 0;
$totalPages = 0;

if (!empty($keyword) || !empty($category)) {
    $categoryFilter = $category !== '' ? (int)$category : null;
    $books = book_search($keyword, $categoryFilter, $limit, $offset);
    $totalBooks = book_get_search_count($keyword, $categoryFilter);
    $totalPages = ceil($totalBooks / $limit);
} else {
    // Hiển thị tất cả sách nếu không có từ khóa tìm kiếm
    $books = book_get_all($limit, $offset);
    $totalBooks = book_get_total_count();
    $totalPages = ceil($totalBooks / $limit);
}

include __DIR__ . '/../layout/header.php';
?>

<div class="search-page">
    <div class="container">
        <!-- Search Header -->
        <div class="search-header">
            <h1 class="page-title">
                <i class="fas fa-search"></i> Tìm kiếm sách
            </h1>
            <p class="page-subtitle">Khám phá hàng ngàn đầu sách phong phú</p>
        </div>

        <!-- Search Form -->
        <div class="search-section">
            <form method="GET" class="search-form">
                <input type="hidden" name="page" value="search">
                
                <div class="search-inputs">
                    <div class="search-field">
                        <i class="fas fa-search"></i>
                        <input type="text" 
                               name="keyword" 
                               class="search-input" 
                               placeholder="Tìm kiếm theo tên sách, tác giả, ISBN..."
                               value="<?php echo htmlspecialchars($keyword); ?>">
                    </div>
                    
                    <div class="search-field">
                        <i class="fas fa-filter"></i>
                        <select name="category" class="search-select">
                            <option value="">Tất cả danh mục</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" 
                                        <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Tìm kiếm
                    </button>
                </div>
            </form>
        </div>

        <!-- Search Results -->
        <div class="search-results">
            <?php if (!empty($keyword) || !empty($category)): ?>
                <div class="results-header">
                    <h2>
                        <i class="fas fa-list"></i> Kết quả tìm kiếm
                        <span class="results-count">(<?php echo $totalBooks; ?> sách)</span>
                    </h2>
                    
                    <?php if (!empty($keyword)): ?>
                        <div class="search-keyword">
                            Từ khóa: <strong>"<?php echo htmlspecialchars($keyword); ?>"</strong>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($category)): ?>
                        <div class="search-category">
                            Danh mục: <strong><?php echo htmlspecialchars(category_get_name_by_id((int)$category)); ?></strong>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (empty($books)): ?>
                <div class="no-results">
                    <div class="no-results-icon">
                        <i class="fas fa-search"></i>
                    </div>
                    <h3>Không tìm thấy sách nào</h3>
                    <p>Hãy thử với từ khóa khác hoặc danh mục khác</p>
                    <a href="index.php?page=search" class="btn btn-primary">
                        <i class="fas fa-refresh"></i> Tìm kiếm lại
                    </a>
                </div>
            <?php else: ?>
                <!-- Books Grid -->
                <div class="books-grid">
                    <?php foreach ($books as $book): ?>
                        <div class="book-card">
                            <div class="book-cover">
                                <?php if (!empty($book['cover_image'])): ?>
                                    <img src="<?php echo $book['cover_image']; ?>" 
                                         alt="<?php echo htmlspecialchars($book['title']); ?>">
                                <?php else: ?>
                                    <img src="uploads/defaults/default-cover.svg" alt="<?php echo htmlspecialchars($book['title']); ?>">
                                <?php endif; ?>
                                
                                <?php if ($book['available_quantity'] > 0): ?>
                                    <div class="book-status available">
                                        <i class="fas fa-check-circle"></i> Có sẵn
                                    </div>
                                <?php else: ?>
                                    <div class="book-status unavailable">
                                        <i class="fas fa-times-circle"></i> Hết sách
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="book-info">
                                <h3 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h3>
                                <p class="book-author">
                                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($book['author']); ?>
                                </p>
                                
                                <div class="book-meta">
                                    <div class="meta-item">
                                        <i class="fas fa-tag"></i>
                                        <span><?php echo htmlspecialchars($book['category_name']); ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-calendar"></i>
                                        <span><?php echo $book['published_year']; ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?php echo htmlspecialchars($book['location']); ?></span>
                                    </div>
                                </div>
                                
                                <?php if ($book['description']): ?>
                                    <p class="book-description">
                                        <?php echo htmlspecialchars(substr($book['description'], 0, 100)) . '...'; ?>
                                    </p>
                                <?php endif; ?>
                                
                                <div class="book-actions">
                                    <a href="index.php?page=book-detail&id=<?php echo $book['id']; ?>" 
                                       class="btn btn-primary btn-sm">
                                        <i class="fas fa-info-circle"></i> Chi tiết
                                    </a>
                                    <?php if (isLoggedIn() && $book['available_quantity'] > 0): ?>
                                        <button class="btn btn-outline btn-sm" 
                                                onclick="addToCart(<?php echo $book['id']; ?>)"
                                                title="Thêm vào giỏ hàng">
                                            <i class="fas fa-shopping-cart"></i> Thêm vào giỏ
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination-wrapper">
                        <nav class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?page=search&keyword=<?php echo urlencode($keyword); ?>&category=<?php echo urlencode($category); ?>&p=<?php echo $page - 1; ?>" 
                                   class="pagination-link">
                                    <i class="fas fa-chevron-left"></i> Trước
                                </a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                                <a href="?page=search&keyword=<?php echo urlencode($keyword); ?>&category=<?php echo urlencode($category); ?>&p=<?php echo $i; ?>" 
                                   class="pagination-link <?php echo $i == $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=search&keyword=<?php echo urlencode($keyword); ?>&category=<?php echo urlencode($category); ?>&p=<?php echo $page + 1; ?>" 
                                   class="pagination-link">
                                    Sau <i class="fas fa-chevron-right"></i>
                                </a>
                            <?php endif; ?>
                        </nav>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.search-page {
    padding: 2rem 0;
}

.search-header {
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

.search-section {
    background: white;
    border-radius: var(--border-radius);
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: var(--shadow);
}

.search-form {
    margin-bottom: 0;
}

.search-inputs {
    display: grid;
    grid-template-columns: 2fr 1fr auto;
    gap: 1rem;
    align-items: end;
}

.search-field {
    position: relative;
}

.search-field i {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--gray);
    font-size: 1.1rem;
}

.search-input {
    width: 100%;
    padding: 1rem 1rem 1rem 3rem;
    border: 2px solid var(--light-gray);
    border-radius: var(--border-radius);
    font-size: 1rem;
    transition: var(--transition);
}

.search-input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.search-select {
    width: 100%;
    padding: 1rem 1rem 1rem 3rem;
    border: 2px solid var(--light-gray);
    border-radius: var(--border-radius);
    font-size: 1rem;
    background: white;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
    background-position: right 0.5rem center;
    background-repeat: no-repeat;
    background-size: 1.5em 1.5em;
    padding-right: 2.5rem;
}

.search-results {
    margin-top: 2rem;
}

.results-header {
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--light-gray);
}

.results-header h2 {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.results-count {
    color: var(--primary);
    font-weight: 500;
}

.search-keyword, .search-category {
    color: var(--gray);
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
}

.no-results {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow);
}

.no-results-icon {
    font-size: 4rem;
    color: var(--gray);
    margin-bottom: 1rem;
}

.no-results h3 {
    font-size: 1.5rem;
    color: var(--dark);
    margin-bottom: 0.5rem;
}

.no-results p {
    color: var(--gray);
    margin-bottom: 2rem;
}

.books-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.book-card {
    background: white;
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: var(--shadow);
    transition: var(--transition);
}

.book-card:hover {
    transform: translateY(-8px);
    box-shadow: var(--shadow-lg);
}

.book-cover {
    position: relative;
    width: 100%;
    height: 250px;
    background: linear-gradient(135deg, var(--primary-light), var(--primary));
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3rem;
    color: var(--white);
    overflow: hidden;
}

.book-cover img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.book-status {
    position: absolute;
    top: 1rem;
    right: 1rem;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.25rem;
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
    padding: 1.5rem;
}

.book-title {
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: var(--dark);
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    word-wrap: break-word;
    word-break: break-word;
    overflow-wrap: break-word;
    hyphens: auto;
}

.book-author {
    color: var(--gray);
    font-size: 0.9rem;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    word-wrap: break-word;
    word-break: break-word;
    overflow-wrap: break-word;
    flex-wrap: wrap;
}

.book-meta {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.85rem;
    color: var(--gray);
    word-wrap: break-word;
    word-break: break-word;
    overflow-wrap: break-word;
    flex-wrap: wrap;
}

.meta-item i {
    width: 16px;
    color: var(--primary);
}

.book-description {
    font-size: 0.9rem;
    color: var(--gray);
    line-height: 1.5;
    margin-bottom: 1rem;
    word-wrap: break-word;
    word-break: break-word;
    overflow-wrap: break-word;
    hyphens: auto;
}

.book-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.book-actions .btn {
    flex: 1;
    min-width: 100px;
}

.pagination-wrapper {
    display: flex;
    justify-content: center;
    margin-top: 3rem;
}

.pagination {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.pagination-link {
    padding: 0.75rem 1rem;
    border: 2px solid var(--light-gray);
    border-radius: var(--border-radius);
    color: var(--dark);
    text-decoration: none;
    transition: var(--transition);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.pagination-link:hover,
.pagination-link.active {
    background: var(--primary);
    color: var(--white);
    border-color: var(--primary);
}

@media (max-width: 768px) {
    .search-inputs {
        grid-template-columns: 1fr;
    }
    
    .books-grid {
        grid-template-columns: 1fr;
    }
    
    .book-actions {
        flex-direction: column;
    }
    
    .pagination {
        flex-wrap: wrap;
    }
}
</style>

<script>
function addToCart(bookId) {
    const formData = new FormData();
    formData.append('book_id', bookId);
    formData.append('quantity', 1);
    formData.append('duration_days', 30);
    
    fetch('index.php?page=api-cart&action=add', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        alert('Có lỗi xảy ra!');
    });
}
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>