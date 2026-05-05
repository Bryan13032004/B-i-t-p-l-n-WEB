<?php
// Quản lý Khuyến mại
require '../db_connection.php';
require '../admin_functions/promotions_functions.php';

session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: index.php');
    exit;
}

$message = '';
$message_type = '';

// Xử lý form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add') {
            $result = add_promotion($conn, $_POST);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        } elseif ($_POST['action'] == 'edit') {
            $result = edit_promotion($conn, $_POST['promo_id'], $_POST);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        } elseif ($_POST['action'] == 'delete') {
            $result = delete_promotion($conn, $_POST['promo_id']);
            $message = $result['message'];
            $message_type = $result['success'] ? 'success' : 'error';
        }
    }
}

// Lấy danh sách khuyến mại
$promotions = get_all_promotions($conn);

// Lấy dữ liệu edit nếu có
$edit_data = null;
if (isset($_GET['edit'])) {
    $edit_data = get_promotion_by_id($conn, intval($_GET['edit']));
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Khuyến mại</title>
    <link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>
    <div class="admin-container">
        <nav class="admin-nav">
            <h2>Admin Panel</h2>
            <ul>
                <li><a href="dashboard.php">Trang Chủ</a></li>
                <li><a href="../index.html">Quay lại</a></li>
            </ul>
        </nav>

        <div class="admin-content">
            <h1>Quản lý Khuyến mại</h1>

            <?php if (!empty($message)) { ?>
                <div class="message <?php echo $message_type; ?>">
                    <?php echo $message; ?>
                </div>
            <?php } ?>

            <!-- Form Thêm/Sửa -->
            <div style="border: 1px solid #ddd; padding: 20px; border-radius: 5px; margin-bottom: 30px; background-color: #f8f9fa;">
                <h2><?php echo $edit_data ? 'Sửa Khuyến mại' : 'Thêm Khuyến mại'; ?></h2>
                
                <form method="POST" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <input type="hidden" name="action" value="<?php echo $edit_data ? 'edit' : 'add'; ?>">
                    
                    <?php if ($edit_data) { ?>
                        <input type="hidden" name="promo_id" value="<?php echo $edit_data['promo_id']; ?>">
                    <?php } ?>

                    <div>
                        <label>Mã khuyến mại: <span style="color: red;">*</span></label>
                        <input type="text" name="promo_code" 
                               value="<?php echo $edit_data ? htmlspecialchars($edit_data['promo_code']) : ''; ?>" 
                               required <?php echo $edit_data ? 'disabled' : ''; ?>>
                    </div>

                    <div>
                        <label>Loại chiết khấu: <span style="color: red;">*</span></label>
                        <select name="discount_type" required>
                            <option value="Percentage" <?php echo ($edit_data && $edit_data['discount_type'] == 'Percentage') ? 'selected' : ''; ?>>Phần trăm (%)</option>
                            <option value="Fixed" <?php echo ($edit_data && $edit_data['discount_type'] == 'Fixed') ? 'selected' : ''; ?>>Số tiền cố định</option>
                        </select>
                    </div>

                    <div>
                        <label>Giá trị chiết khấu: <span style="color: red;">*</span></label>
                        <input type="number" name="discount_value" step="0.01" 
                               value="<?php echo $edit_data ? htmlspecialchars($edit_data['discount_value']) : ''; ?>" 
                               required>
                    </div>

                    <div>
                        <label>Giá trị đơn tối thiểu (VND):</label>
                        <input type="number" name="min_order_value" step="0.01" 
                               value="<?php echo $edit_data ? htmlspecialchars($edit_data['min_order_value']) : '0'; ?>">
                    </div>

                    <div>
                        <label>Ngày bắt đầu:</label>
                        <input type="date" name="valid_from" 
                               value="<?php echo $edit_data ? $edit_data['valid_from'] : date('Y-m-d'); ?>">
                    </div>

                    <div>
                        <label>Ngày kết thúc:</label>
                        <input type="date" name="valid_to" 
                               value="<?php echo $edit_data ? $edit_data['valid_to'] : date('Y-m-d', strtotime('+30 days')); ?>">
                    </div>

                    <div>
                        <label>Số lượt sử dụng tối đa:</label>
                        <input type="number" name="max_uses" 
                               value="<?php echo $edit_data ? htmlspecialchars($edit_data['max_uses']) : '999'; ?>">
                    </div>

                    <div style="display: flex; gap: 10px; grid-column: 1 / -1;">
                        <button type="submit" class="btn-edit">
                            <?php echo $edit_data ? '✏️ Cập nhật' : '➕ Thêm'; ?>
                        </button>
                        
                        <?php if ($edit_data) { ?>
                            <a href="promotions_management.php" class="btn-edit" style="background-color: #6c757d;">❌ Hủy</a>
                        <?php } ?>
                    </div>
                </form>
            </div>

            <!-- Danh sách khuyến mại -->
            <div class="table-container">
                <h2>Danh sách khuyến mại</h2>
                <?php if ($promotions && $promotions->num_rows > 0) { ?>
                    <table>
                        <tr>
                            <th>Mã</th>
                            <th>Loại</th>
                            <th>Giá trị</th>
                            <th>Tối thiểu</th>
                            <th>Ngày bắt đầu</th>
                            <th>Ngày kết thúc</th>
                            <th>Lượt sử dụng</th>
                            <th>Tối đa</th>
                            <th>Hành động</th>
                        </tr>
                        <?php while ($promo = $promotions->fetch_assoc()) { ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($promo['promo_code']); ?></strong></td>
                                <td><?php echo $promo['discount_type'] == 'Percentage' ? '%' : 'Cố định'; ?></td>
                                <td>
                                    <?php 
                                    if ($promo['discount_type'] == 'Percentage') {
                                        echo $promo['discount_value'] . '%';
                                    } else {
                                        echo number_format($promo['discount_value'], 0, ',', '.') . ' VND';
                                    }
                                    ?>
                                </td>
                                <td><?php echo number_format($promo['min_order_value'], 0, ',', '.'); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($promo['valid_from'])); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($promo['valid_to'])); ?></td>
                                <td><?php echo $promo['current_uses']; ?></td>
                                <td><?php echo $promo['max_uses']; ?></td>
                                <td>
                                    <a href="?edit=<?php echo $promo['promo_id']; ?>" class="btn-edit">✏️</a>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Xóa khuyến mại này?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="promo_id" value="<?php echo $promo['promo_id']; ?>">
                                        <button type="submit" class="btn-edit" style="background-color: #dc3545;">🗑️</button>
                                    </form>
                                </td>
                            </tr>
                        <?php } ?>
                    </table>
                <?php } else { ?>
                    <p>Chưa có khuyến mại nào</p>
                <?php } ?>
            </div>
        </div>
    </div>
</body>
</html>
