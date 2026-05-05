<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION['customer_id'])) {
    header('Location: customer_auth.php');
    exit;
}

$customer_id = $_SESSION['customer_id'];
$message = '';

// Xóa sản phẩm khỏi giỏ
if (isset($_GET['remove'])) {
    $cart_id = intval($_GET['remove']);
    $stmt = $conn->prepare("DELETE FROM cart WHERE cart_id = ? AND customer_id = ?");
    $stmt->bind_param("ii", $cart_id, $customer_id);
    $stmt->execute();
    $stmt->close();
    header('Location: cart.php');
    exit;
}

// Cập nhật số lượng
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update'])) {
    foreach ($_POST['qty'] as $cart_id => $qty) {
        $qty = max(1, intval($qty));
        $cart_id = intval($cart_id);
        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE cart_id = ? AND customer_id = ?");
        $stmt->bind_param("iii", $qty, $cart_id, $customer_id);
        $stmt->execute();
        $stmt->close();
    }
    $message = 'Đã cập nhật giỏ hàng!';
}

// Xử lý đặt hàng
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['checkout'])) {
    $address  = trim($_POST['address'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $notes    = trim($_POST['notes'] ?? '');

    if (empty($address) || empty($phone)) {
        $message = 'Vui lòng nhập địa chỉ và số điện thoại!';
    } else {
        // Lấy thông tin khách hàng
        $stmt = $conn->prepare("SELECT * FROM customers WHERE customer_id = ?");
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $customer = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Lấy giỏ hàng
        $stmt = $conn->prepare("SELECT c.*, pv.price, pv.sku, p.product_name FROM cart c JOIN product_variants pv ON c.variant_id = pv.variant_id JOIN products p ON pv.product_id = p.product_id WHERE c.customer_id = ?");
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $cart_items = $stmt->get_result();
        $stmt->close();

        $total = 0;
        $items = [];
        while ($item = $cart_items->fetch_assoc()) {
            $total += $item['price'] * $item['quantity'];
            $items[] = $item;
        }

        if (!empty($items)) {
            // Tạo đơn hàng
            $stmt = $conn->prepare("INSERT INTO orders (customer_id, customer_name, customer_email, customer_phone, shipping_address, order_type, status, total_price, notes) VALUES (?, ?, ?, ?, ?, 'Ready_Stock', 'Pending', ?, ?)");
            $stmt->bind_param("issssds", $customer_id, $customer['customer_name'], $customer['email'], $phone, $address, $total, $notes);
            $stmt->execute();
            $order_id = $conn->insert_id;
            $stmt->close();

            // Thêm order items
            foreach ($items as $item) {
                $item_total = $item['price'] * $item['quantity'];
                $stmt = $conn->prepare("INSERT INTO order_items (order_id, variant_id, sku, product_name, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iissids", $order_id, $item['variant_id'], $item['sku'], $item['product_name'], $item['quantity'], $item['price'], $item_total);
                $stmt->execute();
                $stmt->close();
            }

            // Xóa giỏ hàng
            $stmt = $conn->prepare("DELETE FROM cart WHERE customer_id = ?");
            $stmt->bind_param("i", $customer_id);
            $stmt->execute();
            $stmt->close();

            header("Location: cart.php?order_success=$order_id");
            exit;
        }
    }
}

// Lấy giỏ hàng
$stmt = $conn->prepare("
    SELECT c.cart_id, c.quantity,
           pv.variant_id, pv.sku, pv.color, pv.size, pv.price, pv.quantity_in_stock,
           p.product_id, p.product_name, p.image_url
    FROM cart c
    JOIN product_variants pv ON c.variant_id = pv.variant_id
    JOIN products p ON pv.product_id = p.product_id
    WHERE c.customer_id = ?
    ORDER BY c.created_at DESC
");
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$cart_items = $stmt->get_result();
$stmt->close();

$items = [];
$total = 0;
while ($row = $cart_items->fetch_assoc()) {
    $items[] = $row;
    $total += $row['price'] * $row['quantity'];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ hàng — VISIO</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .cart-page { max-width: 1100px; margin: 6rem auto 4rem; padding: 0 2rem; display: grid; grid-template-columns: 2fr 1fr; gap: 2.5rem; align-items: start; }
        h1.page-title { font-family: 'Cormorant Garamond', serif; font-size: 2.5rem; font-weight: 300; margin-bottom: 1.5rem; }
        .cart-table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 12px rgba(26,23,20,0.08); }
        .cart-table th { padding: 1rem; text-align: left; font-size: 0.75rem; letter-spacing: 0.1em; text-transform: uppercase; color: #7a6050; background: #f5f0e8; border-bottom: 1px solid #e8dcc8; }
        .cart-table td { padding: 1rem; border-bottom: 1px solid #f5f0e8; vertical-align: middle; }
        .cart-item-img { width: 70px; height: 70px; object-fit: cover; border-radius: 4px; }
        .cart-item-name { font-family: 'Cormorant Garamond', serif; font-size: 1rem; color: #1a1714; }
        .cart-item-variant { font-size: 0.78rem; color: #7a6050; margin-top: 2px; }
        .qty-input { width: 55px; text-align: center; padding: 0.4rem; border: 1px solid #e8dcc8; border-radius: 4px; }
        .remove-btn { color: #dc3545; text-decoration: none; font-size: 0.85rem; }
        .remove-btn:hover { text-decoration: underline; }
        .update-btn { padding: 0.5rem 1.2rem; background: none; border: 1px solid #1a1714; border-radius: 4px; cursor: pointer; font-family: inherit; font-size: 0.82rem; margin-top: 1rem; }
        .summary-box { background: #fff; border-radius: 8px; padding: 1.5rem; box-shadow: 0 2px 12px rgba(26,23,20,0.08); position: sticky; top: 6rem; }
        .summary-box h2 { font-family: 'Cormorant Garamond', serif; font-size: 1.5rem; font-weight: 400; margin-bottom: 1.2rem; }
        .summary-row { display: flex; justify-content: space-between; font-size: 0.9rem; margin-bottom: 0.8rem; color: #4a3728; }
        .summary-total { display: flex; justify-content: space-between; font-size: 1.1rem; font-weight: 600; color: #1a1714; border-top: 1px solid #e8dcc8; padding-top: 1rem; margin-top: 0.5rem; }
        .form-group { margin-bottom: 1rem; }
        .form-group label { display: block; font-size: 0.82rem; font-weight: 500; color: #4a3728; margin-bottom: 0.4rem; }
        .form-group input, .form-group textarea { width: 100%; padding: 0.65rem 0.9rem; border: 1.5px solid #e8dcc8; border-radius: 6px; font-family: inherit; font-size: 0.9rem; }
        .form-group input:focus, .form-group textarea:focus { outline: none; border-color: #c8a96e; }
        .btn-order { width: 100%; padding: 1rem; background: #1a1714; color: #f5f0e8; border: none; border-radius: 6px; font-size: 0.9rem; font-weight: 500; cursor: pointer; font-family: inherit; letter-spacing: 0.06em; text-transform: uppercase; margin-top: 0.5rem; }
        .btn-order:hover { background: #a07840; }
        .empty-cart { text-align: center; padding: 4rem 2rem; background: #fff; border-radius: 8px; box-shadow: 0 2px 12px rgba(26,23,20,0.08); }
        .success-box { background: #d4edda; color: #155724; padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem; }
        .msg { padding: 0.8rem 1rem; border-radius: 6px; margin-bottom: 1rem; font-size: 0.9rem; background: #d4edda; color: #155724; }
        @media(max-width:768px) { .cart-page { grid-template-columns: 1fr; margin-top: 5rem; } }
    </style>
</head>
<body>

<nav>
    <a href="index.php" class="nav-logo">VISIO<span>.</span></a>
    <ul class="nav-links">
        <li><a href="index.php#products">Sản Phẩm</a></li>
        <li><a href="TryonVR/index.html">Thử Kính Ảo</a></li>
    </ul>
    <div style="display:flex;gap:1rem;align-items:center;">
        <span style="font-size:0.85rem;color:#4a3728;">👤 <?php echo htmlspecialchars($_SESSION['customer_name']); ?></span>
        <a href="logout.php" style="font-size:0.82rem;color:#7a6050;text-decoration:none;">Đăng xuất</a>
    </div>
</nav>

<div style="max-width:1100px;margin:6rem auto 0;padding:0 2rem;">
    <h1 class="page-title">Giỏ hàng</h1>
</div>

<?php if (isset($_GET['order_success'])): ?>
    <div style="max-width:1100px;margin:0 auto 2rem;padding:0 2rem;">
        <div class="success-box">
            ✅ <strong>Đặt hàng thành công!</strong> Mã đơn hàng: #<?php echo intval($_GET['order_success']); ?>
            <br><small>Chúng tôi sẽ liên hệ xác nhận trong thời gian sớm nhất.</small>
            <br><a href="index.php" style="color:#155724;">← Tiếp tục mua sắm</a>
        </div>
    </div>
<?php endif; ?>

<?php if ($message): ?>
    <div style="max-width:1100px;margin:0 auto 1rem;padding:0 2rem;">
        <div class="msg"><?php echo htmlspecialchars($message); ?></div>
    </div>
<?php endif; ?>

<div class="cart-page">
    <!-- Giỏ hàng -->
    <div>
        <?php if (empty($items)): ?>
            <div class="empty-cart">
                <p style="font-size:3rem;">🛒</p>
                <p style="font-size:1.2rem;color:#7a6050;margin:1rem 0;">Giỏ hàng trống!</p>
                <a href="index.php" class="btn-primary">Tiếp tục mua sắm</a>
            </div>
        <?php else: ?>
            <form method="POST">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th>Giá</th>
                            <th>Số lượng</th>
                            <th>Thành tiền</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <div style="display:flex;gap:1rem;align-items:center;">
                                        <img class="cart-item-img"
                                             src="<?php echo $item['image_url'] ?: 'img/product-1.jpg'; ?>"
                                             alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                             onerror="this.src='img/product-1.jpg'">
                                        <div>
                                            <div class="cart-item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                            <div class="cart-item-variant"><?php echo htmlspecialchars($item['color'] . ' - Size ' . $item['size']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo number_format($item['price'], 0, ',', '.'); ?> ₫</td>
                                <td>
                                    <input type="number" name="qty[<?php echo $item['cart_id']; ?>]"
                                           class="qty-input" value="<?php echo $item['quantity']; ?>" min="1"
                                           max="<?php echo $item['quantity_in_stock']; ?>">
                                </td>
                                <td><strong><?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?> ₫</strong></td>
                                <td>
                                    <a href="?remove=<?php echo $item['cart_id']; ?>" class="remove-btn"
                                       onclick="return confirm('Xóa sản phẩm này?')">✕</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <button type="submit" name="update" class="update-btn">Cập nhật giỏ hàng</button>
            </form>
        <?php endif; ?>
    </div>

    <!-- Thanh toán -->
    <?php if (!empty($items)): ?>
    <div class="summary-box">
        <h2>Đặt hàng</h2>
        <div class="summary-row">
            <span>Tạm tính</span>
            <span><?php echo number_format($total, 0, ',', '.'); ?> ₫</span>
        </div>
        <div class="summary-row">
            <span>Phí vận chuyển</span>
            <span>Miễn phí</span>
        </div>
        <div class="summary-total">
            <span>Tổng cộng</span>
            <span><?php echo number_format($total, 0, ',', '.'); ?> ₫</span>
        </div>

        <form method="POST" style="margin-top:1.5rem;">
            <div class="form-group">
                <label>Số điện thoại *</label>
                <input type="text" name="phone" placeholder="0901234567" required
                       value="<?php echo htmlspecialchars($_SESSION['customer_phone'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label>Địa chỉ giao hàng *</label>
                <input type="text" name="address" placeholder="Số nhà, đường, phường, quận, tỉnh" required>
            </div>
            <div class="form-group">
                <label>Ghi chú</label>
                <textarea name="notes" rows="2" placeholder="Ghi chú cho đơn hàng..."></textarea>
            </div>
            <button type="submit" name="checkout" class="btn-order">Đặt hàng ngay</button>
        </form>
    </div>
    <?php endif; ?>
</div>

</body>
</html>
