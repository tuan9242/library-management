<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../functions/user.php';

$pageTitle = 'Thông tin cá nhân - Thư viện Số';
$currentPage = 'profile';

if (!isLoggedIn()) {
    $_SESSION['alert'] = alert('Vui lòng đăng nhập để xem thông tin cá nhân!', 'warning');
    redirect('index.php?page=login');
}

$userId = $_SESSION['user_id'];
$user = user_get_by_id($userId);

if (!$user) {
    $_SESSION['alert'] = alert('Không tìm thấy thông tin người dùng!', 'error');
    redirect('index.php');
}

// Xử lý cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $data = [
        'username'   => sanitize($_POST['username']),
        'email'      => sanitize($_POST['email']),
        'full_name'  => sanitize($_POST['full_name']),
        'phone'      => sanitize($_POST['phone']),
        'address'    => sanitize($_POST['address']),
        'student_id' => sanitize($_POST['student_id']),
        'role'       => $user['role'],
        'status'     => $user['status'],
    ];

    if (user_update($userId, $data)) {
        $_SESSION['alert'] = alert('Cập nhật thông tin thành công!', 'success');
        $_SESSION['full_name'] = $data['full_name'];
        redirect('index.php?page=profile');
    } else {
        $_SESSION['alert'] = alert('Có lỗi xảy ra khi cập nhật thông tin!', 'error');
    }
}

include __DIR__ . '/../layout/header.php';
?>

<div class="profile-page">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-user"></i> Thông tin cá nhân
            </h1>
            <p class="page-subtitle">Quản lý thông tin tài khoản của bạn</p>
        </div>

        <div class="profile-content">
            <!-- Profile Card -->
            <div class="profile-card">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <div class="profile-info">
                        <h2 class="profile-name"><?php echo htmlspecialchars($user['full_name']); ?></h2>
                        <p class="profile-role">
                            <?php
                            $roleNames = [
                                'admin' => 'Quản trị viên',
                                'librarian' => 'Thủ thư',
                                'student' => 'Sinh viên'
                            ];
                            echo $roleNames[$user['role']] ?? 'Người dùng';
                            ?>
                        </p>
                        <p class="profile-status">
                            <span class="status-badge <?php echo $user['status'] === 'active' ? 'active' : 'inactive'; ?>">
                                <?php echo $user['status'] === 'active' ? 'Hoạt động' : 'Không hoạt động'; ?>
                            </span>
                        </p>
                    </div>
                </div>

                <!-- Profile Form -->
                <form method="POST" class="profile-form">
                    <input type="hidden" name="action" value="update">
                    
                    <div class="form-section">
                        <h3 class="form-section-title">
                            <i class="fas fa-user-edit"></i> Thông tin cơ bản
                        </h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-user"></i> Tên đăng nhập *
                                </label>
                                <input type="text" 
                                       name="username" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($user['username']); ?>"
                                       required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-id-card"></i> Mã sinh viên *
                                </label>
                                <input type="text" 
                                       name="student_id" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($user['student_id']); ?>"
                                       required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-envelope"></i> Email *
                            </label>
                            <input type="email" 
                                   name="email" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>"
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-user-tag"></i> Họ và tên *
                            </label>
                            <input type="text" 
                                   name="full_name" 
                                   class="form-control" 
                                   value="<?php echo htmlspecialchars($user['full_name']); ?>"
                                   required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-phone"></i> Số điện thoại
                                </label>
                                <input type="tel" 
                                       name="phone" 
                                       class="form-control" 
                                       value="<?php echo htmlspecialchars($user['phone']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-calendar"></i> Ngày tạo tài khoản
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       value="<?php echo formatDate($user['created_at']); ?>"
                                       readonly>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-map-marker-alt"></i> Địa chỉ
                            </label>
                            <textarea name="address" 
                                      class="form-control" 
                                      rows="3"><?php echo htmlspecialchars($user['address']); ?></textarea>
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Cập nhật thông tin
                        </button>
                        <a href="index.php?page=change-password" class="btn btn-outline">
                            <i class="fas fa-key"></i> Đổi mật khẩu
                        </a>
                    </div>
                </form>
            </div>

            <!-- Account Stats -->
            <div class="account-stats">
                <h3 class="stats-title">
                    <i class="fas fa-chart-bar"></i> Thống kê tài khoản
                </h3>
                
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value">0</div>
                            <div class="stat-label">Sách đang mượn</div>
                        </div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="fas fa-history"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value">0</div>
                            <div class="stat-label">Sách đã mượn</div>
                        </div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value">0</div>
                            <div class="stat-label">Sách quá hạn</div>
                        </div>
                    </div>
                    
                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="stat-content">
                            <div class="stat-value">0 VNĐ</div>
                            <div class="stat-label">Phí phạt</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.profile-page {
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

.profile-content {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
}

.profile-card {
    background: white;
    border-radius: var(--border-radius);
    padding: 2rem;
    box-shadow: var(--shadow);
}

.profile-header {
    display: flex;
    align-items: center;
    gap: 2rem;
    margin-bottom: 2rem;
    padding-bottom: 2rem;
    border-bottom: 2px solid var(--light-gray);
}

.profile-avatar {
    font-size: 4rem;
    color: var(--primary);
}

.profile-info {
    flex: 1;
}

.profile-name {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 0.5rem;
}

.profile-role {
    font-size: 1rem;
    color: var(--gray);
    margin-bottom: 0.5rem;
}

.profile-status {
    margin: 0;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
}

.status-badge.active {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success);
}

.status-badge.inactive {
    background: rgba(239, 68, 68, 0.1);
    color: var(--danger);
}

.profile-form {
    margin-top: 2rem;
}

.form-section {
    margin-bottom: 2rem;
}

.form-section-title {
    font-size: 1.2rem;
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 0.5rem;
}

.form-control {
    width: 100%;
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

.form-control[readonly] {
    background: var(--light-gray);
    cursor: not-allowed;
}

.form-actions {
    display: flex;
    gap: 1rem;
    padding-top: 2rem;
    border-top: 2px solid var(--light-gray);
}

.account-stats {
    background: white;
    border-radius: var(--border-radius);
    padding: 2rem;
    box-shadow: var(--shadow);
    height: fit-content;
}

.stats-title {
    font-size: 1.2rem;
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: var(--light-gray);
    border-radius: var(--border-radius);
}

.stat-icon {
    font-size: 1.5rem;
    color: var(--primary);
}

.stat-content {
    flex: 1;
}

.stat-value {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.8rem;
    color: var(--gray);
}

@media (max-width: 768px) {
    .profile-content {
        grid-template-columns: 1fr;
    }
    
    .profile-header {
        flex-direction: column;
        text-align: center;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include __DIR__ . '/../layout/footer.php'; ?>
