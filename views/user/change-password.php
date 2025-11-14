<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../functions/user.php';

$pageTitle = 'Đổi mật khẩu - Thư viện Số';
$currentPage = 'change-password';

if (!isLoggedIn()) {
    $_SESSION['alert'] = alert('Vui lòng đăng nhập để đổi mật khẩu!', 'warning');
    redirect('index.php?page=login');
}

$userId = $_SESSION['user_id'];

// Xử lý đổi mật khẩu
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    $errors = [];
    
    // Validation
    if (empty($currentPassword)) {
        $errors[] = "Mật khẩu hiện tại không được để trống!";
    }
    
    if (empty($newPassword)) {
        $errors[] = "Mật khẩu mới không được để trống!";
    } elseif (strlen($newPassword) < 6) {
        $errors[] = "Mật khẩu mới phải có ít nhất 6 ký tự!";
    }
    
    if ($newPassword !== $confirmPassword) {
        $errors[] = "Mật khẩu xác nhận không khớp!";
    }
    
    if (empty($errors)) {
        // Lấy thông tin user hiện tại
        $user = user_get_by_id($userId);
        
        if ($user && password_verify($currentPassword, $user['password'])) {
            if (user_change_password($userId, $newPassword)) {
                $_SESSION['alert'] = alert('Đổi mật khẩu thành công!', 'success');
                redirect('index.php?page=profile');
            } else {
                $errors[] = "Có lỗi xảy ra khi đổi mật khẩu!";
            }
        } else {
            $errors[] = "Mật khẩu hiện tại không đúng!";
        }
    }
}

include __DIR__ . '/../layout/header.php';
?>

<div class="change-password-page">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-key"></i> Đổi mật khẩu
            </h1>
            <p class="page-subtitle">Thay đổi mật khẩu để bảo mật tài khoản</p>
        </div>

        <div class="change-password-content">
            <div class="password-card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-shield-alt"></i> Bảo mật tài khoản
                    </h2>
                    <p class="card-subtitle">Nhập thông tin để thay đổi mật khẩu</p>
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

                <form method="POST" class="password-form">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-lock"></i> Mật khẩu hiện tại *
                        </label>
                        <div class="password-wrapper">
                            <input type="password" 
                                   name="current_password" 
                                   id="current_password"
                                   class="form-control" 
                                   placeholder="Nhập mật khẩu hiện tại"
                                   required>
                            <button type="button" class="toggle-password" onclick="togglePassword('current_password')">
                                <i class="fas fa-eye" id="toggleIcon-current_password"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-key"></i> Mật khẩu mới *
                        </label>
                        <div class="password-wrapper">
                            <input type="password" 
                                   name="new_password" 
                                   id="new_password"
                                   class="form-control" 
                                   placeholder="Nhập mật khẩu mới"
                                   required>
                            <button type="button" class="toggle-password" onclick="togglePassword('new_password')">
                                <i class="fas fa-eye" id="toggleIcon-new_password"></i>
                            </button>
                        </div>
                        <div class="password-strength">
                            <div class="strength-bar">
                                <div class="strength-fill" id="strengthFill"></div>
                            </div>
                            <div class="strength-text" id="strengthText">Nhập mật khẩu</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-check-circle"></i> Xác nhận mật khẩu mới *
                        </label>
                        <div class="password-wrapper">
                            <input type="password" 
                                   name="confirm_password" 
                                   id="confirm_password"
                                   class="form-control" 
                                   placeholder="Nhập lại mật khẩu mới"
                                   required>
                            <button type="button" class="toggle-password" onclick="togglePassword('confirm_password')">
                                <i class="fas fa-eye" id="toggleIcon-confirm_password"></i>
                            </button>
                        </div>
                        <div class="password-match" id="passwordMatch"></div>
                    </div>

                    <div class="password-requirements">
                        <h4><i class="fas fa-info-circle"></i> Yêu cầu mật khẩu:</h4>
                        <ul>
                            <li id="req-length"><i class="fas fa-times"></i> Ít nhất 6 ký tự</li>
                            <li id="req-uppercase"><i class="fas fa-times"></i> Có chữ hoa</li>
                            <li id="req-lowercase"><i class="fas fa-times"></i> Có chữ thường</li>
                            <li id="req-number"><i class="fas fa-times"></i> Có số</li>
                            <li id="req-special"><i class="fas fa-times"></i> Có ký tự đặc biệt</li>
                        </ul>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Đổi mật khẩu
                        </button>
                        <a href="index.php?page=profile" class="btn btn-outline">
                            <i class="fas fa-arrow-left"></i> Quay lại
                        </a>
                    </div>
                </form>
            </div>

            <div class="security-tips">
                <h3 class="tips-title">
                    <i class="fas fa-lightbulb"></i> Mẹo bảo mật
                </h3>
                <ul class="tips-list">
                    <li>
                        <i class="fas fa-check"></i>
                        <span>Sử dụng mật khẩu dài và phức tạp</span>
                    </li>
                    <li>
                        <i class="fas fa-check"></i>
                        <span>Không sử dụng thông tin cá nhân</span>
                    </li>
                    <li>
                        <i class="fas fa-check"></i>
                        <span>Thay đổi mật khẩu định kỳ</span>
                    </li>
                    <li>
                        <i class="fas fa-check"></i>
                        <span>Không chia sẻ mật khẩu với ai</span>
                    </li>
                    <li>
                        <i class="fas fa-check"></i>
                        <span>Sử dụng mật khẩu khác nhau cho các tài khoản</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<style>
