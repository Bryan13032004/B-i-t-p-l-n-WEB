<?php
// Admin Dashboard
require '../db_connection.php';

// Kiểm tra quyền admin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../index.php');
    exit;
}

// Lấy thống kê
$stats = array();

// Đếm tổng số đơn hàng
$sql = "SELECT COUNT(*) as total_orders FROM orders";
$result = $conn->query($sql);
$stats['total_orders'] = $result->fetch_assoc()['total_orders'];

// Đếm tổng khách hàng
$sql = "SELECT COUNT(*) as total_customers FROM customers";
$result = $conn->query($sql);
$stats['total_customers'] = $result->fetch_assoc()['total_customers'];

// Đếm nhân viên
$sql = "SELECT COUNT(*) as total_users FROM users";
$result = $conn->query($sql);
$stats['total_users'] = $result->fetch_assoc()['total_users'];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Quản lý Kính Mắt</title>
    <link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>
    <div class="admin-container">
        <nav class="admin-nav">
            <h2>Admin Panel</h2>
            <ul>
                <li><a href="dashboard.php">Trang Chủ</a></li>
                <li><a href="users_management.php">Quản lý Nhân viên</a></li>
                <li><a href="roles_management.php">Quản lý Vai trò</a></li>
                <li><a href="categories_management.php">Quản lý Phân loại</a></li>
                <li><a href="products_management.php">Quản lý Sản phẩm</a></li>
                <li><a href="orders_management.php">Quản lý Đơn hàng</a></li>
                <li><a href="support_tickets_management.php">Ticket hỗ trợ</a></li>
                <li><a href="shipments_management.php">Quản lý Vận chuyển</a></li>
                <li><a href="policies_and_configs.php">Chính sách & Cấu hình</a></li>
                <li><a href="../index.php">Quay lại</a></li>
            </ul>
        </nav>

        <div class="admin-content">
            <h1>Bảng điều khiển Admin</h1>
            
            <div class="stats-container">
                <div class="stat-card">
                    <h3>Tổng đơn hàng</h3>
                    <p class="stat-number"><?php echo $stats['total_orders']; ?></p>
                </div>

                <div class="stat-card">
                    <h3>Tổng khách hàng</h3>
                    <p class="stat-number"><?php echo $stats['total_customers']; ?></p>
                </div>

                <div class="stat-card">
                    <h3>Tổng nhân viên</h3>
                    <p class="stat-number"><?php echo $stats['total_users']; ?></p>
                </div>
            </div>

            <div class="recent-orders">
                <h2>Đơn hàng gần đây</h2>
                <?php
                $sql = "SELECT o.order_id, c.customer_name, o.order_date, o.total_price 
                        FROM orders o 
                        JOIN customers c ON o.customer_id = c.customer_id 
                        ORDER BY o.order_date DESC 
                        LIMIT 10";
                $result = $conn->query($sql);
                
                if ($result->num_rows > 0) {
                    echo "<table>";
                    echo "<tr><th>ID</th><th>Khách hàng</th><th>Ngày</th><th>Giá</th></tr>";
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $row['order_id'] . "</td>";
                        echo "<td>" . $row['customer_name'] . "</td>";
                        echo "<td>" . $row['order_date'] . "</td>";
                        echo "<td>" . number_format($row['total_price'], 0, ',', '.') . " VND</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                } else {
                    echo "<p>Không có dữ liệu</p>";
                }
                ?>
            </div>
        </div>
    </div>
</body>
</html>
