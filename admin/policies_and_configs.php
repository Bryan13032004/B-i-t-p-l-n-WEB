<?php
// Chính sách & Cấu hình
require '../db_connection.php';
require '../admin_functions/policies_functions.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../index.php');
    exit;
}

$message = '';
$message_type = '';
$edit_policy = null;

// Xử lý thêm/sửa/xóa chính sách
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add') {
            $result = add_policy($conn, $_POST);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        } elseif ($_POST['action'] == 'edit') {
            $result = edit_policy($conn, $_POST);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        } elseif ($_POST['action'] == 'delete') {
            $result = delete_policy($conn, $_POST['policy_id']);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        }
    }
}

// Lấy dữ liệu policy để edit nếu có
if (isset($_GET['edit'])) {
    $policy_id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM policies_and_configs WHERE policy_id=?");
    $stmt->bind_param("i", $policy_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $edit_policy = $result->fetch_assoc();
    }
    $stmt->close();
}

// Lấy danh sách chính sách
$sql = "SELECT * FROM policies_and_configs";
$policies = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chính sách & Cấu hình</title>
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
                <li><a href="policies_and_configs.php">Chính sách & Cấu hình</a></li>
                <li><a href="../index.php">Quay lại</a></li>
            </ul>
        </nav>

        <div class="admin-content">
            <h1>Chính sách & Cấu hình</h1>

            <?php if (!empty($message)) { ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php } ?>

            <div class="form-container">
                <h2><?php echo $edit_policy ? 'Sửa chính sách' : 'Thêm chính sách mới'; ?></h2>
                <form method="POST">
                    <input type="hidden" name="action" value="<?php echo $edit_policy ? 'edit' : 'add'; ?>">
                    <?php if ($edit_policy) { ?>
                        <input type="hidden" name="policy_id" value="<?php echo $edit_policy['policy_id']; ?>">
                    <?php } ?>
                    <input type="text" name="policy_name" placeholder="Tên chính sách" value="<?php echo $edit_policy ? htmlspecialchars($edit_policy['policy_name']) : ''; ?>" required>
                    <textarea name="policy_description" placeholder="Mô tả chính sách" rows="4" required><?php echo $edit_policy ? htmlspecialchars($edit_policy['policy_description']) : ''; ?></textarea>
                    <input type="number" name="warranty_period" placeholder="Thời gian bảo hành (tháng)" value="<?php echo $edit_policy ? ($edit_policy['warranty_period'] ?? '') : ''; ?>">
                    <input type="number" name="return_period" placeholder="Thời gian đối trả (ngày)" value="<?php echo $edit_policy ? ($edit_policy['return_period'] ?? '') : ''; ?>">
                    <button type="submit"><?php echo $edit_policy ? 'Cập nhật' : 'Thêm mới'; ?></button>
                    <?php if ($edit_policy) { ?>
                        <a href="policies_and_configs.php" class="btn-cancel">Hủy</a>
                    <?php } ?>
                </form>
            </div>

            <div class="table-container">
                <h2>Danh sách chính sách</h2>
                <?php if ($policies->num_rows > 0) { ?>
                    <table>
                        <tr>
                            <th>ID</th>
                            <th>Tên chính sách</th>
                            <th>Mô tả</th>
                            <th>Bảo hành (tháng)</th>
                            <th>Đối trả (ngày)</th>
                            <th>Hành động</th>
                        </tr>
                        <?php while ($policy = $policies->fetch_assoc()) { ?>
                            <tr>
                                <td><?php echo $policy['policy_id']; ?></td>
                                <td><?php echo htmlspecialchars($policy['policy_name']); ?></td>
                                <td><?php echo htmlspecialchars(substr($policy['policy_description'], 0, 50)) . (strlen($policy['policy_description']) > 50 ? '...' : ''); ?></td>
                                <td><?php echo $policy['warranty_period'] ?? '-'; ?></td>
                                <td><?php echo $policy['return_period'] ?? '-'; ?></td>
                                <td>
                                    <a href="?edit=<?php echo $policy['policy_id']; ?>" class="btn-edit">Sửa</a>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="policy_id" value="<?php echo $policy['policy_id']; ?>">
                                        <button type="submit" class="btn-delete" onclick="return confirm('Bạn chắc chắn muốn xóa chính sách này?')">Xóa</button>
                                    </form>
                                </td>
                            </tr>
                        <?php } ?>
                    </table>
                <?php } else { ?>
                    <p>Không có dữ liệu</p>
                <?php } ?>
            </div>
        </div>
    </div>
</body>
</html>
