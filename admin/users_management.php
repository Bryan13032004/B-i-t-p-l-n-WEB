<?php
// Quản lý Nhân viên
require '../db_connection.php';
require '../admin_functions/users_functions.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../index.php');
    exit;
}

$message = '';
$message_type = '';
$edit_user = null;

// Xử lý thêm/sửa/xóa nhân viên và gán role
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add') {
            $result = add_user($conn, $_POST);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        } elseif ($_POST['action'] == 'edit') {
            $result = edit_user($conn, $_POST);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        } elseif ($_POST['action'] == 'delete') {
            $result = delete_user($conn, $_POST['user_id']);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        } elseif ($_POST['action'] == 'assign_role') {
            $result = assign_role($conn, $_POST['user_id'], $_POST['role_id']);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        }
    }
}

// Lấy dữ liệu user để edit nếu có
if (isset($_GET['edit'])) {
    $user_id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id=?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $edit_user = $result->fetch_assoc();
    }
    $stmt->close();
}

// Lấy danh sách nhân viên
$sql = "SELECT u.user_id, u.username, u.email, u.full_name, r.role_name, ur.role_id
        FROM users u 
        LEFT JOIN user_roles ur ON u.user_id = ur.user_id 
        LEFT JOIN roles r ON ur.role_id = r.role_id";
$users = $conn->query($sql);

// Lấy danh sách vai trò
$roles_sql = "SELECT * FROM roles ORDER BY role_name";
$roles_result = $conn->query($roles_sql);
$roles = [];
if ($roles_result->num_rows > 0) {
    while ($row = $roles_result->fetch_assoc()) {
        $roles[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Nhân viên</title>
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
            <h1>Quản lý Nhân viên</h1>

            <?php if (!empty($message)) { ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php } ?>

            <div class="form-container">
                <h2><?php echo $edit_user ? 'Sửa nhân viên' : 'Thêm nhân viên mới'; ?></h2>
                <form method="POST">
                    <input type="hidden" name="action" value="<?php echo $edit_user ? 'edit' : 'add'; ?>">
                    <?php if ($edit_user) { ?>
                        <input type="hidden" name="user_id" value="<?php echo $edit_user['user_id']; ?>">
                    <?php } ?>
                    
                    <?php if (!$edit_user) { ?>
                        <input type="text" name="username" placeholder="Tên đăng nhập" required>
                    <?php } ?>
                    
                    <input type="email" name="email" placeholder="Email" value="<?php echo $edit_user ? htmlspecialchars($edit_user['email']) : ''; ?>" required>
                    
                    <?php if (!$edit_user) { ?>
                        <input type="password" name="password" placeholder="Mật khẩu" required>
                    <?php } ?>
                    
                    <input type="text" name="full_name" placeholder="Họ và tên" value="<?php echo $edit_user ? htmlspecialchars($edit_user['full_name']) : ''; ?>" required>
                    <button type="submit"><?php echo $edit_user ? 'Cập nhật' : 'Thêm mới'; ?></button>
                    <?php if ($edit_user) { ?>
                        <a href="users_management.php" class="btn-cancel">Hủy</a>
                    <?php } ?>
                </form>
            </div>

            <div class="table-container">
                <h2>Danh sách nhân viên</h2>
                <?php if ($users->num_rows > 0) { ?>
                    <table>
                        <tr>
                            <th>ID</th>
                            <th>Tên đăng nhập</th>
                            <th>Email</th>
                            <th>Họ tên</th>
                            <th>Vai trò</th>
                            <th>Hành động</th>
                        </tr>
                        <?php while ($user = $users->fetch_assoc()) { ?>
                            <tr>
                                <td><?php echo $user['user_id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td>
                                    <?php if (!empty($roles)) { ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="assign_role">
                                            <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                            <select name="role_id" onchange="this.form.submit()">
                                                <option value="">-- Chọn vai trò --</option>
                                                <?php foreach ($roles as $role) { ?>
                                                    <option value="<?php echo $role['role_id']; ?>" <?php echo ($user['role_id'] == $role['role_id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($role['role_name']); ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        </form>
                                    <?php } else { ?>
                                        <span><?php echo $user['role_name'] ?? 'Chưa gán'; ?></span>
                                    <?php } ?>
                                </td>
                                <td>
                                    <a href="?edit=<?php echo $user['user_id']; ?>" class="btn-edit">Sửa</a>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                        <button type="submit" class="btn-delete" onclick="return confirm('Bạn chắc chắn muốn xóa nhân viên này?')">Xóa</button>
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
