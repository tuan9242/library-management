<h2 align="center">
	<a href="https://dainam.edu.vn/vi/khoa-cong-nghe-thong-tin">
	🎓 Faculty of Information Technology (DaiNam University)
	</a>
</h2>

<h2 align="center">
	Thư viện Số - Digital Library Management System
</h2>

<div align="center">
    <p align="center">
        <img src="docs/logo/aiotlab_logo.png" alt="AIoTLab Logo" width="170"/>
        <img src="docs/logo/fitdnu_logo.png" alt="AIoTLab Logo" width="180"/>
        <img src="docs/logo/dnu_logo.png" alt="DaiNam University Logo" width="200"/>
    </p>

[![AIoTLab](https://img.shields.io/badge/AIoTLab-green?style=for-the-badge)](https://www.facebook.com/DNUAIoTLab)
[![Faculty of Information Technology](https://img.shields.io/badge/Faculty%20of%20Information%20Technology-blue?style=for-the-badge)](https://dainam.edu.vn/vi/khoa-cong-nghe-thong-tin)
[![DaiNam University](https://img.shields.io/badge/DaiNam%20University-orange?style=for-the-badge)](https://dainam.edu.vn)

</div>
## 📖 1. Giới thiệu

Hệ thống Quản lý Thư viện hỗ trợ quản trị sách, người dùng, mượn/trả, thống kê và tìm kiếm trong môi trường đại học. Hệ thống thay thế việc quản lý thủ công bằng một ứng dụng web tập trung, dễ dùng, bảo mật và có khả năng mở rộng.

## 🔧 2. Các công nghệ được sử dụng

<div align="center">

### Hệ điều hành

![macOS](https://img.shields.io/badge/macOS-000000?style=for-the-badge&logo=macos&logoColor=F0F0F0)
[![Windows](https://img.shields.io/badge/Windows-0078D6?style=for-the-badge&logo=windows&logoColor=white)](https://www.microsoft.com/windows)
[![Ubuntu](https://img.shields.io/badge/Ubuntu-E95420?style=for-the-badge&logo=ubuntu&logoColor=white)](https://ubuntu.com)

### Công nghệ chính

[![PHP](https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![HTML5](https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white)](#)
[![CSS](https://img.shields.io/badge/CSS-1572B6?style=for-the-badge&logo=css3&logoColor=white)](#)
[![JavaScript](https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)](#)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-563D7C?style=for-the-badge&logo=bootstrap&logoColor=white)](https://getbootstrap.com/)

### Web Server & Database

[![Apache](https://img.shields.io/badge/Apache-D22128?style=for-the-badge&logo=apache&logoColor=white)](https://httpd.apache.org/)
[![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://www.mysql.com/)
[![XAMPP](https://img.shields.io/badge/XAMPP-FB7A24?style=for-the-badge&logo=xampp&logoColor=white)](https://www.apachefriends.org/)

### Database Management Tools

[![MySQL Workbench](https://img.shields.io/badge/MySQL_Workbench-4479A1?style=for-the-badge&logo=mysql&logoColor=white)](https://dev.mysql.com/downloads/workbench/)

</div>

## 🚀 3. Chức năng chính (Screens & Features)

- Đăng nhập
![Đăng nhập/Đăng ký người dùng](docs/images/1.png)
- Đăng ký người dùng
![Đăng nhập/Đăng ký người dùng](docs/images/2.png)
- Trang chủ, tìm kiếm sách theo từ khóa và danh mục
![Trang chủ, tìm kiếm sách theo từ khóa và danh mục](docs/images/3.png)
- Chi tiết sách
![Chi tiết sách, gợi ý sách liên quan](docs/images/4.png)
- Giỏ mượn
![Giỏ mượn, mượn/trả sách, tính phí trễ hạn](docs/images/5.png)
- Mượn/trả sách
- Quản lý sách
![Quản lý sách, người dùng, mượn trả (Admin/Thủ thư)](docs/images/7.png)
- Quản lý Mượn trả
![Quản lý sách, người dùng, mượn trả (Admin/Thủ thư)](docs/images/8.png)
- Quản lý Người dùng
![Quản lý sách, người dùng, mượn trả (Admin/Thủ thư](docs/images/9.png)
- Dashboard thống kê và báo cáo
![Dashboard thống kê và báo cáo](docs/images/10.png)
- Thông báo, lịch sử mượn trả của người dùng
  
![Thông báo, lịch sử mượn trả của người dùng](docs/images/11.png)


## ⚙️ 4. Cài đặt

### 4.1. Cài đặt công cụ, môi trường

- Cài đặt XAMPP (khuyến nghị PHP 8.x)
  - Trang tải: https://www.apachefriends.org/download.html
- Cài Visual Studio Code và các extension đề xuất:
  - PHP Intelephense
  - MySQL
  - Prettier – Code Formatter

### 4.2. Tải project

Sao chép/giải nén project vào thư mục `htdocs` của XAMPP (ví dụ ổ D):

```bash
D:\xampp\htdocs\library-management
```

Hoặc dùng Git (nếu repo được public):

```bash
cd D:\xampp\htdocs
git clone <REPO_URL> library-management
```

Truy cập qua trình duyệt:

```
http://localhost/library-management/public/
```

### 4.3. Khởi tạo database

Mở XAMPP Control Panel → Start Apache và MySQL.

Tạo database MySQL (có thể dùng MySQL Workbench hoặc phpMyAdmin):

```sql
CREATE DATABASE IF NOT EXISTS library_management
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
```

**Lưu ý**: Các bảng cần thiết (cart, notifications) sẽ được tự động tạo khi bạn truy cập ứng dụng lần đầu. Bạn chỉ cần tạo database `library_management` và ứng dụng sẽ tự động setup phần còn lại.

### 4.4. Cấu hình kết nối DB

Mở file `config/database.php` và cập nhật thông số cho môi trường của bạn:

```php
private $host = "localhost";
private $db_name = "library_management";
private $username = "root";      // thay bằng user của bạn nếu khác
private $password = "";          // thay bằng mật khẩu thật (ví dụ trên XAMPP thường để trống)
```

Lưu ý: File hiện tại có thể chứa giá trị tạm thời. Nhớ đổi `username`/`password` cho khớp máy bạn.

### 4.5. Chạy hệ thống

- Start Apache và MySQL (XAMPP Control Panel)
- Truy cập:

```
http://localhost/library-management/public/
```

Nếu server không trỏ `DocumentRoot` về `public/`, bạn có thể vào trực tiếp:

```
http://localhost/library-management/public/index.php
```

## 🧭 5. Điều hướng & Routes chính

User:
- `GET /?page=home` – Trang chủ
- `GET /?page=search` – Tìm kiếm sách
- `GET /?page=book-detail&id={id}` – Chi tiết sách
- `GET /?page=my-borrows` – Sách đã mượn
- `POST /?page=borrow-book` – Mượn sách (AJAX)

Admin:
- `GET /?page=admin-dashboard` – Dashboard
- `GET /?page=admin-books` – Quản lý sách
- `GET /?page=admin-users` – Quản lý người dùng
- `GET /?page=admin-borrows` – Quản lý mượn sách

Auth:
- `GET /?page=login` – Đăng nhập
- `GET /?page=register` – Đăng ký
- `GET /?action=logout` – Đăng xuất

## 🧩 6. Cấu trúc thư mục

```
library-management/
├── public/                 # Entry point & static (css/, js/, uploads/)
│   ├── index.php
│   ├── css/
│   ├── js/
│   └── uploads/
├── views/                  # Giao diện (auth/, user/, admin/, layout/)
├── functions/              # Business logic functions
├── handlers/               # Request handlers
├── config/                 # Kết nối DB, helpers & auto migration
│   └── database.php
├── assets/                 # Ảnh tĩnh
├── docs/                   # Tài liệu và logos
└── README.md               # Tài liệu này
```

## ✅ 7. Tài khoản demo (tuỳ chọn)

- Admin: `admin / admin123`
- Thủ thư: `librarian / admin123`
- Sinh viên: `student1 / admin123`

## 🛠️ 8. Lưu ý & Khắc phục sự cố

- Bật `mod_rewrite` và (khuyến nghị) trỏ `DocumentRoot` vào thư mục `public/`
- Nếu CSS/JS không tải: đảm bảo đường dẫn dùng dạng tương đối `css/...`, `js/...`
- Kiểm tra quyền ghi thư mục `public/uploads/`
- Kiểm tra lại `config/database.php` nếu không kết nối được DB
- PHP 7.4+ và MySQL 5.7+ (khuyến nghị PHP 8.x)

## 📚 9. Hỗ trợ

- Xem thêm: `USER_GUIDE.md`
- Báo lỗi/đề xuất: tạo issue trong repository
- Liên hệ: bộ phận hỗ trợ/kênh trao đổi của nhóm phát triển
