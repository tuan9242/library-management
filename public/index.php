<?php
require_once __DIR__ . '/../config/database.php';

// Xử lý logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    require_once __DIR__ . '/../handlers/auth.php';
    handle_logout();
}

// Routing
$page = $_GET['page'] ?? 'home';

// Kiểm tra quyền truy cập các trang admin theo role
if (strpos($page, 'admin-') === 0) {
    // Các trang chỉ dành cho admin
    $adminOnlyPages = ['admin-users', 'admin-reports'];
    if (in_array($page, $adminOnlyPages)) {
        if (!isAdmin()) {
            redirect('index.php');
        }
    } else {
        // Các trang admin còn lại yêu cầu tối thiểu thủ thư (librarian)
        if (!isLibrarian()) {
            redirect('index.php');
        }
    }
}

// Kiểm tra đăng nhập cho các trang yêu cầu
$authRequiredPages = ['search', 'my-borrows', 'book-detail', 'profile', 'change-password', 'cart'];
if (in_array($page, $authRequiredPages) && !isLoggedIn()) {
    $_SESSION['alert'] = alert('Vui lòng đăng nhập để tiếp tục!', 'warning');
    redirect('index.php?page=login');
}

// Load trang tương ứng
switch ($page) {
    case 'login':
        include __DIR__ . '/../views/auth/login.php';
        break;
        
    case 'register':
        include __DIR__ . '/../views/auth/register.php';
        break;
        
    case 'search':
        include __DIR__ . '/../views/user/search.php';
        break;
        
    case 'my-borrows':
        include __DIR__ . '/../views/user/my-borrows.php';
        break;
        
    case 'book-detail':
        include __DIR__ . '/../views/user/book-detail.php';
        break;
        
    case 'profile':
        include __DIR__ . '/../views/user/profile.php';
        break;
        
    case 'change-password':
        include __DIR__ . '/../views/user/change-password.php';
        break;
        
    case 'admin-dashboard':
        include __DIR__ . '/../views/admin/dashboard.php';
        break;
        
    case 'admin-books':
        include __DIR__ . '/../views/admin/books.php';
        break;
        
    case 'admin-borrows':
        include __DIR__ . '/../views/admin/borrows.php';
        break;
        
    case 'admin-users':
        include __DIR__ . '/../views/admin/users.php';
        break;
        
    case 'admin-categories':
        include __DIR__ . '/../views/admin/categories.php';
        break;
        
    case 'admin-reports':
        include __DIR__ . '/../views/admin/reports.php';
        break;
        
    case 'cart':
        include __DIR__ . '/../views/user/cart.php';
        break;
        
    case 'api-notifications':
        require_once __DIR__ . '/../handlers/notification.php';
        handle_notifications();
        break;
        
    case 'api-cart':
        require_once __DIR__ . '/../handlers/cart.php';
        handle_cart();
        break;
        
    case 'borrow-book':
        require_once __DIR__ . '/../handlers/borrow.php';
        handle_borrow_book();
        break;
        
    case 'approve-borrow':
        require_once __DIR__ . '/../handlers/borrow.php';
        handle_approve_borrow();
        break;
        
    case 'reject-borrow':
        require_once __DIR__ . '/../handlers/borrow.php';
        handle_reject_borrow();
        break;

    case 'return-borrow':
        require_once __DIR__ . '/../handlers/borrow.php';
        handle_return_borrow();
        break;
        
    case 'upload-cover':
        require_once __DIR__ . '/../handlers/book.php';
        handle_upload_cover();
        break;
        
    case 'delete-user':
        require_once __DIR__ . '/../handlers/user.php';
        handle_delete_user();
        break;
        
    case 'home':
    default:
        include __DIR__ . '/../views/user/home.php';
        break;
}
?>