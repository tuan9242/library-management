<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../functions/book.php';

if (!isLibrarian()) {
    redirect('index.php');
}

$pageTitle = 'Quản lý sách - Admin';
$currentPage = 'admin';

$conn = get_db_connection();
if (!$conn) {
    $_SESSION['alert'] = alert('Không thể kết nối cơ sở dữ liệu!', 'error');
    redirect('index.php');
}

// Xử lý xóa sách
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    if (!isLibrarian()) { redirect('index.php'); }
    if (book_delete((int)$_GET['id'])) {
        $_SESSION['alert'] = alert('Xóa sách thành công!', 'success');
    } else {
        $_SESSION['alert'] = alert('Không thể xóa sách này!', 'error');
    }
    redirect('index.php?page=admin-books');
}

// Xử lý xóa ảnh bìa nhanh từ bảng
if (isset($_GET['action']) && $_GET['action'] === 'clear-cover' && isset($_GET['id'])) {
    if (!isLibrarian()) { redirect('index.php'); }
    $bookId = (int)$_GET['id'];
    $existing = book_get_by_id($bookId);
    if ($existing && !empty($existing['cover_image'])) {
        $path = __DIR__ . '/../../public/' . $existing['cover_image'];
        if (strpos($existing['cover_image'], 'uploads/books/') === 0 && is_file($path)) {
            @unlink($path);
        }
        $stmt = $conn->prepare("UPDATE books SET cover_image = NULL WHERE id = :id");
        $stmt->bindParam(':id', $bookId);
        if ($stmt->execute()) {
            $_SESSION['alert'] = alert('Đã xóa ảnh bìa.', 'success');
        } else {
            $_SESSION['alert'] = alert('Không thể xóa ảnh bìa.', 'error');
        }
    }
    redirect('index.php?page=admin-books');
}

// Xử lý thêm/sửa sách
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bookData = [
        'isbn'               => sanitize($_POST['isbn']),
        'title'              => sanitize($_POST['title']),
        'author'             => sanitize($_POST['author']),
        'publisher'          => sanitize($_POST['publisher']),
        'published_year'     => sanitize($_POST['published_year']),
        'category_id'        => sanitize($_POST['category_id']),
        'available_quantity' => sanitize($_POST['available_quantity']),
        'description'        => sanitize($_POST['description']),
        'location'           => sanitize($_POST['location']),
        'status'             => sanitize($_POST['status']),
        'cover_image'        => ''
    ];
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
        if (in_array($_FILES['cover_image']['type'], $allowed)) {
            $uploadDir = __DIR__ . '/../../public/uploads/books/';
            if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0775, true); }
            $ext = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION);
            $filename = 'cover_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            $dest = $uploadDir . $filename;
            if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $dest)) {
                $bookData['cover_image'] = 'uploads/books/' . $filename; // relative to public/
            }
        }
    }

    // Handle clear existing cover
    $existing = null;
    if (isset($_POST['book_id'])) {
        $existing = book_get_by_id((int)$_POST['book_id']);
    }
    if (isset($_POST['clear_cover']) && $_POST['clear_cover'] === '1') {
        // Try to delete existing file if inside public/uploads/books
        if ($existing && !empty($existing['cover_image'])) {
            $path = __DIR__ . '/../../public/' . $existing['cover_image'];
            if (strpos($existing['cover_image'], 'uploads/books/') === 0 && is_file($path)) {
                @unlink($path);
            }
        }
        $bookData['cover_image'] = '';
    }
    // Preserve existing cover when editing and no new file uploaded and not cleared
    if (isset($_POST['book_id']) && empty($bookData['cover_image']) && (!isset($_POST['clear_cover']) || $_POST['clear_cover'] !== '1')) {
        if ($existing && !empty($existing['cover_image'])) {
            $bookData['cover_image'] = $existing['cover_image'];
        }
    }
    
    if (isset($_POST['book_id']) && !empty($_POST['book_id'])) {
        if (book_update((int)$_POST['book_id'], $bookData)) {
            $_SESSION['alert'] = alert('Cập nhật sách thành công!', 'success');
        } else {
            $_SESSION['alert'] = alert('Có lỗi xảy ra!', 'error');
        }
    } else {
        if (book_create($bookData)) {
            $_SESSION['alert'] = alert('Thêm sách thành công!', 'success');
        } else {
            $_SESSION['alert'] = alert('Có lỗi xảy ra!', 'error');
        }
    }
    redirect('index.php?page=admin-books');
}

// Lấy danh sách danh mục
$categories = $conn->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Phân trang
$page_num = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$limit = 10;
$offset = ($page_num - 1) * $limit;

$keyword = $_GET['search'] ?? '';
if ($keyword) {
    $books = book_search($keyword, null, $limit, $offset);
    $totalBooks = book_get_search_count($keyword);
} else {
    $books = book_get_all($limit, $offset);
    $totalBooks = book_get_total_count();
}
$totalPages = ceil($totalBooks / $limit);

