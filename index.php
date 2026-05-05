<?php
require 'db_connection.php';

// Lấy danh sách sản phẩm từ database
$sql = "SELECT p.*, c.category_name,
        (SELECT MIN(pv.price) FROM product_variants pv WHERE pv.product_id = p.product_id) as min_price
        FROM products p
        JOIN categories c ON p.category_id = c.category_id
        WHERE p.status = 'Active'
        ORDER BY p.created_at DESC";
$products = $conn->query($sql);
$product_list = [];
while ($row = $products->fetch_assoc()) {
    $product_list[] = $row;
}

// Lấy danh sách categories
$cat_sql = "SELECT * FROM categories ORDER BY category_name";
$categories = $conn->query($cat_sql);
$cat_list = [];
while ($row = $categories->fetch_assoc()) {
    $cat_list[] = $row;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>VISIO — Cửa Hàng Mắt Kính</title>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300;1,400&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css" />
</head>
<body>

<nav>
  <a href="#" class="nav-logo">VISIO<span>.</span></a>
  <ul class="nav-links">
    <li><a href="#products">Sản Phẩm</a></li>
    <li><a href="#about">Về Chúng Tôi</a></li>
    <li><a href="#reviews">Đánh Giá</a></li>
    <li><a href="#contact">Liên Hệ</a></li>
    <li><a href="TryonVR/index.html">Thử Kính Ảo</a></li>
  </ul>
  <a href="#contact" class="nav-cta">Đặt Lịch Hẹn</a>
</nav>

<section class="hero">
  <div class="hero-text">
    <p class="hero-eyebrow">Bộ Sưu Tập 2025</p>
    <h1 class="hero-title">Nhìn Thế Giới<br>Qua Một <em>Tầm Nhìn</em><br>Khác Biệt</h1>
    <p class="hero-desc">Chúng tôi mang đến những khung kính tinh tế, phù hợp với từng cá tính — từ cổ điển thanh lịch đến hiện đại táo bạo.</p>
    <div class="hero-buttons">
      <a href="#products" class="btn-primary">Khám Phá Ngay</a>
      <a href="TryonVR/index.html" class="btn-outline">Thử Kính Ảo 🕶️</a>
    </div>
  </div>
  <div class="hero-image">
    <div class="hero-frame">
      <img src="img/store_outside.jpg" alt="Kính nổi bật" />
    </div>
    <div class="hero-badge">
      <span class="hero-badge-num"><?php echo count($product_list); ?>+</span>
      <span class="hero-badge-text">Mẫu Kính</span>
    </div>
  </div>
</section>

<section class="features">
  <div class="section-header">
    <p class="section-eyebrow" style="color:var(--gold);">Tại Sao Chọn Chúng Tôi</p>
    <h2 class="section-title" style="color:var(--cream);">Cam Kết <em>Chất Lượng</em></h2>
  </div>
  <div class="features-grid">
    <div class="feature-item">
      <div class="feature-icon">🔬</div>
      <h3 class="feature-title">Kiểm Tra Thị Lực Miễn Phí</h3>
      <p class="feature-desc">Đội ngũ chuyên gia với hơn 10 năm kinh nghiệm kiểm tra thị lực chính xác cho mọi lứa tuổi.</p>
    </div>
    <div class="feature-item">
      <div class="feature-icon">💎</div>
      <h3 class="feature-title">Tròng Kính Cao Cấp</h3>
      <p class="feature-desc">Tròng kính chống tia UV, chống ánh sáng xanh từ các thương hiệu hàng đầu thế giới.</p>
    </div>
    <div class="feature-item">
      <div class="feature-icon">🛡️</div>
      <h3 class="feature-title">Bảo Hành 2 Năm</h3>
      <p class="feature-desc">Bảo hành toàn diện cho khung và tròng kính, đảm bảo sự hài lòng tuyệt đối của khách hàng.</p>
    </div>
  </div>
</section>

<section class="products" id="products">
  <div class="section-header">
    <p class="section-eyebrow">Bộ Sưu Tập</p>
    <h2 class="section-title">Sản Phẩm <em>Nổi Bật</em></h2>
  </div>

  <div class="products-filter">
    <button class="filter-btn active" data-cat="all">Tất Cả</button>
    <?php foreach ($cat_list as $cat): ?>
      <button class="filter-btn" data-cat="<?php echo $cat['category_id']; ?>">
        <?php echo htmlspecialchars($cat['category_name']); ?>
      </button>
    <?php endforeach; ?>
  </div>

  <div class="products-grid">
    <?php if (empty($product_list)): ?>
      <p style="grid-column:1/-1; text-align:center; color:#888; padding:3rem;">Chưa có sản phẩm nào.</p>
    <?php else: ?>
      <?php foreach ($product_list as $p): ?>
        <div class="product-card" data-cat="<?php echo $p['category_id']; ?>">
          <div class="product-img">
            <img src="<?php echo $p['image_url'] ? htmlspecialchars($p['image_url']) : 'img/product-1.jpg'; ?>"
                 alt="<?php echo htmlspecialchars($p['product_name']); ?>"
                 onerror="this.src='img/product-1.jpg'"/>
            <span class="product-badge"><?php echo htmlspecialchars($p['category_name']); ?></span>
          </div>
          <div class="product-info">
            <h3 class="product-name"><?php echo htmlspecialchars($p['product_name']); ?></h3>
            <p class="product-type"><?php echo htmlspecialchars(substr($p['description'] ?? '', 0, 60)); ?></p>
            <div class="product-footer">
              <span class="product-price">
                <?php
                  $price = $p['min_price'] ?? $p['base_price'];
                  echo number_format($price, 0, ',', '.') . ' ₫';
                ?>
              </span>
              <button class="product-add"
                      data-id="<?php echo $p['product_id']; ?>"
                      data-name="<?php echo htmlspecialchars($p['product_name']); ?>">+</button>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>

<section id="about" class="about-outer">
  <div class="about-strip">
    <div class="about-img-wrap">
      <div class="about-img-main">
        <img src="img/store_customer_visited.jpg" alt="Cửa hàng VISIO" />
      </div>
      <div class="about-img-accent">
        <img src="img/store_customer_visited_2.jpg" alt="Chi tiết kính" />
      </div>
    </div>
    <div class="about-text">
      <p class="section-eyebrow" style="justify-content:flex-start;">Câu Chuyện Của Chúng Tôi</p>
      <h2 class="section-title" style="text-align:left; margin-bottom:1.2rem;">
        Hơn 10 Năm <em>Chắp Cánh</em><br>Cho Tầm Nhìn
      </h2>
      <p style="font-size:.95rem; line-height:1.9; color:var(--brown-light); margin-bottom:1rem;">
        VISIO được thành lập năm 2013 với sứ mệnh mang đến những chiếc kính không chỉ giúp bạn nhìn rõ hơn, mà còn thể hiện phong cách và cá tính riêng của mỗi người.
      </p>
      <p style="font-size:.95rem; line-height:1.9; color:var(--brown-light); margin-bottom:1.8rem;">
        Chúng tôi hợp tác với các thương hiệu uy tín từ Ý, Nhật Bản và Đan Mạch, đảm bảo mỗi sản phẩm đều đạt chuẩn quốc tế về chất lượng và thiết kế.
      </p>
      <div class="about-stats">
        <div><span class="stat-num">10+</span><span class="stat-label">Năm Kinh Nghiệm</span></div>
        <div><span class="stat-num">15k+</span><span class="stat-label">Khách Hàng</span></div>
        <div><span class="stat-num"><?php echo count($product_list); ?>+</span><span class="stat-label">Mẫu Kính</span></div>
      </div>
      <a href="#contact" class="btn-primary">Ghé Thăm Cửa Hàng</a>
    </div>
  </div>
</section>

<section class="reviews" id="reviews">
  <div class="section-header">
    <p class="section-eyebrow">Khách Hàng Nói Gì</p>
    <h2 class="section-title">Đánh Giá <em>Thực Tế</em></h2>
  </div>
  <div class="reviews-grid">
    <div class="review-card">
      <div class="review-stars">★★★★★</div>
      <p class="review-text">"Tôi đã mua kính tại VISIO được 3 năm và không bao giờ thất vọng. Nhân viên tư vấn rất nhiệt tình!"</p>
      <div class="review-author">
        <div class="review-avatar"><img src="img/customer_review-1.jpg" alt="Nguyễn Thị Lan" /></div>
        <div>
          <div class="review-name">Nguyễn Thị Lan</div>
          <div class="review-date">Tháng 3, 2025 · TP. Hồ Chí Minh</div>
        </div>
      </div>
    </div>
    <div class="review-card">
      <div class="review-stars">★★★★★</div>
      <p class="review-text">"Chất lượng tròng kính chống ánh sáng xanh rất tốt, mắt không còn mỏi sau nhiều giờ làm việc!"</p>
      <div class="review-author">
        <div class="review-avatar"><img src="img/customer-review-2.jpg" alt="Trần Minh Khoa" /></div>
        <div>
          <div class="review-name">Trần Minh Khoa</div>
          <div class="review-date">Tháng 2, 2025 · Hà Nội</div>
        </div>
      </div>
    </div>
    <div class="review-card">
      <div class="review-stars">★★★★☆</div>
      <p class="review-text">"Đặt kính online và nhận hàng rất nhanh, chỉ 2 ngày. Kính đóng gói cẩn thận, form dáng đẹp!"</p>
      <div class="review-author">
        <div class="review-avatar"><img src="img/customer-review-3.jpg" alt="Lê Thu Hương" /></div>
        <div>
          <div class="review-name">Lê Thu Hương</div>
          <div class="review-date">Tháng 1, 2025 · Đà Nẵng</div>
        </div>
      </div>
    </div>
    <div class="review-card">
      <div class="review-stars">★★★★★</div>
      <p class="review-text">"Mua kính cho cả gia đình tại VISIO. Giá cả hợp lý, bảo hành uy tín. Rất hài lòng!"</p>
      <div class="review-author">
        <div class="review-avatar"><img src="img/customer-review-4.jpg" alt="Phạm Đức Anh" /></div>
        <div>
          <div class="review-name">Phạm Đức Anh</div>
          <div class="review-date">Tháng 12, 2024 · TP. Hồ Chí Minh</div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="map-section" id="contact">
  <div class="section-header">
    <p class="section-eyebrow">Tìm Chúng Tôi</p>
    <h2 class="section-title">Ghé Thăm <em>Cửa Hàng</em></h2>
  </div>
  <div class="map-wrap">
    <div class="map-info">
      <h3>VISIO Opticians</h3>
      <div class="map-detail">
        <span class="map-detail-icon">📍</span>
        <div class="map-detail-text">
          <span class="map-detail-label">Địa Chỉ</span><br>
          123 Nguyễn Huệ, Quận 1,<br>TP. Hồ Chí Minh
        </div>
      </div>
      <div class="map-detail">
        <span class="map-detail-icon">🕐</span>
        <div class="map-detail-text">
          <span class="map-detail-label">Giờ Mở Cửa</span><br>
          Thứ 2 – Thứ 6: 8:00 – 20:00<br>
          Thứ 7 – CN: 9:00 – 18:00
        </div>
      </div>
      <div class="map-detail">
        <span class="map-detail-icon">📞</span>
        <div class="map-detail-text">
          <span class="map-detail-label">Điện Thoại</span><br>
          <a href="tel:+84901234567" style="color:var(--gold-dark); text-decoration:none;">0901 234 567</a>
        </div>
      </div>
      <div class="map-detail">
        <span class="map-detail-icon">✉️</span>
        <div class="map-detail-text">
          <span class="map-detail-label">Email</span><br>
          <a href="mailto:hello@visio.vn" style="color:var(--gold-dark); text-decoration:none;">hello@visio.vn</a>
        </div>
      </div>
      <div style="margin-top:1.6rem;">
        <a href="tel:+84901234567" class="btn-primary">Gọi Ngay</a>
      </div>
    </div>
    <div class="map-embed">
      <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3919.4469418!2d106.7004!3d10.7769!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31752f4b3330bcc7%3A0x4db964d76bf6e18e!2sNguy%E1%BB%85n%20Hu%E1%BB%87%2C%20B%E1%BA%BFn%20Ngh%C3%A9%2C%20Qu%E1%BA%ADn%201%2C%20Th%C3%A0nh%20ph%E1%BB%91%20H%E1%BB%93%20Ch%C3%AD%20Minh%2C%20Vi%E1%BB%87t%20Nam!5e0!3m2!1svi!2s!4v1699000000000!5m2!1svi!2s"
        allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Bản đồ VISIO">
      </iframe>
    </div>
  </div>
</section>

<footer>
  <div class="footer-grid">
    <div>
      <div class="footer-brand-logo">VISIO<span>.</span></div>
      <p class="footer-brand-desc">Chúng tôi mang đến tầm nhìn sáng rõ và phong cách vượt trội cho mỗi khách hàng từ năm 2013.</p>
    </div>
    <div class="footer-col">
      <h4>Dịch Vụ</h4>
      <ul>
        <li><a href="#">Kiểm Tra Thị Lực</a></li>
        <li><a href="#">Thay Tròng Kính</a></li>
        <li><a href="#">Sửa Chữa Gọng</a></li>
        <li><a href="#">Bảo Hành</a></li>
      </ul>
    </div>
    <div class="footer-col">
      <h4>Thông Tin</h4>
      <ul>
        <li><a href="#about">Về Chúng Tôi</a></li>
        <li><a href="#contact">Liên Hệ</a></li>
        <li><a href="admin/">Quản Trị</a></li>
      </ul>
    </div>
    <div class="footer-col">
      <h4>Danh Mục</h4>
      <ul>
        <?php foreach ($cat_list as $cat): ?>
          <li><a href="#products"><?php echo htmlspecialchars($cat['category_name']); ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div class="footer-col">
      <h4>Theo Dõi Chúng Tôi</h4>
      <div class="social-links">
        <a href="https://facebook.com" target="_blank"><i class="fab fa-facebook-f"></i></a>
        <a href="https://instagram.com" target="_blank"><i class="fab fa-instagram"></i></a>
        <a href="https://www.tiktok.com" target="_blank"><i class="fab fa-tiktok"></i></a>
      </div>
    </div>
  </div>
  <div class="footer-bottom">© 2026 VISIO Opticians. Tất cả quyền được bảo lưu.</div>
</footer>

<script src="js/main.js"></script>
</body>
</html>