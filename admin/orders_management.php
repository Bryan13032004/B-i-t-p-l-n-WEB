<?php
// Quản lý Đơn hàng
require '../db_connection.php';
require '../admin_functions/orders_functions.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header('Location: ../index.php');
    exit;
}

$message = '';
$message_type = '';

// Xử lý cập nhật trạng thái đơn hàng
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'update_status') {
        $result = update_order_status($conn, $_POST['order_id'], $_POST['status']);
        $message = $result['message'];
        $message_type = $result['success'] ? 'success' : 'error';
    }
}

// Lấy danh sách đơn hàng
$orders = get_all_orders($conn);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Đơn hàng</title>
    <link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>
    <div class="admin-container">
        <nav class="admin-nav">
            <h2>Admin Panel</h2>
            <ul>
                <li><a href="dashboard.php">Trang Chủ</a></li>
                <li><a href="orders_management.php">Quản lý Đơn hàng</a></li>
                <li><a href="support_tickets_management.php">Ticket hỗ trợ</a></li>
                <li><a href="shipments_management.php">Vận chuyển</a></li>
                <li><a href="../index.php">Quay lại</a></li>
            </ul>
        </nav>

        <div class="admin-content">
            <h1>Quản lý Đơn hàng</h1>

            <?php if (!empty($message)) { ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php } ?>

            <div class="table-container">
                <h2>Danh sách đơn hàng</h2>
                <?php if ($orders && $orders->num_rows > 0) { ?>
                    <table>
                        <tr>
                            <th>ID</th>
                            <th>Khách hàng</th>
                            <th>Email</th>
                            <th>Tổng tiền (VND)</th>
                            <th>Loại đơn</th>
                            <th>Trạng thái</th>
                            <th>Ngày tạo</th>
                            <th>Hành động</th>
                        </tr>
                        <?php while ($order = $orders->fetch_assoc()) { ?>
                            <tr>
                                <td><strong>#<?php echo $order['order_id']; ?></strong></td>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($order['customer_email']); ?></td>
                                <td><?php echo number_format($order['total_price'] ?? 0, 0, ',', '.'); ?></td>
                                <td>
                                    <?php
                                    $order_types = ['Ready_Stock' => 'Có sẵn', 'Pre_Order' => 'Đặt trước', 'Prescription' => 'Theo đơn'];
                                    echo $order_types[$order['order_type']] ?? $order['order_type'];
                                    ?>
                                </td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                        <select name="status" onchange="this.form.submit()">
                                            <?php
                                            $statuses = ['Pending' => 'Chờ xác nhận', 'Confirmed' => 'Đã xác nhận', 'Processing' => 'Đang xử lý', 'Ready_to_Ship' => 'Sẵn giao', 'Shipped' => 'Đã giao', 'Delivered' => 'Hoàn thành', 'Cancelled' => 'Hủy'];
                                            foreach ($statuses as $key => $value) {
                                                $selected = ($order['status'] == $key) ? 'selected' : '';
                                                echo '<option value="' . $key . '" ' . $selected . '>' . $value . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </form>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($order['order_date'])); ?></td>
                                <td>
                                    <a href="order_details.php?id=<?php echo $order['order_id']; ?>" class="btn-edit">Chi tiết</a>
                                </td>
                            </tr>
                        <?php } ?>
                    </table>
                <?php } else { ?>
                    <p>Chưa có đơn hàng nào</p>
                <?php } ?>
            </div>
        </div>
    </div>
</body>
</html>