// Lấy thông tin sách để sửa
$editBook = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $editBook = book_get_by_id((int)$_GET['id']);
}

include __DIR__ . '/../layout/header.php';
?>

<div class="admin-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>
    
    <main class="admin-main">
        <div class="container">
            <div class="page-header">
                <div>
                    <h1 class="page-title">Quản lý sách</h1>
                    <p class="page-subtitle">Quản lý thông tin sách trong thư viện</p>
                </div>
                <div class="page-actions">
                    <button class="btn btn-primary" onclick="toggleModal('addBookModal')">
                        <i class="fas fa-plus"></i> Thêm sách mới
                    </button>
                </div>
            </div>
            
            <!-- Search Bar -->
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="search-bar">
                        <input type="hidden" name="page" value="admin-books">
                        <input type="text" 
                               name="search" 
                               class="search-input" 
                               placeholder="Tìm kiếm theo tên sách, tác giả, ISBN..."
                               value="<?php echo htmlspecialchars($keyword); ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Tìm kiếm
                        </button>
                        <?php if ($keyword): ?>
                            <a href="index.php?page=admin-books" class="btn btn-outline">
                                <i class="fas fa-times"></i> Xóa lọc
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
            
            <!-- Books Table -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-book"></i> Danh sách sách 
                        <span class="badge badge-primary"><?php echo $totalBooks; ?></span>
                    </h2>
                    <button class="btn btn-outline btn-sm" onclick="exportToCSV('booksTable', 'danh-sach-sach.csv')">
                        <i class="fas fa-download"></i> Xuất CSV
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive table-wrapper" style="overflow-x: auto;">
                        <table class="table" id="booksTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Ảnh</th>
                                    <th>Tên sách</th>
                                    <th>Tác giả</th>
                                    <th>Danh mục</th>
                                    <th>Năm XB</th>
                                    <th>Có sẵn</th>
                                    <th>Vị trí</th>
                                    <th>Trạng thái</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($books as $book): ?>
                                <tr>
                                    <td>#<?php echo $book['id']; ?></td>
                                    <td>
                                        <div style="display:flex; align-items:center; gap:.5rem;">
                                            <?php if (!empty($book['cover_image'])): ?>
                                                <img src="<?php echo $book['cover_image']; ?>" alt="cover" class="thumb-sm" />
                                            <?php else: ?>
                                                <img src="uploads/defaults/default-cover.svg" alt="no cover" class="thumb-sm" />
                                            <?php endif; ?>
                                            <div class="btn-group">
                                                <a href="index.php?page=admin-books&action=edit&id=<?php echo $book['id']; ?>" class="btn btn-sm btn-secondary" title="Chỉnh sửa ảnh">
                                                    <i class="fas fa-image"></i>
                                                </a>
                                                <?php if (!empty($book['cover_image'])): ?>
                                                <a href="index.php?page=admin-books&action=clear-cover&id=<?php echo $book['id']; ?>" class="btn btn-sm btn-danger" title="Xóa ảnh"
                                                   onclick="return confirm('Xóa ảnh bìa hiện tại?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="col-title"><strong><?php echo htmlspecialchars($book['title']); ?></strong></td>
                                    <td class="col-author"><?php echo htmlspecialchars($book['author']); ?></td>
                                    <td>
                                        <span class="badge badge-primary col-category">
                                            <?php echo htmlspecialchars($book['category_name'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td class="col-year"><?php echo isset($book['published_year']) ? $book['published_year'] : ''; ?></td>
                                    <td>
                                        <strong style="color: var(--<?php echo (isset($book['available_quantity']) && $book['available_quantity'] > 0) ? 'success' : 'danger'; ?>)">
                                            <?php echo isset($book['available_quantity']) ? $book['available_quantity'] : 0; ?>
                                        </strong>
                                    </td>
                                    <td class="col-location"><?php echo htmlspecialchars($book['location'] ?? ''); ?></td>
                                    <td>
                                        <?php if ((int)($book['status'] ?? 0) === 1): ?>
                                            <span class="badge badge-success">Có sẵn</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Không có</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="index.php?page=admin-books&action=edit&id=<?php echo $book['id']; ?>" 
                                               class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="index.php?page=admin-books&action=delete&id=<?php echo $book['id']; ?>" 
                                               class="btn btn-sm btn-danger"
                                               onclick="return confirmDelete('Bạn có chắc muốn xóa sách này?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="index.php?page=admin-books&p=<?php echo $i; ?><?php echo $keyword ? '&search=' . urlencode($keyword) : ''; ?>" 
                               class="<?php echo $i === $page_num ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Add/Edit Book Modal -->
<div class="modal" id="addBookModal" style="display: <?php echo $editBook ? 'flex' : 'none'; ?>">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3><?php echo $editBook ? 'Chỉnh sửa sách' : 'Thêm sách mới'; ?></h3>
            <button class="modal-close" onclick="toggleModal('addBookModal')">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <?php if ($editBook): ?>
                <input type="hidden" name="book_id" value="<?php echo $editBook['id']; ?>">
            <?php endif; ?>
            
            <div class="modal-body">
                <input type="hidden" name="isbn" value="<?php echo $editBook['isbn'] ?? ''; ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Tên sách *</label>
                        <input type="text" name="title" class="form-control" 
                               value="<?php echo $editBook['title'] ?? ''; ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Tác giả *</label>
                        <input type="text" name="author" class="form-control" 
                               value="<?php echo $editBook['author'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Nhà xuất bản</label>
                        <input type="text" name="publisher" class="form-control" 
                               value="<?php echo $editBook['publisher'] ?? ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Năm xuất bản</label>
                        <input type="number" name="published_year" class="form-control" 
                               value="<?php echo $editBook['published_year'] ?? date('Y'); ?>" 
                               min="1900" max="<?php echo date('Y'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Danh mục</label>
                        <select name="category_id" class="form-control form-select">
                            <option value="">-- Chọn danh mục --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" 
                                        <?php echo ($editBook['category_id'] ?? '') == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Số lượng có sẵn *</label>
                        <input type="number" name="available_quantity" class="form-control" 
                               value="<?php echo $editBook['available_quantity'] ?? 1; ?>" 
                               min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Vị trí</label>
                        <input type="text" name="location" class="form-control" 
                               value="<?php echo $editBook['location'] ?? ''; ?>" 
                               placeholder="Ví dụ: A-101">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Mô tả</label>
                    <textarea name="description" class="form-control" rows="3"><?php echo $editBook['description'] ?? ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Ảnh bìa</label>
                    <div class="dropzone">
                        <input type="file" name="cover_image" accept="image/*">
                        <div class="dropzone-hint"><i class="fas fa-cloud-upload-alt"></i> Kéo thả ảnh hoặc chọn file</div>
                        <?php if (!empty($editBook['cover_image'])): ?>
                            <img id="cover_preview" class="preview" src="<?php echo $editBook['cover_image']; ?>" style="max-height:120px;margin-top:.5rem;" />
                            <div class="mt-1">
                                <label style="display:flex;align-items:center;gap:.5rem;">
                                    <input type="checkbox" name="clear_cover" value="1">
                                    <span>Xóa ảnh bìa hiện tại</span>
                                </label>
                            </div>
                        <?php else: ?>
                            <img id="cover_preview" class="preview" style="display:none;max-height:120px;margin-top:.5rem;" />
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Trạng thái</label>
                    <select name="status" class="form-control form-select">
                        <option value="available" <?php echo ($editBook['status'] ?? 'available') === 'available' ? 'selected' : ''; ?>>
                            Có sẵn
                        </option>
                        <option value="unavailable" <?php echo ($editBook['status'] ?? '') === 'unavailable' ? 'selected' : ''; ?>>
                            Không có sẵn
                        </option>
                    </select>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="toggleModal('addBookModal')">
                    Hủy
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo $editBook ? 'Cập nhật' : 'Thêm mới'; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.btn-group {
    display: flex;
    gap: 0.5rem;
}

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 2rem;
}

.modal-content {
    background: white;
    border-radius: var(--border-radius);
    max-width: 600px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-lg {
    max-width: 900px;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem;
    border-bottom: 2px solid var(--light-gray);
}

.modal-close {
    background: none;
    border: none;
    font-size: 2rem;
    cursor: pointer;
    color: var(--gray);
}

.modal-body {
    padding: 1.5rem;
}

.modal-footer {
    padding: 1.5rem;
    border-top: 2px solid var(--light-gray);
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
}

/* Ensure readable colors on white admin modal */
.modal-content .form-label { color: var(--dark); }
.modal-content .form-control {
    background: #fff;
    color: var(--dark);
    border: 1px solid var(--light-gray);
}
.modal-content .dropzone { background: #fff; }
.modal-content .dropzone-hint { color: var(--gray); }
.modal-content .card-title, .modal-content h3, .modal-content label { color: var(--dark); }
</style>

<script>
function toggleModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal.style.display === 'flex') {
        modal.style.display = 'none';
    } else {
        modal.style.display = 'flex';
    }
}
</script>

<style>
.thumb-sm {
    width: 48px;
    height: 64px;
    object-fit: cover;
    border-radius: 4px;
    border: 1px solid var(--light-gray);
    box-shadow: var(--shadow-sm);
}
.col-title { 
    max-width: 280px; 
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.col-author { 
    max-width: 200px; 
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.col-location { 
    max-width: 150px; 
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.col-category { 
    max-width: 180px; 
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.col-year { 
    width: 80px; 
    white-space: nowrap;
}
.table td:first-child { 
    width: 80px; 
    white-space: nowrap;
}
.table td:last-child { 
    white-space: nowrap;
    width: auto;
    max-width: none;
}
@media (max-width: 768px) {
    .col-title { max-width: 180px; }
    .col-author { max-width: 140px; }
    .col-location { max-width: 100px; }
    .col-category { max-width: 120px; }
}
</style>

<?php include __DIR__ . '/../layout/footer.php'; ?>
