<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../functions/book.php';
require_once __DIR__ . '/../../functions/user.php';
require_once __DIR__ . '/../../functions/borrow.php';

if (!isLibrarian()) {
    redirect('index.php');
}

$pageTitle = 'Báo cáo thống kê - Admin';
$currentPage = 'admin';
$page = 'admin-reports';

$bookStats = book_get_total_count();
$userStats = user_get_statistics();
$borrowStats = borrow_get_statistics();

// Lấy sách phổ biến
$popularBooks = book_get_popular(10);

// Lấy sách quá hạn
$overdueBooks = borrow_get_overdue_books();

// Lấy thống kê theo tháng
$conn = get_db_connection();

$monthlyStats = [];
if ($conn) {
    for ($i = 11; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i month"));
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM borrows WHERE DATE_FORMAT(created_at, '%Y-%m') = :month");
        $stmt->bindValue(':month', $month);
        $stmt->execute();
        $monthlyStats[] = [
            'month' => $month,
            'count' => $stmt->fetch()['count']
        ];
    }
}

include __DIR__ . '/../layout/header.php';
?>

<div class="admin-layout">
    <?php include __DIR__ . '/sidebar.php'; ?>
    
    <main class="admin-main">
        <div class="container">
            <div class="page-header">
                <div>
                    <h1 class="page-title">Báo cáo thống kê</h1>
                    <p class="page-subtitle">Thống kê và báo cáo hệ thống thư viện</p>
                </div>
                <div class="page-actions">
                    <button class="btn btn-primary" onclick="printReport()">
                        <i class="fas fa-print"></i> In báo cáo
                    </button>
                    <button class="btn btn-outline" onclick="exportToPDF()">
                        <i class="fas fa-file-pdf"></i> Xuất PDF
                    </button>
                </div>
            </div>
            
            <!-- Summary Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo number_format($bookStats); ?></div>
                        <div class="stat-label">Tổng số sách</div>
                    </div>
                </div>
                
                <div class="stat-card success">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo number_format($userStats['total']); ?></div>
                        <div class="stat-label">Tổng người dùng</div>
                    </div>
                </div>
                
                <div class="stat-card warning">
                    <div class="stat-icon">
                        <i class="fas fa-hand-holding"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo number_format($borrowStats['total']); ?></div>
                        <div class="stat-label">Tổng lượt mượn</div>
                    </div>
                </div>
                
                <div class="stat-card danger">
                    <div class="stat-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo number_format($borrowStats['overdue']); ?></div>
                        <div class="stat-label">Sách quá hạn</div>
                    </div>
                </div>
            </div>
            
            <!-- Charts Section -->
            <div class="charts-section">
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-chart-line"></i> Thống kê mượn sách theo tháng
                        </h2>
                    </div>
                    <div class="card-body">
                        <canvas id="monthlyChart" width="400" height="200"></canvas>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">
                            <i class="fas fa-chart-pie"></i> Phân bố trạng thái mượn sách
                        </h2>
                    </div>
                    <div class="card-body">
                        <canvas id="statusChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Popular Books -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-fire"></i> Sách được mượn nhiều nhất
                    </h2>
                </div>
                <div class="card-body">
                    <div class="table-responsive table-wrapper">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>STT</th>
                                    <th>Tên sách</th>
                                    <th>Tác giả</th>
                                    <th>Danh mục</th>
                                    <th>Số lượt mượn</th>
                                    <th>Trạng thái</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($popularBooks as $index => $book): ?>
                                <tr>
                                    <td class="col-stt"><?php echo $index + 1; ?></td>
                                    <td class="col-book-title">
                                        <strong><?php echo htmlspecialchars($book['title']); ?></strong>
                                    </td>
                                    <td class="col-author"><?php echo htmlspecialchars($book['author']); ?></td>
                                    <td class="col-category"><?php echo htmlspecialchars($book['category_name']); ?></td>
                                    <td class="col-count">
                                        <span class="badge badge-primary">
                                            <?php echo $book['borrow_count']; ?> lượt
                                        </span>
                                    </td>
                                    <td class="col-status">
                                        <?php if ($book['available_quantity'] > 0): ?>
                                            <span class="badge badge-success">Có sẵn</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Hết sách</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Overdue Books -->
            <?php if (!empty($overdueBooks)): ?>
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-exclamation-triangle"></i> Sách quá hạn trả
                    </h2>
                </div>
                <div class="card-body">
                    <div class="table-responsive table-wrapper">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Người mượn</th>
                                    <th>Liên hệ</th>
                                    <th>Sách</th>
                                    <th>Ngày mượn</th>
                                    <th>Hạn trả</th>
                                    <th>Quá hạn</th>
                                    <th>Phí phạt</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($overdueBooks as $borrow): 
                                    $daysOverdue = (time() - strtotime($borrow['due_date'])) / (60 * 60 * 24);
                                    $fineAmount = calculateFine($borrow['due_date']);
                                ?>
                                <tr>
                                    <td class="col-id">#<?php echo $borrow['id']; ?></td>
                                    <td class="col-borrower-name">
                                        <strong><?php echo htmlspecialchars($borrow['full_name']); ?></strong>
                                    </td>
                                    <td class="col-phone"><?php echo htmlspecialchars($borrow['phone']); ?></td>
                                    <td class="col-book-title"><?php echo htmlspecialchars($borrow['title']); ?></td>
                                    <td class="col-date"><?php echo formatDate($borrow['borrow_date']); ?></td>
                                    <td class="col-date"><?php echo formatDate($borrow['due_date']); ?></td>
                                    <td class="col-days-overdue">
                                        <span class="badge badge-danger">
                                            <?php echo floor($daysOverdue); ?> ngày
                                        </span>
                                    </td>
                                    <td class="col-fine">
                                        <span class="fine-amount">
                                            <?php echo number_format($fineAmount); ?> VNĐ
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- User Statistics -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">
                        <i class="fas fa-users"></i> Thống kê người dùng
                    </h2>
                </div>
                <div class="card-body">
                    <div class="user-stats-grid">
                        <div class="user-stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-value"><?php echo $userStats['students']; ?></div>
                                <div class="stat-label">Sinh viên</div>
                            </div>
                        </div>
                        
                        <div class="user-stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-value"><?php echo $userStats['librarians']; ?></div>
                                <div class="stat-label">Thủ thư</div>
                            </div>
                        </div>
                        
                        <div class="user-stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-crown"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-value"><?php echo $userStats['admins']; ?></div>
                                <div class="stat-label">Quản trị viên</div>
                            </div>
                        </div>
                        
                        <div class="user-stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-content">
                                <div class="stat-value"><?php echo $userStats['active']; ?></div>
                                <div class="stat-label">Tài khoản hoạt động</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

