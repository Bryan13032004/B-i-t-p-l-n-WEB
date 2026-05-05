<?php
// Quản lý Phân loại sản phẩm
require '../db_connection.php';
require '../admin_functions/categories_functions.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: index.php');
    exit;
}

$message = '';
$message_type = '';
$edit_category = null;

// Xử lý thêm/sửa/xóa phân loại
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add') {
            $result = add_category($conn, $_POST);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        } elseif ($_POST['action'] == 'edit') {
            $result = edit_category($conn, $_POST);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        } elseif ($_POST['action'] == 'delete') {
            $result = delete_category($conn, $_POST['category_id']);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        }
    }
}

// Lấy dữ liệu category để edit nếu có
if (isset($_GET['edit'])) {
    $category_id = intval($_GET['edit']);
    $edit_category = get_category_by_id($conn, $category_id);
}

// Lấy danh sách phân loại
$categories = get_all_categories($conn);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Phân loại sản phẩm</title>
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
                <li><a href="../index.html">Quay lại</a></li>
            </ul>
        </nav>

        <div class="admin-content">
            <h1>Quản lý Phân loại sản phẩm</h1>

            <?php if (!empty($message)) { ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php } ?>

            <div class="form-container">
                <h2><?php echo $edit_category ? 'Sửa phân loại' : 'Thêm phân loại mới'; ?></h2>
                <form method="POST">
                    <input type="hidden" name="action" value="<?php echo $edit_category ? 'edit' : 'add'; ?>">
                    <?php if ($edit_category) { ?>
                        <input type="hidden" name="category_id" value="<?php echo $edit_category['category_id']; ?>">
                    <?php } ?>
                    <input type="text" name="category_name" placeholder="Tên phân loại (Gọng, Tròng, Phụ kiện...)" value="<?php echo $edit_category ? htmlspecialchars($edit_category['category_name']) : ''; ?>" required>
                    <textarea name="description" placeholder="Mô tả phân loại" rows="3"><?php echo $edit_category ? htmlspecialchars($edit_category['description']) : ''; ?></textarea>
                    <button type="submit"><?php echo $edit_category ? 'Cập nhật' : 'Thêm mới'; ?></button>
                    <?php if ($edit_category) { ?>
                        <a href="categories_management.php" class="btn-cancel">Hủy</a>
                    <?php } ?>
                </form>
            </div>

            <div class="table-container">
                <h2>Danh sách phân loại</h2>
                <?php if ($categories && $categories->num_rows > 0) { ?>
                    <table>
                        <tr>
                            <th>ID</th>
                            <th>Tên phân loại</th>
                            <th>Mô tả</th>
                            <th>Ngày tạo</th>
                            <th>Hành động</th>
                        </tr>
                        <?php while ($cat = $categories->fetch_assoc()) { ?>
                            <tr>
                                <td><?php echo $cat['category_id']; ?></td>
                                <td><?php echo htmlspecialchars($cat['category_name']); ?></td>
                                <td><?php echo htmlspecialchars(substr($cat['description'] ?? '', 0, 50)) . (strlen($cat['description'] ?? '') > 50 ? '...' : ''); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($cat['created_at'])); ?></td>
                                <td>
                                    <a href="?edit=<?php echo $cat['category_id']; ?>" class="btn-edit">Sửa</a>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="category_id" value="<?php echo $cat['category_id']; ?>">
                                        <button type="submit" class="btn-delete" onclick="return confirm('Bạn chắc chắn muốn xóa?')">Xóa</button>
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
