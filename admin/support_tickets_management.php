<?php
// Quản lý Ticket hỗ trợ
require '../db_connection.php';
require '../admin_functions/support_tickets_functions.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header('Location: ../index.php');
    exit;
}

$message = '';
$message_type = '';

// Xử lý cập nhật ticket
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'update_status') {
        $result = update_ticket_status($conn, $_POST['ticket_id'], $_POST['status']);
        $message = $result['message'];
        $message_type = $result['success'] ? 'success' : 'error';
    } elseif ($_POST['action'] == 'assign') {
        $result = assign_ticket($conn, $_POST['ticket_id'], $_POST['staff_id']);
        $message = $result['message'];
        $message_type = $result['success'] ? 'success' : 'error';
    } elseif ($_POST['action'] == 'resolve') {
        $result = add_ticket_resolution($conn, $_POST['ticket_id'], $_POST['resolution_notes']);
        $message = $result['message'];
        $message_type = $result['success'] ? 'success' : 'error';
    } elseif ($_POST['action'] == 'close') {
        $result = close_ticket($conn, $_POST['ticket_id']);
        $message = $result['message'];
        $message_type = $result['success'] ? 'success' : 'error';
    }
}

// Lấy danh sách ticket
$filter_status = isset($_GET['status']) ? $_GET['status'] : null;
$tickets = get_all_tickets($conn, $filter_status);

// Lấy danh sách nhân viên
$staff_sql = "SELECT user_id, full_name FROM users WHERE role_id IS NOT NULL ORDER BY full_name";
$staff_result = $conn->query($staff_sql);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Ticket hỗ trợ</title>
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
            <h1>Quản lý Ticket hỗ trợ</h1>

            <?php if (!empty($message)) { ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php } ?>

            <div style="margin-bottom: 20px;">
                <a href="?status=" class="btn-edit">Tất cả</a>
                <a href="?status=Open" class="btn-edit">Mở</a>
                <a href="?status=In_Progress" class="btn-edit">Đang xử lý</a>
                <a href="?status=Resolved" class="btn-edit">Đã giải quyết</a>
                <a href="?status=Closed" class="btn-edit">Đã đóng</a>
            </div>

            <div class="table-container">
                <h2>Danh sách ticket</h2>
                <?php if ($tickets && $tickets->num_rows > 0) { ?>
                    <table>
                        <tr>
                            <th>ID</th>
                            <th>Khách hàng</th>
                            <th>Tiêu đề</th>
                            <th>Loại</th>
                            <th>Độ ưu tiên</th>
                            <th>Trạng thái</th>
                            <th>Nhân viên</th>
                            <th>Ngày tạo</th>
                            <th>Hành động</th>
                        </tr>
                        <?php while ($ticket = $tickets->fetch_assoc()) { ?>
                            <tr>
                                <td><strong>#<?php echo $ticket['ticket_id']; ?></strong></td>
                                <td><?php echo htmlspecialchars($ticket['customer_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($ticket['title']); ?></td>
                                <td>
                                    <?php
                                    $types = ['Warranty' => 'Bảo hành', 'Return' => 'Trả hàng', 'Replacement' => 'Đổi hàng', 'Refund' => 'Hoàn tiền', 'Consultation' => 'Tư vấn'];
                                    echo $types[$ticket['issue_type']] ?? $ticket['issue_type'];
                                    ?>
                                </td>
                                <td>
                                    <span style="padding: 3px 8px; border-radius: 3px; <?php echo ($ticket['priority'] == 'High') ? 'background-color: #dc3545; color: white;' : (($ticket['priority'] == 'Medium') ? 'background-color: #ffc107; color: black;' : 'background-color: #28a745; color: white;'); ?>">
                                        <?php echo $ticket['priority']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $statuses = ['Open' => 'Mở', 'In_Progress' => 'Đang xử lý', 'Waiting_Customer' => 'Chờ KH', 'Resolved' => 'Đã giải quyết', 'Closed' => 'Đã đóng'];
                                    echo $statuses[$ticket['status']] ?? $ticket['status'];
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($ticket['assigned_staff_name'] ?? 'Chưa gán'); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($ticket['created_at'])); ?></td>
                                <td>
                                    <a href="ticket_details.php?id=<?php echo $ticket['ticket_id']; ?>" class="btn-edit">Chi tiết</a>
                                </td>
                            </tr>
                        <?php } ?>
                    </table>
                <?php } else { ?>
                    <p>Không có ticket nào</p>
                <?php } ?>
            </div>
        </div>
    </div>
</body>
</html>
