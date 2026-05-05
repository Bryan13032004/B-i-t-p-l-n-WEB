-- ============================================================
-- VISIO EYEWEAR - DATABASE MySQL
-- Import vào phpMyAdmin, database tên: eyewear_db
-- ============================================================

CREATE DATABASE IF NOT EXISTS eyewear_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE eyewear_db;

-- ============================================================
-- 1. ROLES - Vai trò nhân viên
-- ============================================================
CREATE TABLE roles (
    role_id INT PRIMARY KEY AUTO_INCREMENT,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    role_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- 2. USERS - Nhân viên / Tài khoản
-- ============================================================
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    role VARCHAR(20) DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- 3. USER_ROLES - Gán vai trò cho nhân viên
-- ============================================================
CREATE TABLE user_roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    role_id INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(role_id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_role (user_id, role_id)
);

-- ============================================================
-- 4. CATEGORIES - Phân loại sản phẩm
-- ============================================================
CREATE TABLE categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- 5. PRODUCTS - Sản phẩm
-- ============================================================
CREATE TABLE products (
    product_id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT NOT NULL,
    product_name VARCHAR(150) NOT NULL,
    description TEXT,
    base_price DECIMAL(12,2) NOT NULL,
    image_url VARCHAR(255),
    status ENUM('Active','Inactive') DEFAULT 'Active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id)
);

-- ============================================================
-- 6. PRODUCT_VARIANTS - Biến thể sản phẩm (màu, size, SKU)
-- ============================================================
CREATE TABLE product_variants (
    variant_id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    sku VARCHAR(100) NOT NULL UNIQUE,
    color VARCHAR(50),
    size VARCHAR(50),
    quantity_in_stock INT DEFAULT 0,
    price DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
);

-- ============================================================
-- 7. CUSTOMERS - Khách hàng
-- ============================================================
CREATE TABLE customers (
    customer_id INT PRIMARY KEY AUTO_INCREMENT,
    customer_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- 8. PRESCRIPTIONS - Toa kính / Đơn thuốc mắt
-- ============================================================
CREATE TABLE prescriptions (
    prescription_id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    right_sph DECIMAL(4,2),
    left_sph DECIMAL(4,2),
    pd DECIMAL(4,1),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(customer_id)
);

-- ============================================================
-- 9. PROMOTIONS - Mã khuyến mại
-- ============================================================
CREATE TABLE promotions (
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
);

-- ============================================================
-- 10. ORDERS - Đơn hàng
-- ============================================================
CREATE TABLE orders (
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
);

-- ============================================================
-- 11. ORDER_ITEMS - Chi tiết đơn hàng
-- ============================================================
CREATE TABLE order_items (
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
    FOREIGN KEY (variant_id) REFERENCES product_variants(variant_id) ON DELETE SET NULL,
    FOREIGN KEY (prescription_id) REFERENCES prescriptions(prescription_id) ON DELETE SET NULL
);

-- ============================================================
-- 12. PAYMENTS - Thanh toán
-- ============================================================
CREATE TABLE payments (
    payment_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    payment_method ENUM('COD','Bank_Transfer','Credit_Card','Online_Payment') DEFAULT 'COD',
    payment_status ENUM('Pending','Completed','Failed','Refunded') DEFAULT 'Pending',
    amount DECIMAL(12,2) NOT NULL,
    payment_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
);

-- ============================================================
-- 13. SHIPMENTS - Vận chuyển
-- ============================================================
CREATE TABLE shipments (
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
);

-- ============================================================
-- 14. SUPPORT_TICKETS - Ticket hỗ trợ
-- ============================================================
CREATE TABLE support_tickets (
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
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_to) REFERENCES users(user_id) ON DELETE SET NULL
);

-- ============================================================
-- 15. POLICIES_AND_CONFIGS - Chính sách & Cấu hình
-- ============================================================
CREATE TABLE policies_and_configs (
    policy_id INT PRIMARY KEY AUTO_INCREMENT,
    policy_name VARCHAR(100) NOT NULL,
    policy_description TEXT NOT NULL,
    warranty_period INT COMMENT 'Tháng',
    return_period INT COMMENT 'Ngày',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- DỮ LIỆU MẪU
-- ============================================================

-- Roles
INSERT INTO roles (role_name, role_description) VALUES
('Admin', 'Quản trị viên hệ thống'),
('Sales', 'Nhân viên bán hàng'),
('Operations', 'Nhân viên vận hành'),
('Support', 'Nhân viên hỗ trợ khách hàng');

-- Admin account (password: admin123)
INSERT INTO users (username, email, password, full_name, phone, role) VALUES
('admin', 'admin@visio.vn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Quản Trị Viên', '0901234567', 'admin'),
('sales01', 'sales@visio.vn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Nguyễn Văn Sales', '0902345678', 'staff'),
('ops01', 'ops@visio.vn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Trần Thị Ops', '0903456789', 'staff');

-- Gán role cho admin
INSERT INTO user_roles (user_id, role_id) VALUES (1, 1), (2, 2), (3, 3);

-- Categories
INSERT INTO categories (category_name, description) VALUES
('Gọng Kính', 'Các loại gọng kính thời trang'),
('Tròng Kính', 'Tròng kính cận, viễn, loạn'),
('Kính Mát', 'Kính mát chống UV'),
('Kính Thể Thao', 'Kính dành cho thể thao'),
('Phụ Kiện', 'Hộp kính, dây đeo, khăn lau');

-- Products
INSERT INTO products (category_id, product_name, description, base_price, image_url) VALUES
(1, 'Retro Round Classic', 'Gọng kính tròn kim loại phong cách retro', 850000, 'img/product-1.jpg'),
(1, 'EYE PLUS M8241 – Gọng Mắt Mèo', 'Gọng mắt mèo cá tính màu vàng', 1200000, 'img/product-2.jpg'),
(4, 'EYE PLUS AD38 C5500', 'Gọng nhựa thể thao đen cam', 720000, 'img/product-3.jpg'),
(1, 'GỌNG KÍNH REE-MAN', 'Kính cận chống UV400', 980000, 'img/product-4.jpg'),
(1, 'GỌNG KÍNH HM87275', 'Kính trẻ em chống va đập', 1450000, 'img/product-5.jpg'),
(1, 'GỌNG KÍNH CLUBMASTER H70839', 'Gọng kim loại classic', 550000, 'img/product-6.jpg');

-- Product Variants
INSERT INTO product_variants (product_id, sku, color, size, quantity_in_stock, price) VALUES
(1, 'SKU-RRC-BLK', 'Đen', 'M', 15, 850000),
(1, 'SKU-RRC-GLD', 'Vàng', 'M', 10, 920000),
(2, 'SKU-M8241-GLD', 'Vàng', 'M', 8, 1200000),
(3, 'SKU-AD38-BLK', 'Đen Cam', 'L', 20, 720000),
(4, 'SKU-REE-BLK', 'Đen', 'M', 12, 980000),
(5, 'SKU-HM87-BLU', 'Xanh', 'S', 25, 1450000);

-- Customers
INSERT INTO customers (customer_name, email, phone, address) VALUES
('Nguyễn Thị Lan', 'lan@gmail.com', '0901111111', '123 Nguyễn Huệ, Q1, TP.HCM'),
('Trần Minh Khoa', 'khoa@gmail.com', '0902222222', '456 Lê Lợi, Q1, TP.HCM'),
('Lê Thu Hương', 'huong@gmail.com', '0903333333', '789 Hai Bà Trưng, Q3, TP.HCM');

-- Prescriptions
INSERT INTO prescriptions (customer_id, right_sph, left_sph, pd) VALUES
(1, -2.50, -3.00, 62.5),
(2, -1.75, -2.00, 64.0),
(3, -4.25, -4.50, 61.0);

-- Promotions
INSERT INTO promotions (promo_code, discount_type, discount_value, min_order_value, valid_from, valid_to, max_uses) VALUES
('SALE10', 'Percentage', 10, 500000, '2025-01-01', '2025-12-31', 100),
('GIAM50K', 'Fixed', 50000, 300000, '2025-01-01', '2025-12-31', 50),
('VIP20', 'Percentage', 20, 1000000, '2025-01-01', '2025-12-31', 30);

-- Sample Orders
INSERT INTO orders (customer_id, sales_staff_id, customer_name, customer_email, customer_phone, shipping_address, order_type, status, total_price) VALUES
(1, 2, 'Nguyễn Thị Lan', 'lan@gmail.com', '0901111111', '123 Nguyễn Huệ, Q1', 'Ready_Stock', 'Delivered', 850000),
(2, 2, 'Trần Minh Khoa', 'khoa@gmail.com', '0902222222', '456 Lê Lợi, Q1', 'Prescription', 'Processing', 1200000),
(3, 2, 'Lê Thu Hương', 'huong@gmail.com', '0903333333', '789 Hai Bà Trưng, Q3', 'Ready_Stock', 'Pending', 720000);

-- Order Items
INSERT INTO order_items (order_id, variant_id, sku, product_name, quantity, unit_price, total_price) VALUES
(1, 1, 'SKU-RRC-BLK', 'Retro Round Classic', 1, 850000, 850000),
(2, 3, 'SKU-M8241-GLD', 'EYE PLUS M8241', 1, 1200000, 1200000),
(3, 4, 'SKU-AD38-BLK', 'EYE PLUS AD38 C5500', 1, 720000, 720000);

-- Payments
INSERT INTO payments (order_id, payment_method, payment_status, amount, payment_date) VALUES
(1, 'COD', 'Completed', 850000, NOW()),
(2, 'Bank_Transfer', 'Pending', 1200000, NULL),
(3, 'COD', 'Pending', 720000, NULL);

-- Shipments
INSERT INTO shipments (order_id, ops_staff_id, carrier, tracking_number, status, shipped_date, delivery_date) VALUES
(1, 3, 'Giao Hàng Nhanh', 'GHN123456', 'Delivered', NOW(), NOW()),
(2, 3, 'J&T Express', 'JT789012', 'Packed', NULL, NULL);

-- Policies
INSERT INTO policies_and_configs (policy_name, policy_description, warranty_period, return_period) VALUES
('Bảo Hành Tròng Kính', 'Bảo hành tròng kính 2 năm, áp dụng với lỗi sản xuất', 24, 30),
('Đổi Trả Gọng', 'Đổi trả gọng trong vòng 7 ngày nếu lỗi kỹ thuật', 12, 7),
('Bảo Hành Trẻ Em', 'Kính trẻ em được bảo hành 1 năm kể cả va đập', 12, 15);

-- Support Tickets mẫu
INSERT INTO support_tickets (user_id, order_id, issue_type, title, description, priority, status) VALUES
(1, 1, 'Warranty', 'Tròng kính bị trầy', 'Tròng kính mới dùng 1 tháng đã bị trầy xước', 'High', 'Open'),
(2, 2, 'Consultation', 'Hỏi về độ cận', 'Muốn tư vấn tròng kính phù hợp với độ cận -4.0', 'Low', 'Resolved');
