<?php
// Quản lý Sản phẩm
require '../db_connection.php';
require '../admin_functions/categories_functions.php';
require '../admin_functions/products_functions.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: index.php');
    exit;
}

$message = '';
$message_type = '';
$edit_product = null;

// Xử lý thêm/sửa/xóa sản phẩm
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add') {
            $result = add_product($conn, $_POST);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        } elseif ($_POST['action'] == 'edit') {
            $result = edit_product($conn, $_POST);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        } elseif ($_POST['action'] == 'delete') {
            $result = delete_product($conn, $_POST['product_id']);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        }
    }
}

// Lấy dữ liệu product để edit nếu có
if (isset($_GET['edit'])) {
    $product_id = intval($_GET['edit']);
    $edit_product = get_product_by_id($conn, $product_id);
}

// Lấy danh sách danh mục
$categories = get_all_categories($conn);

// Lấy danh sách sản phẩm
$products = get_all_products($conn);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Sản phẩm</title>
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
            <h1>Quản lý Sản phẩm</h1>

            <?php if (!empty($message)) { ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php } ?>

            <div class="form-container">
                <h2><?php echo $edit_product ? 'Sửa sản phẩm' : 'Thêm sản phẩm mới'; ?></h2>
                <form method="POST">
                    <input type="hidden" name="action" value="<?php echo $edit_product ? 'edit' : 'add'; ?>">
                    <?php if ($edit_product) { ?>
                        <input type="hidden" name="product_id" value="<?php echo $edit_product['product_id']; ?>">
                    <?php } ?>
                    
                    <select name="category_id" required>
                        <option value="">-- Chọn phân loại --</option>
                        <?php 
                        $categories_list = get_all_categories($conn);
                        while ($cat = $categories_list->fetch_assoc()) {
                            $selected = ($edit_product && $edit_product['category_id'] == $cat['category_id']) ? 'selected' : '';
                            echo '<option value="' . $cat['category_id'] . '" ' . $selected . '>' . htmlspecialchars($cat['category_name']) . '</option>';
                        }
                        ?>
                    </select>
                    
                    <input type="text" name="product_name" placeholder="Tên sản phẩm" value="<?php echo $edit_product ? htmlspecialchars($edit_product['product_name']) : ''; ?>" required>
                    
                    <textarea name="description" placeholder="Mô tả sản phẩm" rows="3"><?php echo $edit_product ? htmlspecialchars($edit_product['description']) : ''; ?></textarea>
                    
                    <input type="number" step="0.01" name="base_price" placeholder="Giá sản phẩm (VND)" value="<?php echo $edit_product ? $edit_product['base_price'] : ''; ?>" required>
                    
                    <input type="text" name="image_url" placeholder="Link hình ảnh (URL)" value="<?php echo $edit_product ? htmlspecialchars($edit_product['image_url']) : ''; ?>">
                    
                    <?php if ($edit_product) { ?>
                        <select name="status">
                            <option value="Active" <?php echo ($edit_product['status'] == 'Active') ? 'selected' : ''; ?>>Hoạt động</option>
                            <option value="Inactive" <?php echo ($edit_product['status'] == 'Inactive') ? 'selected' : ''; ?>>Tạm dừng</option>
                        </select>
                    <?php } ?>
                    
                    <button type="submit"><?php echo $edit_product ? 'Cập nhật' : 'Thêm mới'; ?></button>
                    <?php if ($edit_product) { ?>
                        <a href="products_management.php" class="btn-cancel">Hủy</a>
                    <?php } ?>
                </form>
            </div>

            <div class="table-container">
                <h2>Danh sách sản phẩm</h2>
                <?php if ($products && $products->num_rows > 0) { ?>
                    <table>
                        <tr>
                            <th>ID</th>
                            <th>Tên sản phẩm</th>
                            <th>Phân loại</th>
                            <th>Giá (VND)</th>
                            <th>Trạng thái</th>
                            <th>Ngày tạo</th>
                            <th>Hành động</th>
                        </tr>
                        <?php while ($prod = $products->fetch_assoc()) { ?>
                            <tr>
                                <td><?php echo $prod['product_id']; ?></td>
                                <td><?php echo htmlspecialchars($prod['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($prod['category_name']); ?></td>
                                <td><?php echo number_format($prod['base_price'], 0, ',', '.'); ?></td>
                                <td>
                                    <span style="padding: 5px 10px; border-radius: 4px; <?php echo ($prod['status'] == 'Active') ? 'background-color: #d4edda; color: #155724;' : 'background-color: #f8d7da; color: #721c24;'; ?>">
                                        <?php echo ($prod['status'] == 'Active') ? 'Hoạt động' : 'Tạm dừng'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($prod['created_at'])); ?></td>
                                <td>
                                    <a href="?edit=<?php echo $prod['product_id']; ?>" class="btn-edit">Sửa</a>
                                    <a href="product_variants_management.php?product_id=<?php echo $prod['product_id']; ?>" class="btn-edit" style="background-color: #17a2b8; border-color: #17a2b8;">Biến thể</a>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="product_id" value="<?php echo $prod['product_id']; ?>">
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
