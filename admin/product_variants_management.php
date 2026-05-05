<?php
// Quản lý Biến thể sản phẩm (Màu sắc, Kích thước, SKU, Tồn kho)
require '../db_connection.php';
require '../admin_functions/products_functions.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: index.php');
    exit;
}

$product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
if ($product_id <= 0) {
    header('Location: products_management.php');
    exit;
}

// Kiểm tra sản phẩm tồn tại
$product = get_product_by_id($conn, $product_id);
if (!$product) {
    header('Location: products_management.php');
    exit;
}

$message = '';
$message_type = '';
$edit_variant = null;

// Xử lý thêm/sửa/xóa biến thể
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add') {
            $_POST['product_id'] = $product_id;
            $result = add_product_variant($conn, $_POST);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        } elseif ($_POST['action'] == 'edit') {
            $result = edit_product_variant($conn, $_POST);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        } elseif ($_POST['action'] == 'delete') {
            $result = delete_product_variant($conn, $_POST['variant_id']);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        }
    }
}

// Lấy dữ liệu variant để edit nếu có
if (isset($_GET['edit'])) {
    $variant_id = intval($_GET['edit']);
    $edit_variant = get_variant_by_id($conn, $variant_id);
}

// Lấy danh sách biến thể
$variants = get_variants_by_product($conn, $product_id);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Biến thể - <?php echo htmlspecialchars($product['product_name']); ?></title>
    <link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>
    <div class="admin-container">
        <nav class="admin-nav">
            <h2>Admin Panel</h2>
            <ul>
                <li><a href="dashboard.php">Trang Chủ</a></li>
                <li><a href="products_management.php">Quản lý Sản phẩm</a></li>
                <li><a href="categories_management.php">Quản lý Phân loại</a></li>
                <li><a href="../index.html">Quay lại</a></li>
            </ul>
        </nav>

        <div class="admin-content">
            <h1>Biến thể - <?php echo htmlspecialchars($product['product_name']); ?></h1>
            <p style="color: #666; margin-bottom: 20px;">Quản lý màu sắc, kích thước, SKU, giá và tồn kho</p>

            <?php if (!empty($message)) { ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php } ?>

            <div class="form-container">
                <h2><?php echo $edit_variant ? 'Sửa biến thể' : 'Thêm biến thể mới'; ?></h2>
                <form method="POST">
                    <input type="hidden" name="action" value="<?php echo $edit_variant ? 'edit' : 'add'; ?>">
                    <?php if ($edit_variant) { ?>
                        <input type="hidden" name="variant_id" value="<?php echo $edit_variant['variant_id']; ?>">
                    <?php } ?>
                    
                    <input type="text" name="sku" placeholder="SKU (Mã sản phẩm)" value="<?php echo $edit_variant ? htmlspecialchars($edit_variant['sku']) : ''; ?>" required>
                    
                    <input type="text" name="color" placeholder="Màu sắc (ví dụ: Đen, Trắng, Xanh...)" value="<?php echo $edit_variant ? htmlspecialchars($edit_variant['color']) : ''; ?>">
                    
                    <input type="text" name="size" placeholder="Kích thước (ví dụ: S, M, L hoặc số)" value="<?php echo $edit_variant ? htmlspecialchars($edit_variant['size']) : ''; ?>">
                    
                    <input type="number" name="quantity_in_stock" placeholder="Số lượng tồn kho" value="<?php echo $edit_variant ? $edit_variant['quantity_in_stock'] : '0'; ?>" min="0" required>
                    
                    <input type="number" step="0.01" name="price" placeholder="Giá bán (VND)" value="<?php echo $edit_variant ? $edit_variant['price'] : $product['base_price']; ?>" required>
                    
                    <button type="submit"><?php echo $edit_variant ? 'Cập nhật' : 'Thêm mới'; ?></button>
                    <?php if ($edit_variant) { ?>
                        <a href="product_variants_management.php?product_id=<?php echo $product_id; ?>" class="btn-cancel">Hủy</a>
                    <?php } ?>
                </form>
            </div>

            <div class="table-container">
                <h2>Danh sách biến thể</h2>
                <?php if ($variants && $variants->num_rows > 0) { ?>
                    <table>
                        <tr>
                            <th>ID</th>
                            <th>SKU</th>
                            <th>Màu sắc</th>
                            <th>Kích thước</th>
                            <th>Tồn kho</th>
                            <th>Giá (VND)</th>
                            <th>Ngày tạo</th>
                            <th>Hành động</th>
                        </tr>
                        <?php while ($var = $variants->fetch_assoc()) { ?>
                            <tr>
                                <td><?php echo $var['variant_id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($var['sku']); ?></strong></td>
                                <td><?php echo htmlspecialchars($var['color'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($var['size'] ?? '-'); ?></td>
                                <td>
                                    <span style="padding: 5px 10px; border-radius: 4px; <?php echo ($var['quantity_in_stock'] > 0) ? 'background-color: #d4edda; color: #155724;' : 'background-color: #f8d7da; color: #721c24;'; ?>">
                                        <?php echo $var['quantity_in_stock']; ?> cái
                                    </span>
                                </td>
                                <td><?php echo number_format($var['price'], 0, ',', '.'); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($var['created_at'])); ?></td>
                                <td>
                                    <a href="?product_id=<?php echo $product_id; ?>&edit=<?php echo $var['variant_id']; ?>" class="btn-edit">Sửa</a>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="variant_id" value="<?php echo $var['variant_id']; ?>">
                                        <button type="submit" class="btn-delete" onclick="return confirm('Bạn chắc chắn muốn xóa?')">Xóa</button>
                                    </form>
                                </td>
                            </tr>
                        <?php } ?>
                    </table>
                <?php } else { ?>
                    <p>Chưa có biến thể nào. Hãy thêm biến thể đầu tiên!</p>
                <?php } ?>
            </div>

            <div style="margin-top: 20px; text-align: center;">
                <a href="products_management.php" style="color: #667eea; text-decoration: underline;">← Quay lại quản lý sản phẩm</a>
            </div>
        </div>
    </div>
</body>
</html>
