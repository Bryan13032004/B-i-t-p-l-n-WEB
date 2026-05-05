<?php
// ============================================================
// VISIO EYEWEAR - AUTO SETUP
// Chạy 1 lần: http://localhost/B-i-t-p-l-n-WEB/setup.php
// ============================================================

$host     = "localhost";
$username = "root";
$password = "";
$database = "eyewear_db";

$errors   = [];
$success  = [];

// Kết nối MySQL (không chọn database)
$conn = new mysqli($host, $username, $password);
if ($conn->connect_error) {
    die("<h2 style='color:red'> Không kết nối được MySQL: " . $conn->connect_error . "</h2><p>Hãy đảm bảo XAMPP đã bật MySQL!</p>");
}
$conn->set_charset("utf8mb4");

// Tạo database
$conn->query("CREATE DATABASE IF NOT EXISTS `eyewear_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$conn->select_db($database);
$success[] = " Database <strong>eyewear_db</strong> đã sẵn sàng";

// Danh sách SQL tạo bảng
$tables = [
"roles" => "CREATE TABLE IF NOT EXISTS roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    role_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)",
"users" => "CREATE TABLE IF NOT EXISTS users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    role VARCHAR(20) DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)",
"user_roles" => "CREATE TABLE IF NOT EXISTS user_roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    role_id INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(role_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_role (user_id, role_id)
)",
"categories" => "CREATE TABLE IF NOT EXISTS categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)",
"products" => "CREATE TABLE IF NOT EXISTS products (
    product_id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT NOT NULL,
    product_name VARCHAR(150) NOT NULL,
    description TEXT,
    base_price DECIMAL(12,2) NOT NULL,
    image_url VARCHAR(255),
    status ENUM('Active','Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id)
)",
"product_variants" => "CREATE TABLE IF NOT EXISTS product_variants (
    variant_id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    sku VARCHAR(100) NOT NULL UNIQUE,
    color VARCHAR(50),
    size VARCHAR(50),
    quantity_in_stock INT DEFAULT 0,
    price DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
)",
"customers" => "CREATE TABLE IF NOT EXISTS customers (
    customer_id INT PRIMARY KEY AUTO_INCREMENT,
    customer_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    password VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)",
"prescriptions" => "CREATE TABLE IF NOT EXISTS prescriptions (
    prescription_id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    right_sph DECIMAL(4,2),
    left_sph DECIMAL(4,2),
    pd DECIMAL(4,1),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id)
)",
"promotions" => "CREATE TABLE IF NOT EXISTS promotions (
    promo_id INT PRIMARY KEY AUTO_INCREMENT,
    promo_code VARCHAR(20) NOT NULL UNIQUE,
    discount_type ENUM('Percentage','Fixed') DEFAULT 'Percentage',
    discount_value DECIMAL(10,2) NOT NULL,
    min_order_value DECIMAL(12,2) DEFAULT 0,
    valid_from DATE,
    valid_to DATE,
    max_uses INT DEFAULT 999,
    current_uses INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)",
"orders" => "CREATE TABLE IF NOT EXISTS orders (
    order_id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT,
    sales_staff_id INT,
    promo_id INT,
    customer_name VARCHAR(100),
    customer_email VARCHAR(100),
    customer_phone VARCHAR(20),
    shipping_address TEXT,
    order_type ENUM('Ready_Stock','Pre_Order','Prescription') DEFAULT 'Ready_Stock',
    status ENUM('Pending','Confirmed','Processing','Ready_to_Ship','Shipped','Delivered','Cancelled') DEFAULT 'Pending',
    total_price DECIMAL(12,2) DEFAULT 0,
    notes TEXT,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE SET NULL,
    FOREIGN KEY (sales_staff_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (promo_id) REFERENCES promotions(promo_id) ON DELETE SET NULL
)",
"order_items" => "CREATE TABLE IF NOT EXISTS order_items (
    item_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    variant_id INT,
    prescription_id INT,
    sku VARCHAR(100),
    product_name VARCHAR(150),
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(12,2) NOT NULL,
    total_price DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(variant_id) ON DELETE SET NULL
)",
"payments" => "CREATE TABLE IF NOT EXISTS payments (
    payment_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    payment_method ENUM('COD','Bank_Transfer','Credit_Card','Online_Payment') DEFAULT 'COD',
    payment_status ENUM('Pending','Completed','Failed','Refunded') DEFAULT 'Pending',
    amount DECIMAL(12,2) NOT NULL,
    payment_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
)",
"shipments" => "CREATE TABLE IF NOT EXISTS shipments (
    shipment_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    ops_staff_id INT,
    shipping_method VARCHAR(100) DEFAULT 'Standard',
    carrier VARCHAR(100),
    tracking_number VARCHAR(100),
    status ENUM('Packed','Shipped','In_Transit','Delivered','Failed') DEFAULT 'Packed',
    shipped_date TIMESTAMP NULL,
    delivery_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (ops_staff_id) REFERENCES users(user_id) ON DELETE SET NULL
)",
"support_tickets" => "CREATE TABLE IF NOT EXISTS support_tickets (
    ticket_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    order_id INT,
    issue_type ENUM('Warranty','Return','Replacement','Refund','Consultation') DEFAULT 'Consultation',
    title VARCHAR(150) NOT NULL,
    description TEXT,
    priority ENUM('Low','Medium','High') DEFAULT 'Medium',
    status ENUM('Open','In_Progress','Waiting_Customer','Resolved','Closed') DEFAULT 'Open',
    assigned_to INT,
    resolution_notes TEXT,
    resolved_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE SET NULL
)",
"policies_and_configs" => "CREATE TABLE IF NOT EXISTS policies_and_configs (
    policy_id INT PRIMARY KEY AUTO_INCREMENT,
    policy_name VARCHAR(100) NOT NULL,
    policy_description TEXT NOT NULL,
    warranty_period INT,
    return_period INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)",
