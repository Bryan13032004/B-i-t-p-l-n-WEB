# VISIO — Cửa Hàng Mắt Kính 🕶️

Dự án web bán kính mắt được xây dựng bằng HTML, CSS, JavaScript và PHP + MySQL. Bao gồm trang chủ giới thiệu sản phẩm, tính năng thử kính ảo bằng AI và hệ thống quản trị admin.

---

## 👥 Thành viên nhóm

| Tên | Branch | Phần phụ trách |
|---|---|---|
| Bryan (Trưởng nhóm) | main | ...|
| Huỳnh Sĩ Nguyên | Huỳnh-Sĩ-Nguyên | Thử kính ảo (TryonVR)|
| Nguyễn Gia Huy | Nguyễn-Gia-Huy | ... |
| Nguyễn Lâm Tiến | Nguyễn-Lâm-Tiến | ... |
| Phạm Ngọc Diễm My | Phạm-Ngọc-Diễm-My | ... |

---

## 🗂️ Cấu trúc dự án

```
B-i-t-p-l-n-WEB/
├── index.html              # Trang chủ VISIO
├── db_connection.php       # Kết nối database dùng chung
├── css/
│   ├── style.css           # CSS trang chủ
│   └── admin_style.css     # CSS trang admin
├── js/
│   └── main.js             # JS trang chủ
├── img/                    # Hình ảnh sản phẩm, khách hàng
├── TryonVR/                # Tính năng thử kính ảo
│   ├── index.html
│   ├── app.js              # Logic AI nhận diện khuôn mặt
│   ├── style.css
│   └── assets/             # Ảnh kính (kinh1.png, kinh2.png...)
├── eyewear/                # Quản lý đơn hàng & toa kính
│   ├── index.php
│   ├── db_connection.php
│   ├── manage_order.php
│   ├── check_presciption.php
│   ├── functions.php
│   └── database/
│       └── schema.sql
├── admin/                  # Trang quản trị admin
│   ├── index.php           # Trang đăng nhập
│   ├── dashboard.php       # Bảng điều khiển
│   ├── products_management.php
│   ├── orders_management.php
│   ├── categories_management.php
│   ├── users_management.php
│   ├── roles_management.php
│   ├── shipments_management.php
│   ├── support_tickets_management.php
│   ├── promotions_management.php
│   └── policies_and_configs.php
└── admin_functions/        # Các hàm xử lý nghiệp vụ
    ├── products_functions.php
    ├── orders_functions.php
    ├── categories_functions.php
    ├── users_functions.php
    ├── roles_functions.php
    ├── payments_shipments_functions.php
    ├── promotions_functions.php
    ├── support_tickets_functions.php
    └── policies_functions.php
```

---

## ⚙️ Công nghệ sử dụng

- **Frontend:** HTML5, CSS3, JavaScript
- **Backend:** PHP 8.2
- **Database:** MySQL (MariaDB)
- **AI/AR:** MediaPipe Face Landmarker (Google)
- **Server:** XAMPP (Apache + MySQL)
- **Version Control:** Git + GitHub

---

## 🚀 Cài đặt và chạy

### Yêu cầu
- XAMPP (Apache + MySQL)
- Git

### Các bước

**1. Clone dự án về máy:**
```bash
cd C:\xampp\htdocs
git clone https://github.com/Bryan13032004/B-i-t-p-l-n-WEB.git
cd B-i-t-p-l-n-WEB
```

**2. Bật XAMPP:**
- Mở XAMPP Control Panel
- Bấm **Start** cạnh **Apache** và **MySQL**

**3. Tạo database:**
- Vào `http://localhost/phpmyadmin`
- Tạo database tên `eyewear_db`
- Import file `eyewear_db.sql`

**4. Chạy web:**
```
http://localhost/B-i-t-p-l-n-WEB/
```

---

## 🔑 Tài khoản mặc định

| Tài khoản | Mật khẩu | Vai trò |
|---|---|---|
| admin | password | Quản trị viên |
| sales01 | password | Nhân viên bán hàng |
| ops01 | password | Nhân viên vận hành |

---

## 📱 Tính năng chính

### Trang chủ
- Giới thiệu cửa hàng VISIO
- Danh sách sản phẩm với bộ lọc theo loại
- Đánh giá khách hàng
- Bản đồ và thông tin liên hệ

### Thử Kính Ảo (TryonVR)
- Nhận diện khuôn mặt bằng AI (MediaPipe)
- Hiển thị kính lên mặt theo thời gian thực qua camera
- Phân tích hình dạng khuôn mặt và gợi ý kính phù hợp
- Chụp ảnh lưu kết quả

### Quản trị Admin
- Đăng nhập bảo mật
- Quản lý sản phẩm và biến thể (màu, size, SKU)
- Quản lý đơn hàng và cập nhật trạng thái
- Quản lý vận chuyển
- Quản lý ticket hỗ trợ khách hàng
- Quản lý khuyến mại
- Quản lý nhân viên và phân quyền

---

## 🗃️ Database

Gồm 15 bảng chính:

| Bảng | Mô tả |
|---|---|
| users | Tài khoản nhân viên |
| roles / user_roles | Vai trò và phân quyền |
| categories | Phân loại sản phẩm |
| products | Sản phẩm |
| product_variants | Biến thể sản phẩm (màu, size) |
| customers | Khách hàng |
| prescriptions | Toa kính |
| orders | Đơn hàng |
| order_items | Chi tiết đơn hàng |
| payments | Thanh toán |
| shipments | Vận chuyển |
| promotions | Mã khuyến mại |
| support_tickets | Ticket hỗ trợ |
| policies_and_configs | Chính sách & cấu hình |

---

## 🔗 Truy cập

| Trang | URL |
|---|---|
| Trang chủ | `http://localhost/B-i-t-p-l-n-WEB/` |
| Thử kính ảo | `http://localhost/B-i-t-p-l-n-WEB/TryonVR/` |
| Admin | `http://localhost/B-i-t-p-l-n-WEB/admin/` |
| Quản lý đơn hàng | `http://localhost/B-i-t-p-l-n-WEB/eyewear/manage_order.php` |