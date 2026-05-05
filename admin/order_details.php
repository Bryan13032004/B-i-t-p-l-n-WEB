<?php
// Chi tiết Đơn hàng & Thanh toán & Vận chuyển
require '../db_connection.php';
require '../admin_functions/orders_functions.php';
require '../admin_functions/payments_shipments_functions.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header('Location: ../index.php');
    exit;
}

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($order_id <= 0) {
    header('Location: orders_management.php');
    exit;
}

$order = get_order_by_id($conn, $order_id);
if (!$order) {
    header('Location: orders_management.php');
    exit;
}

$items = get_order_items($conn, $order_id);
$payment = get_payment_by_order($conn, $order_id);
$shipment = get_shipment_by_order($conn, $order_id);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết Đơn hàng #<?php echo $order_id; ?></title>
    <link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>
    <div class="admin-container">
        <nav class="admin-nav">
            <h2>Admin Panel</h2>
            <ul>
                <li><a href="orders_management.php">← Quay lại</a></li>
            </ul>
        </nav>

        <div class="admin-content">
            <h1>Chi tiết Đơn hàng #<?php echo $order_id; ?></h1>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;">
                <!-- Thông tin khách hàng -->
                <div style="border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
                    <h3>Thông tin Khách hàng</h3>
                    <p><strong>Tên:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($order['customer_email']); ?></p>
                    <p><strong>Điện thoại:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?></p>
                    <p><strong>Địa chỉ giao:</strong> <?php echo htmlspecialchars($order['shipping_address']); ?></p>
                    <p><strong>Ghi chú:</strong> <?php echo htmlspecialchars($order['notes'] ?? 'Không có'); ?></p>
                </div>

                <!-- Thông tin đơn hàng -->
                <div style="border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
                    <h3>Thông tin Đơn hàng</h3>
                    <p><strong>Loại:</strong> <?php echo ['Ready_Stock' => 'Có sẵn', 'Pre_Order' => 'Đặt trước', 'Prescription' => 'Theo đơn'][$order['order_type']] ?? $order['order_type']; ?></p>
                    <p><strong>Trạng thái:</strong> 
                        <span style="padding: 3px 8px; border-radius: 3px; background-color: #007bff; color: white;">
                            <?php echo ['Pending' => 'Chờ xác nhận', 'Confirmed' => 'Đã xác nhận', 'Processing' => 'Đang xử lý', 'Ready_to_Ship' => 'Sẵn giao', 'Shipped' => 'Đã giao', 'Delivered' => 'Hoàn thành', 'Cancelled' => 'Hủy'][$order['status']] ?? $order['status']; ?>
                        </span>
                    </p>
                    <p><strong>Ngày tạo:</strong> <?php echo date('d/m/Y H:i', strtotime($order['order_date'])); ?></p>
                    <p><strong>Tổng tiền:</strong> <span style="font-size: 18px; color: #28a745; font-weight: bold;"><?php echo number_format($order['total_price'] ?? 0, 0, ',', '.'); ?> VND</span></p>
                </div>
            </div>

            <!-- Mặt hàng -->
            <div style="border: 1px solid #ddd; padding: 15px; border-radius: 5px; margin: 20px 0;">
                <h3>Mặt hàng trong đơn</h3>
                <?php if ($items && $items->num_rows > 0) { ?>
                    <table style="width: 100%;">
                        <tr>
                            <th>SKU</th>
                            <th>Tên sản phẩm</th>
                            <th>Số lượng</th>
                            <th>Đơn giá (VND)</th>
                            <th>Thành tiền (VND)</th>
                        </tr>
                        <?php while ($item = $items->fetch_assoc()) { ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['sku']); ?></td>
                                <td><?php echo htmlspecialchars($item['product_name'] ?? 'N/A'); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td><?php echo number_format($item['unit_price'], 0, ',', '.'); ?></td>
                                <td><?php echo number_format($item['total_price'], 0, ',', '.'); ?></td>
                            </tr>
                        <?php } ?>
                    </table>
                <?php } else { ?>
                    <p>Chưa có mặt hàng</p>
                <?php } ?>
            </div>

            <!-- Thanh toán -->
            <div style="border: 1px solid #ddd; padding: 15px; border-radius: 5px; margin: 20px 0;">
                <h3>Thông tin Thanh toán</h3>
                <?php if ($payment) { ?>
                    <p><strong>Phương thức:</strong> <?php echo $payment['payment_method']; ?></p>
                    <p><strong>Trạng thái:</strong> 
                        <span style="padding: 3px 8px; border-radius: 3px; background-color: <?php echo ($payment['payment_status'] == 'Completed') ? '#28a745' : '#ffc107'; ?>; color: white;">
                            <?php echo ['Pending' => 'Chờ thanh toán', 'Completed' => 'Đã thanh toán', 'Failed' => 'Thất bại', 'Refunded' => 'Đã hoàn tiền'][$payment['payment_status']] ?? $payment['payment_status']; ?>
                        </span>
                    </p>
                    <p><strong>Số tiền:</strong> <?php echo number_format($payment['amount'], 0, ',', '.'); ?> VND</p>
                    <p><strong>Ngày thanh toán:</strong> <?php echo $payment['payment_date'] ? date('d/m/Y H:i', strtotime($payment['payment_date'])) : 'Chưa thanh toán'; ?></p>
                <?php } else { ?>
                    <p style="color: #dc3545;">Chưa có thông tin thanh toán</p>
                <?php } ?>
            </div>

            <!-- Vận chuyển -->
            <div style="border: 1px solid #ddd; padding: 15px; border-radius: 5px; margin: 20px 0;">
                <h3>Thông tin Vận chuyển</h3>
                <?php if ($shipment) { ?>
                    <p><strong>Trạng thái:</strong> 
                        <span style="padding: 3px 8px; border-radius: 3px; background-color: #17a2b8; color: white;">
                            <?php echo ['Packed' => 'Đã đóng gói', 'Shipped' => 'Đã gửi', 'In_Transit' => 'Đang giao', 'Delivered' => 'Hoàn thành', 'Failed' => 'Thất bại'][$shipment['status']] ?? $shipment['status']; ?>
                        </span>
                    </p>
                    <p><strong>Đơn vị vận chuyển:</strong> <?php echo htmlspecialchars($shipment['carrier'] ?? 'Chưa chọn'); ?></p>
                    <p><strong>Mã vận đơn:</strong> <?php echo htmlspecialchars($shipment['tracking_number'] ?? 'Chưa cập nhật'); ?></p>
                    <p><strong>Ngày gửi:</strong> <?php echo $shipment['shipped_date'] ? date('d/m/Y H:i', strtotime($shipment['shipped_date'])) : 'Chưa gửi'; ?></p>
                    <p><strong>Ngày giao:</strong> <?php echo $shipment['delivery_date'] ? date('d/m/Y H:i', strtotime($shipment['delivery_date'])) : 'Chưa giao'; ?></p>
                <?php } else { ?>
                    <p style="color: #dc3545;">Chưa có thông tin vận chuyển</p>
                <?php } ?>
            </div>

            <div style="margin-top: 20px;">
                <a href="orders_management.php" class="btn-edit">← Quay lại</a>
            </div>
        </div>
    </div>
</body>
</html>
