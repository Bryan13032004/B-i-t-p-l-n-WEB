<?php
// Chi tiết Ticket hỗ trợ
require '../db_connection.php';
require '../admin_functions/support_tickets_functions.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Admin') {
    header('Location: ../index.php');
    exit;
}

$ticket_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($ticket_id <= 0) {
    header('Location: support_tickets_management.php');
    exit;
}

$ticket = get_ticket_by_id($conn, $ticket_id);
if (!$ticket) {
    header('Location: support_tickets_management.php');
    exit;
}

$message = '';
$message_type = '';

// Xử lý các hành động
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'update_status') {
        $result = update_ticket_status($conn, $ticket_id, $_POST['status']);
        $message = $result['message'];
        $message_type = $result['success'] ? 'success' : 'error';
        if ($result['success']) {
            $ticket = get_ticket_by_id($conn, $ticket_id);
        }
    } elseif ($_POST['action'] == 'assign') {
        $result = assign_ticket($conn, $ticket_id, $_POST['staff_id']);
        $message = $result['message'];
        $message_type = $result['success'] ? 'success' : 'error';
        if ($result['success']) {
            $ticket = get_ticket_by_id($conn, $ticket_id);
        }
    } elseif ($_POST['action'] == 'resolve') {
        $result = add_ticket_resolution($conn, $ticket_id, $_POST['resolution_notes']);
        $message = $result['message'];
        $message_type = $result['success'] ? 'success' : 'error';
        if ($result['success']) {
            $ticket = get_ticket_by_id($conn, $ticket_id);
        }
    } elseif ($_POST['action'] == 'close') {
        $result = close_ticket($conn, $ticket_id);
        $message = $result['message'];
        $message_type = $result['success'] ? 'success' : 'error';
        if ($result['success']) {
            $ticket = get_ticket_by_id($conn, $ticket_id);
        }
    }
}

