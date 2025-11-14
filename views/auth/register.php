<?php
require_once __DIR__ . '/../../config/database.php';

$pageTitle = 'Đăng ký - Thư viện Số';
$currentPage = 'register';

// Xử lý đăng ký
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = sanitize($_POST['full_name']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    $student_id = sanitize($_POST['student_id']);
    
    $errors = [];
    
    // Validation
    if (empty($username)) {
        $errors[] = "Tên đăng nhập không được để trống!";
    } elseif (strlen($username) < 3) {
        $errors[] = "Tên đăng nhập phải có ít nhất 3 ký tự!";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email không hợp lệ!";
    }
    
    if (empty($password)) {
        $errors[] = "Mật khẩu không được để trống!";
    } elseif (strlen($password) < 6) {
        $errors[] = "Mật khẩu phải có ít nhất 6 ký tự!";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Mật khẩu xác nhận không khớp!";
    }
    
    if (empty($full_name)) {
        $errors[] = "Họ tên không được để trống!";
    }
    
    if (empty($student_id)) {
        $errors[] = "Mã sinh viên không được để trống!";
    }
    
    if (empty($errors)) {
        $conn = get_db_connection();
        if (!$conn) {
            $errors[] = "Không thể kết nối cơ sở dữ liệu!";
        } else {
        // Kiểm tra username đã tồn tại
        $query = "SELECT id FROM users WHERE username = :username OR email = :email OR student_id = :student_id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->execute();
        
        if ($stmt->fetch()) {
            $errors[] = "Tên đăng nhập, email hoặc mã sinh viên đã tồn tại!";
        } else {
            // Tạo tài khoản mới
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Kiểm tra xem cột address có tồn tại không
            try {
                $checkAddress = $conn->query("SHOW COLUMNS FROM users LIKE 'address'")->fetch();
                $hasAddress = ($checkAddress !== false);
            } catch (Exception $e) {
                $hasAddress = false;
            }
            
            if ($hasAddress) {
                $query = "INSERT INTO users (username, email, password, full_name, phone, address, student_id, role, status) 
                          VALUES (:username, :email, :password, :full_name, :phone, :address, :student_id, 'student', 'active')";
            } else {
                $query = "INSERT INTO users (username, email, password, full_name, phone, student_id, role, status) 
                          VALUES (:username, :email, :password, :full_name, :phone, :student_id, 'student', 'active')";
            }
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':full_name', $full_name);
            $stmt->bindParam(':phone', $phone);
            if ($hasAddress) {
                $stmt->bindParam(':address', $address);
            }
            $stmt->bindParam(':student_id', $student_id);
            
            if ($stmt->execute()) {
                $_SESSION['alert'] = alert('Đăng ký thành công! Vui lòng đăng nhập.', 'success');
                redirect('index.php?page=login');
            } else {
                $errors[] = "Có lỗi xảy ra khi tạo tài khoản!";
            }
            }
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
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h2 class="auth-title">Đăng ký tài khoản</h2>
                    <p class="auth-subtitle">Tạo tài khoản để sử dụng dịch vụ thư viện</p>
                </div>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <span class="alert-icon">✗</span>
                        <div class="alert-message">
                            <?php foreach ($errors as $error): ?>
                                <div><?php echo $error; ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="auth-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-user"></i> Tên đăng nhập *
                            </label>
                            <input type="text" 
                                   name="username" 
                                   class="form-control" 
                                   placeholder="Nhập tên đăng nhập"
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-id-card"></i> Mã sinh viên *
                            </label>
                            <input type="text" 
                                   name="student_id" 
                                   class="form-control" 
                                   placeholder="Nhập mã sinh viên"
                                   value="<?php echo isset($_POST['student_id']) ? htmlspecialchars($_POST['student_id']) : ''; ?>"
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
                               placeholder="Nhập email"
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-user-tag"></i> Họ và tên *
                        </label>
                        <input type="text" 
                               name="full_name" 
                               class="form-control" 
                               placeholder="Nhập họ và tên"
                               value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                               required>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-lock"></i> Mật khẩu *
                            </label>
                            <div class="password-wrapper">
                                <input type="password" 
                                       name="password" 
                                       id="password"
                                       class="form-control" 
                                       placeholder="Nhập mật khẩu"
                                       required>
                                <button type="button" class="toggle-password" onclick="togglePassword('password')">
                                    <i class="fas fa-eye" id="toggleIcon-password"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-lock"></i> Xác nhận mật khẩu *
                            </label>
                            <div class="password-wrapper">
                                <input type="password" 
                                       name="confirm_password" 
                                       id="confirm_password"
                                       class="form-control" 
                                       placeholder="Nhập lại mật khẩu"
                                       required>
                                <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">
                                    <i class="fas fa-eye" id="toggleIcon-confirm_password"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-phone"></i> Số điện thoại
                        </label>
                        <input type="tel" 
                               name="phone" 
                               class="form-control" 
                               placeholder="Nhập số điện thoại"
                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-map-marker-alt"></i> Địa chỉ
                        </label>
                        <textarea name="address" 
                                  class="form-control" 
                                  rows="3" 
                                  placeholder="Nhập địa chỉ"><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-user-plus"></i> Đăng ký
                    </button>
                </form>
                
                <div class="auth-footer">
                    <p>Đã có tài khoản? 
                        <a href="index.php?page=login" class="auth-link">
                            Đăng nhập ngay
                        </a>
                    </p>
                </div>
            </div>
            
            <div class="auth-image">
                <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=800" alt="Library">
                <div class="auth-image-overlay">
                    <h3>Tham gia cộng đồng</h3>
                    <p>Kết nối với hàng ngàn sinh viên khác</p>
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

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
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
    
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function togglePassword(fieldId) {
    const passwordInput = document.getElementById(fieldId);
    const toggleIcon = document.getElementById('toggleIcon-' + fieldId);
    
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