<style>
.col-stt { width: 60px; white-space: nowrap; text-align: center; }
.col-book-title { max-width: 280px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.col-author { max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.col-category { max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.col-count { width: 120px; white-space: nowrap; text-align: center; }
.col-status { width: 120px; white-space: nowrap; }
.col-id { width: 80px; white-space: nowrap; }
.col-borrower-name { max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.col-phone { max-width: 120px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.col-date { width: 120px; white-space: nowrap; }
.col-days-overdue { width: 100px; white-space: nowrap; text-align: center; }
.col-fine { width: 120px; white-space: nowrap; text-align: right; }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Monthly Chart
const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
const monthlyChart = new Chart(monthlyCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_column($monthlyStats, 'month')); ?>,
        datasets: [{
            label: 'Số lượt mượn',
            data: <?php echo json_encode(array_column($monthlyStats, 'count')); ?>,
            borderColor: 'rgb(99, 102, 241)',
            backgroundColor: 'rgba(99, 102, 241, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'Thống kê mượn sách theo tháng'
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Status Chart
const statusCtx = document.getElementById('statusChart').getContext('2d');
const statusChart = new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: ['Đang mượn', 'Đã trả', 'Quá hạn'],
        datasets: [{
            data: [
                <?php echo $borrowStats['borrowed']; ?>,
                <?php echo $borrowStats['returned']; ?>,
                <?php echo $borrowStats['overdue']; ?>
            ],
            backgroundColor: [
                'rgb(245, 158, 11)',
                'rgb(16, 185, 129)',
                'rgb(239, 68, 68)'
            ]
        }]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'Phân bố trạng thái mượn sách'
            },
            legend: {
                position: 'bottom'
            }
        }
    }
});

function printReport() {
    window.print();
}

function exportToPDF() {
    // Sử dụng thư viện jsPDF để xuất PDF
    alert('Tính năng xuất PDF sẽ được phát triển trong phiên bản tiếp theo!');
}
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>
