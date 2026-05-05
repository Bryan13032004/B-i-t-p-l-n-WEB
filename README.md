# VISIO — Hệ Thống Bán Kính Mắt Trực Tuyến 🕶️

Web bán kính mắt trực tuyến hỗ trợ mua kính có sẵn, đặt trước hoặc làm kính theo đơn. Tích hợp AI thử kính ảo và hệ thống quản trị toàn diện.

---

## 👥 Thành viên nhóm

| STT | Tên | Branch | Vai trò | Phần phụ trách |
|---|---|---|---|---|
| 1 | Bryan | main | Trưởng nhóm / Admin | ...|
| 2 | Nguyễn Gia Huy | Nguyễn-Gia-Huy | Front-end & Customer | Thiết kế giao diện trang chủ website |
| 3 | Nguyễn Lâm Tiến | Nguyễn-Lâm-Tiến | Back-end & Sales |admin phân quyền quản lý sản phẩm và giao diện đăng nhập người dùng |
| 4 | Phạm Ngọc Diễm My | Phạm-Ngọc-Diễm-My | Database & Operations | ...|
| 5 | Huỳnh Sĩ Nguyên | Huỳnh-Sĩ-Nguyên | AI/AR  | Virtual Try-On, nhận diện khuôn mặt, gợi ý kính |

---

## 🗂️ Cấu trúc dự án

```
B-i-t-p-l-n-WEB/
├── index.php               # Trang chủ (lấy sản phẩm từ DB)
├── index.html              # Trang chủ tĩnh (backup)
├── product.php             # Trang chi tiết sản phẩm
├── customer_auth.php       # Đăng ký / Đăng nhập khách hàng
├── cart.php                # Giỏ hàng + Đặt hàng
├── logout.php              # Đăng xuất khách hàng
├── db_connection.php       # Kết nối database dùng chung
├── setup.php               # Tự động tạo database (chạy 1 lần)
├── eyewear_db.sql          # File SQL backup
├── css/
│   ├── style.css           # CSS trang chủ & khách hàng
│   └── admin_style.css     # CSS trang admin
├── js/
│   └── main.js             # JS trang chủ
├── img/                    # Hình ảnh sản phẩm, khách hàng
├── TryonVR/                # Tính năng thử kính ảo (AI/AR)
│   ├── index.html
│   ├── app.js
│   ├── style.css
│   └── assets/
├── eyewear/                # Quản lý đơn hàng & toa kính
│   ├── manage_order.php
│   ├── check_presciption.php
│   ├── functions.php
│   └── database/schema.sql
├── admin/                  # Trang quản trị
│   ├── index.php           # Đăng nhập admin
│   ├── dashboard.php
│   ├── products_management.php
│   ├── orders_management.php
│   ├── categories_management.php
│   ├── users_management.php
│   ├── roles_management.php
│   ├── shipments_management.php
│   ├── support_tickets_management.php
│   ├── promotions_management.php
│   └── policies_and_configs.php
└── admin_functions/        # Hàm xử lý nghiệp vụ
```

---

## ⚙️ Công nghệ sử dụng

| Thành phần | Công nghệ |
|---|---|
| Frontend | HTML5, CSS3, JavaScript |
| Backend | PHP 8.2 |
| Database | MySQL (MariaDB) |
| AI/AR | MediaPipe Face Landmarker (Google) |
| Server | XAMPP (Apache + MySQL) |
| Version Control | Git + GitHub |

---

## 🚀 Cài đặt và chạy

### Yêu cầu
- XAMPP (Apache + MySQL)
- Git

### Các bước

**1. Clone dự án:**
```bash
cd C:\xampp\htdocs
git clone https://github.com/Bryan13032004/B-i-t-p-l-n-WEB.git
cd B-i-t-p-l-n-WEB
```

**2. Bật XAMPP:**
- Mở XAMPP Control Panel
- Bấm **Start** cạnh **Apache** và **MySQL**

**3. Tạo database tự động — chạy 1 lần:**
```
http://localhost/B-i-t-p-l-n-WEB/setup.php
```

**4. Truy cập web:**
```
http://localhost/B-i-t-p-l-n-WEB/
```

---

## 🔑 Tài khoản mặc định

