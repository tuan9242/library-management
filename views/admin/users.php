<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../functions/user.php';

if (!isAdmin()) {
    redirect('index.php');
}

$pageTitle = 'Quản lý người dùng - Admin';
$currentPage = 'admin';
$page = 'admin-users';

// Xử lý xóa
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    if (!isAdmin()) { redirect('index.php'); }
    if (user_delete((int)$_GET['id'])) {
        $_SESSION['alert'] = alert('Xóa người dùng thành công!', 'success');
    } else {
        $_SESSION['alert'] = alert('Không thể xóa người dùng này!', 'error');
    }
    redirect('index.php?page=admin-users');
}

// Xử lý thêm/sửa
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userData = [
        'username'   => sanitize($_POST['username']),
        'email'      => sanitize($_POST['email']),
        'full_name'  => sanitize($_POST['full_name']),
        'phone'      => sanitize($_POST['phone'] ?? ''),
        'address'    => sanitize($_POST['address'] ?? ''),
        'role'       => sanitize($_POST['role']),
        'student_id' => sanitize($_POST['student_id'] ?? ''),
        'status'     => sanitize($_POST['status']),
    ];
    
    if (isset($_POST['user_id']) && !empty($_POST['user_id'])) {
        if (user_update((int)$_POST['user_id'], $userData)) {
            $_SESSION['alert'] = alert('Cập nhật người dùng thành công!', 'success');
        } else {
            $_SESSION['alert'] = alert('Có lỗi xảy ra!', 'error');
        }
    } else {
        $userData['password'] = $_POST['password'];
        if (user_create($userData)) {
            $_SESSION['alert'] = alert('Thêm người dùng thành công!', 'success');
        } else {
            $_SESSION['alert'] = alert('Có lỗi xảy ra!', 'error');
        }
    }
    redirect('index.php?page=admin-users');
}

$users = user_get_all();
$editUser = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $editUser = user_get_by_id((int)$_GET['id']);
}

include __DIR__ . '/../layout/header.php';
?>

<div class="admin-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>
    
    <main class="admin-main">
        <div class="container">
            <div class="page-header">
                <div>
                    <h1 class="page-title">Quản lý người dùng</h1>
                    <p class="page-subtitle">Quản lý tài khoản người dùng hệ thống</p>
                </div>
                <div class="page-actions">
                    <button class="btn btn-primary" onclick="toggleModal('addUserModal')">
                        <i class="fas fa-user-plus"></i> Thêm người dùng
                    </button>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-users"></i> Danh sách người dùng
                        <span class="badge badge-primary"><?php echo count($users); ?></span>
                    </h2>
                </div>
                <div class="card-body">
                    <div class="table-responsive table-wrapper">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Ảnh</th>
                                    <th>Tên đăng nhập</th>
                                    <th>Họ tên</th>
                                    <th>Email</th>
                                    <th>Số điện thoại</th>
                                    <th>Mã SV</th>
                                    <th>Vai trò</th>
                                    <th>Trạng thái</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td class="col-id">#<?php echo $user['id']; ?></td>
                                    <td class="col-avatar">
                                        <?php 
                                            $name = trim($user['full_name'] ?: $user['username']);
                                            $initials = '';
                                            foreach (explode(' ', $name) as $part) { if ($part !== '') { $initials .= mb_strtoupper(mb_substr($part, 0, 1)); } }
                                            $initials = mb_substr($initials, 0, 2);
                                        ?>
                                        <div class="avatar">
                                            <span><?php echo $initials; ?></span>
                                        </div>
                                    </td>
                                    <td class="col-username"><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                                    <td class="col-fullname"><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td class="col-email"><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td class="col-phone"><?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></td>
                                    <td class="col-student-id"><?php echo htmlspecialchars($user['student_id'] ?? 'N/A'); ?></td>
                                    <td class="col-role">
                                        <?php
                                        $roleColors = ['admin' => 'danger', 'librarian' => 'warning', 'student' => 'primary'];
                                        $roleLabels = ['admin' => 'Admin', 'librarian' => 'Thủ thư', 'student' => 'Sinh viên'];
                                        ?>
                                        <span class="badge badge-<?php echo $roleColors[$user['role']]; ?>">
                                            <?php echo $roleLabels[$user['role']]; ?>
                                        </span>
                                    </td>
                                    <td class="col-status">
                                        <?php if ($user['status'] === 'active'): ?>
                                            <span class="badge badge-success">Hoạt động</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Không hoạt động</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="col-actions">
                                        <div class="btn-group">
                                            <a href="index.php?page=admin-users&action=edit&id=<?php echo $user['id']; ?>" 
                                               class="btn btn-sm btn-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <a href="index.php?page=admin-users&action=delete&id=<?php echo $user['id']; ?>" 
                                                   class="btn btn-sm btn-danger"
                                                   onclick="return confirmDelete('Bạn có chắc muốn xóa người dùng này?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php endif; ?>
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

