</main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <div class="footer-logo">
                        <i class="fas fa-book-open"></i>
                        <span>Thư viện Số</span>
                    </div>
                    <p class="footer-desc">
                        Hệ thống quản lý thư viện đại học hiện đại, 
                        giúp sinh viên dễ dàng tìm kiếm và mượn sách trực tuyến.
                    </p>
                    <div class="footer-social">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h4 class="footer-title">Liên kết nhanh</h4>
                    <ul class="footer-links">
                        <li><a href="index.php"><i class="fas fa-home"></i> Trang chủ</a></li>
                        <li><a href="index.php?page=search"><i class="fas fa-search"></i> Tìm kiếm sách</a></li>
                        <li><a href="index.php?page=my-borrows"><i class="fas fa-book-reader"></i> Sách đã mượn</a></li>
                        <li><a href="#"><i class="fas fa-info-circle"></i> Giới thiệu</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4 class="footer-title">Thông tin</h4>
                    <ul class="footer-links">
                        <li><a href="#"><i class="fas fa-book"></i> Quy định mượn trả</a></li>
                        <li><a href="#"><i class="fas fa-clock"></i> Giờ mở cửa</a></li>
                        <li><a href="#"><i class="fas fa-question-circle"></i> Câu hỏi thường gặp</a></li>
                        <li><a href="#"><i class="fas fa-envelope"></i> Liên hệ</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4 class="footer-title">Liên hệ</h4>
                    <ul class="footer-contact">
                        <li>
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Đại học ABC, Hà Nội, Việt Nam</span>
                        </li>
                        <li>
                            <i class="fas fa-phone"></i>
                            <span>(024) 1234 5678</span>
                        </li>
                        <li>
                            <i class="fas fa-envelope"></i>
                            <span>library@university.edu.vn</span>
                        </li>
                        <li>
                            <i class="fas fa-clock"></i>
                            <span>T2-T6: 7:00 - 21:00<br>T7-CN: 8:00 - 17:00</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2025 Thư viện Số. All rights reserved.</p>
                <p>Được phát triển với <i class="fas fa-heart"></i> bởi Team Developer</p>
            </div>
        </div>
    </footer>

    <script src="js/script.js"></script>
</body>
</html>

<style>
.footer {
    position: relative;
    z-index: 1;
}

.footer-logo {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 1.6rem;
    font-weight: 800;
    margin-bottom: 1.5rem;
    animation: fadeInUp 0.6s ease;
}

.footer-logo i {
    font-size: 2.2rem;
    color: var(--accent-blue-light);
    filter: drop-shadow(0 0 10px rgba(165, 180, 252, 0.5));
}

.footer-desc {
    color: rgba(255, 255, 255, 0.85);
    margin-bottom: 1.5rem;
    line-height: 1.7;
    font-size: 0.95rem;
}

.footer-social {
    display: flex;
    gap: 1rem;
}

.footer-social a {
    width: 45px;
    height: 45px;
    background: rgba(255, 255, 255, 0.15);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    text-decoration: none;
    transition: all 0.3s ease;
    border: 2px solid rgba(255, 255, 255, 0.2);
}

.footer-social a:hover {
    background: white;
    color: var(--primary-blue);
    transform: translateY(-5px) rotate(5deg);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
}

.footer-title {
    font-size: 1.15rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    color: white;
}

.footer-links {
    list-style: none;
}

.footer-links li {
    margin-bottom: 0.75rem;
}

.footer-links a {
    color: rgba(255, 255, 255, 0.85);
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.6rem;
    transition: all 0.3s ease;
}

.footer-links a:hover {
    color: white;
    padding-left: 0.75rem;
}

.footer-links a i {
    font-size: 0.9rem;
    width: 20px;
}

.footer-contact {
    list-style: none;
}

.footer-contact li {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.2rem;
    color: rgba(255, 255, 255, 0.85);
    line-height: 1.5;
}

.footer-contact i {
    color: var(--accent-blue-light);
    width: 20px;
    margin-top: 0.25rem;
    font-size: 1.1rem;
}

.footer-bottom {
    position: relative;
    z-index: 1;
}

.footer-bottom p {
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.footer-bottom .fa-heart {
    color: #ff6b6b;
    animation: heartbeat 1.5s ease-in-out infinite;
}

@keyframes heartbeat {
    0%, 100% { transform: scale(1); }
    10%, 30% { transform: scale(1.2); }
    20%, 40% { transform: scale(1.1); }
}

@media (max-width: 768px) {
    .footer-content {
        grid-template-columns: 1fr;
        gap: 2.5rem;
    }
    
    .footer {
        padding: 2.5rem 0 1rem;
    }
}
</style>
