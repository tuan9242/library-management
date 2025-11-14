<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../functions/category.php';

if (!isLibrarian()) {
    redirect('index.php');
}

$pageTitle = 'Quản lý danh mục - Admin';
$currentPage = 'admin';
$page = 'admin-categories';

// Xử lý xóa
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    if (category_delete((int)$_GET['id'])) {
        $_SESSION['alert'] = alert('Xóa danh mục thành công!', 'success');
    } else {
        $_SESSION['alert'] = alert('Không thể xóa danh mục này!', 'error');
    }
    redirect('index.php?page=admin-categories');
}

// Xử lý thêm/sửa
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryData = [
        'name'        => sanitize($_POST['name']),
        'description' => sanitize($_POST['description'])
    ];
    
    if (isset($_POST['category_id']) && !empty($_POST['category_id'])) {
        if (category_update((int)$_POST['category_id'], $categoryData)) {
            $_SESSION['alert'] = alert('Cập nhật danh mục thành công!', 'success');
        } else {
            $_SESSION['alert'] = alert('Có lỗi xảy ra!', 'error');
        }
    } else {
        if (category_create($categoryData)) {
            $_SESSION['alert'] = alert('Thêm danh mục thành công!', 'success');
        } else {
            $_SESSION['alert'] = alert('Có lỗi xảy ra!', 'error');
        }
    }
    redirect('index.php?page=admin-categories');
}
// API lấy 1 danh mục (cho nút Sửa)
if (isset($_GET['action']) && $_GET['action'] === 'get' && isset($_GET['id'])) {
    header('Content-Type: application/json');
    $cat = category_get_by_id((int)$_GET['id']);
    if ($cat) {
        echo json_encode(['success' => true, 'category' => $cat]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Không tìm thấy danh mục']);
    }
    exit;
}
// Lấy danh sách danh mục
$categories = category_get_all();

// Lấy danh mục để sửa
$editCategory = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $editCategory = category_get_by_id((int)$_GET['id']);
}

include __DIR__ . '/../layout/header.php';
?>

<div class="admin-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>
    
    <main class="admin-main">
        <div class="container">
            <div class="page-header">
                <div>
                    <h1 class="page-title">Quản lý danh mục</h1>
                    <p class="page-subtitle">Quản lý các danh mục sách trong thư viện</p>
                </div>
                <div class="page-actions">
                    <button class="btn btn-primary" onclick="openAddModal()">
                        <i class="fas fa-plus"></i> Thêm danh mục
                    </button>
                </div>
            </div>
            
            <!-- Categories Table -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-tags"></i> Danh sách danh mục
                    </h2>
                    <div class="card-actions">
                        <button class="btn btn-outline btn-sm" onclick="exportToCSV()">
                            <i class="fas fa-download"></i> Xuất CSV
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive table-wrapper">
                        <table class="table" id="categoriesTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Tên danh mục</th>
                                    <th>Mô tả</th>
                                    <th>Số sách</th>
                                    <th>Ngày tạo</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $category): ?>
                                <tr>
                                    <td class="col-id"><?php echo $category['id']; ?></td>
                                    <td class="col-name">
                                        <strong><?php echo htmlspecialchars($category['name']); ?></strong>
                                    </td>
                                    <td class="col-description"><?php echo htmlspecialchars($category['description']); ?></td>
                                    <td class="col-count">
                                        <span class="badge badge-primary">
                                            <?php echo category_get_book_count((int)$category['id']); ?> sách
                                        </span>
                                    </td>
                                    <td class="col-date"><?php echo formatDate($category['created_at']); ?></td>
                                    <td class="col-actions">
                                        <div class="table-actions">
                                            <button class="btn btn-sm btn-primary" onclick="editCategory(<?php echo $category['id']; ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="deleteCategory(<?php echo $category['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
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

<!-- Add/Edit Modal -->
<div class="modal" id="categoryModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="modalTitle">Thêm danh mục mới</h3>
            <button class="modal-close" onclick="closeModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <form method="POST" id="categoryForm">
            <div class="modal-body">
                <input type="hidden" name="category_id" id="categoryId">
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-tag"></i> Tên danh mục *
                    </label>
                    <input type="text" 
                           name="name" 
                           id="categoryName"
                           class="form-control" 
                           placeholder="Nhập tên danh mục"
                           required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-info-circle"></i> Mô tả
                    </label>
                    <textarea name="description" 
                              id="categoryDescription"
                              class="form-control" 
                              rows="4" 
                              placeholder="Nhập mô tả danh mục"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal()">
                    <i class="fas fa-times"></i> Hủy
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Lưu
                </button>
            </div>
        </form>
    </div>

</div>

<script>
function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Thêm danh mục mới';
    document.getElementById('categoryForm').reset();
    document.getElementById('categoryId').value = '';
    document.getElementById('categoryModal').style.display = 'block';
}

function editCategory(id) {
    // Lấy thông tin danh mục từ server
    fetch(`index.php?page=admin-categories&action=get&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('modalTitle').textContent = 'Sửa danh mục';
                document.getElementById('categoryId').value = data.category.id;
                document.getElementById('categoryName').value = data.category.name;
                document.getElementById('categoryDescription').value = data.category.description;
                document.getElementById('categoryModal').style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra!');
        });
}

function deleteCategory(id) {
    if (confirm('Bạn có chắc chắn muốn xóa danh mục này?')) {
        window.location.href = `index.php?page=admin-categories&action=delete&id=${id}`;
    }
}

function closeModal() {
    document.getElementById('categoryModal').style.display = 'none';
}

function exportToCSV() {
    // Tạo CSV từ bảng
    const table = document.getElementById('categoriesTable');
    const rows = table.querySelectorAll('tr');
    let csv = [];
    
    for (let i = 0; i < rows.length; i++) {
        const row = [];
        const cols = rows[i].querySelectorAll('td, th');
        
        for (let j = 0; j < cols.length - 1; j++) { // Bỏ cột thao tác
            row.push(cols[j].textContent.trim());
        }
        
        csv.push(row.join(','));
    }
    
    // Tạo file CSV
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', 'categories.csv');
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Đóng modal khi click outside
window.onclick = function(event) {
    const modal = document.getElementById('categoryModal');
    if (event.target === modal) {
        closeModal();
    }
}
</script>

<style>
.col-id { width: 80px; white-space: nowrap; }
.col-name { max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.col-description { max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.col-count { width: 120px; white-space: nowrap; text-align: center; }
.col-date { width: 140px; white-space: nowrap; }
.col-actions { width: auto; white-space: nowrap; max-width: none; }
</style>

<?php include __DIR__ . '/../layout/footer.php'; ?>
