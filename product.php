<?php
session_start();
require 'db_connection.php';

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($product_id <= 0) {
    header('Location: index.php');
    exit;
}

// Lấy thông tin sản phẩm
$stmt = $conn->prepare("SELECT p.*, c.category_name FROM products p JOIN categories c ON p.category_id = c.category_id WHERE p.product_id = ? AND p.status = 'Active'");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    header('Location: index.php');
    exit;
}

// Lấy biến thể sản phẩm
$stmt = $conn->prepare("SELECT * FROM product_variants WHERE product_id = ? ORDER BY color, size");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$variants_result = $stmt->get_result();
$variants = [];
while ($row = $variants_result->fetch_assoc()) {
    $variants[] = $row;
}
$stmt->close();

// Lấy sản phẩm liên quan
$stmt = $conn->prepare("SELECT p.*, (SELECT MIN(pv.price) FROM product_variants pv WHERE pv.product_id = p.product_id) as min_price FROM products p WHERE p.category_id = ? AND p.product_id != ? AND p.status = 'Active' LIMIT 4");
$stmt->bind_param("ii", $product['category_id'], $product_id);
$stmt->execute();
$related = $stmt->get_result();
$stmt->close();

// Xử lý thêm vào giỏ
$cart_message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['customer_id'])) {
        header('Location: customer_auth.php');
        exit;
    }
    $variant_id = intval($_POST['variant_id']);
    $quantity   = intval($_POST['quantity'] ?? 1);
    $customer_id = $_SESSION['customer_id'];

    // Kiểm tra đã có trong giỏ chưa
    $stmt = $conn->prepare("SELECT cart_id, quantity FROM cart WHERE customer_id = ? AND variant_id = ?");
    $stmt->bind_param("ii", $customer_id, $variant_id);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existing) {
        $new_qty = $existing['quantity'] + $quantity;
        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE cart_id = ?");
        $stmt->bind_param("ii", $new_qty, $existing['cart_id']);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conn->prepare("INSERT INTO cart (customer_id, variant_id, quantity) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $customer_id, $variant_id, $quantity);
        $stmt->execute();
        $stmt->close();
    }
    $cart_message = 'Đã thêm vào giỏ hàng!';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['product_name']); ?> — VISIO</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;1,300&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .product-detail { max-width: 1100px; margin: 6rem auto 4rem; padding: 0 2rem; display: grid; grid-template-columns: 1fr 1fr; gap: 4rem; align-items: start; }
        .product-img-main { width: 100%; aspect-ratio: 4/3; object-fit: cover; border-radius: 8px; box-shadow: 0 8px 40px rgba(26,23,20,0.12); }
        .product-info { padding-top: 1rem; }
        .product-category { font-size: 0.75rem; letter-spacing: 0.2em; text-transform: uppercase; color: #c8a96e; margin-bottom: 0.8rem; }
        .product-title { font-family: 'Cormorant Garamond', serif; font-size: 2rem; font-weight: 300; color: #1a1714; margin-bottom: 1rem; line-height: 1.2; }
        .product-price-big { font-family: 'Cormorant Garamond', serif; font-size: 2rem; font-weight: 600; color: #a07840; margin-bottom: 1.5rem; }
        .product-desc { font-size: 0.95rem; line-height: 1.8; color: #7a6050; margin-bottom: 2rem; }
        .variant-label { font-size: 0.82rem; font-weight: 500; color: #4a3728; margin-bottom: 0.5rem; }
        .variant-select { width: 100%; padding: 0.75rem 1rem; border: 1.5px solid #e8dcc8; border-radius: 6px; font-family: inherit; font-size: 0.95rem; margin-bottom: 1.2rem; cursor: pointer; }
        .variant-select:focus { outline: none; border-color: #c8a96e; }
        .qty-row { display: flex; align-items: center; gap: 1rem; margin-bottom: 1.5rem; }
        .qty-btn { width: 36px; height: 36px; border: 1.5px solid #e8dcc8; background: none; border-radius: 4px; font-size: 1.2rem; cursor: pointer; display: flex; align-items: center; justify-content: center; }
        .qty-input { width: 60px; text-align: center; padding: 0.5rem; border: 1.5px solid #e8dcc8; border-radius: 4px; font-size: 1rem; }
        .btn-cart { width: 100%; padding: 1rem; background: #1a1714; color: #f5f0e8; border: none; border-radius: 6px; font-size: 0.9rem; font-weight: 500; cursor: pointer; font-family: inherit; letter-spacing: 0.08em; text-transform: uppercase; transition: background 0.2s; }
        .btn-cart:hover { background: #a07840; }
        .btn-tryon { width: 100%; padding: 0.85rem; background: none; color: #1a1714; border: 1.5px solid #1a1714; border-radius: 6px; font-size: 0.9rem; font-weight: 500; cursor: pointer; font-family: inherit; letter-spacing: 0.08em; text-transform: uppercase; transition: all 0.2s; margin-top: 0.8rem; text-decoration: none; display: block; text-align: center; }
        .btn-tryon:hover { background: #1a1714; color: #f5f0e8; }
        .stock-badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 0.78rem; margin-bottom: 1.2rem; }
        .in-stock { background: #d4edda; color: #155724; }
        .out-stock { background: #f8d7da; color: #721c24; }
        .cart-success { background: #d4edda; color: #155724; padding: 0.8rem 1rem; border-radius: 6px; margin-bottom: 1rem; font-size: 0.9rem; }
        .related { max-width: 1100px; margin: 0 auto 4rem; padding: 0 2rem; }
        .related h2 { font-family: 'Cormorant Garamond', serif; font-size: 1.8rem; font-weight: 300; margin-bottom: 1.5rem; }
        .related-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 1.2rem; }
        @media(max-width:768px) {
            .product-detail { grid-template-columns: 1fr; gap: 2rem; margin-top: 5rem; }
            .related-grid { grid-template-columns: repeat(2,1fr); }
        }
    </style>
</head>
<body>

<nav>
    <a href="index.php" class="nav-logo">VISIO<span>.</span></a>
    <ul class="nav-links">
        <li><a href="index.php#products">Sản Phẩm</a></li>
        <li><a href="index.php#about">Về Chúng Tôi</a></li>
        <li><a href="TryonVR/index.html">Thử Kính Ảo</a></li>
    </ul>
    <div style="display:flex;gap:1rem;align-items:center;">
        <?php if (isset($_SESSION['customer_id'])): ?>
            <span style="font-size:0.85rem;color:#4a3728;">👤 <?php echo htmlspecialchars($_SESSION['customer_name']); ?></span>
            <a href="cart.php" class="nav-cta">🛒 Giỏ hàng</a>
            <a href="logout.php" style="font-size:0.82rem;color:#7a6050;text-decoration:none;">Đăng xuất</a>
        <?php else: ?>
            <a href="customer_auth.php" class="nav-cta">Đăng nhập</a>
        <?php endif; ?>
    </div>
</nav>

<div class="product-detail">
    <!-- Hình ảnh -->
    <div>
        <img class="product-img-main"
             src="<?php echo $product['image_url'] ?: 'img/product-1.jpg'; ?>"
             alt="<?php echo htmlspecialchars($product['product_name']); ?>"
             onerror="this.src='img/product-1.jpg'">
    </div>

    <!-- Thông tin -->
    <div class="product-info">
        <p class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></p>
        <h1 class="product-title"><?php echo htmlspecialchars($product['product_name']); ?></h1>

        <?php if ($cart_message): ?>
            <div class="cart-success">✅ <?php echo $cart_message; ?> <a href="cart.php">Xem giỏ hàng →</a></div>
        <?php endif; ?>

        <div class="product-price-big" id="display-price">
            <?php
            $base = $variants ? $variants[0]['price'] : $product['base_price'];
            echo number_format($base, 0, ',', '.') . ' ₫';
            ?>
        </div>

        <p class="product-desc"><?php echo htmlspecialchars($product['description'] ?? ''); ?></p>

        <form method="POST">
            <?php if ($variants): ?>
                <p class="variant-label">Chọn biến thể:</p>
                <select name="variant_id" class="variant-select" id="variant-select" onchange="updatePrice(this)">
                    <?php foreach ($variants as $v): ?>
                        <option value="<?php echo $v['variant_id']; ?>"
                                data-price="<?php echo $v['price']; ?>"
                                data-stock="<?php echo $v['quantity_in_stock']; ?>">
                            <?php echo htmlspecialchars($v['color'] . ' - Size ' . $v['size']); ?>
                            (<?php echo number_format($v['price'], 0, ',', '.'); ?>₫)
                            <?php echo $v['quantity_in_stock'] > 0 ? '' : '— Hết hàng'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <span class="stock-badge in-stock" id="stock-badge">
                    Còn <?php echo $variants[0]['quantity_in_stock']; ?> sản phẩm
                </span>
            <?php endif; ?>

            <div class="qty-row">
                <p class="variant-label" style="margin:0;">Số lượng:</p>
                <button type="button" class="qty-btn" onclick="changeQty(-1)">−</button>
                <input type="number" name="quantity" id="qty" class="qty-input" value="1" min="1" max="99">
                <button type="button" class="qty-btn" onclick="changeQty(1)">+</button>
            </div>

            <button type="submit" name="add_to_cart" class="btn-cart">🛒 Thêm vào giỏ hàng</button>
        </form>

        <a href="TryonVR/index.html" class="btn-tryon">🕶️ Thử kính ảo ngay</a>
    </div>
</div>

<!-- Sản phẩm liên quan -->
<?php if ($related->num_rows > 0): ?>
<div class="related">
    <h2>Sản phẩm liên quan</h2>
    <div class="related-grid">
        <?php while ($r = $related->fetch_assoc()): ?>
            <a href="product.php?id=<?php echo $r['product_id']; ?>" style="text-decoration:none;">
                <div class="product-card">
                    <div class="product-img">
                        <img src="<?php echo $r['image_url'] ?: 'img/product-1.jpg'; ?>"
                             alt="<?php echo htmlspecialchars($r['product_name']); ?>"
                             onerror="this.src='img/product-1.jpg'">
                    </div>
                    <div class="product-info">
                        <h3 class="product-name"><?php echo htmlspecialchars($r['product_name']); ?></h3>
                        <div class="product-footer">
                            <span class="product-price">
                                <?php echo number_format($r['min_price'] ?? $r['base_price'], 0, ',', '.'); ?> ₫
                            </span>
                        </div>
                    </div>
                </div>
            </a>
        <?php endwhile; ?>
    </div>
</div>
<?php endif; ?>

<script>
function updatePrice(select) {
    const opt = select.options[select.selectedIndex];
    const price = parseInt(opt.dataset.price);
    const stock = parseInt(opt.dataset.stock);
    document.getElementById('display-price').textContent = price.toLocaleString('vi-VN') + ' ₫';
    const badge = document.getElementById('stock-badge');
    if (stock > 0) {
        badge.textContent = 'Còn ' + stock + ' sản phẩm';
        badge.className = 'stock-badge in-stock';
    } else {
        badge.textContent = 'Hết hàng';
        badge.className = 'stock-badge out-stock';
    }
}
function changeQty(delta) {
    const input = document.getElementById('qty');
    input.value = Math.max(1, parseInt(input.value) + delta);
}
</script>
</body>
</html>
