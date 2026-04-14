--Bảng lưu thông tin toa kính [cite: 43]
CREATE TABLE prescriptions (
    prescription_id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    right_sph DECIMAL(4,2),
    left_sph DECIMAL(4,2),
    pd DECIMAL(4,1),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

--Bảng Đơn hàng (Trung tâm hệ thống) [cite: 54]
CREATE TABLE orders (
    order_id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    sales_staff_id INT, -- Nhân viên Sales xử lý [cite: 55]
    promo_id INT, -- Mã giảm giá áp dụng [cite: 55]
    total_amount DECIMAL(12,2),
    status ENUM('Pending', 'Processing', 'Shipped', 'Completed', 'Cancelled'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

--Bảng Ticket hỗ trợ [cite: 61]
CREATE TABLE support_tickets (
    ticket_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT,
    issue_type ENUM('Warranty', 'Return', 'Consultation'),
    description TEXT,
    status ENUM('Open', 'In Progress', 'Resolved')
);