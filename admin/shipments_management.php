<?php
// Quản lý Vận chuyển
require '../db_connection.php';
require '../admin_functions/payments_shipments_functions.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: index.php');
    exit;
}

$message = '';
$message_type = '';

// Xử lý cập nhật vận chuyển
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'update_tracking') {
        $result = update_shipment_tracking($conn, $_POST['shipment_id'], $_POST['tracking_number'], $_POST['carrier']);
        $message = $result['message'];
        $message_type = $result['success'] ? 'success' : 'error';
    } elseif ($_POST['action'] == 'mark_delivered') {
        $result = update_shipment_delivery($conn, $_POST['shipment_id']);
        $message = $result['message'];
        $message_type = $result['success'] ? 'success' : 'error';
    }
}

// Lấy danh sách vận chuyển
$shipments = get_all_shipments($conn);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Vận chuyển</title>
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
                <li><a href="../index.html">Quay lại</a></li>
            </ul>
        </nav>

        <div class="admin-content">
            <h1>Quản lý Vận chuyển</h1>

            <?php if (!empty($message)) { ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php } ?>

            <div class="table-container">
                <h2>Danh sách vận chuyển</h2>
                <?php if ($shipments && $shipments->num_rows > 0) { ?>
                    <table>
                        <tr>
                            <th>ID</th>
                            <th>Đơn hàng</th>
                            <th>Khách hàng</th>
                            <th>Mã vận đơn</th>
                            <th>Đơn vị vận chuyển</th>
                            <th>Trạng thái</th>
                            <th>Nhân viên</th>
                            <th>Ngày tạo</th>
                            <th>Hành động</th>
                        </tr>
                        <?php while ($shipment = $shipments->fetch_assoc()) { ?>
                            <tr>
                                <td><strong>#<?php echo $shipment['shipment_id']; ?></strong></td>
                                <td>#<?php echo $shipment['order_id']; ?></td>
                                <td><?php echo htmlspecialchars($shipment['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($shipment['tracking_number'] ?? 'Chưa cập nhật'); ?></td>
                                <td><?php echo htmlspecialchars($shipment['carrier'] ?? 'Chưa chọn'); ?></td>
                                <td>
                                    <?php
                                    $statuses = ['Packed' => 'Đã đóng gói', 'Shipped' => 'Đã gửi', 'In_Transit' => 'Đang giao', 'Delivered' => 'Hoàn thành', 'Failed' => 'Thất bại'];
                                    echo $statuses[$shipment['status']] ?? $shipment['status'];
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($shipment['ops_staff_name'] ?? 'N/A'); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($shipment['created_at'])); ?></td>
                                <td>
                                    <?php if ($shipment['status'] == 'Packed') { ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="update_tracking">
                                            <input type="hidden" name="shipment_id" value="<?php echo $shipment['shipment_id']; ?>">
                                            <input type="text" name="tracking_number" placeholder="Mã vận đơn" required style="width: 120px; padding: 5px;">
                                            <input type="text" name="carrier" placeholder="Đơn vị" required style="width: 100px; padding: 5px;">
                                            <button type="submit" style="padding: 5px 10px;">Cập nhật</button>
                                        </form>
                                    <?php } elseif ($shipment['status'] == 'Shipped') { ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="mark_delivered">
                                            <input type="hidden" name="shipment_id" value="<?php echo $shipment['shipment_id']; ?>">
                                            <button type="submit" class="btn-edit">Đã giao</button>
                                        </form>
                                    <?php } else { ?>
                                        <span style="color: #999;">Không hành động</span>
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php } ?>
                    </table>
                <?php } else { ?>
                    <p>Chưa có vận chuyển nào</p>
                <?php } ?>
            </div>
        </div>
    </div>
</body>
</html>
