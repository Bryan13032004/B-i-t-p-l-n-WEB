<?php
// Quản lý Vai trò
require '../db_connection.php';
require '../admin_functions/roles_functions.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../index.php');
    exit;
}

$message = '';
$message_type = '';
$edit_role = null;

// Xử lý thêm/sửa/xóa vai trò
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add') {
            $result = add_role($conn, $_POST);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        } elseif ($_POST['action'] == 'edit') {
            $result = edit_role($conn, $_POST);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        } elseif ($_POST['action'] == 'delete') {
            $result = delete_role($conn, $_POST['role_id']);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        }
    }
}

// Lấy dữ liệu role để edit nếu có
if (isset($_GET['edit'])) {
    $role_id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM roles WHERE role_id=?");
    $stmt->bind_param("i", $role_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $edit_role = $result->fetch_assoc();
    }
    $stmt->close();
}

// Lấy danh sách vai trò
$sql = "SELECT * FROM roles";
$roles = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Vai trò</title>
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
            <h1>Quản lý Vai trò</h1>

            <?php if (!empty($message)) { ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php } ?>

            <div class="form-container">
                <h2><?php echo $edit_role ? 'Sửa vai trò' : 'Thêm vai trò mới'; ?></h2>
                <form method="POST">
                    <input type="hidden" name="action" value="<?php echo $edit_role ? 'edit' : 'add'; ?>">
                    <?php if ($edit_role) { ?>
                        <input type="hidden" name="role_id" value="<?php echo $edit_role['role_id']; ?>">
                    <?php } ?>
                    <input type="text" name="role_name" placeholder="Tên vai trò" value="<?php echo $edit_role ? htmlspecialchars($edit_role['role_name']) : ''; ?>" required>
                    <textarea name="role_description" placeholder="Mô tả vai trò" rows="4"><?php echo $edit_role ? htmlspecialchars($edit_role['role_description']) : ''; ?></textarea>
                    <button type="submit"><?php echo $edit_role ? 'Cập nhật' : 'Thêm mới'; ?></button>
                    <?php if ($edit_role) { ?>
                        <a href="roles_management.php" class="btn-cancel">Hủy</a>
                    <?php } ?>
                </form>
            </div>

            <div class="table-container">
                <h2>Danh sách vai trò</h2>
                <?php if ($roles->num_rows > 0) { ?>
                    <table>
                        <tr>
                            <th>ID</th>
                            <th>Tên vai trò</th>
                            <th>Mô tả</th>
                            <th>Hành động</th>
                        </tr>
                        <?php while ($role = $roles->fetch_assoc()) { ?>
                            <tr>
                                <td><?php echo $role['role_id']; ?></td>
                                <td><?php echo htmlspecialchars($role['role_name']); ?></td>
                                <td><?php echo htmlspecialchars($role['role_description'] ?? ''); ?></td>
                                <td>
                                    <a href="?edit=<?php echo $role['role_id']; ?>" class="btn-edit">Sửa</a>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="role_id" value="<?php echo $role['role_id']; ?>">
                                        <button type="submit" class="btn-delete" onclick="return confirm('Bạn chắc chắn muốn xóa vai trò này?')">Xóa</button>
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
