<?php
require_once __DIR__ . '/../../config/database.php';

$pageTitle = 'Đăng nhập - Thư viện Số';
$currentPage = 'login';

// Xử lý đăng nhập
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    $conn = get_db_connection();
    
    if (!$conn) {
        $error = "Không thể kết nối cơ sở dữ liệu!";
    } else {
    $query = "SELECT * FROM users WHERE (username = :username OR email = :username) AND status = 'active'";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        
        // Log hoạt động
        $logQuery = "INSERT INTO activity_logs (user_id, action, description, ip_address) 
                     VALUES (:user_id, 'login', 'Đăng nhập thành công', :ip)";
        $logStmt = $conn->prepare($logQuery);
        $logStmt->bindParam(':user_id', $user['id']);
        $ip = $_SERVER['REMOTE_ADDR'];
        $logStmt->bindParam(':ip', $ip);
        $logStmt->execute();
        
        $_SESSION['alert'] = alert('Đăng nhập thành công!', 'success');
        
        if ($user['role'] === 'admin' || $user['role'] === 'librarian') {
            redirect('index.php?page=admin-dashboard');
        } else {
            redirect('index.php');
        }
    } else {
        $error = "Tên đăng nhập hoặc mật khẩu không đúng!";
        }
    }
}

include __DIR__ . '/../layout/header.php';
?>

<div class="auth-container">
    <div class="container">
        <div class="auth-wrapper">
            <div class="auth-card">
                <div class="auth-header">
                    <div class="auth-icon">
                        <i class="fas fa-book-open"></i>
                    </div>
                    <h2 class="auth-title">Đăng nhập</h2>
                    <p class="auth-subtitle">Chào mừng bạn quay trở lại!</p>
                </div>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-error">
                        <span class="alert-icon">✗</span>
                        <span class="alert-message"><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="auth-form">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-user"></i> Tên đăng nhập hoặc Email
                        </label>
                        <input type="text" 
                               name="username" 
                               class="form-control" 
                               placeholder="Nhập tên đăng nhập hoặc email"
                               required
                               autofocus>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-lock"></i> Mật khẩu
                        </label>
                        <div class="password-wrapper">
                            <input type="password" 
                                   name="password" 
                                   id="password"
                                   class="form-control" 
                                   placeholder="Nhập mật khẩu"
                                   required>
                            <button type="button" class="toggle-password" onclick="togglePassword()">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="form-options">
                        <label class="checkbox-label">
                            <input type="checkbox" name="remember">
                            <span>Ghi nhớ đăng nhập</span>
                        </label>
                        <a href="index.php?page=forgot-password" class="forgot-link">
                            Quên mật khẩu?
                        </a>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-sign-in-alt"></i> Đăng nhập
                    </button>
                </form>
                
                <div class="auth-footer">
                    <p>Chưa có tài khoản? 
                        <a href="index.php?page=register" class="auth-link">
                            Đăng ký ngay
                        </a>
                    </p>
                </div>
                
                <div class="demo-accounts">
                    <p class="demo-title"><i class="fas fa-info-circle"></i> Tài khoản demo:</p>
                    <div class="demo-list">
                        <div class="demo-item">
                            <strong>Admin:</strong> admin / admin123
                        </div>
                        <div class="demo-item">
                            <strong>Thủ thư:</strong> librarian / admin123
                        </div>
                        <div class="demo-item">
                            <strong>Sinh viên:</strong> student1 / admin123
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="auth-image">
                <img src="https://images.unsplash.com/photo-1481627834876-b7833e8f5570?w=800" alt="Library">
                <div class="auth-image-overlay">
                    <h3>Thư viện Số</h3>
                    <p>Khám phá tri thức, nâng tầm tương lai</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.auth-container {
    min-height: calc(100vh - 80px);
    display: flex;
    align-items: center;
    padding: 2rem 0;
}

.auth-wrapper {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0;
    background: white;
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: var(--shadow-lg);
    max-width: 1000px;
    margin: 0 auto;
}

.auth-card {
    padding: 3rem;
}

.auth-header {
    text-align: center;
    margin-bottom: 2rem;
}

.auth-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, var(--primary), var(--primary-dark));
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
    font-size: 2rem;
    color: white;
}

.auth-title {
    font-size: 2rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 0.5rem;
}

.auth-subtitle {
    color: var(--gray);
    font-size: 1rem;
}

.auth-form {
    margin-bottom: 2rem;
}

.password-wrapper {
    position: relative;
}

.toggle-password {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: var(--gray);
    cursor: pointer;
    font-size: 1.1rem;
}

.form-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
    color: var(--dark);
}

.checkbox-label input {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.forgot-link {
    color: var(--primary);
    text-decoration: none;
    font-weight: 500;
}

.forgot-link:hover {
    text-decoration: underline;
}

.btn-block {
    width: 100%;
    padding: 1rem;
    font-size: 1.1rem;
}

.auth-footer {
    text-align: center;
    padding-top: 2rem;
    border-top: 1px solid var(--light-gray);
}

.auth-link {
    color: var(--primary);
    text-decoration: none;
    font-weight: 600;
}

.auth-link:hover {
    text-decoration: underline;
}

.demo-accounts {
    margin-top: 2rem;
    padding: 1.5rem;
    background: var(--light-gray);
    border-radius: var(--border-radius);
}

.demo-title {
    font-weight: 600;
    margin-bottom: 1rem;
    color: var(--dark);
}

.demo-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.demo-item {
    padding: 0.5rem;
    background: white;
    border-radius: 8px;
    font-size: 0.9rem;
    color: var(--gray);
}

.demo-item strong {
    color: var(--dark);
}

.auth-image {
    position: relative;
    overflow: hidden;
}

.auth-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.auth-image-overlay {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 2rem;
    background: linear-gradient(to top, rgba(0,0,0,0.8), transparent);
    color: white;
}

.auth-image-overlay h3 {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
}

@media (max-width: 968px) {
    .auth-wrapper {
        grid-template-columns: 1fr;
    }
    
    .auth-image {
        display: none;
    }
    
    .auth-card {
        padding: 2rem;
    }
}
</style>

<script>
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>