.change-password-page {
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

.change-password-content {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
    max-width: 1000px;
    margin: 0 auto;
}

.password-card {
    background: white;
    border-radius: var(--border-radius);
    padding: 2rem;
    box-shadow: var(--shadow);
}

.card-header {
    text-align: center;
    margin-bottom: 2rem;
    padding-bottom: 2rem;
    border-bottom: 2px solid var(--light-gray);
}

.card-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--dark);
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.card-subtitle {
    color: var(--gray);
    font-size: 1rem;
}

.password-form {
    margin-top: 2rem;
}

.form-group {
    margin-bottom: 2rem;
}

.form-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 0.5rem;
}

.password-wrapper {
    position: relative;
}

.form-control {
    width: 100%;
    padding: 0.75rem 3rem 0.75rem 1rem;
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

.password-strength {
    margin-top: 0.5rem;
}

.strength-bar {
    width: 100%;
    height: 4px;
    background: var(--light-gray);
    border-radius: 2px;
    overflow: hidden;
}

.strength-fill {
    height: 100%;
    width: 0%;
    transition: all 0.3s ease;
    border-radius: 2px;
}

.strength-text {
    font-size: 0.8rem;
    margin-top: 0.25rem;
    color: var(--gray);
}

.password-match {
    font-size: 0.8rem;
    margin-top: 0.25rem;
}

.password-requirements {
    background: var(--light-gray);
    border-radius: var(--border-radius);
    padding: 1rem;
    margin-bottom: 2rem;
}

.password-requirements h4 {
    font-size: 0.9rem;
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 0.75rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.password-requirements ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.password-requirements li {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.8rem;
    color: var(--gray);
    margin-bottom: 0.25rem;
}

.password-requirements li.valid {
    color: var(--success);
}

.password-requirements li i {
    width: 12px;
}

.form-actions {
    display: flex;
    gap: 1rem;
    padding-top: 2rem;
    border-top: 2px solid var(--light-gray);
}

.security-tips {
    background: white;
    border-radius: var(--border-radius);
    padding: 2rem;
    box-shadow: var(--shadow);
    height: fit-content;
}

.tips-title {
    font-size: 1.2rem;
    font-weight: 600;
    color: var(--dark);
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.tips-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.tips-list li {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--light-gray);
}

.tips-list li:last-child {
    border-bottom: none;
}

.tips-list li i {
    color: var(--success);
    font-size: 0.9rem;
}

.tips-list li span {
    font-size: 0.9rem;
    color: var(--dark);
}

@media (max-width: 768px) {
    .change-password-content {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
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

// Password strength checker
document.getElementById('new_password').addEventListener('input', function() {
    const password = this.value;
    const strengthFill = document.getElementById('strengthFill');
    const strengthText = document.getElementById('strengthText');
    
    let strength = 0;
    let strengthLabel = '';
    let strengthColor = '';
    
    // Check length
    if (password.length >= 6) strength++;
    if (password.length >= 8) strength++;
    
    // Check for uppercase
    if (/[A-Z]/.test(password)) strength++;
    
    // Check for lowercase
    if (/[a-z]/.test(password)) strength++;
    
    // Check for numbers
    if (/\d/.test(password)) strength++;
    
    // Check for special characters
    if (/[^A-Za-z0-9]/.test(password)) strength++;
    
    // Determine strength level
    if (strength <= 2) {
        strengthLabel = 'Yếu';
        strengthColor = '#ef4444';
    } else if (strength <= 4) {
        strengthLabel = 'Trung bình';
        strengthColor = '#f59e0b';
    } else {
        strengthLabel = 'Mạnh';
        strengthColor = '#10b981';
    }
    
    // Update UI
    strengthFill.style.width = (strength / 6 * 100) + '%';
    strengthFill.style.backgroundColor = strengthColor;
    strengthText.textContent = strengthLabel;
    strengthText.style.color = strengthColor;
    
    // Update requirements
    updateRequirements(password);
});

// Password match checker
document.getElementById('confirm_password').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    const matchDiv = document.getElementById('passwordMatch');
    
    if (confirmPassword === '') {
        matchDiv.textContent = '';
        return;
    }
    
    if (newPassword === confirmPassword) {
        matchDiv.textContent = '✓ Mật khẩu khớp';
        matchDiv.style.color = '#10b981';
    } else {
        matchDiv.textContent = '✗ Mật khẩu không khớp';
        matchDiv.style.color = '#ef4444';
    }
});

function updateRequirements(password) {
    const requirements = {
        'req-length': password.length >= 6,
        'req-uppercase': /[A-Z]/.test(password),
        'req-lowercase': /[a-z]/.test(password),
        'req-number': /\d/.test(password),
        'req-special': /[^A-Za-z0-9]/.test(password)
    };
    
    Object.keys(requirements).forEach(reqId => {
        const element = document.getElementById(reqId);
        const icon = element.querySelector('i');
        
        if (requirements[reqId]) {
            element.classList.add('valid');
            icon.classList.remove('fa-times');
            icon.classList.add('fa-check');
        } else {
            element.classList.remove('valid');
            icon.classList.remove('fa-check');
            icon.classList.add('fa-times');
        }
    });
}
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>