"cart" => "CREATE TABLE IF NOT EXISTS cart (
    cart_id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    variant_id INT NOT NULL,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id) ON DELETE CASCADE,
    FOREIGN KEY (variant_id) REFERENCES product_variants(variant_id) ON DELETE CASCADE
)",
];

foreach ($tables as $name => $sql) {
    if ($conn->query($sql)) {
        $success[] = " Bảng <strong>$name</strong> đã tạo";
    } else {
        $errors[] = " Lỗi bảng $name: " . $conn->error;
    }
}

// Kiểm tra đã có dữ liệu mẫu chưa
$check = $conn->query("SELECT COUNT(*) as cnt FROM users")->fetch_assoc();
if ($check['cnt'] == 0) {
    $seeds = [
        "INSERT INTO roles (role_name, role_description) VALUES ('Admin','Quản trị viên'),('Sales','Nhân viên bán hàng'),('Operations','Nhân viên vận hành'),('Support','Nhân viên hỗ trợ')",
        "INSERT INTO users (username, email, password, full_name, phone, role) VALUES ('admin','admin@visio.vn','\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Quản Trị Viên','0901234567','admin'),('sales01','sales@visio.vn','\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Nguyễn Văn Sales','0902345678','staff'),('ops01','ops@visio.vn','\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Trần Thị Ops','0903456789','staff')",
        "INSERT INTO user_roles (user_id, role_id) VALUES (1,1),(2,2),(3,3)",
        "INSERT INTO categories (category_name, description) VALUES ('Gọng Kính','Các loại gọng kính thời trang'),('Tròng Kính','Tròng kính cận, viễn, loạn'),('Kính Mát','Kính mát chống UV'),('Kính Thể Thao','Kính dành cho thể thao'),('Phụ Kiện','Hộp kính, dây đeo, khăn lau')",
        "INSERT INTO products (category_id, product_name, description, base_price, image_url) VALUES (1,'Retro Round Classic','Gọng kính tròn kim loại phong cách retro',850000,'img/product-1.jpg'),(1,'EYE PLUS M8241 – Gọng Mắt Mèo','Gọng mắt mèo cá tính màu vàng',1200000,'img/product-2.jpg'),(4,'EYE PLUS AD38 C5500','Gọng nhựa thể thao đen cam',720000,'img/product-3.jpg'),(1,'GỌNG KÍNH REE-MAN','Kính cận chống UV400',980000,'img/product-4.jpg'),(1,'GỌNG KÍNH HM87275','Kính trẻ em chống va đập',1450000,'img/product-5.jpg'),(1,'GỌNG KÍNH CLUBMASTER H70839','Gọng kim loại classic',550000,'img/product-6.jpg')",
        "INSERT INTO product_variants (product_id, sku, color, size, quantity_in_stock, price) VALUES (1,'SKU-RRC-BLK','Đen','M',15,850000),(1,'SKU-RRC-GLD','Vàng','M',10,920000),(2,'SKU-M8241-GLD','Vàng','M',8,1200000),(3,'SKU-AD38-BLK','Đen Cam','L',20,720000),(4,'SKU-REE-BLK','Đen','M',12,980000),(5,'SKU-HM87-BLU','Xanh','S',25,1450000)",
        "INSERT INTO customers (customer_name, email, phone, address) VALUES ('Nguyễn Thị Lan','lan@gmail.com','0901111111','123 Nguyễn Huệ, Q1, TP.HCM'),('Trần Minh Khoa','khoa@gmail.com','0902222222','456 Lê Lợi, Q1, TP.HCM')",
        "INSERT INTO promotions (promo_code, discount_type, discount_value, min_order_value, valid_from, valid_to, max_uses) VALUES ('SALE10','Percentage',10,500000,'2025-01-01','2025-12-31',100),('GIAM50K','Fixed',50000,300000,'2025-01-01','2025-12-31',50)",
        "INSERT INTO orders (customer_id, sales_staff_id, customer_name, customer_email, customer_phone, shipping_address, order_type, status, total_price) VALUES (1,2,'Nguyễn Thị Lan','lan@gmail.com','0901111111','123 Nguyễn Huệ, Q1','Ready_Stock','Delivered',850000),(2,2,'Trần Minh Khoa','khoa@gmail.com','0902222222','456 Lê Lợi, Q1','Prescription','Processing',1200000)",
        "INSERT INTO order_items (order_id, variant_id, sku, product_name, quantity, unit_price, total_price) VALUES (1,1,'SKU-RRC-BLK','Retro Round Classic',1,850000,850000),(2,3,'SKU-M8241-GLD','EYE PLUS M8241',1,1200000,1200000)",
        "INSERT INTO payments (order_id, payment_method, payment_status, amount, payment_date) VALUES (1,'COD','Completed',850000,NOW()),(2,'Bank_Transfer','Pending',1200000,NULL)",
        "INSERT INTO shipments (order_id, ops_staff_id, carrier, tracking_number, status) VALUES (1,3,'Giao Hàng Nhanh','GHN123456','Delivered')",
        "INSERT INTO policies_and_configs (policy_name, policy_description, warranty_period, return_period) VALUES ('Bảo Hành Tròng Kính','Bảo hành 2 năm với lỗi sản xuất',24,30),('Đổi Trả Gọng','Đổi trả trong 7 ngày nếu lỗi kỹ thuật',12,7)",
    ];

    foreach ($seeds as $sql) {
        if (!$conn->query($sql)) {
            $errors[] = " Lỗi dữ liệu mẫu: " . $conn->error;
        }
    }
    $success[] = " Dữ liệu mẫu đã được thêm";
} else {
    $success[] = " Dữ liệu mẫu đã tồn tại, bỏ qua";
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VISIO — Setup Database</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: #f0f2f5; display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 2rem; }
        .box { background: #fff; border-radius: 12px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); padding: 2.5rem; max-width: 600px; width: 100%; }
        h1 { font-size: 1.8rem; color: #1a1714; margin-bottom: 0.5rem; }
        .subtitle { color: #7a6050; font-size: 0.9rem; margin-bottom: 2rem; }
        .item { padding: 0.5rem 0; font-size: 0.9rem; border-bottom: 1px solid #f5f0e8; color: #333; }
        .item:last-child { border: none; }
        .error { color: #dc3545; }
        .section-title { font-weight: 600; color: #4a3728; margin: 1.2rem 0 0.5rem; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; }
        .done-box { background: #d4edda; border-radius: 8px; padding: 1.2rem 1.5rem; margin-top: 1.5rem; }
        .done-box h2 { color: #155724; font-size: 1.1rem; margin-bottom: 0.8rem; }
        .btn { display: inline-block; padding: 0.75rem 1.8rem; background: #1a1714; color: #f5f0e8; border-radius: 6px; text-decoration: none; font-size: 0.9rem; margin-right: 0.8rem; margin-top: 0.5rem; }
        .btn:hover { background: #a07840; }
        .btn-outline { background: none; color: #1a1714; border: 1.5px solid #1a1714; }
        .btn-outline:hover { background: #1a1714; color: #f5f0e8; }
        .account { background: #f5f0e8; border-radius: 6px; padding: 1rem; margin-top: 1rem; font-size: 0.88rem; color: #4a3728; }
    </style>
</head>
<body>
<div class="box">
    <h1> VISIO Setup</h1>
    <p class="subtitle">Tự động tạo database và dữ liệu mẫu</p>

    <p class="section-title">Kết quả</p>

    <?php foreach ($success as $msg): ?>
        <div class="item"><?php echo $msg; ?></div>
    <?php endforeach; ?>

    <?php foreach ($errors as $err): ?>
        <div class="item error"><?php echo htmlspecialchars($err); ?></div>
    <?php endforeach; ?>

    <?php if (empty($errors)): ?>
        <div class="done-box">
            <h2> Setup hoàn tất!</h2>
            <div class="account">
                <strong>Tài khoản Admin:</strong> admin / password<br>
                <strong>Tài khoản Sales:</strong> sales01 / password
            </div>
            <div style="margin-top:1rem;">
                <a href="index.php" class="btn"> Trang chủ</a>
                <a href="admin/" class="btn btn-outline"> Admin</a>
            </div>
        </div>
    <?php else: ?>
        <div style="margin-top:1rem;padding:1rem;background:#f8d7da;border-radius:8px;color:#721c24;">
             Có lỗi xảy ra, kiểm tra lại XAMPP và thử lại!
        </div>
    <?php endif; ?>
</div>
</body>
</html>