<!-- Add/Edit User Modal -->
<div class="modal" id="addUserModal" style="display: <?php echo $editUser ? 'flex' : 'none'; ?>">
    <div class="modal-content modal-lg">
        <div class="modal-header">
            <h3><?php echo $editUser ? 'Chỉnh sửa người dùng' : 'Thêm người dùng mới'; ?></h3>
            <button class="modal-close" onclick="toggleModal('addUserModal')">&times;</button>
        </div>
        <form method="POST">
            <?php if ($editUser): ?>
                <input type="hidden" name="user_id" value="<?php echo $editUser['id']; ?>">
            <?php endif; ?>
            
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Tên đăng nhập *</label>
                        <input type="text" name="username" class="form-control" 
                               value="<?php echo $editUser['username'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-control" 
                               value="<?php echo $editUser['email'] ?? ''; ?>" required>
                    </div>
                </div>
                
                <?php if (!$editUser): ?>
                <div class="form-group">
                    <label class="form-label">Mật khẩu *</label>
                    <input type="password" name="password" class="form-control" 
                           placeholder="Nhập mật khẩu" required>
                </div>
                <?php endif; ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Họ và tên *</label>
                        <input type="text" name="full_name" class="form-control" 
                               value="<?php echo $editUser['full_name'] ?? ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Số điện thoại</label>
                        <input type="text" name="phone" class="form-control" 
                               value="<?php echo $editUser['phone'] ?? ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Địa chỉ</label>
                    <textarea name="address" class="form-control" rows="2"><?php echo $editUser['address'] ?? ''; ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Mã sinh viên</label>
                        <input type="text" name="student_id" class="form-control" 
                               value="<?php echo $editUser['student_id'] ?? ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Vai trò *</label>
                        <select name="role" class="form-control form-select" required>
                            <option value="student" <?php echo ($editUser['role'] ?? 'student') === 'student' ? 'selected' : ''; ?>>
                                Sinh viên
                            </option>
                            <option value="librarian" <?php echo ($editUser['role'] ?? '') === 'librarian' ? 'selected' : ''; ?>>
                                Thủ thư
                            </option>
                            <option value="admin" <?php echo ($editUser['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>
                                Admin
                            </option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Trạng thái *</label>
                        <select name="status" class="form-control form-select" required>
                            <option value="active" <?php echo ($editUser['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>
                                Hoạt động
                            </option>
                            <option value="inactive" <?php echo ($editUser['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>
                                Không hoạt động
                            </option>
                            <option value="suspended" <?php echo ($editUser['status'] ?? '') === 'suspended' ? 'selected' : ''; ?>>
                                Tạm khóa
                            </option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="toggleModal('addUserModal')">
                    Hủy
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo $editUser ? 'Cập nhật' : 'Thêm mới'; ?>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.col-id { width: 80px; white-space: nowrap; }
.col-avatar { width: 80px; white-space: nowrap; }
.col-username { max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.col-fullname { max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.col-email { max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.col-phone { max-width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.col-student-id { max-width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.col-role { width: 120px; white-space: nowrap; }
.col-status { width: 140px; white-space: nowrap; }
.col-actions { width: auto; white-space: nowrap; max-width: none; }
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

<?php include __DIR__ . '/../layout/footer.php'; ?>
