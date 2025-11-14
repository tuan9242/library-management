<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Thư viện Số'; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                    <a href="index.php" class="logo">
                        <i class="fas fa-book-open"></i>
                        <span>Thư viện Số</span>
                    </a>
                    
                    <nav class="nav-menu">
                    <?php if (!isLoggedIn()): // Khách ?>
                        <a href="index.php" class="nav-link <?php echo ($currentPage ?? '') === 'home' ? 'active' : ''; ?>">
                            <i class="fas fa-home"></i> Trang chủ
                        </a>
                    <?php elseif (isAdmin()): // Admin chỉ hiển thị Quản lý ?>
                        <a href="index.php?page=admin-dashboard" class="nav-link <?php echo in_array(($currentPage ?? ''), ['admin','admin-dashboard','admin-books','admin-borrows','admin-users','admin-categories','admin-reports']) ? 'active' : ''; ?>">
                            <i class="fas fa-chart-line"></i> Quản lý
                        </a>
                    <?php elseif (isLibrarian()): // Thủ thư: chỉ hiển thị Quản lý ?>
                        <a href="index.php?page=admin-dashboard" class="nav-link <?php echo in_array(($currentPage ?? ''), ['admin','admin-dashboard','admin-books','admin-borrows','admin-categories']) ? 'active' : ''; ?>">
                            <i class="fas fa-chart-line"></i> Quản lý
                        </a>
                    <?php else: // Student/người dùng thường ?>
                        <a href="index.php" class="nav-link <?php echo ($currentPage ?? '') === 'home' ? 'active' : ''; ?>">
                            <i class="fas fa-home"></i> Trang chủ
                        </a>
                        <a href="index.php?page=search" class="nav-link <?php echo ($currentPage ?? '') === 'search' ? 'active' : ''; ?>">
                            <i class="fas fa-search"></i> Tìm sách
                        </a>
                        <a href="index.php?page=my-borrows" class="nav-link <?php echo ($currentPage ?? '') === 'my-borrows' ? 'active' : ''; ?>">
                            <i class="fas fa-book-reader"></i> Sách đã mượn
                        </a>
                    <?php endif; ?>
                </nav>
                
                <div class="header-actions">
                    <?php if (isLoggedIn()): 
                        try {
                            require_once __DIR__ . '/../../functions/notification.php';
                            require_once __DIR__ . '/../../functions/cart.php';
                            $unreadCount = notification_get_unread_count($_SESSION['user_id']);
                            // Chỉ hiển thị giỏ hàng cho sinh viên
                            $cartCount = (!isAdmin() && !isLibrarian()) ? cart_get_count($_SESSION['user_id']) : 0;
                        } catch (Exception $e) {
                            // Nếu bảng chưa tồn tại, set giá trị mặc định
                            $unreadCount = 0;
                            $cartCount = 0;
                        }
                    ?>
                        <!-- Notifications - Hiển thị cho tất cả user đã đăng nhập -->
                        <div class="notification-dropdown">
                            <button class="notification-btn" id="notificationBtn">
                                <i class="fas fa-bell"></i>
                                <?php if ($unreadCount > 0): ?>
                                    <span class="notification-badge"><?php echo $unreadCount; ?></span>
                                <?php endif; ?>
                            </button>
                            <div class="notification-dropdown-menu" id="notificationDropdown">
                                <div class="notification-header">
                                    <h3>Thông báo</h3>
                                    <?php if ($unreadCount > 0): ?>
                                        <button class="mark-all-read-btn" onclick="markAllNotificationsRead()">
                                            Đánh dấu tất cả đã đọc
                                        </button>
                                    <?php endif; ?>
                                </div>
                                <div class="notification-list" id="notificationList">
                                    <div class="notification-loading">Đang tải...</div>
                                </div>
                                <div class="notification-footer">
                                    <a href="index.php?page=notifications">Xem tất cả</a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Cart -->
                        <?php if (!isAdmin() && !isLibrarian()): ?>
                        <a href="index.php?page=cart" class="cart-btn">
                            <i class="fas fa-shopping-cart"></i>
                            <?php if ($cartCount > 0): ?>
                                <span class="cart-badge"><?php echo $cartCount; ?></span>
                            <?php endif; ?>
                        </a>
                        <?php endif; ?>
                        
                        <div class="user-dropdown">
                            <button class="user-btn">
                                <i class="fas fa-user-circle"></i>
                                <span><?php echo $_SESSION['full_name'] ?? 'User'; ?></span>
                                <i class="fas fa-chevron-down"></i>
                            </button>
                            <div class="dropdown-menu">
                                <a href="index.php?page=profile">
                                    <i class="fas fa-user"></i> Thông tin cá nhân
                                </a>
                                <a href="index.php?page=change-password">
                                    <i class="fas fa-key"></i> Đổi mật khẩu
                                </a>
                                <div class="dropdown-divider"></div>
                                <a href="index.php?action=logout" class="text-danger">
                                    <i class="fas fa-sign-out-alt"></i> Đăng xuất
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="index.php?page=login" class="btn btn-outline">
                            <i class="fas fa-sign-in-alt"></i> Đăng nhập
                        </a>
                        <a href="index.php?page=register" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Đăng ký
                        </a>
                    <?php endif; ?>
                </div>
                
                <button class="mobile-menu-btn" id="mobileMenuBtn">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Mobile Menu -->
    <div class="mobile-menu" id="mobileMenu">
        <div class="mobile-menu-header">
            <div class="logo">
                <i class="fas fa-book-open"></i>
                <span>Thư viện Số</span>
            </div>
            <button class="close-btn" id="closeMobileMenu">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <nav class="mobile-nav">
            <?php if (!isLoggedIn()): // Khách ?>
                <a href="index.php"><i class="fas fa-home"></i> Trang chủ</a>
                <a href="index.php?page=login"><i class="fas fa-sign-in-alt"></i> Đăng nhập</a>
                <a href="index.php?page=register"><i class="fas fa-user-plus"></i> Đăng ký</a>
            <?php elseif (isAdmin()):  ?>
                <a href="index.php?page=admin-dashboard"><i class="fas fa-chart-line"></i> Quản lý</a>
                <a href="index.php?page=profile"><i class="fas fa-user"></i> Thông tin cá nhân</a>
                <a href="index.php?action=logout" class="text-danger"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
            <?php elseif (isLibrarian()):  ?>
                <a href="index.php?page=admin-dashboard"><i class="fas fa-chart-line"></i> Quản lý</a>
                <a href="index.php?page=profile"><i class="fas fa-user"></i> Thông tin cá nhân</a>
                <a href="index.php?action=logout" class="text-danger"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
            <?php else: // Student ?>
                <a href="index.php"><i class="fas fa-home"></i> Trang chủ</a>
                <a href="index.php?page=search"><i class="fas fa-search"></i> Tìm sách</a>
                <a href="index.php?page=my-borrows"><i class="fas fa-book-reader"></i> Sách đã mượn</a>
                <a href="index.php?page=profile"><i class="fas fa-user"></i> Thông tin cá nhân</a>
                <a href="index.php?action=logout" class="text-danger"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
            <?php endif; ?>
        </nav>
    </div>

    <main class="main-content"><?php echo isset($_SESSION['alert']) ? $_SESSION['alert'] : ''; unset($_SESSION['alert']); ?>
    
    <style>
        .notification-dropdown, .cart-btn {
            position: relative;
            margin-right: 1rem;
        }
        
        .notification-btn, .cart-btn {
            position: relative;
            padding: 0.5rem 1rem;
            background: transparent;
            border: none;
            color: var(--dark);
            font-size: 1.2rem;
            cursor: pointer;
            border-radius: var(--border-radius);
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .notification-btn:hover, .cart-btn:hover {
            background: var(--light-gray);
        }
        
        .notification-badge, .cart-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--danger);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 600;
        }
        
        .notification-dropdown-menu {
            position: absolute;
            top: 100%;
            right: 0;
            width: 350px;
            max-height: 500px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            z-index: 1000;
            display: none;
            margin-top: 0.5rem;
            overflow: hidden;
        }
        
        .notification-dropdown-menu.active {
            display: block;
        }
        
        .notification-header {
            padding: 1rem;
            border-bottom: 1px solid var(--light-gray);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .notification-header h3 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
        }
        
        .mark-all-read-btn {
            background: none;
            border: none;
            color: var(--primary);
            font-size: 0.85rem;
            cursor: pointer;
            padding: 0.25rem 0.5rem;
        }
        
        .notification-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .notification-item {
            padding: 1rem;
            border-bottom: 1px solid var(--light-gray);
            cursor: pointer;
            transition: var(--transition);
        }
        
        .notification-item:hover {
            background: var(--light-gray);
        }
        
        .notification-item.unread {
            background: rgba(99, 102, 241, 0.05);
        }
        
        .notification-item-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
            font-size: 0.95rem;
        }
        
        .notification-item-message {
            font-size: 0.85rem;
            color: var(--gray);
            margin-bottom: 0.5rem;
        }
        
        .notification-item-time {
            font-size: 0.75rem;
            color: var(--gray);
        }
        
        .notification-loading, .notification-empty {
            padding: 2rem;
            text-align: center;
            color: var(--gray);
        }
        
        .notification-footer {
            padding: 0.75rem 1rem;
            border-top: 1px solid var(--light-gray);
            text-align: center;
        }
        
        .notification-footer a {
            color: var(--primary);
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .cart-btn {
            text-decoration: none;
            color: var(--dark);
        }
    </style>
    
    <script>
        // Load notifications
        function loadNotifications() {
            const list = document.getElementById('notificationList');
            if (!list) return;
            
            fetch('index.php?page=api-notifications&action=get')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.notifications) {
                        if (data.notifications.length === 0) {
                            list.innerHTML = '<div class="notification-empty">Không có thông báo nào</div>';
                        } else {
                            list.innerHTML = data.notifications.map(notif => `
                                <div class="notification-item ${notif.is_read == 0 ? 'unread' : ''}" 
                                     onclick="markNotificationRead(${notif.id})">
                                    <div class="notification-item-title">${notif.title}</div>
                                    <div class="notification-item-message">${notif.message}</div>
                                    <div class="notification-item-time">${notif.time_ago}</div>
                                </div>
                            `).join('');
                        }
                    }
                })
                .catch(error => {
                    list.innerHTML = '<div class="notification-empty">Không thể tải thông báo</div>';
                });
        }
        
        // Toggle notification dropdown
        document.addEventListener('DOMContentLoaded', function() {
            const btn = document.getElementById('notificationBtn');
            const dropdown = document.getElementById('notificationDropdown');
            
            if (btn && dropdown) {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    dropdown.classList.toggle('active');
                    if (dropdown.classList.contains('active')) {
                        loadNotifications();
                    }
                });
                
                // Close when clicking outside
                document.addEventListener('click', function(e) {
                    if (dropdown && btn && !dropdown.contains(e.target) && !btn.contains(e.target)) {
                        dropdown.classList.remove('active');
                    }
                });
            }
        });
        
        function markNotificationRead(id) {
            fetch('index.php?page=api-notifications&action=mark-read&id=' + id)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadNotifications();
                        location.reload(); // Reload to update badge
                    }
                });
        }
        
        function markAllNotificationsRead() {
            fetch('index.php?page=api-notifications&action=mark-all-read')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadNotifications();
                        location.reload();
                    }
                });
        }
    </script>