### Tài khoản Admin
| Tài khoản | Mật khẩu | Vai trò |
|---|---|---|
| admin | password | Quản trị viên |
| sales01 | password | Nhân viên bán hàng |
| ops01 | password | Nhân viên vận hành |

### Tài khoản khách hàng
Tự đăng ký tại:
```
http://localhost/B-i-t-p-l-n-WEB/customer_auth.php?tab=register
```

---

## 📱 Tính năng

###  Đã hoàn thành

#### Trang chủ
- Hiển thị sản phẩm từ database (tự động cập nhật khi admin thêm/sửa)
- Bộ lọc sản phẩm theo danh mục
- Giới thiệu cửa hàng, đánh giá khách hàng, bản đồ Google Maps

#### Thử Kính Ảo — TryonVR
- Nhận diện khuôn mặt bằng AI (MediaPipe Face Landmarker)
- Hiển thị kính lên mặt theo thời gian thực qua camera
- Phân tích hình dạng khuôn mặt (Oval, Tròn, Vuông, Dài, Trái tim)
- Gợi ý loại kính phù hợp với khuôn mặt
- Chụp ảnh lưu kết quả

#### Khách hàng
- Đăng ký / Đăng nhập tài khoản
- Xem chi tiết sản phẩm và biến thể (màu, size, giá, tồn kho)
- Giỏ hàng — thêm, cập nhật số lượng, xóa sản phẩm
- Đặt hàng trực tuyến (Ready Stock)

#### Quản trị Admin
- Đăng nhập bảo mật với phân quyền theo vai trò
- Dashboard thống kê đơn hàng, khách hàng, nhân viên
- Quản lý sản phẩm và biến thể (màu, size, SKU, tồn kho)
- Quản lý đơn hàng và cập nhật trạng thái
- Quản lý vận chuyển và mã tracking
- Quản lý ticket hỗ trợ khách hàng
- Quản lý mã khuyến mại
- Quản lý nhân viên và phân quyền
- Quản lý chính sách bảo hành, đổi trả

#### Hệ thống
- 16 bảng database đầy đủ
- `setup.php` tự động khởi tạo database chỉ cần 1 click
- Kiểm tra toa kính (Prescription) cho đơn hàng eyewear

---

###  Chưa hoàn thành

| Tính năng | Ghi chú |
|---|---|
| Đặt hàng Pre-order | Chưa có phía khách hàng |
| Đặt hàng theo toa kính từ phía khách | Chưa có |
| Lịch sử đơn hàng của khách | Chưa có |
| Thanh toán online (VNPay, Momo) | Chưa có |
| Tìm kiếm sản phẩm | Chưa có |
| Đánh giá sản phẩm | Chưa có |
| Báo cáo doanh thu (Manager) | Chưa có |

---

## 🗃️ Database — 16 bảng

| Bảng | Mô tả |
|---|---|
| `users` | Tài khoản nhân viên |
| `roles` | Vai trò hệ thống |
| `user_roles` | Gán vai trò cho nhân viên |
| `categories` | Phân loại sản phẩm |
| `products` | Sản phẩm |
| `product_variants` | Biến thể (màu, size, SKU) |
| `customers` | Tài khoản khách hàng |
| `prescriptions` | Toa kính |
| `orders` | Đơn hàng |
| `order_items` | Chi tiết đơn hàng |
| `payments` | Thanh toán |
| `shipments` | Vận chuyển |
| `promotions` | Mã khuyến mại |
| `support_tickets` | Ticket hỗ trợ |
| `policies_and_configs` | Chính sách & cấu hình |
| `cart` | Giỏ hàng khách hàng |

---

## 🔗 Các đường dẫn chính

| Trang | URL |
|---|---|
| Trang chủ | `http://localhost/B-i-t-p-l-n-WEB/` |
| Thử kính ảo | `http://localhost/B-i-t-p-l-n-WEB/TryonVR/` |
| Đăng nhập khách | `http://localhost/B-i-t-p-l-n-WEB/customer_auth.php` |
| Giỏ hàng | `http://localhost/B-i-t-p-l-n-WEB/cart.php` |
| Admin | `http://localhost/B-i-t-p-l-n-WEB/admin/` |
| Quản lý đơn hàng | `http://localhost/B-i-t-p-l-n-WEB/eyewear/manage_order.php` |
| Setup database | `http://localhost/B-i-t-p-l-n-WEB/setup.php` |
