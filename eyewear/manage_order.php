<?php
require_once 'functions.php';

// Giả sử có hành động bấm "Xác nhận đơn hàng"
if (isset($_GET['action']) && $_GET['action'] == 'confirm') {
    $order_id = $_GET['id'];
    
    // 1. Lấy loại đơn hàng
    $res = $conn->query("SELECT order_type FROM orders WHERE order_id = $order_id");
    $order = $res->fetch_assoc();

    if ($order['order_type'] == 'Prescription') {
        // Gọi hàm kiểm tra toa kính bạn đã viết ở line 22
        $check = checkPrescription($conn, $order_id); 
        if ($check['status'] == 'success') {
            $conn->query("UPDATE orders SET status = 'Processing' WHERE order_id = $order_id");
            echo "Đã xác nhận đơn hàng theo toa!";
        } else {
            echo "Cảnh báo: " . $check['message'];
        }
    } else {
        // Đơn hàng Stock thì xác nhận luôn
        $conn->query("UPDATE orders SET status = 'Processing' WHERE order_id = $order_id");
        echo "Đã xác nhận đơn hàng gọng kính có sẵn!";
    }
}

// Lấy danh sách đơn hàng mới (Pending)
$sql = "SELECT * FROM orders WHERE status = 'Pending'";
$orders = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sales - Quản lý đơn hàng</title>
    <style>
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        .btn { padding: 5px 10px; background: #28a745; color: white; text-decoration: none; border-radius: 3px; }
    </style>
</head>
<body>
    <h2>Danh sách đơn hàng mới tiếp nhận</h2>
    <table>
        <tr>
            <th>ID Đơn</th>
            <th>ID Khách</th>
            <th>Tổng tiền</th>
            <th>Trạng thái</th>
            <th>Thao tác Sales</th>
        </tr>
        <?php while($row = $orders->fetch_assoc()): ?>
        <tr>
            <td>#<?php echo $row['order_id']; ?></td>
            <td><?php echo $row['customer_id']; ?></td>
            <td><?php echo number_format($row['total_price'] ?? 0); ?> VNĐ</td>
            <td><?php echo $row['status']; ?></td>
            <td>
                <a class="btn" href="?action=confirm&id=<?php echo $row['order_id']; ?>&p_id=1">
                    Kiểm tra & Xác nhận
                </a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>