// Lấy danh sách nhân viên
$staff_sql = "SELECT user_id, full_name FROM users WHERE role_id IS NOT NULL ORDER BY full_name";
$staff_result = $conn->query($staff_sql);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết Ticket #<?php echo $ticket_id; ?></title>
    <link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>
    <div class="admin-container">
        <nav class="admin-nav">
            <h2>Admin Panel</h2>
            <ul>
                <li><a href="support_tickets_management.php">← Quay lại</a></li>
            </ul>
        </nav>

        <div class="admin-content">
            <h1>Chi tiết Ticket #<?php echo $ticket_id; ?></h1>

            <?php if (!empty($message)) { ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php } ?>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin: 20px 0;">
                <!-- Thông tin cơ bản -->
                <div style="border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
                    <h3>Thông tin Ticket</h3>
                    <p><strong>ID:</strong> #<?php echo $ticket['ticket_id']; ?></p>
                    <p><strong>Khách hàng:</strong> <?php echo htmlspecialchars($ticket['customer_name'] ?? 'N/A'); ?></p>
                    <p><strong>Tiêu đề:</strong> <?php echo htmlspecialchars($ticket['title']); ?></p>
                    <p><strong>Loại:</strong> 
                        <?php 
                        $types = ['Warranty' => 'Bảo hành', 'Return' => 'Trả hàng', 'Replacement' => 'Đổi hàng', 'Refund' => 'Hoàn tiền', 'Consultation' => 'Tư vấn'];
                        echo $types[$ticket['issue_type']] ?? $ticket['issue_type'];
                        ?>
                    </p>
                    <p><strong>Độ ưu tiên:</strong>
                        <span style="padding: 3px 8px; border-radius: 3px; <?php echo ($ticket['priority'] == 'High') ? 'background-color: #dc3545; color: white;' : (($ticket['priority'] == 'Medium') ? 'background-color: #ffc107; color: black;' : 'background-color: #28a745; color: white;'); ?>">
                            <?php echo $ticket['priority']; ?>
                        </span>
                    </p>
                    <p><strong>Đơn hàng liên quan:</strong> <?php echo $ticket['order_id'] ? '#' . $ticket['order_id'] : 'Không'; ?></p>
                </div>

                <!-- Trạng thái & Giao việc -->
                <div style="border: 1px solid #ddd; padding: 15px; border-radius: 5px;">
                    <h3>Quản lý Ticket</h3>
                    
                    <!-- Cập nhật trạng thái -->
                    <form method="POST" style="margin-bottom: 15px;">
                        <input type="hidden" name="action" value="update_status">
                        <label><strong>Trạng thái:</strong></label>
                        <select name="status" required style="width: 100%; padding: 8px; margin-bottom: 10px;">
                            <?php
                            $statuses = ['Open' => 'Mở', 'In_Progress' => 'Đang xử lý', 'Waiting_Customer' => 'Chờ KH', 'Resolved' => 'Đã giải quyết', 'Closed' => 'Đã đóng'];
                            foreach ($statuses as $key => $value) {
                                $selected = ($ticket['status'] == $key) ? 'selected' : '';
                                echo '<option value="' . $key . '" ' . $selected . '>' . $value . '</option>';
                            }
                            ?>
                        </select>
                        <button type="submit" class="btn-edit">Cập nhật trạng thái</button>
                    </form>

                    <!-- Giao việc -->
                    <form method="POST" style="margin-bottom: 15px;">
                        <input type="hidden" name="action" value="assign">
                        <label><strong>Giao cho nhân viên:</strong></label>
                        <select name="staff_id" required style="width: 100%; padding: 8px; margin-bottom: 10px;">
                            <option value="">-- Chọn nhân viên --</option>
                            <?php
                            if ($staff_result) {
                                while ($staff = $staff_result->fetch_assoc()) {
                                    $selected = ($ticket['assigned_to'] == $staff['user_id']) ? 'selected' : '';
                                    echo '<option value="' . $staff['user_id'] . '" ' . $selected . '>' . htmlspecialchars($staff['full_name']) . '</option>';
                                }
                            }
                            ?>
                        </select>
                        <button type="submit" class="btn-edit">Giao việc</button>
                    </form>

                    <p><strong>Giao cho:</strong> <?php echo htmlspecialchars($ticket['assigned_staff_name'] ?? 'Chưa gán'); ?></p>
                    <p><strong>Ngày tạo:</strong> <?php echo date('d/m/Y H:i', strtotime($ticket['created_at'])); ?></p>
                    <p><strong>Giải quyết lúc:</strong> <?php echo $ticket['resolved_at'] ? date('d/m/Y H:i', strtotime($ticket['resolved_at'])) : 'Chưa giải quyết'; ?></p>
                </div>
            </div>

            <!-- Mô tả vấn đề -->
            <div style="border: 1px solid #ddd; padding: 15px; border-radius: 5px; margin: 20px 0;">
                <h3>Mô tả Vấn đề</h3>
                <div style="background-color: #f8f9fa; padding: 10px; border-radius: 3px; white-space: pre-wrap;">
                    <?php echo htmlspecialchars($ticket['description']); ?>
                </div>
            </div>

            <!-- Giải quyết -->
            <?php if ($ticket['status'] != 'Closed') { ?>
                <div style="border: 1px solid #ddd; padding: 15px; border-radius: 5px; margin: 20px 0;">
                    <h3>Ghi chú Giải quyết</h3>
                    <?php if ($ticket['resolution_notes']) { ?>
                        <div style="background-color: #f8f9fa; padding: 10px; border-radius: 3px; white-space: pre-wrap; margin-bottom: 15px;">
                            <?php echo htmlspecialchars($ticket['resolution_notes']); ?>
                        </div>
                    <?php } ?>

                    <?php if ($ticket['status'] != 'Resolved' && $ticket['status'] != 'Closed') { ?>
                        <form method="POST">
                            <input type="hidden" name="action" value="resolve">
                            <textarea name="resolution_notes" placeholder="Nhập ghi chú giải quyết..." required style="width: 100%; height: 100px; padding: 10px; border: 1px solid #ddd; border-radius: 3px; margin-bottom: 10px;"></textarea>
                            <button type="submit" class="btn-edit">Ghi nhận giải quyết</button>
                        </form>
                    <?php } ?>

                    <?php if ($ticket['status'] == 'Resolved' && $ticket['status'] != 'Closed') { ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="close">
                            <button type="submit" class="btn-edit">Đóng Ticket</button>
                        </form>
                    <?php } ?>
                </div>
            <?php } ?>

            <div style="margin-top: 20px;">
                <a href="support_tickets_management.php" class="btn-edit">← Quay lại</a>
            </div>
        </div>
    </div>
</body>
</html>
